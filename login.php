<?php
    session_start();

    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    include_once("conexao.php");

    if (isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Você ja está logado!";
        header('location: index.php');
        exit;
    }

    $csrf_login = bin2hex(random_bytes(32));
    $_SESSION['csrf_login'] = $csrf_login;
    $old_login_cpf = isset($_SESSION['old_login_cpf']) ? (string)$_SESSION['old_login_cpf'] : '';
    unset($_SESSION['old_login_cpf']);

    include_once("all.php");
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
        <script>
            function showFormNotice(message) {
                if (window.showToast) {
                    showToast(message, "warn");
                }
            }

            function validarLogin() {
                const form = document.getElementById("form2");
                const cpfDigits = (form.cpf_login.value || "").replace(/\D/g, "");
                const senha = form.senha_login.value || "";

                if (cpfDigits.length !== 11) {
                    showFormNotice("Informe um CPF valido.");
                    return false;
                }
                if (senha.trim() === "") {
                    showFormNotice("Informe sua senha.");
                    return false;
                }
                return true;
            }

            function alternarSenhaLogin() {
                const senha = document.getElementById("senha");
                if (!senha) {
                    return;
                }
                senha.type = senha.type === "password" ? "text" : "password";
            }
        </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        <form name="form2" method="POST" action="processa_login.php" id="form2">
            <div class="fonte">
                <div class="dentro form-card">
                    <div class="title" style="text-align: center;">Login</div>
                    <div class="form-grid form-grid--single">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_login, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="campo-texto">
                            <label>Digite o seu CPF</label>
                            <input type="text" name="cpf_login" id="cpf" placeholder="000.000.000-00" value="<?php echo htmlspecialchars($old_login_cpf, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" inputmode="numeric" required>
                        </div>
                        <div class="campo-texto">
                            <label>Digite a senha</label>
                            <input type="password" name="senha_login" id="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
                            <div class="button-group" style="margin-top: 8px;">
                                <input type="button" value="Mostrar senha" onclick="alternarSenhaLogin()">
                            </div>
                        </div>
                    </div>
                    <div class="button-group"><input type="submit" value="Entrar" onclick="return validarLogin()"></div>
                    <div class="texto" style="margin-top: 10px; text-align: center;">
                        Ainda nao tem conta? <a href="cadastro.php">Cadastre-se</a>
                    </div>
                </div>
            </div>
        </form>    
        </main>
    </body>
</html>
