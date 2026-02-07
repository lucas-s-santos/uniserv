<?php
    session_start();
    include_once("conexao.php");
    include_once("audit.php");
?>
<?php
    if (isset($_POST['nome'])) {
        $telefone = ""; $email ="";
        $nome = $_POST['nome'];
        $apelido = $_POST['apelido'];
        $estado = $_POST['estado'];
        $cpf = $_POST['cpf'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];
        $genero = $_POST['genero'];
        $data = $_POST['data_ani'];

        $pesquise_usuarios= "SELECT * FROM registro";
        $resultado = mysqli_query($conn, $pesquise_usuarios); $erro = 'nao';
        while ($linha = mysqli_fetch_array($resultado)) {
            if ($cpf == $linha['cpf']) {
                $_SESSION['avisar'] = "Esse cpf ja foi cadastrado!";
                $erro = 'sim';
            }

            if ($email != '') { if ($email == $linha['email']) {
                $_SESSION['avisar'] = "Esse e-mail ja foi cadastrado!";
                $erro = 'sim';
            }}

            if ($telefone != '') { if ($telefone == $linha['telefone']) {
                $_SESSION['avisar'] = "Esse telefone ja foi cadastrado!";
                $erro = 'sim';
            }}
        }

        if ($erro == 'nao') {
            $inserir = "INSERT INTO registro(nome, apelido, cpf, estado, cidade, sexo, cnpj, email, telefone, senha, servicos_ok, data_ani, funcao, descricao, atualizar)
            values ('$nome', '$apelido', '$cpf', '$estado', ' ', '$genero', '0', '$email', '$telefone', '$senha', '0', '$data', '3', ' ', '0')";
            $resultado = mysqli_query($conn, $inserir);
            
            if(mysqli_insert_id($conn)){
                $_SESSION['avisar'] = "Cadastro bem sucedido, basta fazer login agora!";
                audit_log($conn, 'criar', 'registro', mysqli_insert_id($conn), 'Cadastro de usuario');
            }else{
                $_SESSION['avisar'] = "Cadastro falhou ERRO_ID_INSERT_X";
            }
            
            mysqli_close($conn);
        }
    } else {
        $_SESSION['avisar'] = "Cadastro falhou POST_NOT_FOUND_X";
    }
    echo $_SESSION['avisar'];
    header('Location: index.php');
    ?>

<?php

?>