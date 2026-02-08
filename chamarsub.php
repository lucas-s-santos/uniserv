<?php
    session_start();
    include_once "conexao.php";
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (!isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Você precisa estar logado no site!";
        header('location: login.php');
        exit;
    }
    $role = (int)$_SESSION['funcao'];
    if ($role !== 1 && $role !== 3) {
        $_SESSION['avisar'] = "Acesso restrito para clientes.";
        header('location: login.php');
        exit;
    }
    
    if (isset($_GET['teste'])) {
        $_SESSION['ordem_id'] = $_GET['teste'];
    }

    if (isset($_POST['formnum'])) {
        $_SESSION['ordem_id'] = $_POST['formnum'];
    }
    if (!isset($_SESSION['ordem_id'])) {
        $_SESSION['ordem_id'] = 1;
    }

    $usuario_cidade = '';
    $usuario_estado = '';
    $buscar_usuario = "SELECT cidade, estado FROM registro WHERE id_registro = '$_SESSION[id_acesso]' LIMIT 1";
    $res_usuario = mysqli_query($conn, $buscar_usuario);
    if ($res_usuario) {
        $linha_usuario = mysqli_fetch_assoc($res_usuario);
        if ($linha_usuario) {
            $usuario_cidade = $linha_usuario['cidade'];
            $usuario_estado = $linha_usuario['estado'];
        }
    }
    if ($_SESSION['ordem_id']==2) {
        if (isset($_POST['funcao_servico'])) {
            $_SESSION['funcao_preferida'] = $_POST['funcao_servico'];
        }
        $pesquise_id_de_funcao= "SELECT * FROM trabalhador_funcoes";
        $resultado_ids = mysqli_query($conn, $pesquise_id_de_funcao);
        $cont = 0;
        while ($linha10 = mysqli_fetch_array($resultado_ids)) {
            if ($linha10['funcoes_id_funcoes'] == $_SESSION['funcao_preferida']) {
                $cont = $cont+1;
                $ids_de_pessoas[$cont] = $linha10['registro_id_registro'];
            }
        }
    }
    if ($_SESSION['ordem_id']==3) {
        if (isset($_POST['rua'], $_POST['numero'], $_POST['bairro'])) {
            $_SESSION['endereco'] = "Rua: $_POST[rua]. Número: $_POST[numero]. Bairro: $_POST[bairro]";
        }
        if (isset($_POST['cidade_servico']) && $_POST['cidade_servico'] !== '') {
            $_SESSION['cidade_preferida'] = $_POST['cidade_servico'];
        } else if (!isset($_SESSION['cidade_preferida']) && $usuario_cidade !== '') {
            $_SESSION['cidade_preferida'] = $usuario_cidade;
        }
        if (!isset($_SESSION['estado']) && $usuario_estado !== '') {
            $_SESSION['estado'] = $usuario_estado;
        }
        if (!isset($_SESSION['cidade_preferida']) || $_SESSION['cidade_preferida'] === '') {
            $cont_2 = 0;
        } else {
            $veja_todos_dessa_cidade= "SELECT * FROM registro WHERE cidade = '$_SESSION[cidade_preferida]' AND funcao = '2' AND estado = '$_SESSION[estado]'";
            $resultados_encontrados = mysqli_query($conn, $veja_todos_dessa_cidade);
            $cont_2 = 0;
            while ($linha19 = mysqli_fetch_array($resultados_encontrados)) {
                $cont_2 = $cont_2+1;
                $ids_de_pessoas2[$cont_2] = $linha19['id_registro'];
                $nome_de_pessoas2[$cont_2] = $linha19['apelido'];
            }
        }
    }

    if ($_SESSION['ordem_id']==4) {
        
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
        <div class="notice notice--warn" id="formNotice" style="display: none;">
            <div id="formNoticeText">Aviso</div>
            <button type="button" onclick="this.parentElement.style.display='none';">Fechar</button>
        </div>
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Página principal</title>
            <script>
                function showFormNotice(message) {
                    let notice = document.getElementById("formNotice");
                    let text = document.getElementById("formNoticeText");
                    if (!notice || !text) {
                        return;
                    }
                    text.textContent = message;
                    notice.style.display = "flex";
                    notice.scrollIntoView({ behavior: "smooth", block: "center" });
                }
                function alterarPagina (K) {
                    window.location.href = "?teste="+K;
                }

                function vazioNao() {
                    let formT = document.getElementById("form2");
                    let campofunc = formT.funcao_servico;
                    for (var i=0; i < campofunc.length; i++) {
                        if (campofunc[i].selected && campofunc[i].value == "") {
                            showFormNotice("Selecione o servico que deseja.");
                            return false;
                        }       
                    }
                    return true;
                }
            </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">

    <?php
        if ($_SESSION['ordem_id'] == 1) {
            echo '<form name="form2" method="POST" action="#" id="form2">
            <div class="fonte">
                <div class="dentro">
                    <div class="title" style="color: yellow">Qual serviço deseja?</div>
                    <div class="campo-texto"> <input type="hidden" name="formnum" value="2">
                        <select name="funcao_servico">
                        <option value="" selected>Disponíveis:';
                        $pesquise_funcoes= "SELECT * FROM funcoes";
                        $resultado_funcoes = mysqli_query($conn, $pesquise_funcoes);
                        while ($linha9 = mysqli_fetch_array($resultado_funcoes)) {
                
                            echo "<option value='".$linha9['id_funcoes']."'>".$linha9['nome_func'];
                        }
                        echo '</select>
                    </div> 
                    <div class="botao"><input type="submit" value="Escolher" onclick="return vazioNao()"></div>
                </div>
            </div>
        </form>';
        }
        if ($_SESSION['ordem_id'] == 2) {
            $cidade_label = $usuario_cidade !== '' ? $usuario_cidade : 'Nao definida';
            echo '<form name="form2" method="POST" action="#" id="form3">
            <div class="fonte">
                <div class="dentro">
                    <div class="subtitle" style="color: yellow">Cidade do chamado</div>
                    <div class="texto">Cidade atual: '.$cidade_label.'. Voce pode trocar abaixo.</div>
                    <div class="campo-texto"> <input type="hidden" name="formnum" value="3">
                        <select name="cidade_servico">
                        <option value="" selected>Escolha a cidade:';
                        if ($cont == 0) {
                            echo "<option value=''> Sem cidades disponíveis";
                        }
                        $cont2 = 0;
                        while ($cont > 0) {
                            $comando_selecione = "SELECT * FROM registro WHERE id_registro = '$ids_de_pessoas[$cont]' LIMIT 1";
                            $selecione_banco = mysqli_query($conn, $comando_selecione);
                            $resultado23 = mysqli_fetch_assoc($selecione_banco);
                            $resposta = "nao";
                            $cont3 = 0;
                            while ($cont2 > $cont3) {
                                $cont3 = $cont3+1;
                                if ($cidades_on[$cont3] == $resultado23['cidade']) {
                                    $resposta = "sim";
                                }
                            }
                            if ($resposta == "nao") {
                                $selected = ($usuario_cidade !== '' && $usuario_cidade === $resultado23['cidade']) ? ' selected' : '';
                                echo "<option value='".$resultado23['cidade']."'".$selected.">".$resultado23['cidade'];
                            }
                            $cont2 = $cont2+1;
                            $cidades_on[$cont2] = $resultado23['cidade'];
                            $cont = $cont-1;
                        }
                        if ($usuario_cidade !== '' && $cont2 === 0) {
                            echo "<option value='".$usuario_cidade."' selected>".$usuario_cidade;
                        }
                        echo '</select>
                    </div> 
                    <div class="campo-texto" style="font-size: 25px;;"><label>Digite seu endereço<br></label>
                    <input type="text" style="width: 150px" name="bairro" placeholder="Bairro" required>
                    <input type="text" style="width: 150px" name="rua" placeholder="Rua" required>
                    <input type="text" style="width: 150px" name="numero" placeholder="Número" required>

                    <div class="botao"><input type="submit" value="Pronto"> <input type="reset" value="Retornar" onclick="alterarPagina(1)"></div>
                </div>
            </div>
            </form>';
        }
        if ($_SESSION['ordem_id'] == 3) {
            $funciona_diacho= "SELECT * FROM funcoes WHERE id_funcoes = '$_SESSION[funcao_preferida]' LIMIT 1";
            $cd_o_resultado = mysqli_query($conn, $funciona_diacho);
            $resultado_12 = mysqli_fetch_assoc($cd_o_resultado);

            echo "<div class='title' style='color: yellow'>Disponíveis</div>
            <div class='subtitle'>Função: $resultado_12[nome_func] - Cidade: $_SESSION[cidade_preferida].</div> <div class='botao'><input type='reset' value='Retornar' onclick='alterarPagina(1)'></div>";
            echo "<br><div class='tabela_2'>
            <table>
                <tr><td>APELIDO</td><td>PREÇO / HORA</td><td>DISPONIVEL</td></tr>";
            while ($cont_2 > 0) {
                $chamar_final = "SELECT * FROM trabalhador_funcoes WHERE registro_id_registro = '$ids_de_pessoas2[$cont_2]' AND funcoes_id_funcoes = '$_SESSION[funcao_preferida]'";
                $conexao_final = mysqli_query($conn, $chamar_final);
                $linha34 = mysqli_fetch_array($conexao_final);
                $teste_se_ocupado = "SELECT * FROM servico WHERE id_trabalhador = '$ids_de_pessoas2[$cont_2]' AND ativo = '1'";
                $jogue_no_banco = mysqli_query($conn, $teste_se_ocupado); $ocupado = 'nao';
                while ($linha12 = mysqli_fetch_array($jogue_no_banco)) {
                    $ocupado = 'sim';
                }
                if (isset($linha34['id_trafun'])) {
                    echo "<tr><td>$nome_de_pessoas2[$cont_2]</td><td>$linha34[valor_hora]</td>";
                        if ($linha34['disponivel']==0) {
                            echo "<td style='background: red'>OFF-LINE</td></tr>";
                        } else if ($ocupado == 'sim') {
                            echo "<td style='background: red'>Em serviço</td></tr>";
                        } else {
                            if ($linha34['registro_id_registro'] == $_SESSION['id_acesso']) {
                                echo "<td style='background: greenyellow'>Olha você!</td></tr>";
                            } else {
                                echo "<td style='background: cyan'><a href='perfil_chamar.php?ta=$linha34[id_trafun]' target='_parent'>INFORMAÇÃO</a></td></tr>";
                            }
                        }
                }
                $cont_2 = $cont_2-1;
            }
            if ($cont_2 == 0) {
                echo "<tr><td colspan='3'>Nenhum colaborador encontrado para esta cidade e funcao.</td></tr>";
            }
            echo "</table></div>";
        }



    ?>
    </body>
</html>