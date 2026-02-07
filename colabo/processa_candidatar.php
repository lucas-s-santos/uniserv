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
?>

<?php
    if (isset($_POST['acao_cand'])) {
        $imagem = "";
        $id_colaborador = $_POST['acao_cand'];
        $funcao = $_POST['funcao_servico'];
        $imagem = $_POST['certificado'];
        $valor = $_POST['valor_hora'];
    
        $inserir = "INSERT INTO trabalhador_funcoes(funcoes_id_funcoes, registro_id_registro, certificado, valor_hora, disponivel)
         values ('$funcao', '$id_colaborador', '$imagem', '$valor', '0')";
        $resultado = mysqli_query($conn, $inserir);
        
        if(mysqli_insert_id($conn)){
            $_SESSION['avisar'] = "Sucesso! O serviço foi atribuido<br>como opção para você!";
        }else{
            $_SESSION['avisar'] = "Função não estabelecida ERRO_INSERT_ID_X";
        }
    } else {
        $_SESSION['avisar'] = "Função não estabelecida ERRO_CHECK_POST_X";
    }
    mysqli_close($conn);
    header('location: ../index.php');
?>