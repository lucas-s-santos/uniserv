<?php
    include_once "includes/bootstrap.php";

    if (isset($_POST['nome'])) {
        $telefone = "";
        $email = "";
        $nome = trim($_POST['nome']);
        $apelido = trim($_POST['apelido']);
        $estado = trim($_POST['estado']);
        $cpf = trim($_POST['cpf']);
        $cidade = trim($_POST['cidade']);
        $telefone = trim($_POST['telefone']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];
        $genero = trim($_POST['genero']);
        $data = trim($_POST['data_ani']);
        $latitude = null;
        $longitude = null;
        if (isset($_POST['latitude'], $_POST['longitude'])) {
            $lat_raw = str_replace(',', '.', trim($_POST['latitude']));
            $lng_raw = str_replace(',', '.', trim($_POST['longitude']));
            if ($lat_raw !== '' && $lng_raw !== '' && is_numeric($lat_raw) && is_numeric($lng_raw)) {
                $latitude = (float)$lat_raw;
                $longitude = (float)$lng_raw;
            }
        }
        if ($latitude === null || $longitude === null) {
            $geo = geocode_nominatim($cidade . ', ' . $estado . ', Brasil');
            if ($geo) {
                $latitude = $geo['lat'];
                $longitude = $geo['lng'];
            }
        }

        $erro = 'nao';

        $stmt = $conn->prepare("SELECT 1 FROM registro WHERE cpf = ? LIMIT 1");
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['avisar'] = "Esse cpf ja foi cadastrado!";
            $erro = 'sim';
        }
        $stmt->close();

        if ($erro == 'nao' && $email !== '') {
            $stmt = $conn->prepare("SELECT 1 FROM registro WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $_SESSION['avisar'] = "Esse e-mail ja foi cadastrado!";
                $erro = 'sim';
            }
            $stmt->close();
        }

        if ($erro == 'nao' && $telefone !== '') {
            $stmt = $conn->prepare("SELECT 1 FROM registro WHERE telefone = ? LIMIT 1");
            $stmt->bind_param("s", $telefone);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $_SESSION['avisar'] = "Esse telefone ja foi cadastrado!";
                $erro = 'sim';
            }
            $stmt->close();
        }

        if ($erro == 'nao') {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $cnpj = '0';
            $servicos_ok = '0';
            $funcao = '3';
            $descricao = ' ';
            $atualizar = '0';

            if ($latitude !== null && $longitude !== null) {
                $stmt = $conn->prepare("INSERT INTO registro(nome, apelido, cpf, estado, cidade, latitude, longitude, sexo, cnpj, email, telefone, senha, servicos_ok, data_ani, funcao, descricao, atualizar)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "sssssddssssssssss",
                    $nome,
                    $apelido,
                    $cpf,
                    $estado,
                    $cidade,
                    $latitude,
                    $longitude,
                    $genero,
                    $cnpj,
                    $email,
                    $telefone,
                    $senha_hash,
                    $servicos_ok,
                    $data,
                    $funcao,
                    $descricao,
                    $atualizar
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO registro(nome, apelido, cpf, estado, cidade, sexo, cnpj, email, telefone, senha, servicos_ok, data_ani, funcao, descricao, atualizar)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "sssssssssssssss",
                    $nome,
                    $apelido,
                    $cpf,
                    $estado,
                    $cidade,
                    $genero,
                    $cnpj,
                    $email,
                    $telefone,
                    $senha_hash,
                    $servicos_ok,
                    $data,
                    $funcao,
                    $descricao,
                    $atualizar
                );
            }
            $stmt->execute();
            $novo_id = $stmt->insert_id;
            $stmt->close();

            if ($novo_id) {
                $_SESSION['avisar'] = "Cadastro bem sucedido, basta fazer login agora!";
                audit_log($conn, 'criar', 'registro', $novo_id, 'Cadastro de usuario');
            } else {
                $_SESSION['avisar'] = "Cadastro falhou ERRO_ID_INSERT_X";
            }
            mysqli_close($conn);
        }
    } else {
        $_SESSION['avisar'] = "Cadastro falhou POST_NOT_FOUND_X";
    }
    header('Location: index.php');
?>