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
                let formLOG = document.getElementById("form2");
                    let campoCpf = formLOG.cpf_login.value,
                        campoSenha = formLOG.senha_login.value;
                    <?php
                        $pesquise_usuarios= "SELECT * FROM registro";
                        $resultado = mysqli_query($conn, $pesquise_usuarios);
                        while ($linha = mysqli_fetch_array($resultado)) {
                            echo "if (campoCpf=='$linha[cpf]') {
                                    if (campoSenha == '$linha[senha]') {
                                        return true;
                                    } else {
                                        showFormNotice('Senha incorreta. Tente novamente.');
                                        formLOG.senha_login.value = '';
                                        return false;
                                    }
                            }";
                        }
                    ?>
                showFormNotice("CPF nao encontrado. Verifique e tente novamente.");
                formLOG.senha_login.value = '';
                return false;
            }
        </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <div style="width:100%; position: fixed"><object data="menu.php" height="80px" width="100%"></object></div>
        <div class="menu-spacer"></div>
        <main class="page">
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