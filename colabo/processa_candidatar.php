<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([2], '../login.php', 'Acesso restrito para colaboradores.');

    if (isset($_POST['acao_cand'])) {
        $id_colaborador = (int)$_POST['acao_cand'];
        $funcao = (int)$_POST['funcao_servico'];
        $valor = trim($_POST['valor_hora']);
        $imagem = null;
        $foto_invalida = false;

        if (isset($_FILES['certificado']) && $_FILES['certificado']['error'] !== UPLOAD_ERR_NO_FILE) {
            $arquivo = $_FILES['certificado'];
            if ($arquivo['error'] === UPLOAD_ERR_OK) {
                $tamanho_max = 2 * 1024 * 1024;
                $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png'];
                $info_img = @getimagesize($arquivo['tmp_name']);
                if ($arquivo['size'] <= $tamanho_max && $info_img && in_array($ext, $permitidas, true)) {
                    $hash_nome = hash('sha256', $id_colaborador . '_' . microtime(true));
                    $novo_nome = 'cert_' . $hash_nome . '.' . $ext;
                    $destino = __DIR__ . '/../image/certificados/' . $novo_nome;
                    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
                        $imagem = 'image/certificados/' . $novo_nome;
                    }
                } else {
                    $foto_invalida = true;
                }
            }
        }

        if ($id_colaborador !== (int)$_SESSION['id_acesso'] || $funcao <= 0) {
            $_SESSION['avisar'] = "Requisicao invalida.";
            header('location: ../index.php');
            exit;
        }

        $stmt = $conn->prepare("SELECT 1 FROM funcoes WHERE id_funcoes = ? LIMIT 1");
        $stmt->bind_param("i", $funcao);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $_SESSION['avisar'] = "Funcao invalida.";
            header('location: ../index.php');
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT 1 FROM trabalhador_funcoes WHERE funcoes_id_funcoes = ? AND registro_id_registro = ? LIMIT 1");
        $stmt->bind_param("ii", $funcao, $id_colaborador);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $_SESSION['avisar'] = "Voce ja se candidatou a esse servico.";
            header('location: ../index.php');
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO trabalhador_funcoes(funcoes_id_funcoes, registro_id_registro, certificado, valor_hora, disponivel)
            VALUES (?, ?, ?, ?, '0')");
        $stmt->bind_param("iiss", $funcao, $id_colaborador, $imagem, $valor);
        $stmt->execute();
        $novo_id = $stmt->insert_id;
        $stmt->close();

        if ($novo_id) {
            $_SESSION['avisar'] = "Sucesso! O serviço foi atribuido<br>como opção para você!";
        } else {
            $_SESSION['avisar'] = "Função não estabelecida ERRO_INSERT_ID_X";
        }
        if ($foto_invalida) {
            $_SESSION['avisar'] = "Certificado invalido. Use JPG ou PNG ate 2MB.";
        }
    } else {
        $_SESSION['avisar'] = "Função não estabelecida ERRO_CHECK_POST_X";
    }
    mysqli_close($conn);
    header('location: ../index.php');
?>