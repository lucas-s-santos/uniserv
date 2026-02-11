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
            <!-- HERO HEADER -->
            <section class="about-hero">
                <div class="about-hero-bg"></div>
                <div class="about-hero-content">
                    <span class="about-badge">✨ Nossa História</span>
                    <h1 class="about-title">Uniserv: Conectando comunidades</h1>
                    <p class="about-tagline">Uma plataforma que nasceu da necessidade de aproximar clientes e profissionais com confiança, rapidez e simplicidade.</p>
                </div>
            </section>

            <!-- WHAT IS SECTION -->
            <section class="about-section">
                <div class="about-card large-card">
                    <div class="card-icon">🎯</div>
                    <h2>O que é Uniserv?</h2>
                    <p class="card-description">Uniserv é uma plataforma de serviços descentralizada criada para a comunidade de Alfenas e região. Conecta clientes com profissionais qualificados em um fluxo simples, transparente e seguro.</p>
                    <div class="card-highlights">
                        <span class="highlight">✓ Fácil de usar</span>
                        <span class="highlight">✓ Rastreamento real-time</span>
                        <span class="highlight">✓ Suporte seguro</span>
                    </div>
                </div>
            </section>

            <!-- FOR WHOM SECTION -->
            <section class="about-section">
                <h2 class="section-title">Para quem serve?</h2>
                <div class="about-personas">
                    <div class="persona-card">
                        <div class="persona-icon">👥</div>
                        <h3>Clientes</h3>
                        <p>Encontre profissionais confiáveis para realizar seus serviços. Acompanhe tudo em tempo real e construa uma relação de confiança.</p>
                        <ul class="persona-benefits">
                            <li>Busca rápida e fácil</li>
                            <li>Rastreamento completo</li>
                            <li>Avaliações honestas</li>
                        </ul>
                    </div>

                    <div class="persona-card">
                        <div class="persona-icon">💼</div>
                        <h3>Profissionais</h3>
                        <p>Expanda seus negócios e ganhe oportunidades. Controle seus horários, ganhos e clientes em uma plataforma intuitiva.</p>
                        <ul class="persona-benefits">
                            <li>Novos clientes</li>
                            <li>Gestão simplificada</li>
                            <li>Pagamento garantido</li>
                        </ul>
                    </div>

                    <div class="persona-card">
                        <div class="persona-icon">⚙️</div>
                        <h3>Administradores</h3>
                        <p>Gerencie a plataforma com ferramentas poderosas. Auditoria completa, controle de usuários e segurança garantida.</p>
                        <ul class="persona-benefits">
                            <li>Painel de controle</li>
                            <li>Auditoria detalhada</li>
                            <li>Gerenciamento full</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- DEVELOPER SECTION -->
            <section class="about-section">
                <div class="developer-card">
                    <div class="dev-header">
                        <h2>Quem criou isso?</h2>
                        <p class="dev-subtitle">Conheça o criador por trás da Uniserv</p>
                    </div>

                    <div class="dev-bio">
                        <div class="dev-avatar">
                            <div class="avatar-placeholder">👨‍💻</div>
                        </div>
                        
                        <div class="dev-content">
                            <h3>Lucas Silva dos Santos</h3>
                            <p class="dev-role">Desenvolvedor Web Junior & Designer Gráfico</p>
                            
                            <p class="dev-description">
                                No meu primeiro projeto profissional, criei a Uniserv como um desafio pessoal de combinar <strong>front-end moderno</strong>, <strong>back-end robusto</strong> e <strong>design intuitivo</strong>. 
                                <br><br>
                                Meu objetivo é criar soluções que não apenas funcionam, mas que as pessoas realmente querem usar.
                            </p>

                            <div class="dev-location">
                                <span class="location-item">📍 Alfenas - MG</span>
                                <span class="location-item">✉️ lucassilvadossantos2005@gmail.com</span>
                            </div>

                            <div class="dev-links">
                                <a class="btn btn-ghost" href="https://www.linkedin.com/in/lucas-silva-dos-santos-a82b4b201/" target="_blank" rel="noopener">
                                    💼 LinkedIn
                                </a>
                                <a class="btn btn-ghost" href="https://github.com/lucas-s-santos" target="_blank" rel="noopener">
                                    🐙 GitHub
                                </a>
                                <a class="btn btn-primary" href="https://portfolio-lucas-s-s.netlify.app/" target="_blank" rel="noopener">
                                    🌐 Meu Portfólio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA SECTION -->
            <section class="about-cta">
                <div class="cta-wrapper">
                    <div class="cta-main">
                        <h2>Quer fazer parte dessa história?</h2>
                        <p>Nos ajude a crescer e construa experiências incríveis na Uniserv</p>
                        <?php if (isset($_SESSION['funcao'])) {
                            if ($_SESSION['funcao'] == '3') {
                                echo "<a class='btn btn-accent btn-large' href='colabo/cadastro_colaborador.php'>
                                    🚀 Quero trabalhar aqui
                                </a>";
                            }
                        } else { ?>
                            <a class="btn btn-accent btn-large" href="cadastro.php">
                                🚀 Criar minha conta
                            </a>
                        <?php } ?>
                    </div>
                    <div class="cta-graphic">
                        <div class="graphic-shape"></div>
                    </div>
                </div>
            </section>

            <?php if (isset($_SESSION['funcao'])) {
                if ($_SESSION['funcao'] == '3' && !isset($_GET['skipinterest'])) {
                    // Hidden button for collaborators who want to register
                }
            } ?>
        </main>
    </body>
</html>