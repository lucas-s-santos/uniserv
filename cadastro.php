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

$old_cadastro = isset($_SESSION['old_cadastro']) && is_array($_SESSION['old_cadastro']) ? $_SESSION['old_cadastro'] : [];
unset($_SESSION['old_cadastro']);
$csrf_cadastro = bin2hex(random_bytes(32));
$_SESSION['csrf_cadastro'] = $csrf_cadastro;

include_once("all.php");
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

        function validarEmail(valor) {
            if (!valor) {
                return true;
            }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor);
        }

        function alternarSenhaCadastro(inputId) {
            var input = document.getElementById(inputId);
            if (!input) {
                return;
            }
            input.type = input.type === "password" ? "text" : "password";
        }

        function validarDados() {
            let formCAD = document.getElementById("form1");
            let campoEstado = formCAD.estado;
            let campoCidade = formCAD.cidade.value;
            let campoCpf = formCAD.cpf.value;
            let campoEmail = formCAD.email.value;
            let campoTel = formCAD.telefone.value;
            let campoData = formCAD.data_ani.value;
            let campoSenha = formCAD.senha.value;
            let campoConf = formCAD.confsenha.value;
            let cpfDigitos = campoCpf.replace(/\D/g, "");
            let telDigitos = campoTel.replace(/\D/g, "");

            for (var i = 0; i < campoEstado.length; i++) {
                if (campoEstado[i].selected && campoEstado[i].value == "") {
                    showFormNotice("Selecione um estado.");
                    return false;
                }
            }
            if (cpfDigitos.length !== 11) {
                showFormNotice("CPF incompleto.");
                return false;
            }
            if (!testeoCpf(campoCpf)) {
                showFormNotice("CPF invalido.");
                return false;
            }
            if (telDigitos !== "" && telDigitos.length !== 11) {
                showFormNotice("Telefone incompleto.");
                return false;
            }
            if (campoCidade.trim() === "") {
                showFormNotice("Informe sua cidade.");
                return false;
            }
            if (!validarEmail(campoEmail.trim())) {
                showFormNotice("Informe um e-mail valido.");
                return false;
            }
            if (!campoData) {
                showFormNotice("Informe sua data de nascimento.");
                return false;
            }
            if (campoSenha.length < 8) {
                showFormNotice("Senha deve ter no minimo 8 caracteres.");
                return false;
            }
            if (!/[A-Za-z]/.test(campoSenha) || !/[0-9]/.test(campoSenha)) {
                showFormNotice("Use pelo menos 1 letra e 1 numero na senha.");
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
                    <?php
                    $estado_old = isset($old_cadastro['estado']) ? (string) $old_cadastro['estado'] : '';
                    $genero_old = isset($old_cadastro['genero']) ? (string) $old_cadastro['genero'] : '';
                    ?>
                    <div class="title" style="text-align: center;">Cadastre-se</div>
                    <div class="form-grid">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($csrf_cadastro, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="campo-texto full"> <label>Nome completo</label> <input type="text" name="nome"
                                placeholder="Diga seu nome" autocomplete="name"
                                value="<?php echo htmlspecialchars((string) ($old_cadastro['nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                required> </div>
                        <div class="campo-texto"> <label>Apelido</label> <input type="text" name="apelido"
                                placeholder="Escreva um apelido pequeno ou seu primeiro nome"
                                value="<?php echo htmlspecialchars((string) ($old_cadastro['apelido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                required> </div>
                        <div class="campo-texto">
                            <label>Estado</label>
                            <select name="estado" id="estado" required>
                                <option value="" <?php echo $estado_old === '' ? 'selected' : ''; ?>>Escolha</option>
                                <option value="Acre" <?php echo $estado_old === 'Acre' ? 'selected' : ''; ?>>Acre-AC
                                </option>
                                <option value="Alagoas" <?php echo $estado_old === 'Alagoas' ? 'selected' : ''; ?>>
                                    Alagoas-AL</option>
                                <option value="Amapa" <?php echo $estado_old === 'Amapa' ? 'selected' : ''; ?>>Amapa-AP
                                </option>
                                <option value="Amazonas" <?php echo $estado_old === 'Amazonas' ? 'selected' : ''; ?>>
                                    Amazonas-AM</option>
                                <option value="Bahia" <?php echo $estado_old === 'Bahia' ? 'selected' : ''; ?>>Bahia-BA
                                </option>
                                <option value="Ceara" <?php echo $estado_old === 'Ceara' ? 'selected' : ''; ?>>Ceara-CE
                                </option>
                                <option value="Distrito federal" <?php echo $estado_old === 'Distrito federal' ? 'selected' : ''; ?>>Distrito federal-DF</option>
                                <option value="Espirito Santo" <?php echo $estado_old === 'Espirito Santo' ? 'selected' : ''; ?>>Espirito Santo-ES</option>
                                <option value="Goias" <?php echo $estado_old === 'Goias' ? 'selected' : ''; ?>>Goias-GO
                                </option>
                                <option value="Maranhão" <?php echo $estado_old === 'Maranhão' ? 'selected' : ''; ?>>
                                    Maranhao-MA</option>
                                <option value="Mato Grosso" <?php echo $estado_old === 'Mato Grosso' ? 'selected' : ''; ?>>Mato Grosso-MT</option>
                                <option value="Mato Grosso do Sul" <?php echo $estado_old === 'Mato Grosso do Sul' ? 'selected' : ''; ?>>Mato Grosso do Sul-MS</option>
                                <option value="Minas Gerais" <?php echo $estado_old === 'Minas Gerais' ? 'selected' : ''; ?>>Minas Gerais-MG</option>
                                <option value="Pará" <?php echo $estado_old === 'Pará' ? 'selected' : ''; ?>>Para-PA
                                </option>
                                <option value="Paraíba" <?php echo $estado_old === 'Paraíba' ? 'selected' : ''; ?>>
                                    Paraiba-PB</option>
                                <option value="Paraná" <?php echo $estado_old === 'Paraná' ? 'selected' : ''; ?>>Parana-PR
                                </option>
                                <option value="Pernambuco" <?php echo $estado_old === 'Pernambuco' ? 'selected' : ''; ?>>
                                    Pernambuco-PE</option>
                                <option value="Piauí" <?php echo $estado_old === 'Piauí' ? 'selected' : ''; ?>>Piaui-PI
                                </option>
                                <option value="Rio de Janeiro" <?php echo $estado_old === 'Rio de Janeiro' ? 'selected' : ''; ?>>Rio de Janeiro-RJ</option>
                                <option value="Rio Grande do Norte" <?php echo $estado_old === 'Rio Grande do Norte' ? 'selected' : ''; ?>>Rio Grande do Norte-RN</option>
                                <option value="Rio Grande do Sul" <?php echo $estado_old === 'Rio Grande do Sul' ? 'selected' : ''; ?>>Rio Grande do Sul-RS</option>
                                <option value="Rondônia" <?php echo $estado_old === 'Rondônia' ? 'selected' : ''; ?>>
                                    Rondonia-RO</option>
                                <option value="Roraima" <?php echo $estado_old === 'Roraima' ? 'selected' : ''; ?>>
                                    Roraima-RR</option>
                                <option value="Santa Catarina" <?php echo $estado_old === 'Santa Catarina' ? 'selected' : ''; ?>>Santa Catarina-SC</option>
                                <option value="São Paulo" <?php echo $estado_old === 'São Paulo' ? 'selected' : ''; ?>>Sao
                                    Paulo-SP</option>
                                <option value="Sergipe" <?php echo $estado_old === 'Sergipe' ? 'selected' : ''; ?>>
                                    Sergipe-SE</option>
                                <option value="Tocantins" <?php echo $estado_old === 'Tocantins' ? 'selected' : ''; ?>>
                                    Tocantins-TO</option>
                            </select>
                        </div>
                        <div class="campo-texto"> <label>CPF </label> <input type="text" name="cpf" id="cpf"
                                placeholder="000.000.000-00" inputmode="numeric" autocomplete="username"
                                value="<?php echo htmlspecialchars((string) ($old_cadastro['cpf'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                required> </div>
                        <div class="campo-texto"> <label>Cidade </label> <input type="text" name="cidade"
                                placeholder="Digite sua cidade" autocomplete="address-level2"
                                value="<?php echo htmlspecialchars((string) ($old_cadastro['cidade'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                required> </div>
                        <div class="campo-texto full">
                            <label>Localizacao (opcional)</label>
                            <div class="button-group">
                                <input type="button" value="Usar minha localizacao"
                                    onclick="preencherLocalizacao('cadLat', 'cadLng', 'cadLocalStatus')">
                            </div>
                            <div class="texto" id="cadLocalStatus"></div>
                        </div>
                        <div class="campo-texto"> <label>E-mail(opcional)</label> <input type="email" name="email"
                                placeholder="nome@exemplo.com" autocomplete="email"
                                value="<?php echo htmlspecialchars((string) ($old_cadastro['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="campo-texto"> <label>Telefone(opcional) </label> <input type="text" id="telefone"
                                name="telefone" placeholder="(00)0 0000-0000" autocomplete="tel"
                                value="<?php echo htmlspecialchars((string) ($old_cadastro['telefone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="campo-texto"> <label>Data de nascimento </label> <input type="date" id="data_ani"
                                name="data_ani"
                                value="<?php echo htmlspecialchars((string) ($old_cadastro['data_ani'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                required> </div>
                        <div class="campo-texto"> <label>Gênero </label>
                            <select name="genero">
                                <option value="" <?php echo $genero_old === '' ? 'selected' : ''; ?>>Escolha</option>
                                <option value="M" <?php echo $genero_old === 'M' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="F" <?php echo $genero_old === 'F' ? 'selected' : ''; ?>>Feminino</option>
                                <option value="O" <?php echo $genero_old === 'O' ? 'selected' : ''; ?>>Outro</option>
                                <option value="P" <?php echo $genero_old === 'P' ? 'selected' : ''; ?>>Prefiro não falar
                                </option>
                            </select>
                        </div>
                        <div class="campo-texto">
                            <label>Crie uma senha </label>
                            <input type="password" name="senha" id="cad_senha"
                                placeholder="Minimo 8 caracteres com letra e numero" autocomplete="new-password"
                                minlength="8" required>
                            <div class="button-group" style="margin-top: 8px;">
                                <input type="button" value="Mostrar senha" onclick="alternarSenhaCadastro('cad_senha')">
                            </div>
                        </div>
                        <div class="campo-texto">
                            <label>Repita a senha </label>
                            <input type="password" name="confsenha" id="cad_confsenha"
                                placeholder="Digite a senha novamente" autocomplete="new-password" minlength="8"
                                required>
                            <div class="button-group" style="margin-top: 8px;">
                                <input type="button" value="Mostrar senha"
                                    onclick="alternarSenhaCadastro('cad_confsenha')">
                            </div>
                        </div>
                    </div>
                    <div class="button-group"><input type="reset" value="Limpar"><input type="submit" value="Cadastrar"
                            onclick="return validarDados()"></div>
                    <input type="hidden" name="latitude" id="cadLat" value="">
                    <input type="hidden" name="longitude" id="cadLng" value="">
                    <div class="texto" style="margin-top: 10px; text-align: center;">
                        Ja tem conta? <a href="login.php">Fazer login</a>
                    </div>
                </div>
            </div>
        </form>
    </main>

</body>

</html>