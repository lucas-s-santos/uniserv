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
        function preencherLocalizacao(latId, lngId, statusId) {
            if (!navigator.geolocation) {
                showFormNotice("Navegador nao suporta geolocalizacao.");
                return;
            }
            var statusEl = document.getElementById(statusId);
            if (statusEl) {
                statusEl.textContent = "Buscando localizacao...";
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                var latEl = document.getElementById(latId);
                var lngEl = document.getElementById(lngId);
                if (latEl && lngEl) {
                    latEl.value = pos.coords.latitude.toFixed(7);
                    lngEl.value = pos.coords.longitude.toFixed(7);
                }
                if (statusEl) {
                    statusEl.textContent = "Localizacao capturada.";
                }
                if (window.showToast) {
                    showToast("Localizacao capturada.", "success");
                }
            }, function () {
                if (statusEl) {
                    statusEl.textContent = "Nao foi possivel obter localizacao.";
                }
                showFormNotice("Nao foi possivel obter localizacao.");
            }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
        }
    </script>
    </head>
    
    <body class="centralizar <?php echo $themeClass; ?>">
        <?php include 'menu.php'; ?>
        <div class="menu-spacer"></div>
        <main class="page">
        <section class="page-header">
            <div>
                <div class="page-kicker">Meu perfil</div>
                <h1 class="page-title">Sobre voce</h1>
                <p class="page-subtitle">Gerencie seus dados pessoais e preferen cias.</p>
            </div>
            <div class="page-actions">
                <button type="button" class="btn btn-accent" onclick='invisibleON("editar")'>Editar perfil</button>
                <a class="btn btn-ghost" href="historico.php?qm=1">Historico</a>
                <?php
                    if ((int)$_SESSION['funcao'] === 3) {
                        echo "<a class='btn btn-primary' href='colabo/cadastro_colaborador.php'>Virar colaborador</a>";
                    }
                ?>
                <a class="btn btn-ghost" href="sair.php">Sair</a>
            </div>
        </section>
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
                <label>Localizacao (opcional):</label>
                <div class='button-group'>
                    <input type='button' value='Usar minha localizacao' onclick=\"preencherLocalizacao('perfilLat', 'perfilLng', 'perfilLocalStatus')\">
                </div>
                <div class='texto' id='perfilLocalStatus'></div>
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
                <p style='text-align: center'><input type='password' name='senha' placeholder='Digite a senha atual' value='' required> </p>
                <input type='hidden' name='latitude' id='perfilLat' value=''>
                <input type='hidden' name='longitude' id='perfilLng' value=''>"
        ?>
                <div class='hidden_sub' style='text-align: center'><input type='submit' value='Editar' onclick='return validarDados()'> <input type='reset' value='Cancelar' onclick="invisibleON('editar')"></div>
                    
            </form>
        <section class="info-panel">
            <div class="section-title">Seus dados</div>
            <p class="section-subtitle">Informacoes cadastradas no sistema.</p>
        </section>
        <div class="profile-hero">
        <?php
            $foto_perfil = $resultado['foto'] ? $resultado['foto'] : 'image/logoservicore.jpg';
            $foto_perfil_safe = htmlspecialchars($foto_perfil, ENT_QUOTES, 'UTF-8');
            echo "<div class='profile-card'>
                <div class='profile-identity'>
                    <div class='profile-avatar profile-avatar--sm'>
                        <img src='$foto_perfil_safe' alt='Foto de perfil'>
                    </div>
                    <div>
                        <div class='profile-title'>$resultado[nome]</div>
                        <div class='profile-subtitle'>CPF: $resultado[cpf]</div>
                    </div>
                </div>
                <div class='profile-list'>
                    <div><span>Estado</span><strong>$resultado[estado]</strong></div>
                    <div><span>Cidade</span><strong>$resultado[cidade]</strong></div>
                    <div><span>E-mail</span><strong>$resultado[email]</strong></div>
                    <div><span>Telefone</span><strong>$resultado[telefone]</strong></div>
                    <div><span>Data de nascimento</span><strong>$data_a</strong></div>
                    <div><span>Sexo</span><strong>";
                    switch ($resultado['sexo']) {case 'M': echo "Masculino"; break;    case 'F': echo "Feminino"; break;
                        case 'P': echo "Voce se optou por nao falar"; break;    default: echo "Outro"; break; 
                    }
                    echo "</strong></div>
                </div>
            </div>";
        ?>
        </div>
        </main>
    </body>
</html>