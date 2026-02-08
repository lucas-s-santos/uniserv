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
                let notice = document.getElementById("formNotice");
                let text = document.getElementById("formNoticeText");
                if (!notice || !text) {
                    return;
                }
                text.textContent = message;
                notice.style.display = "flex";
                notice.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            function testarSenha() {
                return true;
            }
        </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        <?php
            if (isset($_SESSION['avisar'])) {
                $msg = htmlspecialchars($_SESSION['avisar'], ENT_QUOTES, 'UTF-8');
                echo '<div class="notice" id="aviso"><div><strong>Aviso:</strong> ' . $msg . '</div></div>';
                unset($_SESSION['avisar']);
            }
        ?>
        <div class="notice notice--warn" id="formNotice" style="display: none;">
            <div id="formNoticeText">Aviso</div>
            <button type="button" onclick="this.parentElement.style.display='none';">Fechar</button>
        </div>
        <form name="form2" method="POST" action="processa_login.php" id="form2">
            <div class="fonte">
                <div class="dentro form-card">
                    <div class="title" style="text-align: center;">Login</div>
                    <div class="form-grid form-grid--single">
                        <div class="campo-texto"> <label>Digite o seu cpf</label> <input type="text" name="cpf_login" id="cpf" placeholder="Digite o cpf" required> </div>
                        <div class="campo-texto"> <label>Digite a senha</label> <input type="password" name="senha_login" id="senha" placeholder="Digite a senha" required> </div>
                    </div>
                    <div class="button-group"><input type="submit" value="Entrar" onclick="return testarSenha()"></div>
                </div>
            </div>
        </form>    
        </main>
    </body>
    <footer class="footer">
        <object data="pe.html" height="45px" width="100%"></object>
    </footer>

</html>