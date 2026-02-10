<?php
    if (!isset($conn)) {
        include_once __DIR__ . '/../conexao.php';
    }

    $usuarioId = isset($_SESSION['id_acesso']) ? (int)$_SESSION['id_acesso'] : 0;
    $usuarioCidade = '';
    $usuarioEstado = '';
    if ($usuarioId > 0) {
        $stmt = $conn->prepare("SELECT cidade, estado FROM registro WHERE id_registro = ? LIMIT 1");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $resUsuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($resUsuario) {
            $usuarioCidade = $resUsuario['cidade'] ?? '';
            $usuarioEstado = $resUsuario['estado'] ?? '';
        }
    }

    $funcoes = [];
    $resultadoFuncoes = mysqli_query($conn, "SELECT id_funcoes, nome_func FROM funcoes ORDER BY nome_func");
    while ($linha = mysqli_fetch_assoc($resultadoFuncoes)) {
        $funcoes[] = [
            'id' => (int)$linha['id_funcoes'],
            'nome' => $linha['nome_func']
        ];
    }

    $ocupados = [];
    $resOcupados = mysqli_query($conn, "SELECT DISTINCT id_trabalhador FROM servico WHERE ativo = '1'");
    while ($linha = mysqli_fetch_assoc($resOcupados)) {
        $ocupados[(int)$linha['id_trabalhador']] = true;
    }

    $profissionais = [];
    $queryProfissionais = "SELECT A.id_trafun, A.funcoes_id_funcoes, A.valor_hora, A.disponivel, A.registro_id_registro,
        B.apelido, B.cidade, B.estado, B.latitude, B.longitude, B.pix_chave, B.aceita_pix, B.aceita_dinheiro, B.aceita_cartao_presencial
        FROM trabalhador_funcoes A
        INNER JOIN registro B ON B.id_registro = A.registro_id_registro
        WHERE B.funcao = '2'";
    $resultadoProfissionais = mysqli_query($conn, $queryProfissionais);
    while ($linha = mysqli_fetch_assoc($resultadoProfissionais)) {
        $idTrabalhador = (int)$linha['registro_id_registro'];
        $profissionais[] = [
            'idTrafun' => (int)$linha['id_trafun'],
            'funcaoId' => (int)$linha['funcoes_id_funcoes'],
            'valorHora' => $linha['valor_hora'],
            'disponivel' => (int)$linha['disponivel'] === 1,
            'idTrabalhador' => $idTrabalhador,
            'apelido' => $linha['apelido'],
            'cidade' => $linha['cidade'],
            'estado' => $linha['estado'],
            'lat' => $linha['latitude'] !== null ? (float)$linha['latitude'] : null,
            'lng' => $linha['longitude'] !== null ? (float)$linha['longitude'] : null,
            'hasPix' => !empty($linha['pix_chave']) && (int)$linha['aceita_pix'] === 1,
            'acceptsCash' => (int)$linha['aceita_dinheiro'] === 1,
            'acceptsCard' => (int)$linha['aceita_cartao_presencial'] === 1,
            'ocupado' => isset($ocupados[$idTrabalhador]),
            'self' => $usuarioId > 0 && $usuarioId === $idTrabalhador
        ];
    }
?>

<section class="wizard" id="chamarWizard">
    <div class="wizard-steps">
        <div class="wizard-step is-active" data-step="1">Passo 1</div>
        <div class="wizard-step" data-step="2">Passo 2</div>
        <div class="wizard-step" data-step="3">Passo 3</div>
    </div>

    <div class="wizard-panel is-active" data-step="1">
        <div class="fonte">
            <div class="dentro">
                <div class="title" style="color: yellow">Qual servico deseja?</div>
                <div class="campo-texto">
                    <label>Servico</label>
                    <select id="wizardFuncao">
                        <option value="">Selecione o servico</option>
                        <?php foreach ($funcoes as $funcao) { ?>
                            <option value="<?php echo (int)$funcao['id']; ?>"><?php echo htmlspecialchars($funcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="botao">
                    <input type="button" value="Continuar" data-action="next">
                </div>
            </div>
        </div>
    </div>

    <div class="wizard-panel" data-step="2">
        <div class="fonte">
            <div class="dentro">
                <div class="subtitle" style="color: yellow">Cidade do chamado</div>
                <div class="texto" id="wizardCidadeAtual"></div>
                <div class="campo-texto">
                    <label>Escolha a cidade</label>
                    <select id="wizardCidade"></select>
                </div>
                <div class="campo-texto">
                    <label>Digite seu endereco</label>
                    <input type="text" id="wizardBairro" placeholder="Bairro" required>
                    <input type="text" id="wizardRua" placeholder="Rua" required>
                    <input type="text" id="wizardNumero" placeholder="Numero" required>
                </div>
                <div class="campo-texto">
                    <label>Localizacao (opcional)</label>
                    <div class="button-group">
                        <input type="button" value="Usar minha localizacao" id="wizardUseLocation">
                    </div>
                    <div class="texto" id="wizardGeoStatus"></div>
                </div>
                <div class="button-group">
                    <input type="button" value="Voltar" data-action="prev">
                    <input type="button" value="Continuar" data-action="next">
                </div>
            </div>
        </div>
    </div>

    <div class="wizard-panel" data-step="3">
        <div class="wizard-results">
            <div class="wizard-summary">
                <div class="section-title">Resumo do chamado</div>
                <div class="section-subtitle" id="wizardResumo"></div>
                <div class="button-group">
                    <input type="button" value="Voltar" data-action="prev">
                </div>
            </div>
            <div class="wizard-filters">
                <input type="text" id="wizardSearch" placeholder="Buscar por apelido">
                <select id="wizardSort">
                    <option value="preco-asc">Preco menor</option>
                    <option value="preco-desc">Preco maior</option>
                </select>
                <div class="wizard-distance">
                    <label>Raio (km)</label>
                    <input type="number" id="wizardRadius" min="1" max="100" value="10">
                </div>
                <label class="wizard-checkbox">
                    <input type="checkbox" id="wizardOnlyAvailable"> Mostrar apenas disponiveis
                </label>
                <label class="wizard-checkbox">
                    <input type="checkbox" id="wizardDistanceFilter"> Filtrar por distancia
                </label>
            </div>
            <div class="wizard-map" id="wizardMap"></div>
            <div class="wizard-cards" id="wizardCards"></div>
        </div>
    </div>
</section>

<script>
    (function () {
        var data = {
            funcoes: <?php echo json_encode($funcoes, JSON_UNESCAPED_UNICODE); ?>,
            profissionais: <?php echo json_encode($profissionais, JSON_UNESCAPED_UNICODE); ?>,
            usuario: {
                id: <?php echo (int)$usuarioId; ?>,
                cidade: <?php echo json_encode($usuarioCidade, JSON_UNESCAPED_UNICODE); ?>,
                estado: <?php echo json_encode($usuarioEstado, JSON_UNESCAPED_UNICODE); ?>
            }
        };

        var wizard = document.getElementById("chamarWizard");
        if (!wizard) {
            return;
        }

        var state = {
            step: 1,
            funcaoId: "",
            cidade: "",
            endereco: "",
            userLocation: null,
            geocodePending: false
        };

        var funcaoSelect = document.getElementById("wizardFuncao");
        var cidadeSelect = document.getElementById("wizardCidade");
        var cidadeAtual = document.getElementById("wizardCidadeAtual");
        var resumo = document.getElementById("wizardResumo");
        var cardsContainer = document.getElementById("wizardCards");
        var bairroInput = document.getElementById("wizardBairro");
        var ruaInput = document.getElementById("wizardRua");
        var numeroInput = document.getElementById("wizardNumero");
        var useLocationBtn = document.getElementById("wizardUseLocation");
        var geoStatus = document.getElementById("wizardGeoStatus");
        var distanceCheck = document.getElementById("wizardDistanceFilter");
        var radiusInput = document.getElementById("wizardRadius");
        var mapEl = document.getElementById("wizardMap");
        var searchInput = document.getElementById("wizardSearch");
        var sortSelect = document.getElementById("wizardSort");
        var onlyAvailableCheck = document.getElementById("wizardOnlyAvailable");

        var map = null;
        var mapMarkers = null;

        function showToastMessage(message, type) {
            if (window.showToast) {
                window.showToast(message, type || "error");
            }
        }

        function setGeoStatus(message) {
            if (geoStatus) {
                geoStatus.textContent = message;
            }
        }

        function requestGeocode(payload) {
            if (state.geocodePending) {
                return;
            }
            state.geocodePending = true;
            setGeoStatus("Buscando localizacao por endereco...");
            fetch("geocode.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(payload).toString()
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (json && json.ok && json.lat && json.lng) {
                        state.userLocation = { lat: Number(json.lat), lng: Number(json.lng) };
                        setGeoStatus("Localizacao encontrada pelo endereco.");
                        renderProfissionais();
                    } else {
                        setGeoStatus("Nao foi possivel localizar pelo endereco.");
                    }
                })
                .catch(function () {
                    setGeoStatus("Falha ao buscar localizacao pelo endereco.");
                })
                .finally(function () {
                    state.geocodePending = false;
                });
        }

        function requestReverseGeocode(lat, lng) {
            if (!lat || !lng) {
                return;
            }
            setGeoStatus("Preenchendo endereco pelo GPS...");
            fetch("reverse_geocode.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ lat: lat, lng: lng }).toString()
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (json && json.ok) {
                        if (bairroInput && json.bairro) {
                            bairroInput.value = json.bairro;
                        }
                        if (ruaInput && json.rua) {
                            ruaInput.value = json.rua;
                        }
                        if (numeroInput && json.numero) {
                            numeroInput.value = json.numero;
                        }
                        var missing = [];
                        if (!json.bairro) { missing.push("bairro"); }
                        if (!json.rua) { missing.push("rua"); }
                        if (!json.numero) { missing.push("numero"); }
                        if (missing.length) {
                            setGeoStatus("Endereco incompleto pelo GPS. Complete: " + missing.join(", ") + ".");
                            showToastMessage("Complete o endereco manualmente: " + missing.join(", ") + ".", "warn");
                        } else {
                            setGeoStatus("Endereco preenchido pelo GPS.");
                        }
                    } else {
                        setGeoStatus("Nao foi possivel preencher o endereco pelo GPS.");
                    }
                })
                .catch(function () {
                    setGeoStatus("Falha ao preencher endereco pelo GPS.");
                });
        }

        function fallbackGeocodeFromForm() {
            if (state.userLocation) {
                return;
            }
            var cidade = state.cidade || "";
            var estado = data.usuario.estado || "";
            var enderecoCompleto = state.endereco ? (state.endereco + ", " + cidade + ", " + estado + ", Brasil") : "";
            if (enderecoCompleto.trim() !== "") {
                requestGeocode({ endereco: enderecoCompleto });
            } else if (cidade) {
                requestGeocode({ cidade: cidade, estado: estado });
            }
        }

        function setStep(step) {
            state.step = step;
            wizard.querySelectorAll(".wizard-panel").forEach(function (panel) {
                panel.classList.toggle("is-active", Number(panel.dataset.step) === step);
            });
            wizard.querySelectorAll(".wizard-step").forEach(function (item) {
                item.classList.toggle("is-active", Number(item.dataset.step) === step);
            });
        }

        function getFuncaoLabel(funcaoId) {
            var funcao = data.funcoes.find(function (item) {
                return String(item.id) === String(funcaoId);
            });
            return funcao ? funcao.nome : "";
        }

        function buildCidadeOptions() {
            var cidades = {};
            data.profissionais.forEach(function (item) {
                if (String(item.funcaoId) === String(state.funcaoId)) {
                    cidades[item.cidade] = true;
                }
            });

            cidadeSelect.innerHTML = "";
            var placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = "Escolha a cidade";
            cidadeSelect.appendChild(placeholder);

            Object.keys(cidades).sort().forEach(function (cidade) {
                var option = document.createElement("option");
                option.value = cidade;
                option.textContent = cidade;
                if (data.usuario.cidade && data.usuario.cidade === cidade) {
                    option.selected = true;
                }
                cidadeSelect.appendChild(option);
            });

            if (data.usuario.cidade && cidades[data.usuario.cidade]) {
                state.cidade = data.usuario.cidade;
            }
        }

        function getDistanceKm(lat1, lng1, lat2, lng2) {
            var rad = Math.PI / 180;
            var dLat = (lat2 - lat1) * rad;
            var dLng = (lng2 - lng1) * rad;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * rad) * Math.cos(lat2 * rad) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return 6371 * c;
        }

        function ensureMap() {
            if (!mapEl || map || !window.L) {
                return;
            }
            map = L.map(mapEl, { scrollWheelZoom: false });
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19,
                attribution: "&copy; OpenStreetMap"
            }).addTo(map);
            mapMarkers = L.layerGroup().addTo(map);
        }

        function updateMap(lista) {
            if (!map) {
                return;
            }
            mapMarkers.clearLayers();
            var bounds = [];
            lista.forEach(function (item) {
                if (item.lat === null || item.lng === null) {
                    return;
                }
                var marker = L.marker([item.lat, item.lng]).bindPopup(item.apelido + " - R$ " + item.valorHora + "/hora");
                mapMarkers.addLayer(marker);
                bounds.push([item.lat, item.lng]);
            });
            if (state.userLocation) {
                var userMarker = L.circleMarker([state.userLocation.lat, state.userLocation.lng], {
                    radius: 6,
                    color: "#1f6feb",
                    fillColor: "#1f6feb",
                    fillOpacity: 0.9
                }).bindPopup("Voce esta aqui");
                mapMarkers.addLayer(userMarker);
                bounds.push([state.userLocation.lat, state.userLocation.lng]);
                if (distanceCheck && distanceCheck.checked && radiusInput && radiusInput.value) {
                    var radiusMeters = Number(radiusInput.value) * 1000;
                    if (radiusMeters > 0) {
                        var circle = L.circle([state.userLocation.lat, state.userLocation.lng], {
                            radius: radiusMeters,
                            color: "#18b899",
                            fillColor: "#18b899",
                            fillOpacity: 0.1
                        });
                        mapMarkers.addLayer(circle);
                    }
                }
            }
            if (bounds.length) {
                map.fitBounds(bounds, { padding: [20, 20] });
            } else {
                map.setView([-14.235, -51.9253], 4);
            }
        }

        function renderProfissionais() {
            if (!cardsContainer) {
                return;
            }
            cardsContainer.innerHTML = "";
            var estadoFiltro = data.usuario.estado || "";
            var aplicarDistancia = distanceCheck && distanceCheck.checked;
            var radiusKm = radiusInput && radiusInput.value ? Number(radiusInput.value) : 0;
            var lista = data.profissionais.filter(function (item) {
                if (String(item.funcaoId) !== String(state.funcaoId)) {
                    return false;
                }
                if (state.cidade && item.cidade !== state.cidade) {
                    return false;
                }
                if (estadoFiltro && item.estado !== estadoFiltro) {
                    return false;
                }
                if (onlyAvailableCheck && onlyAvailableCheck.checked) {
                    if (!item.disponivel || item.ocupado || item.self) {
                        return false;
                    }
                }
                if (searchInput && searchInput.value.trim()) {
                    var busca = searchInput.value.trim().toLowerCase();
                    if (!String(item.apelido || "").toLowerCase().includes(busca)) {
                        return false;
                    }
                }
                if (aplicarDistancia) {
                    if (!state.userLocation) {
                        item._dist = null;
                        return true;
                    }
                    if (item.lat === null || item.lng === null || radiusKm <= 0) {
                        return false;
                    }
                    var dist = getDistanceKm(state.userLocation.lat, state.userLocation.lng, item.lat, item.lng);
                    item._dist = dist;
                    if (dist > radiusKm) {
                        return false;
                    }
                } else {
                    item._dist = null;
                }
                return true;
            });

            if (aplicarDistancia && state.userLocation) {
                lista.sort(function (a, b) {
                    var distA = a._dist !== null && a._dist !== undefined ? a._dist : Number.POSITIVE_INFINITY;
                    var distB = b._dist !== null && b._dist !== undefined ? b._dist : Number.POSITIVE_INFINITY;
                    if (distA !== distB) {
                        return distA - distB;
                    }
                    return Number(a.valorHora) - Number(b.valorHora);
                });
            } else if (sortSelect && sortSelect.value === "preco-desc") {
                lista.sort(function (a, b) { return Number(b.valorHora) - Number(a.valorHora); });
            } else {
                lista.sort(function (a, b) { return Number(a.valorHora) - Number(b.valorHora); });
            }

            if (!lista.length) {
                var empty = document.createElement("div");
                empty.className = "collab-empty";
                empty.textContent = "Nenhum colaborador encontrado para esta cidade e funcao.";
                cardsContainer.appendChild(empty);
                return;
            }

            lista.forEach(function (item) {
                var card = document.createElement("div");
                card.className = "wizard-card";

                var header = document.createElement("div");
                header.className = "wizard-card__header";

                var name = document.createElement("div");
                name.className = "wizard-card__title";
                name.textContent = item.apelido;

                var price = document.createElement("div");
                price.className = "wizard-card__price";
                price.textContent = "R$ " + item.valorHora + " / hora";

                header.appendChild(name);
                header.appendChild(price);

                var meta = document.createElement("div");
                meta.className = "wizard-card__meta";
                meta.textContent = item.cidade + " - " + item.estado;

                if (!item.hasPix && !item.acceptsCash && !item.acceptsCard) {
                    var warn = document.createElement("div");
                    warn.className = "wizard-card__meta";
                    warn.textContent = "Aviso: colaborador sem pagamento configurado.";
                    card.appendChild(warn);
                }

                var status = document.createElement("span");
                status.className = "status-badge";
                if (!item.disponivel) {
                    status.classList.add("status-badge--canceled");
                    status.textContent = "Indisponivel";
                } else if (item.ocupado) {
                    status.classList.add("status-badge--pending");
                    status.textContent = "Em servico";
                } else if (item.self) {
                    status.classList.add("status-badge--active");
                    status.textContent = "Seu perfil";
                } else {
                    status.classList.add("status-badge--done");
                    status.textContent = "Disponivel";
                }

                var actions = document.createElement("div");
                actions.className = "wizard-card__actions";
                if (!item.disponivel || item.ocupado || item.self || (!item.hasPix && !item.acceptsCash && !item.acceptsCard)) {
                    var disabled = document.createElement("span");
                    disabled.className = "wizard-card__hint";
                    disabled.textContent = !item.hasPix && !item.acceptsCash && !item.acceptsCard ? "Sem pagamento configurado" : "Nao disponivel agora";
                    actions.appendChild(disabled);
                } else {
                    var link = document.createElement("a");
                    link.className = "btn btn-small btn-primary";
                    link.textContent = "Ver perfil";
                    link.href = buildPerfilLink(item.idTrafun);
                    actions.appendChild(link);
                }

                card.appendChild(header);
                card.appendChild(meta);
                if (item._dist !== null && item._dist !== undefined) {
                    var dist = document.createElement("div");
                    dist.className = "wizard-card__meta";
                    dist.textContent = "Distancia: " + item._dist.toFixed(1) + " km";
                    card.appendChild(dist);
                }
                card.appendChild(status);
                card.appendChild(actions);
                cardsContainer.appendChild(card);
            });

            ensureMap();
            if (map) {
                setTimeout(function () {
                    map.invalidateSize();
                }, 0);
                updateMap(lista);
            }
        }

        function buildPerfilLink(idTrafun) {
            var endereco = state.endereco || "";
            var params = new URLSearchParams();
            params.set("ta", idTrafun);
            params.set("funcao", state.funcaoId);
            params.set("cidade", state.cidade || "");
            params.set("estado", data.usuario.estado || "");
            params.set("endereco", endereco);
            return "perfil_chamar.php?" + params.toString();
        }

        wizard.addEventListener("click", function (event) {
            var action = event.target.getAttribute("data-action");
            if (!action) {
                return;
            }

            if (action === "next") {
                if (state.step === 1) {
                    state.funcaoId = funcaoSelect.value;
                    if (!state.funcaoId) {
                        showToastMessage("Selecione o servico que deseja.");
                        return;
                    }
                    buildCidadeOptions();
                    var cidadeLabel = data.usuario.cidade ? data.usuario.cidade : "Nao definida";
                    cidadeAtual.textContent = "Cidade atual: " + cidadeLabel + ". Voce pode trocar abaixo.";
                    setStep(2);
                } else if (state.step === 2) {
                    var cidade = cidadeSelect.value;
                    var bairro = bairroInput.value.trim();
                    var rua = ruaInput.value.trim();
                    var numero = numeroInput.value.trim();
                    var temEnderecoCompleto = bairro && rua && numero;
                    var temEnderecoParcial = bairro || rua || numero;
                    if (!cidade) {
                        showToastMessage("Escolha a cidade do chamado.");
                        return;
                    }
                    if (temEnderecoParcial && !temEnderecoCompleto) {
                        showToastMessage("Preencha bairro, rua e numero ou use a localizacao.");
                        return;
                    }
                    state.cidade = cidade;
                    if (temEnderecoCompleto) {
                        state.endereco = "Rua: " + rua + ". Numero: " + numero + ". Bairro: " + bairro;
                    } else if (state.userLocation) {
                        state.endereco = "Localizacao por GPS";
                    } else {
                        var estadoFallback = data.usuario.estado || "";
                        state.endereco = "Cidade: " + cidade + (estadoFallback ? " - Estado: " + estadoFallback : "");
                    }
                    resumo.textContent = "Funcao: " + getFuncaoLabel(state.funcaoId) + " | Cidade: " + cidade + " | Endereco: " + state.endereco;
                    if (distanceCheck && distanceCheck.checked && !state.userLocation) {
                        showToastMessage("Ative sua localizacao para filtrar por distancia.", "warn");
                    }
                    if (!state.userLocation) {
                        fallbackGeocodeFromForm();
                    }
                    renderProfissionais();
                    setStep(3);
                }
            }

            if (action === "prev") {
                if (state.step === 2) {
                    setStep(1);
                } else if (state.step === 3) {
                    setStep(2);
                }
            }
        });

        if (searchInput) {
            searchInput.addEventListener("input", renderProfissionais);
        }
        if (sortSelect) {
            sortSelect.addEventListener("change", renderProfissionais);
        }
        if (onlyAvailableCheck) {
            onlyAvailableCheck.addEventListener("change", renderProfissionais);
        }
        if (distanceCheck) {
            distanceCheck.addEventListener("change", function () {
                if (distanceCheck.checked && !state.userLocation) {
                    showToastMessage("Ative sua localizacao para filtrar por distancia.", "warn");
                    fallbackGeocodeFromForm();
                }
                renderProfissionais();
            });
        }
        if (radiusInput) {
            radiusInput.addEventListener("input", renderProfissionais);
        }
        if (useLocationBtn) {
            useLocationBtn.addEventListener("click", function () {
                if (!navigator.geolocation) {
                    showToastMessage("Navegador nao suporta geolocalizacao.");
                    return;
                }
                setGeoStatus("Buscando localizacao...");
                navigator.geolocation.getCurrentPosition(function (pos) {
                    state.userLocation = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude
                    };
                    setGeoStatus("Localizacao capturada.");
                    requestReverseGeocode(state.userLocation.lat, state.userLocation.lng);
                    renderProfissionais();
                }, function () {
                    setGeoStatus("Nao foi possivel obter localizacao.");
                    showToastMessage("Nao foi possivel obter localizacao.");
                }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
            });
        }
    })();
</script>
