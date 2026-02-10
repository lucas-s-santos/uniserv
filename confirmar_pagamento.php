<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";
    include_once "status.php";

    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    require_login('login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([2], 'login.php', 'Acesso restrito para colaboradores.');

    $id_servico = isset($_GET['servico']) ? (int)$_GET['servico'] : 0;
    if ($id_servico <= 0) {
        $_SESSION['avisar'] = 'Servico nao encontrado.';
        header('location: servicos.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT s.id_servico, s.registro_id_registro, s.id_trabalhador, s.ativo,
            s.pagamento_status, s.pagamento_comprovante, s.valor_final, s.tempo_servico, s.valor_atual,
            r.nome as nome_cliente
        FROM servico s
        INNER JOIN registro r ON r.id_registro = s.registro_id_registro
        WHERE s.id_servico = ? LIMIT 1");
    $stmt->bind_param("i", $id_servico);
    $stmt->execute();
    $servico = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$servico || (int)$servico['id_trabalhador'] !== (int)$_SESSION['id_acesso']) {
        $_SESSION['avisar'] = 'Acesso restrito para este servico.';
        header('location: servicos.php');
        exit;
    }

    if ((int)$servico['ativo'] !== SERVICO_STATUS_AGUARDANDO_PAGAMENTO) {
        $_SESSION['avisar'] = 'Este servico nao esta aguardando pagamento.';
        header('location: servicos.php');
        exit;
    }

    if ((int)$servico['pagamento_status'] !== 1 || empty($servico['pagamento_comprovante'])) {
        $_SESSION['avisar'] = 'Nenhum comprovante pendente.';
        header('location: servicos.php');
        exit;
    }

    $valor_final = $servico['valor_final'] !== null ? (float)$servico['valor_final'] : 0.0;
    if ($valor_final <= 0 && $servico['tempo_servico']) {
        $valor_final = ((float)$servico['valor_atual'] * ((float)$servico['tempo_servico'] / 60));
    }
    $valor_fmt = number_format($valor_final, 2, ',', '.');

    include_once "all.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
        <meta name="keywords" content="HTML, CSS">
        <meta name="description" content="Confirmar pagamento">
        <link rel="stylesheet" href="css/estrutura_geral.css">
        <title>Confirmar pagamento</title>
    </head>
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
            <section class="page-header">
                <div>
                    <div class="page-kicker">Confirmacao</div>
                    <h1 class="page-title">Confirmar pagamento</h1>
                    <p class="page-subtitle">Verifique o comprovante antes de concluir.</p>
                </div>
                <div class="page-actions">
                    <a class="btn btn-ghost" href="servicos.php">Voltar</a>
                </div>
            </section>

            <div class="fonte">
                <div class="dentro">
                    <div class="section-title">Dados do servico</div>
                    <div class="texto">Cliente: <?php echo htmlspecialchars($servico['nome_cliente'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="texto">Valor final: <b>R$ <?php echo $valor_fmt; ?></b></div>

                    <div class="section-title" style="margin-top: 16px;">Comprovante</div>
                    <?php if ($servico['pagamento_comprovante'] === 'presencial') { ?>
                        <div class="texto">Pagamento informado como presencial.</div>
                    <?php } else { ?>
                        <a class="btn btn-ghost btn-small" href="<?php echo htmlspecialchars($servico['pagamento_comprovante'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Ver comprovante</a>
                    <?php } ?>

                    <form action="servicos.php" method="POST" style="margin-top: 12px;">
                        <input type="hidden" name="acao" value="confirmar_pagamento">
                        <input type="hidden" name="id_servico" value="<?php echo (int)$servico['id_servico']; ?>">
                        <button type="submit" class="btn btn-primary btn-small">Confirmar pagamento</button>
                    </form>
                </div>
            </div>
        </main>
    </body>
    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>
</html>
