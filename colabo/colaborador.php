<?php
    session_start();
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Voce precisa estar logado para acessar esta area.";
        header('location: ../login.php');
        exit;
    }
    if ((int)$_SESSION['funcao'] !== 2) {
        $_SESSION['avisar'] = "Acesso restrito para colaboradores.";
        header('location: ../login.php');
        exit;
    }
    include_once("../conexao.php");
    include_once ("../all.php");
    include_once ("../audit.php");
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    if (isset($_POST['atualizar_disponivel'])) {
        $numero_servico = $_POST['atualizar_disponivel'];
        $jogue_no_banco = "SELECT * FROM trabalhador_funcoes WHERE id_trafun = '$_POST[atualizar_disponivel]'";
        $resultado_e = mysqli_query($conn, $jogue_no_banco);
        $linha45 = mysqli_fetch_array($resultado_e);
        if ($linha45['disponivel'] == "1") {
            $comando_final = "UPDATE trabalhador_funcoes SET disponivel='0' WHERE id_trafun='$linha45[id_trafun]'";
        } else {
            $comando_final = "UPDATE trabalhador_funcoes SET disponivel='1' WHERE id_trafun='$linha45[id_trafun]'";
        }
        mysqli_query($conn, $comando_final);
    }

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

                $("#id_p3").mask("0000000000");

                function calma() {
                    showFormNotice('Funcao temporariamente indisponivel.');
                }

                function testeCandidatar() {
                    let formCADS = document.getElementById("newclass");
                    let campoValor = formCADS.valor_hora.value,
                    campoFuncao = formCADS.funcao_servico;
                    for (var i=0; i < campoFuncao.length; i++) {
                        if (campoFuncao[i].selected && campoFuncao[i].value == "") {
                            showFormNotice("Selecione um servico.");
                            return false;
                        }
                        if (campoFuncao[i].selected) {
                            var salvarFuncao = campoFuncao[i].value;
                        }    
                    }
                    <?php
                        $analise_funcoes= "SELECT * FROM trabalhador_funcoes";
                        $analise_geral = mysqli_query($conn, $analise_funcoes);
                        while ($linha12 = mysqli_fetch_array($analise_geral)) {
                            echo "if ('$_SESSION[id_acesso]' == '$linha12[registro_id_registro]') {
                                        if (salvarFuncao == '$linha12[funcoes_id_funcoes]') { showFormNotice('Voce ja se candidatou a esse servico.'); return false; }
                                  }";
                        }
                    ?>
                }

                function onOffAtualizar(Y) {
                    let editar = document.getElementById(Y);
                    
                    if (editar.style.background == "red") {
                        editar.style.background = "green";
                    } else {
                        editar.style.background = "red";
                    }
                }

            </script>
            <title>Página principal</title>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <div style="width:100%; position: fixed"><object data="../menu.php" height="80px" width="100%"></object></div>
        <div class="menu-spacer"></div>
        <div class="notice notice--warn" id="formNotice" style="display: none;">
            <div id="formNoticeText">Aviso</div>
            <button type="button" onclick="this.parentElement.style.display='none';">Fechar</button>
        </div>
        
        <form action='processa_candidatar.php' method='POST' class='hidden_two' id='newclass' style='text-align: left;'>
        <?php
            echo "<input type='hidden' name='acao_cand' value='$_SESSION[id_acesso]'>
            <div class='title'>Candidatar-se</div>
            <label>Qual função você se interessa?</label><br>
            <select name='funcao_servico'>
                <option value='' selected>Disponíveis:";
            $pesquise_funcoes= "SELECT * FROM funcoes";
            $resultado_funcoes = mysqli_query($conn, $pesquise_funcoes);
            while ($linha9 = mysqli_fetch_array($resultado_funcoes)) {
                
                echo "<option value='".$linha9['id_funcoes']."'>".$linha9['nome_func'];
            }     
        ?>    
            </select> <br>
            <label>Certificado(Opcional, coloque foto do original se tiver)</label><br>
            <input type="file" name="certificado" accept="image/png, image/jpeg"> <br>
            <label>Quanto você cobra por hora(taxa 10%)?</label><br>
            <input type='text' name='valor_hora' id="preco" placeholder='Valor em R$(XXXX)' required> <br>
            <div class='hidden_sub' style='text-align: center'><input type='submit' value='Confirmar' onclick='return testeCandidatar()'> <input type='reset' value='Cancelar' onclick="invisibleON('newclass')"></div>
        </form>

        <div class="title">Informação</div>
        <div class='botaolist'><a onclick="invisibleON('newclass')">Inscrever se em uma função</a> <a href='../historico.php?qm=2'>Historico de serviços</a> </div>
        <div class="subtitle">Seus serviços</div>

        <?php
            $analise_funcoes2= "SELECT B.nome_func as 'funcao', A.certificado, A.valor_hora, A.disponivel, A.id_trafun
             FROM trabalhador_funcoes A INNER JOIN funcoes B ON A.funcoes_id_funcoes = B.id_funcoes WHERE registro_id_registro = '$_SESSION[id_acesso]'";
            $analise_geral2 = mysqli_query($conn, $analise_funcoes2);
            while ($linha13 = mysqli_fetch_array($analise_geral2)) {
                echo "<div class='caixa'> <form action='#' method='POST'><input  type='hidden' value='".$linha13['id_trafun']."' name='atualizar_disponivel'>
                <input class='off' type='submit' style='width:100px; height: 30px; background: ";
                if ($linha13['disponivel'] == 0) {echo "red";} else {echo "green";}
                echo ";' value='STATUS'>";
                echo "<b>$linha13[funcao]</b> / Valor por hora: $linha13[valor_hora] / Seu certificado: <img src=='$linha13[certificado]'></form></div>";
            }
        ?>
       <object data="convites_update.php" height="600px" width="100%"></object>


        
    </body>

    <footer class="footer">
        <object data="../pe.html" height="45px" width="100%"></object>
    </footer>
</html>