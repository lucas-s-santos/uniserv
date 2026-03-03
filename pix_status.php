<?php
include_once "includes/bootstrap.php";
include_once "includes/pix_gateway.php";

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function pix_status_response($httpCode, array $payload)
{
    http_response_code((int) $httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['cpf']) || !isset($_SESSION['id_acesso'])) {
    pix_status_response(401, [
        'ok' => false,
        'message' => 'Sessao invalida.'
    ]);
}

$id_servico = isset($_GET['servico']) ? (int) $_GET['servico'] : 0;
if ($id_servico <= 0) {
    pix_status_response(400, [
        'ok' => false,
        'message' => 'Servico invalido.'
    ]);
}

$pix_dynamic_schema = pix_has_dynamic_schema($conn);

$sql = "SELECT s.id_servico, s.registro_id_registro, s.id_trabalhador, s.ativo, s.pagamento_status";
if ($pix_dynamic_schema) {
    $sql .= ", s.pix_status, s.pix_expira_em, s.pix_txid, s.pix_pago_em";
}
$sql .= " FROM servico s WHERE s.id_servico = ? LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    pix_status_response(500, [
        'ok' => false,
        'message' => 'Falha ao consultar servico.'
    ]);
}

$stmt->bind_param("i", $id_servico);
$stmt->execute();
$servico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$servico) {
    pix_status_response(404, [
        'ok' => false,
        'message' => 'Servico nao encontrado.'
    ]);
}

$user_id = (int) $_SESSION['id_acesso'];
$is_cliente = (int) $servico['registro_id_registro'] === $user_id;
$is_trabalhador = (int) $servico['id_trabalhador'] === $user_id;
if (!$is_cliente && !$is_trabalhador) {
    pix_status_response(403, [
        'ok' => false,
        'message' => 'Acesso negado.'
    ]);
}

$status_servico = (int) $servico['ativo'];
$pagamento_status = isset($servico['pagamento_status']) ? (int) $servico['pagamento_status'] : 0;
$pagamento_confirmado = $pagamento_status === 2 || $status_servico === SERVICO_STATUS_FINALIZADO;

$pix_status = '';
$pix_expira_em = '';
$pix_expira_fmt = '';
$expirado = false;

if ($pix_dynamic_schema) {
    $pix_status = strtolower(trim((string) ($servico['pix_status'] ?? '')));
    $pix_expira_em = trim((string) ($servico['pix_expira_em'] ?? ''));

    if (!$pagamento_confirmado && $pix_expira_em !== '' && pix_is_charge_expired($pix_expira_em)) {
        $expirado = true;
        if ($pix_status !== 'expired') {
            $pix_status = 'expired';
            $stmtExpired = $conn->prepare("UPDATE servico SET pix_status = 'expired' WHERE id_servico = ?");
            if ($stmtExpired) {
                $stmtExpired->bind_param("i", $id_servico);
                $stmtExpired->execute();
                $stmtExpired->close();
            }
        }
    }

    if ($pix_expira_em !== '') {
        $ts = strtotime($pix_expira_em);
        if ($ts !== false) {
            $pix_expira_fmt = date('d/m/Y H:i', $ts);
        }
    }
}

if ($pagamento_confirmado && $pix_status === '') {
    $pix_status = 'paid';
}

$pix_status_label = 'Aguardando pagamento via Pix.';
if ($pagamento_confirmado) {
    $pix_status_label = 'Pagamento confirmado automaticamente.';
} elseif ($pagamento_status === 1) {
    $pix_status_label = 'Comprovante enviado. Aguardando confirmacao.';
} elseif ($expirado || $pix_status === 'expired') {
    $pix_status_label = 'Codigo Pix expirado. Recarregue para gerar outro.';
} elseif ($pix_status === 'failed') {
    $pix_status_label = 'Falha no pagamento Pix.';
}

pix_status_response(200, [
    'ok' => true,
    'servico' => (int) $servico['id_servico'],
    'pagamento_status' => $pagamento_status,
    'pagamento_confirmado' => $pagamento_confirmado,
    'pix_status' => $pix_status,
    'pix_status_label' => $pix_status_label,
    'pix_expira_em' => $pix_expira_em,
    'pix_expira_em_fmt' => $pix_expira_fmt,
    'expirado' => $expirado
]);
