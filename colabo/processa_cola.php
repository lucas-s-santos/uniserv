<?php
    include_once "../includes/bootstrap.php";
    include_once "../includes/auth.php";
    require_login('../login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([3], '../login.php', 'Acesso apenas para clientes interessados em colaborar.');

    $id = isset($_POST['id_pessoal']) ? (int)$_POST['id_pessoal'] : 0;
    if ($id <= 0 || $id !== (int)$_SESSION['id_acesso']) {
        $_SESSION['avisar'] = "ID invalido.";
        header("Location: ../index.php");
        exit;
    }

    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
    $cnpj = isset($_POST['cnpj']) ? trim($_POST['cnpj']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $pix_tipo = isset($_POST['pix_tipo']) ? trim($_POST['pix_tipo']) : '';
    $pix_chave = isset($_POST['pix_chave']) ? trim($_POST['pix_chave']) : '';
        $pix_digits = preg_replace('/\D+/', '', $pix_chave);
        if ($pix_chave !== '') {
            $pix_valido = false;
            if ($pix_tipo === 'cpf') {
                $pix_valido = strlen($pix_digits) === 11;
            } elseif ($pix_tipo === 'telefone') {
                $pix_valido = strlen($pix_digits) >= 10 && strlen($pix_digits) <= 15;
            } elseif ($pix_tipo === 'email') {
                $pix_valido = filter_var($pix_chave, FILTER_VALIDATE_EMAIL) !== false;
            } elseif ($pix_tipo === 'aleatoria') {
                $pix_valido = preg_match('/^[A-Za-z0-9\-]{32,36}$/', $pix_chave) === 1;
            }
            if (!$pix_valido) {
                $_SESSION['avisar'] = 'Chave PIX invalida para o tipo selecionado.';
                $_SESSION['avisar_tipo'] = 'error';
                header('Location: cadastro_colaborador.php');
                exit;
            }
        }
    $latitude = null;
    $longitude = null;
    $has_coords = false;
    if (isset($_POST['latitude'], $_POST['longitude'])) {
        $lat_raw = str_replace(',', '.', trim($_POST['latitude']));
        $lng_raw = str_replace(',', '.', trim($_POST['longitude']));
        if ($lat_raw !== '' && $lng_raw !== '' && is_numeric($lat_raw) && is_numeric($lng_raw)) {
            $latitude = (float)$lat_raw;
            $longitude = (float)$lng_raw;
            $has_coords = true;
        }
    }
    if (!$has_coords) {
        $geo = geocode_nominatim($cidade . ', Brasil');
        if ($geo) {
            $latitude = $geo['lat'];
            $longitude = $geo['lng'];
            $has_coords = true;
        }
    }

    $stmt = $conn->prepare("UPDATE registro SET email=?, telefone=?, cidade=?, cnpj=?, descricao=?, pix_tipo=?, pix_chave=?, funcao='2', atualizar='1' WHERE id_registro=?");
    $stmt->bind_param("sssssssi", $email, $telefone, $cidade, $cnpj, $descricao, $pix_tipo, $pix_chave, $id);
    $stmt->execute();
    $stmt->close();

    if ($has_coords) {
        $stmt = $conn->prepare("UPDATE registro SET latitude=?, longitude=? WHERE id_registro=?");
        $stmt->bind_param("ddi", $latitude, $longitude, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ../index.php");
?>