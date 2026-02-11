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
            <!-- HERO SECTION -->
            <section class="home-hero">
                <div class="home-hero-background">
                    <div class="hero-shape hero-shape-1"></div>
                    <div class="hero-shape hero-shape-2"></div>
                    <div class="hero-shape hero-shape-3"></div>
                </div>
                <div class="home-hero-wrapper">
                    <div class="hero-content-box">
                        <span class="hero-badge">⚡ Conectando Profissionais</span>
                        <h1 class="hero-title-big">Serviços incríveis a um clique de distância</h1>
                        <p class="hero-description">Uniserv conecta você ao profissional certo na sua cidade. Rápido, confiável e sem complicações.</p>
                        <div class="hero-actions-group">
                            <?php if (isset($_SESSION['cpf'])) { ?>
                                <a class="btn btn-primary btn-hero" href="chamar.php">
                                    <span>⚡ Chamar Serviço</span>
                                </a>
                                <a class="btn btn-ghost btn-hero" href="perfil.php">
                                    <span>👤 Meu Perfil</span>
                                </a>
                            <?php } else { ?>
                                <a class="btn btn-primary btn-hero" href="cadastro.php">
                                    <span>🚀 Criar Conta</span>
                                </a>
                                <a class="btn btn-ghost btn-hero" href="login.php">
                                    <span>🔓 Entrar</span>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="hero-visual">
                        <div class="hero-logo-container">
                            <img class="hero-logo-img" src="image/logoservicore.jpg" alt="Uniserv">
                            <div class="logo-glow"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- BENEFITS SECTION -->
            <section class="home-benefits">
                <div class="benefits-header">
                    <h2>Por que escolher Uniserv?</h2>
                    <p>Tudo que você precisa para solicitar ou oferecer serviços em um único lugar</p>
                </div>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">⚡</div>
                        <h3>Super Rápido</h3>
                        <p>Crie um chamado em poucos segundos. Sem burocracia, sem complicação.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">👁️</div>
                        <h3>Acompanhamento Real</h3>
                        <p>Veja em tempo real o status do seu serviço de início até conclusão.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">🔒</div>
                        <h3>100% Seguro</h3>
                        <p>Seus dados protegidos com segurança em primeiro lugar.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">📊</div>
                        <h3>Histórico Completo</h3>
                        <p>Mantenha um registro organizado de todos os seus serviços.</p>
                    </div>
                </div>
            </section>

            <!-- HOW IT WORKS -->
            <section class="home-process">
                <div class="process-header">
                    <h2>Como funciona?</h2>
                    <p>3 passos simples para conectar com profissionais incríveis</p>
                </div>
                <div class="process-steps">
                    <div class="process-step">
                        <div class="step-number-circle">
                            <span>1</span>
                        </div>
                        <div class="step-content">
                            <h3>Crie sua conta</h3>
                            <p>Cadastre-se como cliente ou colaborador e veja recursos personalizados para você.</p>
                            <div class="step-icon">📝</div>
                        </div>
                    </div>

                    <div class="step-connector">
                        <svg width="100%" height="60" viewBox="0 0 100 60" preserveAspectRatio="none">
                            <path d="M 0 30 Q 50 0, 100 30" stroke="var(--c-primary)" stroke-width="2" fill="none" stroke-dasharray="5,5" />
                        </svg>
                    </div>

                    <div class="process-step">
                        <div class="step-number-circle">
                            <span>2</span>
                        </div>
                        <div class="step-content">
                            <h3>Encontre e contrate</h3>
                            <p>Procure pelo serviço que precisa ou aceite novos chamados como profissional.</p>
                            <div class="step-icon">🔍</div>
                        </div>
                    </div>

                    <div class="step-connector">
                        <svg width="100%" height="60" viewBox="0 0 100 60" preserveAspectRatio="none">
                            <path d="M 0 30 Q 50 0, 100 30" stroke="var(--c-primary)" stroke-width="2" fill="none" stroke-dasharray="5,5" />
                        </svg>
                    </div>

                    <div class="process-step">
                        <div class="step-number-circle">
                            <span>3</span>
                        </div>
                        <div class="step-content">
                            <h3>Finalize e avalie</h3>
                            <p>Confirme a entrega, registre comentários e mantenha seu histórico completo.</p>
                            <div class="step-icon">✓</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA SECTION -->
            <section class="home-cta">
                <div class="cta-content">
                    <h2>Pronto para começar?</h2>
                    <p>Junte-se a milhares de pessoas que já confiam na Uniserv</p>
                    <?php if (!isset($_SESSION['cpf'])) { ?>
                    <a class="btn btn-accent btn-large" href="cadastro.php">Criar minha conta gratuitamente</a>
                    <?php } else { ?>
                    <a class="btn btn-accent btn-large" href="chamar.php">Solicitar um serviço agora</a>
                    <?php } ?>
                </div>
            </section>
        </main>
    </body>
</html>