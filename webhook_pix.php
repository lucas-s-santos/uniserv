<?php
include_once "includes/bootstrap.php";
include_once "includes/pix_gateway.php";

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function pix_webhook_response($httpCode, array $payload)
{
    http_response_code((int) $httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function pix_insert_notificacao($conn, $registroId, $mensagem, $link)
{
    $registroId = (int) $registroId;
    if ($registroId <= 0 || !($conn instanceof mysqli)) {
        return;
    }
    $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $registroId, $mensagem, $link);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pix_webhook_response(405, [
        'ok' => false,
        'message' => 'Metodo nao permitido.'
    ]);
}

if (!pix_has_dynamic_schema($conn)) {
    pix_webhook_response(503, [
        'ok' => false,
        'message' => 'Schema Pix dinamico nao disponivel.'
    ]);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || trim((string) $rawBody) === '') {
    pix_webhook_response(400, [
        'ok' => false,
        'message' => 'Body vazio.'
    ]);
}

$signature = '';
if (isset($_SERVER['HTTP_X_PIX_SIGNATURE'])) {
    $signature = (string) $_SERVER['HTTP_X_PIX_SIGNATURE'];
} elseif (isset($_SERVER['HTTP_X_SIGNATURE'])) {
    $signature = (string) $_SERVER['HTTP_X_SIGNATURE'];
}

if (!pix_webhook_signature_is_valid($rawBody, $signature)) {
    pix_webhook_response(401, [
        'ok' => false,
        'message' => 'Assinatura invalida.'
    ]);
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    pix_webhook_response(400, [
        'ok' => false,
        'message' => 'JSON invalido.'
    ]);
}

$txid = pix_webhook_extract_string($payload, [
    'txid',
    'transaction_id',
    'data.txid',
    'payload.txid',
    'charge.txid',
    'evento.txid'
]);

if ($txid === '') {
    pix_webhook_response(400, [
        'ok' => false,
        'message' => 'txid nao informado.'
    ]);
}

$statusRaw = pix_webhook_extract_string($payload, [
    'status',
    'payment_status',
    'data.status',
    'payload.status',
    'charge.status',
    'evento.status'
]);

if ($statusRaw === '') {
    $paidFlags = [
        pix_array_get_path($payload, 'paid'),
        pix_array_get_path($payload, 'data.paid'),
        pix_array_get_path($payload, 'payload.paid'),
        pix_array_get_path($payload, 'charge.paid')
    ];
    foreach ($paidFlags as $flag) {
        if ($flag === true || $flag === 1 || $flag === '1') {
            $statusRaw = 'PAID';
            break;
        }
    }
}

if ($statusRaw === '') {
    $statusRaw = 'PENDING';
}

$statusType = 'pending';
if (pix_webhook_status_is_paid($statusRaw)) {
    $statusType = 'paid';
} elseif (pix_webhook_status_is_failed($statusRaw)) {
    $statusType = 'failed';
} elseif (pix_webhook_status_is_pending($statusRaw)) {
    $statusType = 'pending';
}

$stmt = $conn->prepare("SELECT id_servico, id_trabalhador, registro_id_registro, ativo, pagamento_status
    FROM servico WHERE pix_txid = ? LIMIT 1");
if (!$stmt) {
    pix_webhook_response(500, [
        'ok' => false,
        'message' => 'Falha ao consultar txid.'
    ]);
}

$stmt->bind_param("s", $txid);
$stmt->execute();
$servico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$servico) {
    pix_webhook_response(404, [
        'ok' => false,
        'message' => 'txid nao localizado.'
    ]);
}

$id_servico = (int) $servico['id_servico'];
$ativo_atual = (int) $servico['ativo'];
$pagamento_atual = isset($servico['pagamento_status']) ? (int) $servico['pagamento_status'] : 0;
$alreadyConfirmed = $pagamento_atual === 2 || $ativo_atual === SERVICO_STATUS_FINALIZADO;

if ($statusType === 'paid') {
    $conn->begin_transaction();
    try {
        if ($alreadyConfirmed) {
            $stmt = $conn->prepare("UPDATE servico SET pix_status = 'paid', pix_pago_em = COALESCE(pix_pago_em, NOW()), pix_webhook_payload = ? WHERE id_servico = ?");
            if ($stmt) {
                $stmt->bind_param("si", $rawBody, $id_servico);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $status_finalizado = SERVICO_STATUS_FINALIZADO;
            $pix_status_paid = 'paid';
            $stmt = $conn->prepare("UPDATE servico
                SET ativo = ?, pagamento_status = 2, pagamento_data = NOW(),
                    pix_status = ?, pix_pago_em = COALESCE(pix_pago_em, NOW()), pix_webhook_payload = ?
                WHERE id_servico = ?");
            if (!$stmt) {
                throw new Exception('Falha no update de pagamento.');
            }
            $stmt->bind_param("issi", $status_finalizado, $pix_status_paid, $rawBody, $id_servico);
            $stmt->execute();
            $stmt->close();

            pix_insert_notificacao(
                $conn,
                (int) $servico['registro_id_registro'],
                'Pagamento Pix confirmado automaticamente. Servico concluido.',
                'historico.php'
            );
            pix_insert_notificacao(
                $conn,
                (int) $servico['id_trabalhador'],
                'Pagamento Pix do cliente confirmado automaticamente.',
                'historico.php'
            );

            audit_log(
                $conn,
                'pix_webhook_pago',
                'servico',
                $id_servico,
                'Pagamento confirmado automaticamente via webhook'
            );
        }

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        pix_webhook_response(500, [
            'ok' => false,
            'message' => 'Erro ao confirmar pagamento.',
            'error' => $e->getMessage()
        ]);
    }

    pix_webhook_response(200, [
        'ok' => true,
        'servico' => $id_servico,
        'txid' => $txid,
        'status' => 'paid',
        'already_confirmed' => $alreadyConfirmed
    ]);
}

if ($statusType === 'failed') {
    $stmt = $conn->prepare("UPDATE servico SET pix_status = 'failed', pix_webhook_payload = ? WHERE id_servico = ?");
    if ($stmt) {
        $stmt->bind_param("si", $rawBody, $id_servico);
        $stmt->execute();
        $stmt->close();
    }

    audit_log(
        $conn,
        'pix_webhook_falha',
        'servico',
        $id_servico,
        'Webhook sinalizou falha/cancelamento no Pix'
    );

    pix_webhook_response(200, [
        'ok' => true,
        'servico' => $id_servico,
        'txid' => $txid,
        'status' => 'failed'
    ]);
}

$stmt = $conn->prepare("UPDATE servico SET pix_status = 'pending', pix_webhook_payload = ? WHERE id_servico = ?");
if ($stmt) {
    $stmt->bind_param("si", $rawBody, $id_servico);
    $stmt->execute();
    $stmt->close();
}

pix_webhook_response(200, [
    'ok' => true,
    'servico' => $id_servico,
    'txid' => $txid,
    'status' => 'pending'
]);
