<?php
    session_start();
    include_once("conexao.php");

    $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
    $themeClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

    if (isset($_SESSION['cpf'])) {
        $_SESSION['avisar'] = "Você quer cadastrar ja 
        estando logado no site?
        Resumindo: não pode";
        header('location: index.php');
        exit;
    } else {
        
    }
    include_once ("all.php");
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
                    let formCAD = document.getElementById("form1");
                    let campoNome = formCAD.nome.value,
                        campoEstado = formCAD.estado,
                        campoCidade = formCAD.cidade.value,
                        campoCpf = formCAD.cpf.value,
                        campoEmail = formCAD.email.value,
                        campoTel = formCAD.telefone.value,
                        campoData = formCAD.data_ani,
                        campoGen = formCAD.genero,
                        campoSenha = formCAD.senha.value,
                        campoConf = formCAD.confsenha.value;

                    for (var i=0; i < campoEstado.length; i++) {
                        if (campoEstado[i].selected && campoEstado[i].value == "") {
                            showFormNotice("Selecione um estado.");
                            return false;
                        }       
                    }
                    if (campoCpf.length != 14) {
                        showFormNotice("CPF incompleto.");
                        return false;
                    }
                    if (!testeoCpf(campoCpf)) {
                        showFormNotice("CPF invalido.");
                        return false;
                    }
                    if (campoTel.length != 15 && campoTel != "") {
                        showFormNotice("Telefone incompleto.");
                        return false;
                    }
                    if (campoCidade.trim() === "") {
                        showFormNotice("Informe sua cidade.");
                        return false;
                    }
                    
                    if (campoSenha.length < 8) {
                        showFormNotice("Senha deve ter no minimo 8 caracteres.");
                        return false;
                    }
                    if (campoSenha != campoConf) {
                        showFormNotice("As senhas nao conferem.");
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
        <form name="form1" method="POST" action="processa_cad.php" id="form1">
            <div class="fonte">
                <div class="dentro form-card">
                    <div class="title" style="text-align: center;">Cadastre-se</div>
                    <div class="form-grid">
                        <div class="campo-texto full"> <label>Nome completo</label> <input type="text" name="nome" placeholder="Diga seu nome" required> </div>
                        <div class="campo-texto"> <label>Apelido</label> <input type="text" name="apelido" placeholder="Escreva um apelido pequeno ou seu primeiro nome" required> </div>
                        <div class="campo-texto"> 
                            <label>Estado</label>
                            <select name="estado" id="estado">
                                <option value="">Escolha
                                <option value="Acre">Acre-AC
                                <option value="Alagoas">Alagoas-AL
                                <option value="Amapa">Amapa-AP
                                <option value="Amazonas">Amazonas-AM
                                <option value="Bahia">Bahia-BA
                                <option value="Ceara">Ceara-CE
                                <option value="Distrito federal">Distrito_federal-DF
                                <option value="Espirito Santo">Espirito_Santo-ES
                                <option value="Goias">Goiás - GO
                                <option value="Maranhão">Maranhão - MA
                                <option value="Mato Grosso">Mato Grosso – MT
                                <option value="Mato Grosso do Sul">Mato Grosso do Sul - MS
                                <option value="Minas Gerais">Minas Gerais - MG
                                <option value="Pará">Pará - PA
                                <option value="Paraíba">Paraíba – PB
                                <option value="Paraná">Paraná - PR
                                <option value="Pernambuco">Pernambuco - PE
                                <option value="Piauí">Piauí - PI
                                <option value="Rio de Janeiro">Rio de Janeiro – RJ
                                <option value="Rio Grande do Norte">Rio Grande do Norte - RN
                                <option value="Rio Grande do Sul">Rio Grande do Sul - RS
                                <option value="Rondônia">Rondônia - RO
                                <option value="Roraima">Roraima - RR
                                <option value="Santa Catarina">Santa Catarina - SC
                                <option value="São Paulo">São Paulo - SP
                                <option value="Sergipe">Sergipe - SE
                                <option value="Tocantins">Tocantins - TO
                            </select>
                        </div>
                        <div class="campo-texto"> <label>CPF </label> <input type="text" name="cpf" id="cpf" placeholder="Digite o cpf" required> </div>
                        <div class="campo-texto"> <label>Cidade </label> <input type="text" name="cidade" placeholder="Digite sua cidade" required> </div>
                        <div class="campo-texto full">
                            <label>Localizacao (opcional)</label>
                            <div class="button-group">
                                <input type="button" value="Usar minha localizacao" onclick="preencherLocalizacao('cadLat', 'cadLng', 'cadLocalStatus')">
                            </div>
                            <div class="texto" id="cadLocalStatus"></div>
                        </div>
                        <div class="campo-texto"> <label>E-mail(opcional)</label> <input type="text" name="email" placeholder="É opcional, mas pode te ajudar"> </div>
                        <div class="campo-texto"> <label>Telefone(opcional) </label> <input type="text" id="telefone" name="telefone" placeholder="É opcional, mas facilita comunicação"> </div>
                        <div class="campo-texto"> <label>Data de nascimento </label> <input type="date" id="data_ani" name="data_ani" required> </div>
                        <div class="campo-texto"> <label>Gênero </label>
                            <select name="genero"> 
                                <option value="">Escolha
                                <option value="M">Masculino
                                <option value="F">Feminino
                                <option value="O">Outro
                                <option value="P">Prefiro não falar
                            </select>
                        </div>
                        <div class="campo-texto"> <label>Crie uma senha </label> <input type="password" name="senha" placeholder="Digite a senha" required> </div>
                        <div class="campo-texto"> <label>Repita a senha </label> <input type="password" name="confsenha" placeholder="Digite a senha novamente" required> </div>
                    </div>
                    <div class="button-group"><input type="reset" value="Limpar"><input type="submit" value="Cadastrar" onclick="return validarDados()"></div>
                    <input type="hidden" name="latitude" id="cadLat" value="">
                    <input type="hidden" name="longitude" id="cadLng" value="">
                    <br><br>
                </div>
            </div>
        </form>
        </main>
        
    </body>
</html>