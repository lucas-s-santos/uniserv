<?php
    include_once "../includes/bootstrap.php";

    $acao = "nada";
    $acao = $_POST['acao'];

    if ($acao <> "nada") {
        $telefone = " ";
        $email = " ";
        $id = (int)$acao;
        $nome = $_POST['nome'];
        $apelido = $_POST['apelido'];
        $email = $_POST['email'];
        $cidade = $_POST['cidade'];
        $estado = $_POST['estado'];
        $telefone = $_POST['telefone'];
        $genero = $_POST['genero'];
        $senha_nova = $_POST['senhanova'];
        $senha_atual = $_POST['senha'];
        $foto_path = null;
        $foto_invalida = false;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $arquivo = $_FILES['foto'];
            if ($arquivo['error'] === UPLOAD_ERR_OK) {
                $tamanho_max = 2 * 1024 * 1024;
                $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png'];
                $info_img = @getimagesize($arquivo['tmp_name']);
                if ($arquivo['size'] <= $tamanho_max && $info_img && in_array($ext, $permitidas, true)) {
                    $novo_nome = 'usuario_' . $id . '_' . date('Ymd_His') . '.' . $ext;
                    $destino = __DIR__ . '/../image/perfil/' . $novo_nome;
                    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
                        $foto_path = 'image/perfil/' . $novo_nome;
                    }
                } else {
                    $foto_invalida = true;
                }
            }
        }

        $stmt = $conn->prepare("SELECT senha FROM registro WHERE id_registro = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$resultado) {
            $_SESSION['avisar'] = "Usuario nao encontrado.";
            header("Location: ../perfil.php");
            exit;
        }

        $senha_hash = $resultado['senha'];
        $senha_valida = password_verify($senha_atual, $senha_hash);
        if (!$senha_valida && $senha_hash === $senha_atual) {
            $senha_valida = true;
        }

        if (!$senha_valida) {
            $_SESSION['avisar'] = "Senha atual incorreta.";
            header("Location: ../perfil.php");
            exit;
        }

        if ($senha_nova <> "") {
            $novo_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
            if ($foto_path) {
                $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, telefone=?, email=?, senha=?, sexo=?, foto=?, atualizar='1' WHERE id_registro=?");
                $stmt->bind_param("sssssssssi", $nome, $apelido, $estado, $cidade, $telefone, $email, $novo_hash, $genero, $foto_path, $id);
            } else {
                $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, telefone=?, email=?, senha=?, sexo=?, atualizar='1' WHERE id_registro=?");
                $stmt->bind_param("ssssssssi", $nome, $apelido, $estado, $cidade, $telefone, $email, $novo_hash, $genero, $id);
            }
        } else {
            if ($senha_hash === $senha_atual) {
                $novo_hash = password_hash($senha_atual, PASSWORD_DEFAULT);
                if ($foto_path) {
                    $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, telefone=?, email=?, senha=?, sexo=?, foto=?, atualizar='1' WHERE id_registro=?");
                    $stmt->bind_param("sssssssssi", $nome, $apelido, $estado, $cidade, $telefone, $email, $novo_hash, $genero, $foto_path, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, telefone=?, email=?, senha=?, sexo=?, atualizar='1' WHERE id_registro=?");
                    $stmt->bind_param("ssssssssi", $nome, $apelido, $estado, $cidade, $telefone, $email, $novo_hash, $genero, $id);
                }
            } else {
                if ($foto_path) {
                    $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, telefone=?, email=?, sexo=?, foto=?, atualizar='1' WHERE id_registro=?");
                    $stmt->bind_param("ssssssssi", $nome, $apelido, $estado, $cidade, $telefone, $email, $genero, $foto_path, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE registro SET nome=?, apelido=?, estado=?, cidade=?, telefone=?, email=?, sexo=?, atualizar='1' WHERE id_registro=?");
                    $stmt->bind_param("sssssssi", $nome, $apelido, $estado, $cidade, $telefone, $email, $genero, $id);
                }
            }
        }

        $stmt->execute();
        $stmt->close();
        audit_log($conn, 'editar', 'registro', $id, 'Usuario atualizou perfil');

        if ($foto_invalida) {
            $_SESSION['avisar'] = 'Foto invalida. Use JPG ou PNG ate 2MB.';
        }

        unset($_SESSION['id_adm']);
    }

    header("Location: ../index.php");
?>