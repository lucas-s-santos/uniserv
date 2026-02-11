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
    <div class="wizard-progress">
        <div class="wizard-progress__track">
            <div class="wizard-progress__step is-active" data-step="1">
                <div class="wizard-progress__circle">1</div>
                <div class="wizard-progress__label">Escolher servico</div>
            </div>
            <div class="wizard-progress__line"></div>
            <div class="wizard-progress__step" data-step="2">
                <div class="wizard-progress__circle">2</div>
                <div class="wizard-progress__label">Local do chamado</div>
            </div>
            <div class="wizard-progress__line"></div>
            <div class="wizard-progress__step" data-step="3">
                <div class="wizard-progress__circle">3</div>
                <div class="wizard-progress__label">Selecionar profissional</div>
            </div>
        </div>
    </div>

    <div class="wizard-panel is-active" data-step="1">
        <div class="wizard-content">
            <div class="wizard-intro">
                <div class="wizard-intro__icon">🛠️</div>
                <h2 class="wizard-intro__title">Qual servico voce precisa?</h2>
                <p class="wizard-intro__subtitle">Escolha o tipo de servico que deseja contratar</p>
            </div>
            <div class="wizard-form">
                <div class="form-card">
                    <div class="campo-texto campo-texto--large">
                        <label>Selecione o servico</label>
                        <select id="wizardFuncao" class="select-large">
                            <option value="">Escolha uma opcao...</option>
                            <?php foreach ($funcoes as $funcao) { ?>
                                <option value="<?php echo (int)$funcao['id']; ?>"><?php echo htmlspecialchars($funcao['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="wizard-actions">
                    <button type="button" class="btn btn-primary btn-large" data-action="next">
                        Continuar →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="wizard-panel" data-step="2">
        <div class="wizard-content">
            <div class="wizard-intro">
                <div class="wizard-intro__icon">📍</div>
                <h2 class="wizard-intro__title">Onde sera o servico?</h2>
                <p class="wizard-intro__subtitle" id="wizardCidadeAtual">Informe o local para encontrarmos profissionais proximos</p>
            </div>
            <div class="wizard-form">
                <div class="form-card">
                    <div class="form-card__section">
                        <div class="form-card__title">Local do atendimento</div>
                        <div class="campo-texto campo-texto--large full">
                            <label>Cidade</label>
                            <select id="wizardCidade" class="select-large"></select>
                        </div>
                    </div>
                    <div class="form-card__section">
                        <div class="form-card__title">Endereco completo</div>
                        <div class="form-grid">
                            <div class="campo-texto">
                                <label>Rua</label>
                                <input type="text" id="wizardRua" placeholder="Nome da rua">
                            </div>
                            <div class="campo-texto">
                                <label>Numero</label>
                                <input type="text" id="wizardNumero" placeholder="Nº">
                            </div>
                            <div class="campo-texto full">
                                <label>Bairro</label>
                                <input type="text" id="wizardBairro" placeholder="Nome do bairro">
                            </div>
                        </div>
                    </div>
                    <div class="form-card__section">
                        <div class="form-card__title">Localizacao GPS (opcional)</div>
                        <button type="button" class="btn btn-ghost btn-block" id="wizardUseLocation">
                            <span>📡</span> Usar minha localizacao
                        </button>
                        <div class="geo-status" id="wizardGeoStatus"></div>
                    </div>
                </div>
                <div class="wizard-actions">
                    <button type="button" class="btn btn-ghost" data-action="prev">
                        ← Voltar
                    </button>
                    <button type="button" class="btn btn-primary btn-large" data-action="next">
                        Continuar →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="wizard-panel" data-step="3">
        <div class="wizard-results">
            <div class="results-header">
                <div class="results-summary">
                    <button type="button" class="btn btn-ghost btn-small" data-action="prev">
                        ← Voltar
                    </button>
                    <div class="results-summary__info">
                        <div class="results-summary__icon">📋</div>
                        <div>
                            <div class="results-summary__title">Resumo do chamado</div>
                            <div class="results-summary__text" id="wizardResumo"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="results-content">
                <aside class="results-sidebar">
                    <div class="filter-card">
                        <div class="filter-card__header">
                            <div class="filter-card__icon">🔍</div>
                            <div class="filter-card__title">Filtros e busca</div>
                        </div>
                        <div class="filter-card__body">
                            <div class="campo-texto">
                                <label>Buscar profissional</label>
                                <input type="text" id="wizardSearch" placeholder="Digite o apelido...">
                            </div>
                            <div class="campo-texto">
                                <label>Ordenar por</label>
                                <select id="wizardSort">
                                    <option value="preco-asc">💰 Menor preco</option>
                                    <option value="preco-desc">💵 Maior preco</option>
                                </select>
                            </div>
                            <div class="filter-divider"></div>
                            <div class="campo-texto">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="wizardOnlyAvailable">
                                    <span>Apenas disponiveis</span>
                                </label>
                            </div>
                            <div class="filter-divider"></div>
                            <div class="campo-texto">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="wizardDistanceFilter">
                                    <span>Filtrar por distancia</span>
                                </label>
                            </div>
                            <div class="campo-texto">
                                <label>Raio maximo (km)</label>
                                <input type="number" id="wizardRadius" min="1" max="100" value="10">
                            </div>
                        </div>
                    </div>
                    <div class="map-card">
                        <div class="map-card__header">🗺️ Mapa dos profissionais</div>
                        <div class="wizard-map" id="wizardMap"></div>
                    </div>
                </aside>
                <div class="results-main">
                    <div class="results-main__header">
                        <div class="results-count" id="resultsCount">Carregando profissionais...</div>
                    </div>
                    <div class="wizard-cards" id="wizardCards"></div>
                </div>
            </div>
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
            wizard.querySelectorAll(".wizard-progress__step").forEach(function (item) {
                var itemStep = Number(item.dataset.step);
                item.classList.toggle("is-active", itemStep === step);
                item.classList.toggle("is-completed", itemStep < step);
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

            var resultsCount = document.getElementById("resultsCount");
            if (resultsCount) {
                resultsCount.textContent = lista.length + " profissional(is) encontrado(s)";
            }

            if (!lista.length) {
                var empty = document.createElement("div");
                empty.className = "collab-empty";
                empty.innerHTML = "<div class='collab-empty__icon'>🔍</div><div class='collab-empty__text'>Nenhum profissional encontrado</div><p class='collab-empty__hint'>Tente ajustar os filtros ou escolher outra cidade</p>";
                cardsContainer.appendChild(empty);
                return;
            }

            lista.forEach(function (item) {
                var card = document.createElement("div");
                card.className = "pro-card";
                
                var isUnavailable = !item.disponivel || item.ocupado || item.self;
                var noPayment = !item.hasPix && !item.acceptsCash && !item.acceptsCard;
                if (isUnavailable || noPayment) {
                    card.classList.add("pro-card--disabled");
                }

                var header = document.createElement("div");
                header.className = "pro-card__header";

                var avatar = document.createElement("div");
                avatar.className = "pro-card__avatar";
                var initials = item.apelido.substring(0, 2).toUpperCase();
                avatar.textContent = initials;

                var info = document.createElement("div");
                info.className = "pro-card__info";

                var name = document.createElement("div");
                name.className = "pro-card__name";
                name.textContent = item.apelido;

                var location = document.createElement("div");
                location.className = "pro-card__location";
                location.innerHTML = "📍 " + item.cidade + ", " + item.estado;

                info.appendChild(name);
                info.appendChild(location);
                header.appendChild(avatar);
                header.appendChild(info);

                var price = document.createElement("div");
                price.className = "pro-card__price";
                price.innerHTML = "<div class='pro-card__price-label'>Valor/hora</div><div class='pro-card__price-value'>R$ " + item.valorHora + "</div>";

                var meta = document.createElement("div");
                meta.className = "pro-card__meta";
                
                if (item._dist !== null && item._dist !== undefined) {
                    var dist = document.createElement("div");
                    dist.className = "pro-card__meta-item";
                    dist.innerHTML = "📏 <span>" + item._dist.toFixed(1) + " km</span>";
                    meta.appendChild(dist);
                }

                var payment = document.createElement("div");
                payment.className = "pro-card__meta-item";
                var paymentMethods = [];
                if (item.hasPix) paymentMethods.push("PIX");
                if (item.acceptsCash) paymentMethods.push("💵");
                if (item.acceptsCard) paymentMethods.push("💳");
                payment.innerHTML = "💰 <span>" + (paymentMethods.length ? paymentMethods.join(", ") : "Sem pagamento") + "</span>";
                meta.appendChild(payment);

                var status = document.createElement("div");
                status.className = "pro-card__status";
                if (!item.disponivel) {
                    status.className += " pro-card__status--unavailable";
                    status.textContent = "❌ Indisponível";
                } else if (item.ocupado) {
                    status.className += " pro-card__status--busy";
                    status.textContent = "⏳ Em serviço";
                } else if (item.self) {
                    status.className += " pro-card__status--self";
                    status.textContent = "👤 Seu perfil";
                } else if (noPayment) {
                    status.className += " pro-card__status--warning";
                    status.textContent = "⚠️ Sem pagamento";
                } else {
                    status.className += " pro-card__status--available";
                    status.textContent = "✓ Disponível";
                }

                var actions = document.createElement("div");
                actions.className = "pro-card__actions";
                if (isUnavailable || noPayment) {
                    var reason = document.createElement("div");
                    reason.className = "pro-card__hint";
                    reason.textContent = noPayment ? "Este profissional não configurou formas de pagamento" : "Profissional não disponível no momento";
                    actions.appendChild(reason);
                } else {
                    var link = document.createElement("a");
                    link.className = "btn btn-primary btn-block";
                    link.innerHTML = "Ver perfil e solicitar →";
                    link.href = buildPerfilLink(item.idTrafun);
                    actions.appendChild(link);
                }

                card.appendChild(header);
                card.appendChild(price);
                card.appendChild(meta);
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
