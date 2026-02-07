<?php
    session_start();
    include_once("conexao.php");
    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';
    if (isset($_SESSION['funcao'])) {
        if ($_SESSION['funcao'] != '1') {
            $_SESSION['avisar'] = "Ei espera ta faltando algo aqui...<br> ah claro.. <br>SUA PERMISSAO PRA ENTRAR AQUI NÉ";
            header('location: login.php');
            exit;
        }
    } else {
        $_SESSION['avisar'] = "Faça login no site!";
        header('location: login.php');
        exit;
    }
    include_once ("all.php");
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <!-- <meta http-equiv="refresh" content="15"> -->
            <meta name="keywords" content="HTML, CSS">
            <meta name="description" content="Pagina inicial do Serviços Relâmpagos">
            <link rel="stylesheet" href="css/estrutura_geral.css">
            <title>Página principal</title>
            <script>

                

                function adicionarF() {
                    alert('Adicionar forçado');
                }

                function issoENumero(Y) {
                    let formCHECK = document.getElementById(Y);
                    let campoID = formCHECK.id_adm.value;
                    let teste = isNumber(campoID);
                    if (teste == false) {alert('Isso não é um número!');}
                    return teste;
                }

                function confirmacaoID(ID) {
                    let formCHECK = document.getElementById(ID);
                    let campoID = formCHECK.id_adm.value;
                    return confirm('Tem certeza que deseja apagar a pessoa de ID "'+campoID+'"?');
                }

                function testarOEditar() {
                    let formEDITAR = document.getElementById("hidden3");
                    let campoNome = formEDITAR.nome.value,
                        campoEstado = formEDITAR.estado,
                        campoCpf = formEDITAR.cpf.value,
                        campoEmail = formEDITAR.email.value,
                        campoTel = formEDITAR.telefone.value,
                        campoData = formEDITAR.data_ani,
                        campoGen = formEDITAR.genero,
                        campoSenha = formEDITAR.senha.value;
                    if (!testeoCpf(campoCpf)) {
                        alert("Esse cpf é falso!");
                        return false;
                    }
                    if (campoCpf.length != 14) {
                        alert("Esse cpf está incompleto");
                        return false;
                    }
                    if (campoTel.length != 15 && campoTel != "") {
                        alert("Esse numero está incompleto");
                        return false;
                    }
                    <?php
                        if (isset($_SESSION['id_adm'])) {
                            $comando_mysql2 = "SELECT * FROM registro WHERE id_registro = '$_SESSION[id_adm]'";
                            $procure2 = mysqli_query($conn, $comando_mysql2);
                            $resultado2 = mysqli_fetch_assoc($procure2);
                        }
                    ?>
                    <?php
                        $pesquise_usuarios= "SELECT * FROM registro";
                        $resultado = mysqli_query($conn, $pesquise_usuarios);
                        if (isset($_SESSION['id_adm'])) {
                            while ($linha = mysqli_fetch_array($resultado)) {
                                echo "if (campoCpf != '$resultado2[cpf]') {if (campoCpf == '$linha[cpf]') {alert('Esse cpf ja foi cadastrado!'); return false;}}";

                                echo "if (campoEmail != '$resultado2[email]' && campoEmail != '') {if (campoEmail == '$linha[email]') {alert('Esse email ja foi cadastrado'); return false;}}";

                                echo "if (campoTel != '$resultado2[telefone]' && campoTel != '') {if (campoTel == '$linha[telefone]') {alert('Esse telefone já foi cadastrado!'); return false;}}";
                            }
                        }
                    ?>

                    return true;
                }
            </script>


    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <div style="width:100%; position: fixed"><object data="menu.php" height="80px" width="100%"></object></div>
        <div style="width:100%; height: 80px;"></div>
        <div class="title">Seção de Adm</div>
        <div class="admopcao">
            <p onclick="invisibleON('hidden5')">Adicionar Serviço</p>
            <p onclick="invisibleON('hidden1')">Analisar cadastros</p>
            <p onclick="invisibleON('hidden2')">Editar</p>
            <p onclick="invisibleON('hidden4')">Excluir</p>
        </div>

        <form action="adm/processa_pesquisa.php" method="POST" class="hidden" id="hidden1">
            <div class="title">Pesquisar</div>
            <label>Nome:</label>
            <input type="text" name="nome_adm" placeholder="Filtro por nome"> <br>
            <label>Cpf:</label>
            <input type="text" name="cpf_adm" id="cpf" placeholder="Filtro por Cpf"> <br>
            <div class="hidden_sub"><input type="submit"></div>
        </form>

        <form action="adm/processa_editar.php" method="POST" class="hidden" id="hidden2">
            <div class="title">Editar</div>
            <input type="hidden" name="acao" value="pesquisar">
            <label>Digite o id da pessoa:</label> <br>
            <input type="text" name="id_adm" id="id_p" placeholder="Necessario para a busca"> <br>
            <div class="hidden_sub" style="text-align: left"><input type="submit" value="Pesquisar" onclick="return issoENumero('hidden2')"></div>
        </form>

        <form action="adm/processa_deletar.php" method="POST" class="hidden" id="hidden4">
            <div class="title">Excluir</div>
            <input type="hidden" name="acao" value="pesquisar">
            <label>Digite o id da pessoa:</label> <br>
            <input type="text" name="id_adm" id="id_p2" placeholder="Necessario para a busca"> <br>
            <div class="hidden_sub" style="text-align: left"><input type="submit" value="Apagar" onclick="return confirmacaoID('hidden4')"></div>
        </form>

        <form action="adm/processa_criar.php" method="POST" class="hidden" id="hidden5">
            <div class="title">Criando novo</div>
            <label>Nome:</label>
            <input type="text" name="nome_func" placeholder="Filtro por nome"> <br>
            <label>Categoria:</label>
            <input type="text" name="categoria" placeholder="Ex: Limpeza, Eletrica"> <br>
            <label>Valor base:</label>
            <input type="text" name="valor_base" placeholder="Valor base em R$"> <br>
            <label>Duração estimada (min):</label>
            <input type="text" name="duracao_estimada" placeholder="Ex: 60"> <br>
            <label>Descrição:</label>
            <input type="text" name="descricao" placeholder="Detalhes do serviço"> <br>
            <label>Imagem:</label>
            <input type="file" name="avatar" accept="image/png, image/jpeg"> <br>
            <div class="hidden_sub"><input type="submit"></div>
        </form>

        <?php
            if (isset($_SESSION['id_adm'])) {
                echo "<form action='adm/processa_editar.php' method='POST' class='hidden is-open' id='hidden3' style='text-align: left;'>
                <input type='hidden' name='acao' value='$_SESSION[id_adm]'>
                <div class='title'>Editar</div>
                <label>Nome:</label>
                <input type='text' name='nome' placeholder='Nome' value='$resultado2[nome]' required> <br>
                <label>Apelido:</label>
                <input type='text' name='apelido' placeholder='Nome' value='$resultado2[apelido]' required> <br>
                <label>Cpf:</label>
                <input type='text' id='cpf2' name='cpf' placeholder='Cpf' value='$resultado2[cpf]' required> <br>
                <label>Estado:</label>
                <select name='estado' id='estado'>
                <option value='$resultado2[estado]' selected>Atual: $resultado2[estado]
                    <option value='Acre'>Acre-AC
                    <option value='Alagoas'>Alagoas-AL
                    <option value='Amapa'>Amapa-AP
                    <option value='Amazonas'>Amazonas-AM
                    <option value='Bahia'>Bahia-BA
                    <option value='Ceara'>Ceara-CE
                    <option value='Distrito federal'>Distrito_federal-DF
                    <option value='Espirito Santo'>Espirito_Santo-ES
                    <option value='Goias'>Goiás - GO
                    <option value='Maranhão'>Maranhão - MA
                    <option value='Mato Grosso'>Mato Grosso – MT
                    <option value='Mato Grosso do Sul'>Mato Grosso do Sul - MS
                    <option value='Minas Gerais'>Minas Gerais - MG
                    <option value='Pará'>Pará - PA
                    <option value='Paraíba'>Paraíba – PB
                    <option value='Paraná'>Paraná - PR
                    <option value='Pernambuco'>Pernambuco - PE
                    <option value='Piauí'>Piauí - PI
                    <option value='Rio de Janeiro'>Rio de Janeiro – RJ
                    <option value='Rio Grande do Norte'>Rio Grande do Norte - RN
                    <option value='Rio Grande do Sul'>Rio Grande do Sul - RS
                    <option value='Rondônia'>Rondônia - RO
                    <option value='Roraima'>Roraima - RR
                    <option value='Santa Catarina'>Santa Catarina - SC
                    <option value='São Paulo'>São Paulo - SP
                    <option value='Sergipe'>Sergipe - SE
                    <option value='Tocantins'>Tocantins - TO
                </select> <br>
                <label>E-mail:</label>
                <input type='text' name='email' placeholder='E-mail' value='$resultado2[email]'> <br>
                <label>Telefone:</label>
                <input type='text' id='telefone' name='telefone' placeholder='Telefone' value='$resultado2[telefone]'> <br>
                <label>Data de nascimento:</label>
                <input type='text' name='data_ani' placeholder='data_ani' value='$resultado2[data_ani]' required> <br>
                <label>Gênero:</label>
                <select name='genero'> 
                    <option value='$resultado2[sexo]' selected>Atual: $resultado2[sexo]
                    <option value='M'>Masculino
                    <option value='F'>Feminino
                    <option value='O'>Outro
                    <option value='P'>Prefiro não falar
                </select> <br>
                <label>Senha:</label>
                <input type='text' name='senha' placeholder='senha???' value='$resultado2[senha]' required> <br>
                <div class='hidden_sub' style='text-align: center'><input type='submit' value='Editar' onclick='return testarOEditar()'></div>    
            </form>";
            unset ($_SESSION['id_adm']);
            }
        ?>
        

        <br><br>
        <div class="tabela_adm" id="tabela_cadastros">
            <table>
                <tr><td>ID</td><td>NOME COMPLETO</td><td>CPF</td><td>ESTADO</td>
                        <td>GÊNERO</td><td>CNPJ</td><td>EMAIL</td><td>TELEFONE</td><td>SENHA</td><td>Serviços prestados</td><td>Função</td></tr>
                <?php
                    if (isset($_SESSION['nome_adm'])) {$nome_filtro = $_SESSION['nome_adm'];} else {$nome_filtro = "";}
                    if (isset($_SESSION['cpf_adm'])) {$cpf_filtro = $_SESSION['cpf_adm'];} else {$cpf_filtro = "";}
                    $pesquise_usuarios= "SELECT * FROM registro WHERE nome like '%$nome_filtro%' AND cpf like '%$cpf_filtro%'";
                    $resultado = mysqli_query($conn, $pesquise_usuarios);
                    $cont = 0;
                    while ($linha = mysqli_fetch_array($resultado)) {
                        $cont++;
                        echo "<tr><td>$linha[id_registro]</td><td>$linha[nome]</td><td>$linha[cpf]</td><td>$linha[estado]</td><td>";
                        switch ($linha['sexo']) {case 'M': echo "Masculino"; break;    case 'F': echo "Feminino"; break;
                            case 'P': echo "Não falar"; break;    default: echo "Outro"; break; 
                        }   
                        echo "</td><td>$linha[cnpj]</td><td>$linha[email]</td><td>$linha[telefone]</td><td>$linha[senha]</td><td>$linha[servicos_ok]</td><td>";
                        switch ($linha['funcao']) {case '1': echo "Administrador"; break;    case '2': echo "Colaborador"; break;
                            default: echo "Cliente"; break; 
                        }
                        echo "</td></tr>";
                    }
                    unset ($_SESSION['nome_adm'], $_SESSION['cpf_adm']);
                    echo "<tr><td>(X)</td><td>($cont) Resultados</td></tr>";
                ?>
            </table>
            <br><br><br><br>
        </div>
            <script>
                let edit2 = document.getElementById('tabela_cadastros'); 
                <?php 
                    if (isset($_SESSION['exibir_tabela'])) {
                        echo "invisibleON('tabela_cadastros')";
                        unset($_SESSION['exibir_tabela']);
                    }
                ?>
            </script>
    </body>

    <footer class="footer">
        <object data="pe.html" height="45px" width="100%"></object>
    </footer>

</html>