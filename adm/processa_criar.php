<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Acesso restrito para administradores.');
    require_role([1], '../login.php', 'Acesso restrito para administradores.');

    $nome = isset($_POST['nome_func']) ? trim((string)$_POST['nome_func']) : '';
    $categoria = isset($_POST['categoria']) ? trim((string)$_POST['categoria']) : '';
    $valor_base_raw = isset($_POST['valor_base']) ? trim((string)$_POST['valor_base']) : '';
    $valor_base_raw = str_replace(',', '.', $valor_base_raw);
    $valor_base = $valor_base_raw !== '' ? (float)$valor_base_raw : null;
    $duracao_estimada = isset($_POST['duracao_estimada']) ? (int)$_POST['duracao_estimada'] : 0;
    $duracao_estimada_db = $duracao_estimada > 0 ? $duracao_estimada : null;
    $descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';
    $motivo_acao = isset($_POST['motivo_acao']) ? trim((string)$_POST['motivo_acao']) : '';
    $imagem = null;

    if ($nome === '' || $categoria === '' || $descricao === '') {
        $_SESSION['avisar'] = "Preencha nome, categoria e descricao do servico.";
        $_SESSION['avisar_tipo'] = "warn";
        header("Location: ../administrador.php");
        exit;
    }
    if ($valor_base_raw !== '' && !is_numeric($valor_base_raw)) {
        $_SESSION['avisar'] = "Valor base invalido.";
        $_SESSION['avisar_tipo'] = "warn";
        header("Location: ../administrador.php");
        exit;
    }
    if ($valor_base !== null && $valor_base < 0) {
        $_SESSION['avisar'] = "Valor base invalido.";
        $_SESSION['avisar_tipo'] = "warn";
        header("Location: ../administrador.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT id_funcoes FROM funcoes WHERE nome_func = ? AND categoria = ? LIMIT 1");
    $stmt->bind_param("ss", $nome, $categoria);
    $stmt->execute();
    $ja_existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($ja_existe) {
        $_SESSION['avisar'] = "Ja existe um servico com esse nome e categoria.";
        $_SESSION['avisar_tipo'] = "warn";
        header("Location: ../administrador.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO funcoes(nome_func, categoria, valor_base, duracao_estimada, descricao, imagem)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiss", $nome, $categoria, $valor_base, $duracao_estimada_db, $descricao, $imagem);
    $stmt->execute();
    $novo_id = $stmt->insert_id;
    $stmt->close();

    if ($novo_id) {
        $_SESSION['avisar'] = "Funcao criada com sucesso.";
        $_SESSION['avisar_tipo'] = "success";
        $detalhes = "Nome: $nome";
        if ($motivo_acao !== '') {
            $detalhes .= ". Motivo: $motivo_acao";
        }
        audit_log($conn, 'criar', 'funcoes', $novo_id, $detalhes);
    } else {
        $_SESSION['avisar'] = "Erro ao criar funcao.";
        $_SESSION['avisar_tipo'] = "error";
    }
    mysqli_close($conn);
    header("Location: ../administrador.php");
?>
