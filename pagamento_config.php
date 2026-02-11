<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";

    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    require_login('login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([2], 'login.php', 'Acesso restrito para colaboradores.');

    function pix_only_digits($value) {
        return preg_replace('/\D+/', '', (string)$value);
    }

    function pix_validate_key($tipo, $chave) {
        $tipo = trim((string)$tipo);
        $chave = trim((string)$chave);
        if ($chave === '') {
            return true;
        }
        if ($tipo === 'cpf') {
            return strlen(pix_only_digits($chave)) === 11;
        }
        if ($tipo === 'telefone') {
            $digits = pix_only_digits($chave);
            return strlen($digits) >= 10 && strlen($digits) <= 15;
        }
        if ($tipo === 'email') {
            return filter_var($chave, FILTER_VALIDATE_EMAIL) !== false;
        }
        if ($tipo === 'aleatoria') {
            return preg_match('/^[A-Za-z0-9\-]{32,36}$/', $chave) === 1;
        }
        return false;
    }

    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pix_tipo = isset($_POST['pix_tipo']) ? trim($_POST['pix_tipo']) : '';
        $pix_chave = isset($_POST['pix_chave']) ? trim($_POST['pix_chave']) : '';
        $aceita_pix = isset($_POST['aceita_pix']) ? 1 : 0;
        $aceita_dinheiro = isset($_POST['aceita_dinheiro']) ? 1 : 0;
        $aceita_cartao_presencial = isset($_POST['aceita_cartao_presencial']) ? 1 : 0;
        $pagamento_preferido = isset($_POST['pagamento_preferido']) ? trim($_POST['pagamento_preferido']) : '';
        $mensagem_pagamento = isset($_POST['mensagem_pagamento']) ? trim($_POST['mensagem_pagamento']) : '';
        if (strlen($mensagem_pagamento) > 255) {
            $mensagem_pagamento = substr($mensagem_pagamento, 0, 255);
        }

        if ($pix_chave !== '' && $pix_tipo === '') {
            $errors[] = 'Selecione o tipo da chave PIX.';
        }
        if ($pix_tipo !== '' && !pix_validate_key($pix_tipo, $pix_chave)) {
            $errors[] = 'Chave PIX invalida para o tipo selecionado.';
        }
        if ($aceita_pix === 1 && $pix_chave === '') {
            $errors[] = 'Para aceitar PIX, informe a chave.';
        }
        if ($pagamento_preferido !== '' && !in_array($pagamento_preferido, ['pix', 'dinheiro', 'cartao', 'qualquer'], true)) {
            $pagamento_preferido = '';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE registro SET pix_tipo=?, pix_chave=?, aceita_pix=?, aceita_dinheiro=?, aceita_cartao_presencial=?, pagamento_preferido=?, mensagem_pagamento=? WHERE id_registro=?");
            $stmt->bind_param("ssiiissi", $pix_tipo, $pix_chave, $aceita_pix, $aceita_dinheiro, $aceita_cartao_presencial, $pagamento_preferido, $mensagem_pagamento, $_SESSION['id_acesso']);
            $stmt->execute();
            $stmt->close();
            $_SESSION['avisar'] = 'Configuracoes de pagamento atualizadas.';
            $_SESSION['avisar_tipo'] = 'success';
            header('location: pagamento_config.php');
            exit;
        }
        $_SESSION['avisar'] = implode(' ', $errors);
        $_SESSION['avisar_tipo'] = 'error';
    }

    $stmt = $conn->prepare("SELECT pix_tipo, pix_chave, aceita_pix, aceita_dinheiro, aceita_cartao_presencial, pagamento_preferido, mensagem_pagamento FROM registro WHERE id_registro = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['id_acesso']);
    $stmt->execute();
    $dados = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $pix_tipo = $dados['pix_tipo'] ?? '';
    $pix_chave = $dados['pix_chave'] ?? '';
    $aceita_pix = isset($dados['aceita_pix']) ? (int)$dados['aceita_pix'] === 1 : true;
    $aceita_dinheiro = isset($dados['aceita_dinheiro']) ? (int)$dados['aceita_dinheiro'] === 1 : false;
    $aceita_cartao_presencial = isset($dados['aceita_cartao_presencial']) ? (int)$dados['aceita_cartao_presencial'] === 1 : false;
    $pagamento_preferido = $dados['pagamento_preferido'] ?? '';
    $mensagem_pagamento = $dados['mensagem_pagamento'] ?? '';

    include_once "all.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
        <meta name="keywords" content="HTML, CSS">
        <meta name="description" content="Configuracoes de pagamento">
        <link rel="stylesheet" href="css/estrutura_geral.css">
        <title>Pagamento</title>
    </head>
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
            <section class="page-header">
                <div>
                    <div class="page-kicker">Pagamento</div>
                    <h1 class="page-title">Configurar recebimento</h1>
                    <p class="page-subtitle">Defina como deseja receber e a mensagem para o cliente.</p>
                </div>
                <div class="page-actions">
                    <a class="btn btn-ghost" href="colabo/colaborador.php">Voltar</a>
                </div>
            </section>

            <div class="fonte">
                <div class="dentro">
                    <form method="POST" action="pagamento_config.php">
                        <div class="section-title">Metodos aceitos</div>
                        <label class="wizard-checkbox">
                            <input type="checkbox" name="aceita_pix" <?php echo $aceita_pix ? 'checked' : ''; ?>> Aceito PIX
                        </label>
                        <label class="wizard-checkbox" style="margin-left: 12px;">
                            <input type="checkbox" name="aceita_dinheiro" <?php echo $aceita_dinheiro ? 'checked' : ''; ?>> Aceito dinheiro
                        </label>
                        <label class="wizard-checkbox" style="margin-left: 12px;">
                            <input type="checkbox" name="aceita_cartao_presencial" <?php echo $aceita_cartao_presencial ? 'checked' : ''; ?>> Cartao presencial
                        </label>

                        <div class="campo-texto" style="margin-top: 12px;">
                            <label>Preferencia</label>
                            <select name="pagamento_preferido">
                                <option value="" <?php echo $pagamento_preferido === '' ? 'selected' : ''; ?>>Nao definir</option>
                                <option value="pix" <?php echo $pagamento_preferido === 'pix' ? 'selected' : ''; ?>>PIX</option>
                                <option value="dinheiro" <?php echo $pagamento_preferido === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                                <option value="cartao" <?php echo $pagamento_preferido === 'cartao' ? 'selected' : ''; ?>>Cartao presencial</option>
                                <option value="qualquer" <?php echo $pagamento_preferido === 'qualquer' ? 'selected' : ''; ?>>Qualquer</option>
                            </select>
                        </div>

                        <div class="section-title" style="margin-top: 12px;">PIX</div>
                        <div class="campo-texto">
                            <label>Tipo de PIX</label>
                            <select name="pix_tipo">
                                <option value="" <?php echo $pix_tipo === '' ? 'selected' : ''; ?>>Escolha</option>
                                <option value="cpf" <?php echo $pix_tipo === 'cpf' ? 'selected' : ''; ?>>CPF</option>
                                <option value="telefone" <?php echo $pix_tipo === 'telefone' ? 'selected' : ''; ?>>Telefone</option>
                                <option value="email" <?php echo $pix_tipo === 'email' ? 'selected' : ''; ?>>E-mail</option>
                                <option value="aleatoria" <?php echo $pix_tipo === 'aleatoria' ? 'selected' : ''; ?>>Chave aleatoria</option>
                            </select>
                        </div>
                        <div class="campo-texto">
                            <label>Chave PIX</label>
                            <input type="text" name="pix_chave" placeholder="Digite sua chave PIX" value="<?php echo htmlspecialchars($pix_chave, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="section-title" style="margin-top: 12px;">Mensagem para o cliente</div>
                        <div class="campo-texto">
                            <label>Mensagem (opcional)</label>
                            <input type="text" name="mensagem_pagamento" placeholder="Ex: Obrigado pelo servico!" value="<?php echo htmlspecialchars($mensagem_pagamento, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="button-group" style="margin-top: 12px;">
                            <button type="submit" class="btn btn-primary">Salvar configuracoes</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </body>
</html>
