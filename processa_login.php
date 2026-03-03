<?php
    include_once "includes/bootstrap.php";

    function normalizar_cpf_para_formato($cpf) {
        $digits = preg_replace('/\D+/', '', (string)$cpf);
        if (strlen($digits) !== 11) {
            return '';
        }
        return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
    }

    function redirecionar_falha_login($mensagem, $cpfParaRetorno = '') {
        if ($cpfParaRetorno !== '') {
            $_SESSION['old_login_cpf'] = $cpfParaRetorno;
        }
        $_SESSION['avisar'] = $mensagem;
        header("Location: login.php");
        exit;
    }

    if (!isset($_SESSION['login_guard']) || !is_array($_SESSION['login_guard'])) {
        $_SESSION['login_guard'] = [
            'attempts' => 0,
            'locked_until' => 0
        ];
    }
    $login_guard = &$_SESSION['login_guard'];
    $max_tentativas = 5;
    $tempo_bloqueio = 300;
    $agora = time();

    if ((int)$login_guard['locked_until'] > $agora) {
        $segundos_restantes = (int)$login_guard['locked_until'] - $agora;
        $minutos = (int)ceil($segundos_restantes / 60);
        redirecionar_falha_login("Muitas tentativas. Aguarde aproximadamente {$minutos} minuto(s) e tente novamente.");
    }

    if (!isset($_POST['cpf_login'], $_POST['senha_login'], $_POST['csrf_token'])) {
        redirecionar_falha_login("Nao foi possivel concluir o login. Tente novamente.");
    }

    $csrf_recebido = (string)$_POST['csrf_token'];
    $csrf_sessao = isset($_SESSION['csrf_login']) ? (string)$_SESSION['csrf_login'] : '';
    unset($_SESSION['csrf_login']);
    if ($csrf_sessao === '' || !hash_equals($csrf_sessao, $csrf_recebido)) {
        redirecionar_falha_login("Sessao expirada. Recarregue a pagina e tente novamente.");
    }

    $cpf_bruto = trim((string)$_POST['cpf_login']);
    $senha_login = (string)$_POST['senha_login'];
    $_SESSION['old_login_cpf'] = $cpf_bruto;

    $cpf_digits = preg_replace('/\D+/', '', $cpf_bruto);
    $cpf_formatado = normalizar_cpf_para_formato($cpf_bruto);
    if ($cpf_formatado === '' || trim($senha_login) === '') {
        redirecionar_falha_login("CPF ou senha invalidos.", $cpf_bruto);
    }

    $stmt = $conn->prepare("SELECT id_registro, apelido, cpf, funcao, senha
        FROM registro
        WHERE cpf = ? OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?
        LIMIT 1");
    $stmt->bind_param("ss", $cpf_formatado, $cpf_digits);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $senha_valida = false;
    if ($resultado) {
        $senha_hash = (string)$resultado['senha'];
        $senha_valida = password_verify($senha_login, $senha_hash);

        if (!$senha_valida && $senha_hash === $senha_login) {
            $senha_valida = true;
            $novo_hash = password_hash($senha_login, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE registro SET senha = ? WHERE id_registro = ? LIMIT 1");
            $stmt->bind_param("si", $novo_hash, $resultado['id_registro']);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (!$resultado || !$senha_valida) {
        $login_guard['attempts'] = (int)$login_guard['attempts'] + 1;
        if ((int)$login_guard['attempts'] >= $max_tentativas) {
            $login_guard['attempts'] = 0;
            $login_guard['locked_until'] = $agora + $tempo_bloqueio;
            redirecionar_falha_login("Muitas tentativas. Aguarde 5 minutos para tentar novamente.", $cpf_bruto);
        }
        $restantes = $max_tentativas - (int)$login_guard['attempts'];
        redirecionar_falha_login("CPF ou senha invalidos. Tentativas restantes: {$restantes}.", $cpf_bruto);
    }

    $login_guard['attempts'] = 0;
    $login_guard['locked_until'] = 0;
    unset($_SESSION['old_login_cpf']);

    session_regenerate_id(true);

    $stmt = $conn->prepare("UPDATE registro SET atualizar = '0' WHERE id_registro = ? LIMIT 1");
    $stmt->bind_param("i", $resultado['id_registro']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['atualizar'] = "nao";

    $_SESSION['apelido'] = $resultado['apelido'];
    $_SESSION['cpf'] = $resultado['cpf'];
    $_SESSION['funcao'] = $resultado['funcao'];
    $_SESSION['id_acesso'] = $resultado['id_registro'];
    audit_log($conn, 'login', 'registro', (int)$resultado['id_registro'], 'Login realizado');

    mysqli_close($conn);
    header("Location: index.php");
    exit;
?>
