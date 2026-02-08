<?php
    session_start();
    include_once "conexao.php";
    include_once "status.php";
    include_once "audit.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Você precisa estar logado no site!";
        header('location: login.php');
        exit;
        
    }
    if (isset($_POST['id_servico'])) {
        $id_servico = (int)$_POST['id_servico'];
        $buscar_status = "SELECT ativo FROM servico WHERE id_servico='$id_servico' LIMIT 1";
        $resultado_status = mysqli_query($conn, $buscar_status);
        $status_atual = mysqli_fetch_assoc($resultado_status);

        if (!$status_atual) {
            $_SESSION['avisar'] = "Servico nao encontrado.";
            header('location: servicos.php');
            exit;
        }

        $status_atual = (int)$status_atual['ativo'];
        if ($status_atual !== SERVICO_STATUS_ATIVO) {
            $_SESSION['avisar'] = "Nao e possivel finalizar este servico no status atual.";
            header('location: servicos.php');
            exit;
        }

        $status_finalizado = SERVICO_STATUS_FINALIZADO;
        $tempo = mysqli_real_escape_string($conn, $_POST['tempo']);
        $atualizar = "UPDATE servico SET ativo='$status_finalizado', tempo_servico='$tempo', endereco='Finalizado' WHERE id_servico='$id_servico'";
        mysqli_query($conn, $atualizar);
        audit_log($conn, 'finalizar', 'servico', $id_servico, "Tempo: $tempo");
        header('location: index.php');
        exit;
    }
    include_once ("all.php");
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
                function seiLa() {
                    let valor_hora = document.getElementById("conta1").textContent;
                    let minutos = document.getElementById("conta2").value;
                    let hora = minutos/60;
                    let total = Math.round(hora * valor_hora);
                    document.getElementById("conta3").textContent = "R$ "+total;
                }
                function confirmeIsso() {
                    let check = document.getElementById("confirm_total");
                    if (check && !check.checked) {
                        showFormNotice("Confirme que informou o valor total ao cliente.");
                        return false;
                    }
                    return true;
                }
            </script>
    </head>

    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        <div class="notice notice--warn" id="formNotice" style="display: none;">
            <div id="formNoticeText">Aviso</div>
            <button type="button" onclick="this.parentElement.style.display='none';">Fechar</button>
        </div>
        <br>
        <?php 
            if (isset($_GET['servic'])) {
                $comando_nome = "SELECT B.nome_func as 'funcao', A.valor_atual FROM servico A INNER JOIN funcoes B ON A.funcoes_id_funcoes = B.id_funcoes WHERE id_servico = '$_GET[servic]'";
                $joga_no_banco = mysqli_query($conn, $comando_nome);
                $nome_funcao = mysqli_fetch_array($joga_no_banco);
                echo "<form action='servicos.php' method='POST' class='hidden' id='hidden65'>
                    <div class='title'>Finalizar serviço</div>
                    <label>Função: $nome_funcao[funcao].</label>  <label>Valor por Hora: <b id='conta1'>$nome_funcao[valor_atual]</b>.</label> <br>
                    <label>Quanto tempo durou?(responda em minutos)</label> <br>
                    <input type='hidden' value='$_GET[servic]' name='id_servico'>
                    <input type='text' name='tempo' id='conta2' placeholder='TEMPO' onchange='seiLa()'> <br>
                    <label>Valor total calculado: <b id='conta3'>Digite o tempo</b></label>
                    <div class='hidden_sub'>
                        <label style='display: block; margin-bottom: 8px;'>
                            <input type='checkbox' id='confirm_total'> Confirmo que informei o valor total
                        </label>
                        <input type='submit' value='Confirmar' onclick='return confirmeIsso()'>
                    </div>
                </form>";
                echo "<script>invisibleON('hidden65');</script>";
            }
        ?>
        <object data="servicossub.php" height="600px" width="100%"></object>
        </main>
    </body>

    <footer class="footer">
        <object data="pe.html" height="45px" width="100%"></object>
    </footer>
</html>