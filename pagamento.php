<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";
    include_once "status.php";

    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    require_login('login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([1, 3], 'login.php', 'Acesso restrito para clientes.');

    $id_servico = isset($_GET['servico']) ? (int)$_GET['servico'] : 0;
    if ($id_servico <= 0) {
        $_SESSION['avisar'] = 'Servico nao encontrado.';
        header('location: servicos.php');
        exit;
    }

        $stmt = $conn->prepare("SELECT s.id_servico, s.registro_id_registro, s.id_trabalhador, s.valor_atual, s.tempo_servico,
            s.valor_final, s.ativo, s.pagamento_status, s.pagamento_comprovante,
            r.nome as nome_trabalhador, r.cidade as cidade_trabalhador, r.pix_tipo, r.pix_chave,
            r.aceita_pix, r.aceita_dinheiro, r.pagamento_preferido, r.mensagem_pagamento
        FROM servico s
        INNER JOIN registro r ON r.id_registro = s.id_trabalhador
        WHERE s.id_servico = ? LIMIT 1");
    $stmt->bind_param("i", $id_servico);
    $stmt->execute();
    $servico = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$servico || (int)$servico['registro_id_registro'] !== (int)$_SESSION['id_acesso']) {
        $_SESSION['avisar'] = 'Acesso restrito para este servico.';
        header('location: servicos.php');
        exit;
    }

    if ((int)$servico['ativo'] !== SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
        $_SESSION['avisar'] = 'Este servico nao esta aguardando pagamento.';
        header('location: servicos.php');
        exit;
    }

    $valor_final = $servico['valor_final'] !== null ? (float)$servico['valor_final'] : 0.0;
    if ($valor_final <= 0 && $servico['tempo_servico']) {
        $valor_final = ((float)$servico['valor_atual'] * ((float)$servico['tempo_servico'] / 60));
    }
    $valor_fmt = number_format($valor_final, 2, ',', '.');

    $pix_tipo = htmlspecialchars((string)$servico['pix_tipo'], ENT_QUOTES, 'UTF-8');
    $pix_chave_raw = (string)$servico['pix_chave'];
    $pix_chave = htmlspecialchars($pix_chave_raw, ENT_QUOTES, 'UTF-8');
    $pix_info = $pix_chave ? ($pix_tipo ? "$pix_tipo: $pix_chave" : $pix_chave) : '';
    $pix_payload = '';
    $pix_qr = '';
    $cidade_trabalhador = (string)($servico['cidade_trabalhador'] ?? '');
    $mensagem_pagamento = trim((string)($servico['mensagem_pagamento'] ?? ''));
    $aceita_pix = isset($servico['aceita_pix']) ? (int)$servico['aceita_pix'] === 1 : true;
    $aceita_dinheiro = isset($servico['aceita_dinheiro']) ? (int)$servico['aceita_dinheiro'] === 1 : false;
    $pagamento_preferido = htmlspecialchars((string)($servico['pagamento_preferido'] ?? ''), ENT_QUOTES, 'UTF-8');

    function pix_emv_field($id, $value) {
        $len = str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $len . $value;
    }

    function pix_sanitize($text, $maxLen) {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $text = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        }
        $text = strtoupper($text);
        $text = preg_replace('/[^A-Z0-9 ]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        if (strlen($text) > $maxLen) {
            $text = substr($text, 0, $maxLen);
        }
        return $text;
    }

    function pix_crc16($payload) {
        $crc = 0xFFFF;
        $polynomial = 0x1021;
        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    if ($pix_chave_raw !== '' && $aceita_pix) {
        $nome_emv = pix_sanitize($servico['nome_trabalhador'] ?? 'UNISERV', 25);
        $cidade_emv = pix_sanitize($cidade_trabalhador !== '' ? $cidade_trabalhador : 'BRASIL', 15);
        $valor_emv = number_format($valor_final, 2, '.', '');
        $txid = 'UNISERV' . $id_servico;
        $merchant_info = pix_emv_field('00', 'BR.GOV.BCB.PIX') . pix_emv_field('01', $pix_chave_raw);
        $payload = '';
        $payload .= pix_emv_field('00', '01');
        $payload .= pix_emv_field('26', $merchant_info);
        $payload .= pix_emv_field('52', '0000');
        $payload .= pix_emv_field('53', '986');
        $payload .= pix_emv_field('54', $valor_emv);
        $payload .= pix_emv_field('58', 'BR');
        $payload .= pix_emv_field('59', $nome_emv);
        $payload .= pix_emv_field('60', $cidade_emv);
        $payload .= pix_emv_field('62', pix_emv_field('05', $txid));
        $payload .= '6304';
        $payload .= pix_crc16($payload);
        $pix_payload = $payload;
        $pix_qr = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($pix_payload);
    }
    $pagamento_status = isset($servico['pagamento_status']) ? (int)$servico['pagamento_status'] : 0;

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
            function copyToClipboard(text, noticeId) {
                if (!text) {
                    return;
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        var el = document.getElementById(noticeId);
                        if (el) {
                            el.textContent = "Copiado.";
                        }
                        if (window.showToast) {
                            showToast("Copiado.", "success");
                        }
                    });
                }
            }

            function bindCopyButtons() {
                document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var text = btn.getAttribute('data-copy-text');
                        var noticeId = btn.getAttribute('data-copy-target');
                        copyToClipboard(text, noticeId);
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', bindCopyButtons);
        </script>
    </head>
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
            <section class="page-header">
                <div>
                    <div class="page-kicker">Pagamento</div>
                    <h1 class="page-title">Finalize o pagamento do servico</h1>
                    <p class="page-subtitle">Envie o comprovante para o colaborador confirmar.</p>
                </div>
                <div class="page-actions">
                    <a class="btn btn-ghost" href="servicos.php">Voltar</a>
                </div>
            </section>

            <div class="fonte">
                <div class="dentro">
                    <div class="section-title">Dados do pagamento</div>
                    <div class="texto">Colaborador: <?php echo htmlspecialchars($servico['nome_trabalhador'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="texto">Valor final: <b>R$ <?php echo $valor_fmt; ?></b></div>
                    <div class="texto">Metodos aceitos: <b>
                        <?php
                            $metodos = [];
                            if ($aceita_pix) { $metodos[] = 'PIX'; }
                            if ($aceita_dinheiro) { $metodos[] = 'Dinheiro'; }
                            if (isset($servico['aceita_cartao_presencial']) && (int)$servico['aceita_cartao_presencial'] === 1) { $metodos[] = 'Cartao presencial'; }
                            echo htmlspecialchars(implode(' e ', $metodos) ?: 'Nao informado', ENT_QUOTES, 'UTF-8');
                        ?>
                    </b></div>
                    <?php if (!empty($pagamento_preferido)) { ?>
                        <div class="texto">Preferencia: <b><?php echo $pagamento_preferido; ?></b></div>
                    <?php } ?>
                    <?php if (!empty($mensagem_pagamento)) { ?>
                        <div class="notice" style="margin-top: 12px;">
                            <?php echo htmlspecialchars($mensagem_pagamento, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <?php if ($pix_info && $aceita_pix) { ?>
                        <div class="texto">Tipo: <b><?php echo $pix_tipo ?: 'Nao informado'; ?></b></div>
                        <div class="texto">Chave PIX: <b><?php echo $pix_chave; ?></b></div>
                        <div class="button-group" style="margin-top: 10px;">
                            <button type="button" class="btn btn-ghost btn-small" data-copy-text="<?php echo htmlspecialchars($pix_chave_raw, ENT_QUOTES, 'UTF-8'); ?>" data-copy-target="pixCopyNotice">Copiar PIX</button>
                            <button type="button" class="btn btn-ghost btn-small" data-copy-text="<?php echo htmlspecialchars((string)$valor_final, ENT_QUOTES, 'UTF-8'); ?>" data-copy-target="valorCopyNotice">Copiar valor</button>
                            <?php if ($pix_payload) { ?>
                                <button type="button" class="btn btn-ghost btn-small" data-copy-text="<?php echo htmlspecialchars($pix_payload, ENT_QUOTES, 'UTF-8'); ?>" data-copy-target="payloadCopyNotice">Copiar PIX copia e cola</button>
                            <?php } ?>
                        </div>
                        <div class="texto" id="pixCopyNotice"></div>
                        <div class="texto" id="valorCopyNotice"></div>
                        <div class="texto" id="payloadCopyNotice"></div>
                        <?php if ($pix_qr) { ?>
                            <div class="section-title" style="margin-top: 12px;">QR code PIX com valor</div>
                            <img src="<?php echo htmlspecialchars($pix_qr, ENT_QUOTES, 'UTF-8'); ?>" alt="QR code PIX" style="width: 220px; height: 220px; border-radius: 12px; border: 1px solid var(--c-border);">
                            <div class="texto" style="font-size: 12px;">Escaneie para pagar com PIX.</div>
                        <?php } ?>
                    <?php } else { ?>
                        <?php if ($aceita_dinheiro || (isset($servico['aceita_cartao_presencial']) && (int)$servico['aceita_cartao_presencial'] === 1)) { ?>
                            <div class="notice" style="margin-top: 12px;">
                                Pagamento presencial. Combine no atendimento.
                            </div>
                            <?php if ($pagamento_status === 0) { ?>
                                <form action="servicos.php" method="POST" style="margin-top: 8px;">
                                    <input type="hidden" name="acao" value="pagar_presencial">
                                    <input type="hidden" name="id_servico" value="<?php echo (int)$servico['id_servico']; ?>">
                                    <button type="submit" class="btn btn-primary btn-small">Confirmar pagamento presencial</button>
                                </form>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="texto">O colaborador ainda nao informou uma chave PIX.</div>
                        <?php } ?>
                    <?php } ?>

                    <div class="section-title" style="margin-top: 16px;">Comprovante</div>
                    <?php if ($pagamento_status === 1 && !empty($servico['pagamento_comprovante'])) { ?>
                        <div class="texto">Comprovante enviado. Aguarde confirmacao.</div>
                        <a class="btn btn-ghost btn-small" href="<?php echo htmlspecialchars($servico['pagamento_comprovante'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Ver comprovante</a>
                    <?php } else { ?>
                        <form action="servicos.php" method="POST" enctype="multipart/form-data" style="margin-top: 8px;">
                            <input type="hidden" name="acao" value="pagar">
                            <input type="hidden" name="id_servico" value="<?php echo (int)$servico['id_servico']; ?>">
                            <input type="file" name="comprovante" accept="image/png, image/jpeg, application/pdf" required>
                            <button type="submit" class="btn btn-primary btn-small">Enviar comprovante</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </main>
    </body>
    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>
</html>
