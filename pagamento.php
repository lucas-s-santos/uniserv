<?php
include_once "includes/bootstrap.php";
include_once "includes/auth.php";
include_once "includes/pix_gateway.php";
include_once "status.php";

$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
$themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

require_login('login.php', 'Voce precisa estar logado para acessar esta area.');
require_role([1, 3], 'login.php', 'Acesso restrito para clientes.');

$id_servico = isset($_GET['servico']) ? (int) $_GET['servico'] : 0;
if ($id_servico <= 0) {
    $_SESSION['avisar'] = 'Servico nao encontrado.';
    header('location: servicos.php');
    exit;
}

$stmt = $conn->prepare("SELECT s.*,
        r.nome AS nome_trabalhador, r.cidade AS cidade_trabalhador, r.pix_tipo, r.pix_chave,
        r.aceita_pix, r.aceita_dinheiro, r.aceita_cartao_presencial, r.pagamento_preferido, r.mensagem_pagamento
    FROM servico s
    INNER JOIN registro r ON r.id_registro = s.id_trabalhador
    WHERE s.id_servico = ? LIMIT 1");
$stmt->bind_param("i", $id_servico);
$stmt->execute();
$servico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$servico || (int) $servico['registro_id_registro'] !== (int) $_SESSION['id_acesso']) {
    $_SESSION['avisar'] = 'Acesso restrito para este servico.';
    header('location: servicos.php');
    exit;
}

$status_servico = (int) $servico['ativo'];
$pagamento_status = isset($servico['pagamento_status']) ? (int) $servico['pagamento_status'] : 0;
$pagamento_confirmado = $pagamento_status === 2 || $status_servico === SERVICO_STATUS_FINALIZADO;

if (!$pagamento_confirmado && $status_servico !== SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
    $_SESSION['avisar'] = 'Este servico nao esta aguardando pagamento.';
    header('location: servicos.php');
    exit;
}

$valor_final = $servico['valor_final'] !== null ? (float) $servico['valor_final'] : 0.0;
if ($valor_final <= 0 && $servico['tempo_servico']) {
    $valor_final = ((float) $servico['valor_atual'] * ((float) $servico['tempo_servico'] / 60));
}
$valor_fmt = number_format($valor_final, 2, ',', '.');

$pix_tipo_raw = (string) $servico['pix_tipo'];
$pix_tipo = htmlspecialchars($pix_tipo_raw, ENT_QUOTES, 'UTF-8');
$pix_chave_raw = (string) $servico['pix_chave'];
$pix_chave = htmlspecialchars($pix_chave_raw, ENT_QUOTES, 'UTF-8');
$pix_info = $pix_chave ? ($pix_tipo ? "$pix_tipo: $pix_chave" : $pix_chave) : '';

$cidade_trabalhador = (string) ($servico['cidade_trabalhador'] ?? '');
$mensagem_pagamento = trim((string) ($servico['mensagem_pagamento'] ?? ''));
$aceita_pix = isset($servico['aceita_pix']) ? (int) $servico['aceita_pix'] === 1 : true;
$aceita_dinheiro = isset($servico['aceita_dinheiro']) ? (int) $servico['aceita_dinheiro'] === 1 : false;
$aceita_cartao_presencial = isset($servico['aceita_cartao_presencial']) ? (int) $servico['aceita_cartao_presencial'] === 1 : false;
$pagamento_preferido_raw = trim((string) ($servico['pagamento_preferido'] ?? ''));
$preferencia_labels = [
    'pix' => 'PIX',
    'dinheiro' => 'Dinheiro',
    'cartao' => 'Cartao presencial',
    'qualquer' => 'Qualquer metodo'
];
$pagamento_preferido_label = isset($preferencia_labels[$pagamento_preferido_raw]) ? $preferencia_labels[$pagamento_preferido_raw] : '';
$metodos_ativos_count = 0;
if ($aceita_pix) {
    $metodos_ativos_count++;
}
if ($aceita_dinheiro) {
    $metodos_ativos_count++;
}
if ($aceita_cartao_presencial) {
    $metodos_ativos_count++;
}
if ($metodos_ativos_count <= 1 && $pagamento_preferido_raw !== 'qualquer') {
    $pagamento_preferido_label = '';
}

$pix_dynamic_enabled = pix_has_dynamic_schema($conn);
$pix_payload = '';
$pix_qr = '';
$pix_txid = '';
$pix_expira_em = '';
$pix_status = '';

if ($pix_chave_raw !== '' && $aceita_pix) {
    if ($pix_dynamic_enabled) {
        $pix_txid = trim((string) ($servico['pix_txid'] ?? ''));
        $pix_payload = trim((string) ($servico['pix_payload'] ?? ''));
        $pix_qr = trim((string) ($servico['pix_qr_url'] ?? ''));
        $pix_expira_em = trim((string) ($servico['pix_expira_em'] ?? ''));
        $pix_status = strtolower(trim((string) ($servico['pix_status'] ?? '')));
        $pix_valor = isset($servico['pix_valor']) ? (float) $servico['pix_valor'] : 0.0;

        $charge_needs_refresh = $pix_txid === ''
            || $pix_payload === ''
            || $pix_qr === ''
            || $pix_expira_em === ''
            || pix_is_charge_expired($pix_expira_em)
            || abs($pix_valor - $valor_final) > 0.009
            || in_array($pix_status, ['failed', 'expired', 'canceled', 'cancelled'], true);

        if (!$pagamento_confirmado && $pagamento_status === 0 && $charge_needs_refresh) {
            $charge = pix_build_dynamic_charge(
                $id_servico,
                $pix_chave_raw,
                (string) ($servico['nome_trabalhador'] ?? 'UNISERV'),
                $cidade_trabalhador !== '' ? $cidade_trabalhador : 'BRASIL',
                $valor_final
            );

            if (!empty($charge['payload'])) {
                $pix_txid = (string) $charge['txid'];
                $pix_payload = (string) $charge['payload'];
                $pix_qr = (string) $charge['qr_url'];
                $pix_expira_em = (string) $charge['expires_at'];
                $pix_status = (string) $charge['status'];
                $pix_valor = (float) $charge['amount'];

                $stmtPix = $conn->prepare("UPDATE servico
                    SET pix_txid = ?, pix_payload = ?, pix_qr_url = ?, pix_gateway = ?, pix_expira_em = ?, pix_valor = ?, pix_status = ?, pix_pago_em = NULL, pix_webhook_payload = NULL
                    WHERE id_servico = ?");
                if ($stmtPix) {
                    $gateway = (string) $charge['gateway'];
                    $stmtPix->bind_param(
                        "sssssdsi",
                        $pix_txid,
                        $pix_payload,
                        $pix_qr,
                        $gateway,
                        $pix_expira_em,
                        $pix_valor,
                        $pix_status,
                        $id_servico
                    );
                    $stmtPix->execute();
                    $stmtPix->close();
                }
            }
        }

        if (!$pagamento_confirmado && $pagamento_status === 0 && $pix_expira_em !== '' && pix_is_charge_expired($pix_expira_em)) {
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
    }

    if ($pix_payload === '' || $pix_qr === '') {
        $nome_emv = pix_sanitize($servico['nome_trabalhador'] ?? 'UNISERV', 25);
        $cidade_emv = pix_sanitize($cidade_trabalhador !== '' ? $cidade_trabalhador : 'BRASIL', 15);
        $txid = 'UNISERV' . $id_servico;
        $pix_payload = pix_build_payload($pix_chave_raw, $nome_emv, $cidade_emv, $valor_final, $txid);
        $pix_qr = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($pix_payload);
        if ($pix_status === '') {
            $pix_status = $pagamento_confirmado ? 'paid' : 'pending';
        }
    }
}

$pix_expirado = !$pagamento_confirmado && $pix_expira_em !== '' && pix_is_charge_expired($pix_expira_em);
$pix_expira_fmt = '';
if ($pix_expira_em !== '') {
    $pix_expira_ts = strtotime($pix_expira_em);
    if ($pix_expira_ts !== false) {
        $pix_expira_fmt = date('d/m/Y H:i', $pix_expira_ts);
    }
}

$pix_status_label = 'Aguardando pagamento via Pix.';
$pix_status_feedback_class = '';
if ($pagamento_confirmado) {
    $pix_status_label = 'Pagamento confirmado automaticamente.';
    $pix_status_feedback_class = 'copy-feedback--ok';
} elseif ($pagamento_status === 1) {
    $pix_status_label = 'Comprovante enviado. Aguardando confirmacao.';
} elseif ($pix_expirado || $pix_status === 'expired') {
    $pix_status_label = 'Codigo Pix expirado. Recarregue a pagina para gerar outro.';
    $pix_status_feedback_class = 'copy-feedback--error';
} elseif ($pix_status === 'failed') {
    $pix_status_label = 'Falha no processamento do Pix. Tente novamente.';
    $pix_status_feedback_class = 'copy-feedback--error';
}

$tem_metodo = ($pix_info && $aceita_pix) || $aceita_dinheiro || $aceita_cartao_presencial;
$pix_poll_enabled = !$pagamento_confirmado && $pix_dynamic_enabled && $pix_txid !== '';
$comprovante_opcional = $pix_poll_enabled;

include_once "all.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
        <meta name="keywords" content="HTML, CSS">
        <meta name="description" content="Pagamento do servico">
        <link rel="stylesheet" href="css/estrutura_geral.css">
        <title>Pagamento</title>
        <script>
            (function () {
                var PIX_POLL_URL = <?php echo json_encode('pix_status.php?servico=' . (int) $servico['id_servico']); ?>;
                var PIX_POLL_ENABLED = <?php echo $pix_poll_enabled ? 'true' : 'false'; ?>;
                var PIX_REDIRECT_URL = 'historico.php';
                var pixAlreadyPaid = <?php echo $pagamento_confirmado ? 'true' : 'false'; ?>;
                var pollTimer = null;

                function setFeedback(id, text, type) {
                    var el = document.getElementById(id);
                    if (!el) {
                        return;
                    }
                    el.textContent = text;
                    el.classList.remove('copy-feedback--ok', 'copy-feedback--error');
                    if (type === 'ok') {
                        el.classList.add('copy-feedback--ok');
                    }
                    if (type === 'error') {
                        el.classList.add('copy-feedback--error');
                    }
                }

                function fallbackCopy(text) {
                    var textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'fixed';
                    textarea.style.left = '-9999px';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    textarea.setSelectionRange(0, textarea.value.length);
                    var ok = false;
                    try {
                        ok = document.execCommand('copy');
                    } catch (e) {
                        ok = false;
                    }
                    document.body.removeChild(textarea);
                    return ok;
                }

                function copyFromTarget(selector) {
                    var target = document.querySelector(selector);
                    if (!target) {
                        return '';
                    }
                    if (target.value !== undefined) {
                        return String(target.value);
                    }
                    return String(target.textContent || '');
                }

                function copyText(text) {
                    if (!text) {
                        return Promise.resolve(false);
                    }
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        return navigator.clipboard.writeText(text).then(function () {
                            return true;
                        }).catch(function () {
                            return fallbackCopy(text);
                        });
                    }
                    return Promise.resolve(fallbackCopy(text));
                }

                function bindCopyButtons() {
                    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var selector = btn.getAttribute('data-copy-target');
                            var feedbackId = btn.getAttribute('data-feedback-id');
                            var text = copyFromTarget(selector).trim();

                            if (!text) {
                                setFeedback(feedbackId, 'Nada para copiar.', 'error');
                                return;
                            }

                            copyText(text).then(function (ok) {
                                if (ok) {
                                    setFeedback(feedbackId, 'Copiado para a area de transferencia.', 'ok');
                                    if (window.showToast) {
                                        showToast('Copiado para a area de transferencia.', 'success');
                                    }
                                } else {
                                    setFeedback(feedbackId, 'Nao foi possivel copiar automaticamente. Selecione e copie manualmente.', 'error');
                                }
                            });
                        });
                    });
                }

                function bindFileInput() {
                    var input = document.getElementById('comprovante-input');
                    var label = document.getElementById('file-name-label');
                    if (!input || !label) {
                        return;
                    }
                    input.addEventListener('change', function () {
                        if (input.files && input.files.length > 0) {
                            label.textContent = 'Arquivo selecionado: ' + input.files[0].name;
                        } else {
                            label.textContent = 'PNG, JPEG ou PDF';
                        }
                    });
                }

                function setPixStatusText(text, type) {
                    var el = document.getElementById('pix-live-status-text');
                    if (!el) {
                        return;
                    }
                    el.textContent = text;
                    el.classList.remove('copy-feedback--ok', 'copy-feedback--error');
                    if (type === 'ok') {
                        el.classList.add('copy-feedback--ok');
                    }
                    if (type === 'error') {
                        el.classList.add('copy-feedback--error');
                    }
                }

                function setPixExpiryText(text) {
                    var el = document.getElementById('pix-expire-at');
                    if (!el || !text) {
                        return;
                    }
                    el.textContent = text;
                }

                function handlePaidState() {
                    if (pixAlreadyPaid) {
                        return;
                    }
                    pixAlreadyPaid = true;
                    setPixStatusText('Pagamento confirmado automaticamente.', 'ok');
                    if (window.showToast) {
                        showToast('Pagamento Pix confirmado. Servico concluido.', 'success');
                    }
                    window.setTimeout(function () {
                        window.location.href = PIX_REDIRECT_URL;
                    }, 1400);
                }

                function applyPixStatus(data) {
                    if (!data || data.ok !== true) {
                        return;
                    }

                    if (data.pix_status_label) {
                        var state = '';
                        if (data.pagamento_confirmado) {
                            state = 'ok';
                        } else if (data.expirado || data.pix_status === 'failed') {
                            state = 'error';
                        }
                        setPixStatusText(data.pix_status_label, state);
                    }

                    if (data.pix_expira_em_fmt) {
                        setPixExpiryText(data.pix_expira_em_fmt);
                    }

                    if (data.pagamento_confirmado) {
                        handlePaidState();
                        if (pollTimer) {
                            window.clearInterval(pollTimer);
                            pollTimer = null;
                        }
                    }
                }

                function pollPixStatus(silent) {
                    if (!PIX_POLL_ENABLED) {
                        return;
                    }
                    fetch(PIX_POLL_URL + '&_=' + Date.now(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(function (res) {
                        if (!res.ok) {
                            throw new Error('HTTP ' + res.status);
                        }
                        return res.json();
                    })
                    .then(function (data) {
                        applyPixStatus(data);
                    })
                    .catch(function () {
                        if (!silent) {
                            setPixStatusText('Falha ao consultar status. Tente novamente.', 'error');
                        }
                    });
                }

                function bindStatusRefresh() {
                    var btn = document.getElementById('pix-refresh-btn');
                    if (!btn) {
                        return;
                    }
                    btn.addEventListener('click', function () {
                        pollPixStatus(false);
                    });
                }

                document.addEventListener('DOMContentLoaded', function () {
                    bindCopyButtons();
                    bindFileInput();
                    bindStatusRefresh();

                    if (PIX_POLL_ENABLED) {
                        pollPixStatus(true);
                        pollTimer = window.setInterval(function () {
                            pollPixStatus(true);
                        }, 12000);
                    }
                });
            })();
        </script>
    </head>
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>

        <main class="page">
            <section class="payment-hero">
                <div class="payment-hero-content">
                    <h1 class="payment-title">Pagamento do servico</h1>
                    <p class="payment-subtitle">Pague com seguranca para <?php echo htmlspecialchars($servico['nome_trabalhador'], ENT_QUOTES, 'UTF-8'); ?>.</p>
                    <div class="payment-amount">
                        <span class="amount-label">Valor final</span>
                        <span class="amount-value">R$ <?php echo $valor_fmt; ?></span>
                    </div>
                </div>
                <div class="payment-return">
                    <a class="btn btn-ghost" href="servicos.php">Voltar</a>
                </div>
            </section>

            <div class="payment-container">
                <section class="payment-methods-section">
                    <h2 class="section-header">Passo 1: Escolha como pagar</h2>
                    <p class="payment-step-note">Use Pix para pagamento imediato ou escolha presencial quando disponivel.</p>

                    <?php if (!$tem_metodo) { ?>
                        <div class="payment-method-card payment-empty-method">
                            <h3>Nenhum metodo disponivel</h3>
                            <p>O colaborador ainda nao configurou metodo de pagamento. Entre em contato antes de concluir.</p>
                        </div>
                    <?php } else { ?>
                        <div class="payment-methods-grid">
                            <?php if ($pix_info && $aceita_pix) { ?>
                                <div class="payment-method-card pix-method">
                                    <div class="method-header">
                                        <h3>PIX</h3>
                                    </div>
                                    <p class="method-badge">Recomendado</p>

                                    <?php if ($pix_qr) { ?>
                                        <div class="qr-section">
                                            <div class="qr-container">
                                                <img src="<?php echo htmlspecialchars($pix_qr, ENT_QUOTES, 'UTF-8'); ?>" alt="QR code PIX" class="qr-image">
                                                <p class="qr-instruction">Escaneie no app do seu banco</p>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <div class="pix-details">
                                        <div class="detail-row">
                                            <span class="detail-label">Tipo</span>
                                            <span class="detail-value"><?php echo $pix_tipo ?: 'Nao informado'; ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Chave</span>
                                            <span class="detail-value pix-key"><?php echo $pix_chave; ?></span>
                                        </div>
                                        <?php if ($pix_txid !== '') { ?>
                                            <div class="detail-row">
                                                <span class="detail-label">Txid</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($pix_txid, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="copy-stack">
                                        <label class="detail-label" for="pix-key-input">Copiar chave Pix</label>
                                        <div class="copy-line">
                                            <input id="pix-key-input" class="copy-input" type="text" readonly value="<?php echo htmlspecialchars($pix_chave_raw, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="button" class="btn btn-copy" data-copy-target="#pix-key-input" data-feedback-id="pixCopyNotice">Copiar</button>
                                        </div>
                                        <div id="pixCopyNotice" class="copy-feedback"></div>
                                    </div>

                                    <?php if ($pix_payload) { ?>
                                        <div class="copy-stack">
                                            <label class="detail-label" for="pix-payload-input">PIX copia e cola</label>
                                            <div class="copy-line copy-line--payload">
                                                <textarea id="pix-payload-input" class="copy-textarea" readonly><?php echo htmlspecialchars($pix_payload, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                <button type="button" class="btn btn-copy" data-copy-target="#pix-payload-input" data-feedback-id="payloadCopyNotice">Copiar codigo</button>
                                            </div>
                                            <div id="payloadCopyNotice" class="copy-feedback"></div>
                                        </div>
                                    <?php } ?>

                                    <?php if ($pix_txid !== '') { ?>
                                        <div class="copy-stack">
                                            <label class="detail-label">Status do Pix</label>
                                            <div id="pix-live-status-text" class="copy-feedback <?php echo $pix_status_feedback_class; ?>">
                                                <?php echo htmlspecialchars($pix_status_label, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <?php if ($pix_expira_fmt !== '') { ?>
                                                <div class="copy-feedback">Expira em: <span id="pix-expire-at"><?php echo htmlspecialchars($pix_expira_fmt, ENT_QUOTES, 'UTF-8'); ?></span></div>
                                            <?php } ?>
                                            <?php if ($pix_poll_enabled) { ?>
                                                <button type="button" id="pix-refresh-btn" class="btn btn-ghost btn-small" style="margin-top: 8px;">Atualizar status do Pix</button>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <?php if ($aceita_dinheiro || $aceita_cartao_presencial) { ?>
                                <div class="payment-method-card cash-method">
                                    <div class="method-header">
                                        <h3>Pagamento presencial</h3>
                                    </div>
                                    <p class="method-badge method-badge--secondary">No local do atendimento</p>

                                    <div class="cash-info">
                                        <p>Metodos aceitos presencialmente:</p>
                                        <ul class="payment-accepts">
                                            <?php if ($aceita_dinheiro) { ?>
                                                <li>Dinheiro</li>
                                            <?php } ?>
                                            <?php if ($aceita_cartao_presencial) { ?>
                                                <li>Cartao de debito ou credito</li>
                                            <?php } ?>
                                        </ul>
                                    </div>

                                    <?php if (!$pagamento_confirmado && $pagamento_status === 0) { ?>
                                        <form action="servicos.php" method="POST" style="margin-top: 12px;">
                                            <input type="hidden" name="acao" value="pagar_presencial">
                                            <input type="hidden" name="id_servico" value="<?php echo (int) $servico['id_servico']; ?>">
                                            <button type="submit" class="btn btn-primary btn-full">Confirmar pagamento presencial</button>
                                        </form>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($pagamento_preferido_label) || !empty($mensagem_pagamento)) { ?>
                        <div class="info-box">
                            <?php if (!empty($pagamento_preferido_label)) { ?>
                                <div class="info-item">
                                    <strong>Preferencia:</strong>
                                    <span><?php echo htmlspecialchars($pagamento_preferido_label, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            <?php } ?>
                            <?php if (!empty($mensagem_pagamento)) { ?>
                                <div class="info-item">
                                    <strong>Mensagem:</strong>
                                    <p><?php echo htmlspecialchars($mensagem_pagamento, ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </section>

                <section class="payment-receipt-section">
                    <?php if ($comprovante_opcional) { ?>
                        <h2 class="section-header">Passo 2 (opcional): Envie comprovante</h2>
                    <?php } else { ?>
                        <h2 class="section-header">Passo 2: Envie o comprovante</h2>
                    <?php } ?>

                    <?php if ($pagamento_confirmado) { ?>
                        <div class="receipt-status success" id="receipt-live-status">
                            <div>
                                <p class="status-title">Pagamento confirmado</p>
                                <p class="status-subtitle">Servico concluido com sucesso.</p>
                            </div>
                            <a class="btn btn-ghost btn-small" href="historico.php">Ver historico</a>
                        </div>
                    <?php } elseif ($pagamento_status === 1 && !empty($servico['pagamento_comprovante'])) { ?>
                        <div class="receipt-status success">
                            <div>
                                <p class="status-title">Comprovante enviado</p>
                                <p class="status-subtitle">Aguardando confirmacao do colaborador</p>
                            </div>
                            <?php if ($servico['pagamento_comprovante'] !== 'presencial') { ?>
                                <a class="btn btn-ghost btn-small" href="<?php echo htmlspecialchars($servico['pagamento_comprovante'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Ver comprovante</a>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="receipt-upload">
                            <?php if ($comprovante_opcional) { ?>
                                <p class="upload-instruction">Se o Pix nao confirmar automaticamente, envie o comprovante manualmente.</p>
                            <?php } else { ?>
                                <p class="upload-instruction">Depois de pagar, envie o comprovante para o colaborador confirmar.</p>
                            <?php } ?>
                            <form action="servicos.php" method="POST" enctype="multipart/form-data" class="receipt-form">
                                <input type="hidden" name="acao" value="pagar">
                                <input type="hidden" name="id_servico" value="<?php echo (int) $servico['id_servico']; ?>">

                                <div class="file-input-wrapper">
                                    <input type="file" id="comprovante-input" name="comprovante" accept="image/png, image/jpeg, application/pdf" required>
                                    <label for="comprovante-input" class="file-input-label">
                                        <span class="upload-text">Selecione o comprovante</span>
                                        <span id="file-name-label" class="upload-format">PNG, JPEG ou PDF</span>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary btn-full btn-large">Enviar comprovante</button>
                            </form>
                        </div>
                    <?php } ?>
                </section>
            </div>
        </main>
    </body>
</html>
