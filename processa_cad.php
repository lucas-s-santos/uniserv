<?php
    include_once "includes/bootstrap.php";

    function normalizar_cpf_para_formato($cpf) {
        $digits = preg_replace('/\D+/', '', (string)$cpf);
        if (strlen($digits) !== 11) {
            return '';
        }
        return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
    }

    function cpf_valido($cpf_formatado) {
        $cpf = preg_replace('/\D+/', '', (string)$cpf_formatado);
        if (strlen($cpf) !== 11) {
            return false;
        }
        if (preg_match('/^(\\d)\\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int)$cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int)$cpf[$c] !== $d) {
                return false;
            }
        }
        return true;
    }

    function normalizar_telefone($telefone) {
        $digits = preg_replace('/\D+/', '', (string)$telefone);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) === 11) {
            return '(' . substr($digits, 0, 2) . ')' . substr($digits, 2, 1) . ' ' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
        }
        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6, 4);
        }
        return '';
    }

    function redirecionar_erro_cadastro($mensagem, $dados = []) {
        $_SESSION['avisar'] = $mensagem;
        if (!empty($dados)) {
            $_SESSION['old_cadastro'] = $dados;
        }
        header('Location: cadastro.php');
        exit;
    }

    if (!isset($_POST['csrf_token'])) {
        redirecionar_erro_cadastro("Nao foi possivel concluir o cadastro. Tente novamente.");
    }
    $csrf_recebido = (string)$_POST['csrf_token'];
    $csrf_sessao = isset($_SESSION['csrf_cadastro']) ? (string)$_SESSION['csrf_cadastro'] : '';
    unset($_SESSION['csrf_cadastro']);
    if ($csrf_sessao === '' || !hash_equals($csrf_sessao, $csrf_recebido)) {
        redirecionar_erro_cadastro("Sessao expirada. Recarregue a pagina e tente novamente.");
    }

    if (!isset($_POST['nome'], $_POST['apelido'], $_POST['estado'], $_POST['cpf'], $_POST['cidade'], $_POST['senha'], $_POST['confsenha'], $_POST['data_ani'], $_POST['genero'])) {
        redirecionar_erro_cadastro("Nao foi possivel concluir o cadastro. Tente novamente.");
    }

    $nome = trim((string)$_POST['nome']);
    $apelido = trim((string)$_POST['apelido']);
    $estado = trim((string)$_POST['estado']);
    $cpf_bruto = trim((string)$_POST['cpf']);
    $cidade = trim((string)$_POST['cidade']);
    $telefone_bruto = trim((string)$_POST['telefone']);
    $email = strtolower(trim((string)$_POST['email']));
    $senha = (string)$_POST['senha'];
    $confsenha = (string)$_POST['confsenha'];
    $genero = trim((string)$_POST['genero']);
    $data = trim((string)$_POST['data_ani']);

    $dados_old = [
        'nome' => $nome,
        'apelido' => $apelido,
        'estado' => $estado,
        'cpf' => $cpf_bruto,
        'cidade' => $cidade,
        'telefone' => $telefone_bruto,
        'email' => $email,
        'genero' => $genero,
        'data_ani' => $data
    ];

    $cpf = normalizar_cpf_para_formato($cpf_bruto);
    $cpf_digits = preg_replace('/\D+/', '', $cpf_bruto);
    if ($cpf === '' || !cpf_valido($cpf)) {
        redirecionar_erro_cadastro("CPF invalido.", $dados_old);
    }

    if ($nome === '' || strlen($nome) < 3) {
        redirecionar_erro_cadastro("Informe um nome valido.", $dados_old);
    }
    if ($apelido === '' || strlen($apelido) < 2) {
        redirecionar_erro_cadastro("Informe um apelido valido.", $dados_old);
    }
    if ($estado === '' || $cidade === '') {
        redirecionar_erro_cadastro("Estado e cidade sao obrigatorios.", $dados_old);
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirecionar_erro_cadastro("Informe um e-mail valido.", $dados_old);
    }

    $telefone = normalizar_telefone($telefone_bruto);
    $telefone_digits = preg_replace('/\D+/', '', (string)$telefone_bruto);
    if ($telefone_bruto !== '' && $telefone === '') {
        redirecionar_erro_cadastro("Telefone invalido. Use DDD + numero.", $dados_old);
    }

    $generos_validos = ['', 'M', 'F', 'O', 'P'];
    if (!in_array($genero, $generos_validos, true)) {
        redirecionar_erro_cadastro("Genero invalido.", $dados_old);
    }

    $data_obj = DateTime::createFromFormat('Y-m-d', $data);
    if (!$data_obj || $data_obj->format('Y-m-d') !== $data) {
        redirecionar_erro_cadastro("Data de nascimento invalida.", $dados_old);
    }
    $hoje = new DateTime('today');
    if ($data_obj > $hoje) {
        redirecionar_erro_cadastro("Data de nascimento nao pode ser futura.", $dados_old);
    }
    $idade = (int)$hoje->diff($data_obj)->y;
    if ($idade < 13) {
        redirecionar_erro_cadastro("Cadastro permitido apenas para maiores de 13 anos.", $dados_old);
    }

    if (strlen($senha) < 8) {
        redirecionar_erro_cadastro("Senha deve ter no minimo 8 caracteres.", $dados_old);
    }
    if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
        redirecionar_erro_cadastro("Senha precisa ter pelo menos uma letra e um numero.", $dados_old);
    }
    if ($senha !== $confsenha) {
        redirecionar_erro_cadastro("As senhas nao conferem.", $dados_old);
    }

    $latitude = null;
    $longitude = null;
    if (isset($_POST['latitude'], $_POST['longitude'])) {
        $lat_raw = str_replace(',', '.', trim((string)$_POST['latitude']));
        $lng_raw = str_replace(',', '.', trim((string)$_POST['longitude']));
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

    $stmt = $conn->prepare("SELECT 1 FROM registro WHERE cpf = ? OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = ? LIMIT 1");
    $stmt->bind_param("ss", $cpf, $cpf_digits);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        redirecionar_erro_cadastro("Esse CPF ja foi cadastrado.", $dados_old);
    }
    $stmt->close();

    if ($email !== '') {
        $stmt = $conn->prepare("SELECT 1 FROM registro WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            redirecionar_erro_cadastro("Esse e-mail ja foi cadastrado.", $dados_old);
        }
        $stmt->close();
    }

    if ($telefone !== '') {
        $stmt = $conn->prepare("SELECT 1 FROM registro
            WHERE telefone = ?
               OR REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), ' ', ''), '-', '') = ?
            LIMIT 1");
        $stmt->bind_param("ss", $telefone, $telefone_digits);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            redirecionar_erro_cadastro("Esse telefone ja foi cadastrado.", $dados_old);
        }
        $stmt->close();
    }

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

    if (!$novo_id) {
        redirecionar_erro_cadastro("Nao foi possivel concluir o cadastro. Tente novamente.", $dados_old);
    }

    unset($_SESSION['old_cadastro']);
    $_SESSION['avisar'] = "Cadastro realizado com sucesso. Faça login para continuar.";
    audit_log($conn, 'criar', 'registro', $novo_id, 'Cadastro de usuario');
    mysqli_close($conn);
    header('Location: login.php');
    exit;
?>
