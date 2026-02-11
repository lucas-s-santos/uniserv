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
            <!-- HERO SECTION -->
            <section class="confirm-hero">
                <div class="confirm-hero-content">
                    <div class="confirm-icon">💰</div>
                    <h1 class="confirm-title">Confirme o Pagamento</h1>
                    <p class="confirm-subtitle">Verifique o comprovante e finalize a confirmação do pagamento do cliente</p>
                </div>
            </section>

            <div class="confirm-container">
                <!-- SERVICE INFO CARD -->
                <div class="confirm-card service-info-card">
                    <div class="card-header">
                        <h2 class="card-title">📋 Informações do Serviço</h2>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Cliente</span>
                            <span class="info-value"><?php echo htmlspecialchars($servico['nome_cliente'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Valor Final</span>
                            <span class="info-value amount">R$ <?php echo $valor_fmt; ?></span>
                        </div>
                    </div>

                    <div class="status-badge success">
                        <span class="status-indicator">✓</span>
                        <span class="status-text">Pagamento recebido</span>
                    </div>
                </div>

                <!-- RECEIPT VERIFICATION -->
                <div class="confirm-card receipt-card">
                    <div class="card-header">
                        <h2 class="card-title">📸 Comprovante de Pagamento</h2>
                    </div>

                    <?php if ($servico['pagamento_comprovante'] === 'presencial') { ?>
                        <div class="receipt-content presencial">
                            <div class="presencial-icon">💵</div>
                            <p class="presencial-text">Pagamento informado como <strong>Presencial</strong></p>
                            <p class="presencial-subtitle">O cliente confirmou o pagamento na hora do atendimento</p>
                        </div>
                    <?php } else { ?>
                        <div class="receipt-content">
                            <p class="receipt-instruction">Vizualize o comprovante enviado pelo cliente:</p>
                            <a class="btn btn-ghost receipt-link" href="<?php echo htmlspecialchars($servico['pagamento_comprovante'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                🔍 Ver Comprovante
                            </a>
                        </div>
                    <?php } ?>
                </div>

                <!-- CONFIRMATION SECTION -->
                <div class="confirm-card action-card">
                    <div class="card-header">
                        <h2 class="card-title">✅ Confirmar Recebimento</h2>
                        <p class="card-subtitle">Ao confirmar, você reconhece que recebeu o pagamento</p>
                    </div>

                    <form action="servicos.php" method="POST" class="confirm-form">
                        <input type="hidden" name="acao" value="confirmar_pagamento">
                        <input type="hidden" name="id_servico" value="<?php echo (int)$servico['id_servico']; ?>">
                        
                        <div class="form-notice">
                            <span class="notice-icon">⚠️</span>
                            <p>Confirme apenas se recebeu o pagamento. Esta ação não pode ser desfeita.</p>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-full btn-large confirm-btn">
                                <span>✓ Confirmar Pagamento</span>
                            </button>
                            <a href="servicos.php" class="btn btn-ghost btn-full">
                                ← Voltar para Serviços
                            </a>
                        </div>
                    </form>
                </div>

                <!-- TIMELINE -->
                <div class="confirm-timeline">
                    <h3>Próximas etapas</h3>
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-status">✓</div>
                            <div class="timeline-text">
                                <p class="timeline-title">Pagamento Enviado</p>
                                <p class="timeline-desc">Cliente enviou comprovante</p>
                            </div>
                        </div>
                        <div class="timeline-item current">
                            <div class="timeline-status">📍</div>
                            <div class="timeline-text">
                                <p class="timeline-title">Você está aqui</p>
                                <p class="timeline-desc">Confirme o recebimento</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-status">4</div>
                            <div class="timeline-text">
                                <p class="timeline-title">Serviço Finalizado</p>
                                <p class="timeline-desc">Transação concluída com sucesso</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
