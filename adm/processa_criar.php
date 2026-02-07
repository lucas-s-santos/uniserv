<?php
    session_start();
    if (!isset($_SESSION['cpf']) || (int)$_SESSION['funcao'] !== 1) {
        $_SESSION['avisar'] = "Acesso restrito para administradores.";
        header('location: ../login.php');
        exit;
    }
    include_once("../conexao.php");
    include_once("../audit.php");
?>
<?php
    $nome = mysqli_real_escape_string($conn, $_POST['nome_func']);
    $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
    $valor_base = str_replace(',', '.', $_POST['valor_base']);
    $valor_base = $valor_base !== '' ? (float)$valor_base : null;
    $duracao_estimada = $_POST['duracao_estimada'] !== '' ? (int)$_POST['duracao_estimada'] : null;
    $descricao = mysqli_real_escape_string($conn, $_POST['descricao']);
    $imagem = null;

    $valor_sql = $valor_base !== null ? "'" . $valor_base . "'" : "NULL";
    $duracao_sql = $duracao_estimada !== null ? "'" . $duracao_estimada . "'" : "NULL";
    $imagem_sql = $imagem !== null ? "'" . $imagem . "'" : "NULL";

    $inserir = "INSERT INTO funcoes(nome_func, categoria, valor_base, duracao_estimada, descricao, imagem)
     values ('$nome', '$categoria', $valor_sql, $duracao_sql, '$descricao', $imagem_sql)";
    $resultado = mysqli_query($conn, $inserir);
    if(mysqli_insert_id($conn)){
        $_SESSION['avisar'] = "Funcao criada com sucesso.";
        audit_log($conn, 'criar', 'funcoes', mysqli_insert_id($conn), "Nome: $nome");
        header("Location: ../administrador.php");
    }else{
        $_SESSION['avisar'] = "Erro ao criar funcao.";
        header("Location: ../administrador.php");
    }
    mysqli_close($conn);
?>