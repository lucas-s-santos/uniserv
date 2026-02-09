<?php
    session_start();
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Página principal</title>

    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
            <section class="page-header">
                <div>
                    <div class="page-kicker">Sobre</div>
                    <h1 class="page-title">Uniserv: conexao rapida com servicos locais</h1>
                    <p class="page-subtitle">Sistema que aproxima clientes e colaboradores com um fluxo simples de chamados, acompanhamento em tempo real e historico organizado.</p>
                </div>
                <div class="page-actions">
                    <a class="btn btn-accent" href="chamar.php">Abrir chamado</a>
                    <a class="btn btn-ghost" href="servicos.php">Ver servicos</a>
                </div>
            </section>

            <section class="info-panel">
                <div class="section-title">O que e o sistema</div>
                <p class="section-subtitle">O Uniserv foi criado para facilitar a solicitacao de servicos em Alfenas e regiao. O cliente encontra profissionais, cria o chamado e acompanha o status. O colaborador recebe pedidos, confirma disponibilidade e gerencia seus atendimentos. O administrador garante a seguranca, o cadastro e o acompanhamento das acoes do sistema.</p>
            </section>

            <section class="info-panel">
                <div class="section-title">Para quem serve</div>
                <p class="section-subtitle">Clientes que precisam de servicos rapidos, colaboradores que desejam novas oportunidades e administradores que precisam de controle e auditoria.</p>
            </section>

            <section class="info-panel">
                <div class="section-title">Quem sou eu</div>
                <p class="section-subtitle">Lucas Silva dos Santos - Desenvolvedor Web Junior e Designer Grafico. Busco minha primeira oportunidade como desenvolvedor web junior, com foco em front-end moderno e back-end, aliando UX/UI e design visual para criar solucoes eficientes e bonitas.</p>
                <div class="service-card__meta">
                    <span>Alfenas - MG</span>
                    <span>lucassilvadossantos2005@gmail.com</span>
                </div>
                <div class="action-bar" style="margin-top: 12px;">
                    <a class="btn btn-ghost" href="https://www.linkedin.com/in/lucas-silva-dos-santos-a82b4b201/" target="_blank" rel="noopener">LinkedIn</a>
                    <a class="btn btn-ghost" href="https://github.com/lucas-s-santos" target="_blank" rel="noopener">GitHub</a>
                    <a class="btn btn-primary" href="https://portfolio-lucas-s-s.netlify.app/" target="_blank" rel="noopener">Portfolio</a>
                </div>
            </section>

            <?php if (isset($_SESSION['funcao'])) {
                        if ($_SESSION['funcao'] == '3') {
                             echo "<div class='botaolist'><a href='colabo/cadastro_colaborador.php'>Tenho interesse em trabalhar nesse site</a></div>";
                        }
                    }
            ?>
        </main>
    </body>

    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>

</html>