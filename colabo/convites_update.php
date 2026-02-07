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
    include_once "../conexao.php";
    include_once "../status.php";
    include_once "../audit.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    if (isset($_POST['cidade_mudar'])) {
        $comando_editar = "UPDATE registro SET cidade='$_POST[cidade_mudar]' WHERE id_registro='$_SESSION[id_acesso]'";
        unset($_POST['cidade_mudar']);
        mysqli_query($conn, $comando_editar);
    }
    if (isset($_POST['id_servico'])) {
        $id_servico = (int)$_POST['id_servico'];
        $buscar_status = "SELECT ativo FROM servico WHERE id_servico='$id_servico' LIMIT 1";
        $resultado_status = mysqli_query($conn, $buscar_status);
        $status_atual = mysqli_fetch_assoc($resultado_status);

        if (!$status_atual) {
            $_SESSION['avisar'] = "Servico nao encontrado.";
        } else {
            $status_atual = (int)$status_atual['ativo'];
            $status_pendente = SERVICO_STATUS_PENDENTE;
            if ($status_atual !== $status_pendente) {
                $_SESSION['avisar'] = "Este chamado ja foi respondido.";
            } else {
                if ($_POST['escolha'] == 'sim') {
                    $status_aceito = SERVICO_STATUS_ATIVO;
                    $comando_editar = "UPDATE servico SET ativo='$status_aceito' WHERE id_servico='$id_servico'";
                } else {
                    $status_recusado = SERVICO_STATUS_RECUSADO;
                    $comando_editar = "UPDATE servico SET ativo='$status_recusado', comentario='recusedservice#43242' WHERE id_servico='$id_servico'";
                }
                mysqli_query($conn, $comando_editar);
                $acao = $_POST['escolha'] == 'sim' ? 'aceitar' : 'recusar';
                audit_log($conn, $acao, 'servico', $id_servico, 'Resposta do colaborador');
                if ($_POST['escolha'] == 'sim') {
                    echo "<script> parent.window.location.href = '../servicos.php';</script>";
                }
            }
        }
        unset($_POST['id_servico']);
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta http-equiv="refresh" content="15">
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
                function naoVazio() {
                    let formCAD = document.getElementById("form23");
                    let campoEscolha = formCAD.escolha;
                    for (var i=0; i < campoEscolha.length; i++) {
                        if (campoEscolha[i].selected && campoEscolha[i].value == "") {
                            showFormNotice("Selecione aceitar ou recusar.");
                            return false;
                        }       
                    }
                }
            </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <div class="notice notice--warn" id="formNotice" style="display: none;">
            <div id="formNoticeText">Aviso</div>
            <button type="button" onclick="this.parentElement.style.display='none';">Fechar</button>
        </div>
        <div class="caixa2">
            <form action="#" method="POST" style="text-align: center">
                Sua Cidade:
                <?php 
                    $analise_cidade = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_acesso]' LIMIT 1";
                    $procure_sua_cidade = mysqli_query($conn, $analise_cidade);
                    $resultado5 = mysqli_fetch_assoc($procure_sua_cidade);
    
                    echo "<input type='text' name='cidade_mudar' placeholder='digite sua cidade' value='$resultado5[cidade]' required> <input type='submit' value='Alterar cidade'> ";
    
                    if ($resultado5['cidade'] == " " || $resultado5['cidade'] == "") {
                        echo "<p style='color: red; font-style: oblique;'>Você precisa digitar sua cidade ou nunca será chamado</p>";
                    }
                ?>
            </form>
            <div class="subtitle">Chamados:</div>
            <?php
                $status_pendente = SERVICO_STATUS_PENDENTE;
                $verifique = "SELECT A.endereco 'endereco', B.nome_func 'funcao', A.id_servico 'id_servico',
                 C.nome 'nome' FROM servico A INNER JOIN registro C ON C.id_registro = A.registro_id_registro 
                INNER JOIN funcoes B ON B.id_funcoes = A.funcoes_id_funcoes WHERE id_trabalhador = '$_SESSION[id_acesso]' AND ativo='$status_pendente'";
                $jogue_no_banco = mysqli_query($conn, $verifique);
                while ($linha75 = mysqli_fetch_array($jogue_no_banco)) {
                    echo "<table align='left' class='chega_de_tabela'><tr><td>Nome: $linha75[nome]</td>
                    <td>Endereço-> $linha75[endereco]</td><td>Função: $linha75[funcao]</td>";
                     echo "<form action='#' id='form23' method='POST'>
                     <input type='hidden' name='id_servico' value='$linha75[id_servico]'>
                     <td><select name='escolha' id='escolha'>
                        <option value=''>Aceitar??<option value='sim'>Aceitar<option value='nao'>Recusar
                     </select></td>
                     <td><input type='submit' value='Confirmar' onclick='return naoVazio()'></td>
                 </form></tr></table>";
                    
                }
            ?>
            
            <br><br>
            
        </div>

        
    </body>
</html>