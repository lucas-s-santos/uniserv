<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    require_login('login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([1, 3], 'login.php', 'Acesso restrito para clientes.');
    if (isset($_GET['ta'])) {
        $id_trafun = (int)$_GET['ta'];
    } else {
        $_SESSION['avisar'] = "Por favor, selecione novamente<br>quem deseja chamar";
        header("Location: index.php");
        exit;
    }
    if (isset($_POST['formnum'])) {
        $data_hj = date('Y/m/d');

        $erro = 'nao';
        $teste_se_ocupado = "SELECT * FROM servico WHERE id_trabalhador = '$_POST[id_chamado]' AND ativo = '1'";
        $jogue_no_banco = mysqli_query($conn, $teste_se_ocupado); $ocupado = 'nao';
        while ($linha12 = mysqli_fetch_array($jogue_no_banco)) {
            $_SESSION['avisar'] = 'Esse colaborador está<br>ocupado em outro serviço';
            $erro = 'sim';
        }
        $stmt = $conn->prepare("SELECT disponivel FROM trabalhador_funcoes WHERE id_trafun = ? LIMIT 1");
        $stmt->bind_param("i", $id_trafun);
        $stmt->execute();
        $ta_on = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($ta_on['disponivel'] == 0) {
            $_SESSION['avisar'] = 'Esse colaborador não<br>está disponível';
            $erro = 'sim';
        }
        if ($_POST['id_chamado'] == $_SESSION['id_acesso']) {
            $_SESSION['avisar'] = 'Kkkk muito engraçado você<br>tentando chamar você mesmo';
            $erro = 'sim';
        }

        if ($erro == 'nao') {
            $status_pendente = SERVICO_STATUS_PENDENTE;
            $comando_chamar = "INSERT INTO servico(registro_id_registro, id_trabalhador, funcoes_id_funcoes, endereco, valor_atual, tempo_servico, avaliacao, ativo, comentario, data_2) 
            VALUES ('$_POST[id_qmchamou]', '$_POST[id_chamado]', '$_POST[funcao]', '$_POST[endereco]', '$_POST[valor_atual]', '0', '0', '$status_pendente', '', '$data_hj')";
            $joga_no_banco = mysqli_query($conn, $comando_chamar);
                $novo_servico_id = mysqli_insert_id($conn);
                if ($novo_servico_id) {
                    audit_log($conn, 'criar', 'servico', $novo_servico_id, 'Chamado criado');
                    $mensagem = 'Novo chamado de ' . $_SESSION['apelido'];
                    $link = 'colabo/colaborador.php';
                    $stmt = $conn->prepare("INSERT INTO notificacoes (registro_id_registro, mensagem, link) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $_POST['id_chamado'], $mensagem, $link);
                    $stmt->execute();
                    $stmt->close();
                }
            unset ($_POST['formnum']);
            header("Location: servicos.php");
        } else {
            header("Location: index.php");
        }
    }
    $analise_servico = "SELECT C.nome 'nome_trabalhador', C.descricao 'descricao', C.data_ani 'data_aniversario', C.sexo 'genero', B.nome_func 'funcao', A.valor_hora 'valor_hora',
    A.registro_id_registro 'id_trabalhador', A.certificado 'certificado', A.funcoes_id_funcoes 'id_funcao', A.id_trafun 'id_trafun' FROM trabalhador_funcoes A INNER JOIN
    registro C ON C.id_registro = A.registro_id_registro INNER JOIN funcoes B ON B.id_funcoes = A.funcoes_id_funcoes WHERE id_trafun = ? LIMIT 1";
    $stmt = $conn->prepare($analise_servico);
    $stmt->bind_param("i", $id_trafun);
    $stmt->execute();
    $resultado_trafun = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $comando_pra_contar = "SELECT COUNT(*) as 'conta' FROM servico WHERE id_trabalhador = '$resultado_trafun[id_trabalhador]' AND ativo='0' AND avaliacao>0";
    $joga_no_banco = mysqli_query($conn, $comando_pra_contar);
    $salvar = mysqli_fetch_assoc($joga_no_banco);
    $total_avaliacoes = $salvar['conta'];

    $conta = 0;
    $comando_avaliacoes = "SELECT avaliacao as 'soma' FROM servico 
    WHERE id_trabalhador = '$resultado_trafun[id_trabalhador]' AND ativo='0' AND avaliacao>0 ORDER BY id_servico DESC";
    $joga_no_banco_2 = mysqli_query($conn, $comando_avaliacoes);
    while ($linha23 = mysqli_fetch_assoc($joga_no_banco_2)) {
        $conta = $conta + $linha23['soma'];
    }
    if (!$total_avaliacoes == 0) {
        $media_avaliacoes = $conta / $total_avaliacoes;
        $media_avaliacoes = number_format($media_avaliacoes, 2, '.', '');
    } else {
        $media_avaliacoes = 'Nova pessoa';
    }

    $analise_registro_cliente = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_acesso]' LIMIT 1";
    $procure = mysqli_query($conn, $analise_registro_cliente);
    $resultado_registro2 = mysqli_fetch_assoc($procure);

    $data_hj = date('Y/m/d');
    $data_new = date($resultado_trafun['data_aniversario']);
    $diferenca = strtotime($data_hj) - strtotime($data_new);
    $total = $diferenca / 60 / 60 / 24 / 365;
    if ($total < 1) {
        $idade = "Menos de 1 ano";
    } else {
        $idade = floor($total)." Anos";
    }
    switch ($resultado_trafun['genero']) {case 'M': $genero = "Masculino"; break; case 'F': $genero = "Feminino"; break;
        case 'P': $genero = "Se optou por não falar"; break; default: $genero = "Outro"; break; 
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
            <link rel="stylesheet" href="css/estrutura_geral.css">

            <script>
                function alterarPagina (K) {
                    window.location.href = "chamar.php?teste="+K;
                }
            </script>
            <title>Página principal</title>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
            <table class="final" style="text-align: left">
                <tr><td colspan="2" id="id1" style="text-align: center"><div class="subtitle">Colaborador</div></td></tr>
                <?php 
                echo "<tr><td id='id1'>Nome:</td><td id='id2'>$resultado_trafun[nome_trabalhador]</td></tr>
                <tr><td id='id1'>Idade:</td><td id='id2'>$idade</td></tr>
                <tr><td id='id1'>Gênero:</td><td id='id2'>$genero</td></tr>
                <tr><td id='id1'>Descrição:</td><td id='id2'>$resultado_trafun[descricao]</td></tr>
                <tr><td id='id1'>Função:</td><td id='id2'>$resultado_trafun[funcao]</td></tr>
                <tr><td id='id1'>Preço:</td><td id='id2'>R$ $resultado_trafun[valor_hora] por hora</td></tr>
                <tr><td id='id1'>Avaliação:</td><td id='id2'>$media_avaliacoes/5 Estrelas</td></tr>
                <tr><td id='id1'>Comentários:</td><td id='id2'><select name='enfeite' id='id2'>";
                $comando_avaliacoes = "SELECT comentario as 'coment' FROM servico WHERE id_trabalhador = '$resultado_trafun[id_trabalhador]'
                 AND ativo='0' AND avaliacao>0 ORDER BY id_servico DESC LIMIT 20";
                $joga_no_banco_2 = mysqli_query($conn, $comando_avaliacoes);
                while ($linha24 = mysqli_fetch_assoc($joga_no_banco_2)) {
                    echo "<option>$linha24[coment]";
                }
                echo "</select></td></tr>
            </table>
            <table class='final' style='text-align: left'>
                <tr><td colspan='2' id='id1' style='text-align: center'><div class='subtitle'>Você</div></td></tr>
                <tr><td id='id1'>Nome:</td><td id='id2'>$resultado_registro2[apelido]</td></tr>
                <tr><td id='id1'>Endereço:</td><td id='id2'>$_SESSION[endereco]</td></tr>
                <tr><td id='id1'>Cidade:</td><td id='id2'>$_SESSION[cidade_preferida]</td></tr>
                <tr><td id='id1'>Estado:</td><td id='id2'>$resultado_registro2[estado]</td></tr>
            </table>";
            echo "
            <br>
                <form action='#' method='POST'>
                    <input type='hidden' name='id_qmchamou' value='$_SESSION[id_acesso]'> <input type='hidden' name='id_chamado' value='$resultado_trafun[id_trabalhador]'>
                    <input type='hidden' name='endereco' value='$_SESSION[endereco]'> <input type='hidden' name='valor_atual' value='$resultado_trafun[valor_hora]'>
                    <input type='hidden' name='funcao' value='$resultado_trafun[id_funcao]'>
                    <input type='hidden' name='formnum' value='4'>";
                ?>
                     
                    <div class='botao'>
                        <input type='reset' value='Mudar endereço' onclick='alterarPagina(2)'> <input type='submit' value='CHAMAR'>
                    </div>
                </form>
            </div>
            <div style="width:100%; height:50px"></div>
    </body>

    <footer class="footer">
        <object data="pe.html" height="45px" width="100%"></object>
    </footer>
</html>