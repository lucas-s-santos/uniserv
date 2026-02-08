<?php
include_once "includes/bootstrap.php";
include_once "includes/auth.php";
require_login('login.php', 'Voce precisa estar logado para acessar esta area.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_notificacao'])) {
        $id_notificacao = (int)$_POST['id_notificacao'];
        $stmt = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id_notificacao = ? AND registro_id_registro = ?");
        $stmt->bind_param("ii", $id_notificacao, $_SESSION['id_acesso']);
        $stmt->execute();
        $stmt->close();
        header('Location: notificacoes.php');
        exit;
    }
    if (isset($_POST['marcar_todas'])) {
        $stmt = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE registro_id_registro = ? AND lida = 0");
        $stmt->bind_param("i", $_SESSION['id_acesso']);
        $stmt->execute();
        $stmt->close();
        header('Location: notificacoes.php');
        exit;
    }
}

$stmt = $conn->prepare("SELECT id_notificacao, mensagem, lida, link, data_criacao FROM notificacoes WHERE registro_id_registro = ? ORDER BY data_criacao DESC LIMIT 50");
$stmt->bind_param("i", $_SESSION['id_acesso']);
$stmt->execute();
$notificacoes = $stmt->get_result();
$stmt->close();

$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
$themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
    <meta name="keywords" content="HTML, CSS">
    <meta name="description" content="Notificacoes">
    <link rel="stylesheet" href="css/estrutura_geral.css">
    <title>Notificacoes</title>
</head>
<body class="centralizar <?php echo $themeClass; ?>">
    <?php include 'menu.php'; ?>
    <div class="menu-spacer"></div>
    <main class="page">
        <div class="title">Notificacoes</div>
        <form method="POST" class="button-group">
            <input type="hidden" name="marcar_todas" value="1">
            <button type="submit">Marcar todas como lidas</button>
        </form>
        <?php
            $tem = false;
            while ($linha = $notificacoes->fetch_assoc()) {
                $tem = true;
                $mensagem = htmlspecialchars($linha['mensagem'], ENT_QUOTES, 'UTF-8');
                $link = $linha['link'] ? htmlspecialchars($linha['link'], ENT_QUOTES, 'UTF-8') : '';
                $data = $linha['data_criacao'] ? date('d/m/Y H:i', strtotime($linha['data_criacao'])) : '';
                $badge = $linha['lida'] ? 'Lida' : 'Nova';
                echo "<div class='service-card'>
                    <div class='service-card__header'>
                        <div class='service-card__title'>{$mensagem}</div>
                        <span class='status-badge'>{$badge}</span>
                    </div>
                    <div class='service-card__meta'>
                        <span>{$data}</span>
                    </div>";
                if ($link !== '') {
                    echo "<div class='button-group'><a class='btn btn-ghost' href='{$link}'>Abrir</a></div>";
                }
                if (!$linha['lida']) {
                    echo "<form method='POST' class='button-group'>
                        <input type='hidden' name='id_notificacao' value='{$linha['id_notificacao']}'>
                        <button type='submit'>Marcar como lida</button>
                    </form>";
                }
                echo "</div>";
            }
            if (!$tem) {
                echo "<div class='texto'>Sem notificacoes no momento.</div>";
            }
        ?>
    </main>
</body>
</html>
