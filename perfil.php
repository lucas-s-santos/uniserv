<?php
    session_start();
    include_once('conexao.php');

    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    if (isset($_SESSION['cpf'])) {

    } else {
        $_SESSION['avisar'] = "Faça login no site!";
        header("Location: login.php");
        exit;

    }

    include_once ("all.php");


    $cpf_pessoal = $_SESSION['cpf'];

    $comando_mysql = "SELECT * FROM registro WHERE cpf = '$cpf_pessoal' LIMIT 1";
    $procure = mysqli_query($conn, $comando_mysql);
    $resultado = mysqli_fetch_assoc($procure);

    $data_a = date('d/m/Y',  strtotime($resultado['data_ani']));
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
        <title>Página principal</title>
    <script>
        function showFormNotice(message) {
            if (window.showToast) {
                showToast(message, "warn");
            }
        }
        function validarDados() {
            let formEDI = document.getElementById("editar");
            let campoSenhaNova = formEDI.senhanova.value;
            if (campoSenhaNova != "" && campoSenhaNova.length < 8) {
                showFormNotice("A nova senha deve ter no minimo 8 caracteres.");
                return false;
            }
            return true;
        }
    </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        <?php
            echo "<form action='adm/processa_editar_perfil.php' method='POST' enctype='multipart/form-data' class='hidden_two form-card' id='editar' style='text-align: left;'>
                <input type='hidden' name='acao' value='$_SESSION[id_acesso]'>
                <div class='title'>Editar</div>
                <label>Nome:</label>
                <input type='text' name='nome' placeholder='Nome' value='$resultado[nome]' required> <br>
                <label>Apelido:</label>
                <input type='text' name='apelido' placeholder='Nome' value='$resultado[apelido]' required> <br>
                <label>Estado:</label>
                <select name='estado' id='estado'>
                <option value='$resultado[estado]' selected>Atual: $resultado[estado]
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
                <input type='email' name='email' placeholder='Escreva seu e-mail se quiser' value='$resultado[email]' required> <br>
                <label>Cidade:</label>
                <input type='text' name='cidade' placeholder='Digite sua cidade' value='$resultado[cidade]' required> <br>
                <label>Telefone:</label>
                <input type='text' id='telefone' name='telefone' placeholder='Telefone' value='$resultado[telefone]'> <br>
                <label>Foto de perfil:</label>
                <input type='file' name='foto' accept='image/png, image/jpeg'> <br>
                <label>Gênero:</label>
                <select name='genero'> 
                    <option value='$resultado[sexo]' selected>$resultado[sexo]
                    <option value='M'>Masculino
                    <option value='F'>Feminino
                    <option value='O'>Outro
                    <option value='P'>Prefiro não falar
                </select> <br>
                <label>Senha:</label>
                <input type='password' name='senhanova' placeholder='Se deseja mudar a senha digite aqui' value=''> <br>
                <label>Digite a senha atual para confirmar:</label> <br>
                <p style='text-align: center'><input type='password' name='senha' placeholder='Digite a senha atual' value='' required> </p>"
        ?>
                <div class='hidden_sub' style='text-align: center'><input type='submit' value='Editar' onclick='return validarDados()'> <input type='reset' value='Cancelar' onclick="invisibleON('editar')"></div>
                    
            </form>
        <div class="title" style="text-align: left">Sobre você</div>
        <?php
            $foto_perfil = $resultado['foto'] ? $resultado['foto'] : 'image/logoservicore.jpg';
            $foto_perfil_safe = htmlspecialchars($foto_perfil, ENT_QUOTES, 'UTF-8');
            echo "<div class='texto'><img src='$foto_perfil_safe' alt='Foto de perfil' style='width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 1px solid #22314a;'></div>
                <div class='texto'>Nome: $resultado[nome] </div>
                    <div class='texto'>Cpf: $resultado[cpf] </div>
                    <div class='botaolist'><a href='sair.php'>Sair</a></div>
                    <div class='texto'>Estado: $resultado[estado] </div>
                    <div class='texto'>Cidade: $resultado[cidade] </div>
                    <div class='texto'>E-mail: $resultado[email] </div>
                    <div class='texto'>Telefone: $resultado[telefone] </div>
                    <div class='texto'>Data de nascimento: $data_a </div>
                    <div class='texto'>Sexo: ";
                    switch ($resultado['sexo']) {case 'M': echo "Masculino"; break;    case 'F': echo "Feminino"; break;
                        case 'P': echo "Você se optou por não falar"; break;    default: echo "Outro"; break; 
                    }
                    echo "</div>";
        ?>
        <div class='botaolist'><a onclick='invisibleON("editar")'>Editar</a> <a href='historico.php?qm=1'>Histórico</a>
        <?php
            if ((int)$_SESSION['funcao'] === 3) {
                echo " <a href='colabo/cadastro_colaborador.php'>Virar colaborador</a>";
            }
        ?>
        </div>
        </main>
    </body>

    <footer class="footer">
        <?php include 'pe.html'; ?>
    </footer>

</html>