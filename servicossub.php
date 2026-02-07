<?php
    session_start();
    include_once "conexao.php";
    include_once "status.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (isset($_SESSION['cpf'])) {
    } else {
        $_SESSION['avisar'] = "Você precisa estar logado no site!";
        header('location: index.php');
        
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <meta http-equiv="refresh" content="15">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Página principal</title>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <div class="tabela_adm">
            <table style="visibility: visible">
                <tr>
                    <td>Situação</td>
                    <td>Nome</td>
                    <td>Tipo de serviço</td>
                    <td>Valor por Hora</td>
                    <td>Endereço</td>
                </tr>
                <?php
                $comando_testar2 = "SELECT A.nome 'nome_trabalhador', B.nome_func 'funcao', C.endereco 'endereco',
                 C.valor_atual 'valor', C.ativo 'ativo', C.registro_id_registro 'id_cliente', C.id_trabalhador 'id_trabalhador', C.id_servico FROM servico C INNER JOIN registro A ON A.id_registro = C.id_trabalhador 
                INNER JOIN funcoes B ON B.id_funcoes = C.funcoes_id_funcoes WHERE ativo>0";
                $joga_no_banco = mysqli_query($conn, $comando_testar2);
                while ($linha54 = mysqli_fetch_array($joga_no_banco)) {
                    if ($linha54['id_cliente'] == $_SESSION['id_acesso'] || $linha54['id_trabalhador'] == $_SESSION['id_acesso']) {
                        $status_label = servico_status_label($linha54['ativo']);
                        $badge_class = servico_status_badge_class($linha54['ativo']);
                        echo "<tr>
                            <td><span class='status-badge $badge_class'>$status_label</span></td>
                            <td>$linha54[nome_trabalhador]</td>
                            <td>$linha54[funcao]</td>
                            <td>$linha54[valor]</td>
                            <td>$linha54[endereco]</td>
                        </tr>";
                        if ($linha54['id_trabalhador'] == $_SESSION['id_acesso']) {
                            echo "<tr><td colspan='5'><a href='servicos.php?servic=$linha54[id_servico]' target='_parent'>Finalizar serviço</a></td></tr>";
                        }
                    }
                }
                ?>
            </table>
        </div>
    </body>

</html>