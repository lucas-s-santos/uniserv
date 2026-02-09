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
            <section class="hero">
                <div class="hero-content">
                    <div class="eyebrow">Servicos relampago</div>
                    <h1 class="hero-title">Uniserv conecta voce ao servico certo, na sua cidade</h1>
                    <p class="hero-subtitle">Encontre profissionais disponiveis, acompanhe pedidos ativos e mantenha seu historico organizado em um so lugar.</p>
                    <div class="hero-actions">
                        <?php if (isset($_SESSION['cpf'])) { ?>
                            <a class="btn btn-accent" href="chamar.php">Chamar servico</a>
                            <a class="btn" href="perfil.php">Meu perfil</a>
                        <?php } else { ?>
                            <a class="btn btn-accent" href="cadastro.php">Criar conta</a>
                            <a class="btn btn-primary" href="login.php">Entrar</a>
                        <?php } ?>
                    </div>
                </div>
                <div class="hero-media">
                    <img class="hero-logo" src="image/logoservicore.jpg" alt="Logo Uniserv">
                </div>
            </section>

            <section class="features">
                <article class="feature-card">
                    <h3>Fluxo simples</h3>
                    <p>Abra um chamado em poucos passos, sem telas confusas ou excesso de popups.</p>
                </article>
                <article class="feature-card">
                    <h3>Informacao clara</h3>
                    <p>Acompanhe servicos ativos, status e historico com visual direto.</p>
                </article>
                <article class="feature-card">
                    <h3>Perfis organizados</h3>
                    <p>Divisao por funcao com menus e acessos adequados para cada publico.</p>
                </article>
            </section>

            <section class="steps">
                <article class="step-card">
                    <span class="step-number">PASSO 1</span>
                    <h3>Crie ou acesse sua conta</h3>
                    <p>Entre como cliente, colaborador ou administrador para ver os recursos certos.</p>
                </article>
                <article class="step-card">
                    <span class="step-number">PASSO 2</span>
                    <h3>Abra ou aceite um chamado</h3>
                    <p>Escolha o servico, envie os detalhes e acompanhe o andamento.</p>
                </article>
                <article class="step-card">
                    <span class="step-number">PASSO 3</span>
                    <h3>Finalize e avalie</h3>
                    <p>Confirme a entrega, registre comentarios e mantenha o historico completo.</p>
                </article>
            </section>
        </main>
    </body>

    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>
</html>