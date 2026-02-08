<?php
    session_start();
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Voce precisa estar logado para acessar esta area.";
        header('location: ../login.php');
        exit;
    }
    if ((int)$_SESSION['funcao'] !== 3) {
        $_SESSION['avisar'] = "Acesso apenas para clientes interessados em colaborar.";
        header('location: ../login.php');
        exit;
    }
    include_once("../conexao.php");
    include_once ("../all.php");
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
            <link rel="stylesheet" href="../css/estrutura_geral.css">
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
                function invisibleON(X) {
                    let edit = document.getElementById(X);

                    if (!edit) {
                        return;
                    }

                    let backdrop = document.getElementById("modal-backdrop");
                    if (!backdrop) {
                        backdrop = document.createElement("div");
                        backdrop.id = "modal-backdrop";
                        backdrop.className = "modal-backdrop";
                        backdrop.addEventListener("click", function () {
                            let openModal = document.querySelector(".hidden.is-open, .hidden_two.is-open");
                            if (openModal) {
                                openModal.classList.remove("is-open");
                            }
                            updateBackdrop(backdrop);
                        });
                        document.body.appendChild(backdrop);
                    }

                    ensureModalHeader(edit, backdrop);
                    edit.classList.toggle("is-open");
                    updateBackdrop(backdrop);
                }

                function updateBackdrop(backdrop) {
                    let hasOpen = document.querySelector(".hidden.is-open, .hidden_two.is-open");
                    if (hasOpen) {
                        backdrop.classList.add("is-open");
                    } else {
                        backdrop.classList.remove("is-open");
                    }
                }

                function ensureModalHeader(modal, backdrop) {
                    if (modal.querySelector(".modal-header")) {
                        return;
                    }

                    let titleText = modal.getAttribute("data-title");
                    if (!titleText) {
                        let titleEl = modal.querySelector(".title");
                        if (titleEl) {
                            titleText = titleEl.textContent.trim();
                        }
                    }
                    if (!titleText) {
                        titleText = "Detalhes";
                    }

                    let header = document.createElement("div");
                    header.className = "modal-header";

                    let title = document.createElement("div");
                    title.className = "modal-title";
                    title.textContent = titleText;

                    let closeBtn = document.createElement("button");
                    closeBtn.type = "button";
                    closeBtn.className = "modal-close";
                    closeBtn.textContent = "Fechar";
                    closeBtn.addEventListener("click", function () {
                        modal.classList.remove("is-open");
                        updateBackdrop(backdrop);
                    });

                    header.appendChild(title);
                    header.appendChild(closeBtn);
                    modal.insertBefore(header, modal.firstChild);
                }

                function analiseInformacoes() {
                    let formCAD = document.getElementById("form1");
                    let campoEmail = formCAD.email.value,
                        campoCnpj = formCAD.cnpj.value,
                        campoDesc = formCAD.descricao.value,
                        campoTelefone = formCAD.telefone.value,
                        campoCidade = formCAD.cidade.value;
                        if (campoDesc.length > 125) {
                            showFormNotice('Limite de descricao atingido: '+campoDesc.length+' / 125.');
                            return false;
                        }
                        if (campoCidade.trim() === "") {
                            showFormNotice('Informe sua cidade.');
                            return false;
                        }
                    <?php
                        $pesquise_usuarios= "SELECT * FROM registro";
                        $resultado = mysqli_query($conn, $pesquise_usuarios);
                        while ($linha = mysqli_fetch_array($resultado)) {
                            echo "if ($_SESSION[id_acesso] != $linha[id_registro]) {";

                            echo "if (campoTelefone != '') {if (campoTelefone == '$linha[telefone]') {showFormNotice('Telefone ja cadastrado.'); return false;}}";

                            echo "if (campoEmail != '') {if (campoEmail == '$linha[email]') {showFormNotice('E-mail ja cadastrado.'); return false;}}";

                            echo "if (campoCnpj != '') {if (campoCnpj == '$linha[cnpj]') {showFormNotice('CNPJ ja cadastrado.'); return false;}}";

                            echo "}";
                        }
                    ?>
                }

            </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include '../menu.php'; ?>
        <div class="menu-spacer"></div>
        <div class="notice notice--warn" id="formNotice" style="display: none;">
            <div id="formNoticeText">Aviso</div>
            <button type="button" onclick="this.parentElement.style.display='none';">Fechar</button>
        </div>

        <div class='hidden' id="hidden8"><label>Ei não é como se eu fosse colocar esse site no ar, então por agora, não vejo necessidades disto, obrigado<label></div>
        <form name="form1" method="POST" action="processa_cola.php" id="form1">
            <div class="fonte">
                <div class="dentro form-card">
                    <?php  
                        $comando_testar = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_acesso]' LIMIT 1";
                        $procure_o_teste = mysqli_query($conn, $comando_testar);
                        $resultado7 = mysqli_fetch_assoc($procure_o_teste);
                        echo '<div class="title" style="color: yellow">Colaborador</div>
                            <input type="hidden" name="id_pessoal" value="'.$_SESSION["id_acesso"].'">
                            <div class="campo-texto"> <label>E-mail (Obrigatorio)</label> <input type="text" name="email" value="'.$resultado7["email"].'" placeholder="Necessario para contato" required> </div>
                            <div class="campo-texto"> <label>Cidade</label> <input type="text" name="cidade" value="'.$resultado7["cidade"].'" placeholder="Digite sua cidade" required> </div>
                            <div class="campo-texto"> <label>Cnpj (Se tiver)</label> <input type="text" name="cnpj" placeholder="É opcional"> </div>
                            <div class="campo-texto"> <label>Telefone(opcional)</label> <input type="text" id="telefone" name="telefone" value="'.$resultado7["telefone"].'"placeholder="É opcional, mas facilita comunicação"> </div>
                            <div class="campo-texto"> <label>Descrição</label> <input type="text" name="descricao" value="'.$resultado7["descricao"].'" placeholder="Escreva um pouco sobre você!"> </div>';

                    ?>
                    <div class="botao"><input type="reset" value="Limpar"><input type="submit" value="Fazer inscrição" onclick="return analiseInformacoes()"></div>
                    <br>
                    <div class="campo-texto">Ao clicar em fazer inscrição você concorda com os termos e condições do site</div><div class='botaolist2'><p onclick="invisibleON('hidden8')">Clique aqui para ler!</p></div>
                </div>
            </div>
        </form>
        
    </body>

    <footer class="footer">
        <object data="pe.html" height="45px" width="100%"></object>
    </footer>

</html>