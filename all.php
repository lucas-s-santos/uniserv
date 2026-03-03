<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
<meta http-equiv="Cache-Control" content="no-cache" />
<meta name="keywords" content="HTML, CSS">
<meta name="description" content="Pagina inicial do fast services">
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.mask.min.js"></script>
<script>
                $("#cpf").mask("000.000.000-00");
                $("#cpf2").mask("000.000.000-00");
                $("#telefone").mask("(00)0 0000-0000");
                $("#data").mask("0000-00-00");
                $("#id_p").mask("0000000000");
                $("#id_p2").mask("0000000000");
                $("#preco").mask("000.00");

                (function () {
                    var MODAL_SELECTOR = ".hidden, .hidden_two";
                    var MODAL_OPEN_SELECTOR = ".hidden.is-open, .hidden_two.is-open";
                    var backdrop = null;
                    var activeModal = null;

                    function hasClosest(element) {
                        return element && typeof element.closest === "function";
                    }

                    function ensureBackdrop() {
                        if (backdrop && document.body.contains(backdrop)) {
                            return backdrop;
                        }
                        backdrop = document.getElementById("modal-backdrop");
                        if (!backdrop) {
                            backdrop = document.createElement("div");
                            backdrop.id = "modal-backdrop";
                            backdrop.className = "modal-backdrop";
                            backdrop.addEventListener("click", function () {
                                closeTopModal();
                            });
                            document.body.appendChild(backdrop);
                        }
                        return backdrop;
                    }

                    function getOpenModals() {
                        return Array.prototype.slice.call(document.querySelectorAll(MODAL_OPEN_SELECTOR));
                    }

                    function getModalTitle(modal) {
                        var titleText = modal.getAttribute("data-title");
                        var sourceTitle = modal.querySelector(".title");
                        if (!titleText && sourceTitle) {
                            titleText = sourceTitle.textContent.trim();
                        }
                        if (!titleText) {
                            titleText = "Detalhes";
                        }
                        if (sourceTitle) {
                            sourceTitle.classList.add("modal-source-title");
                        }
                        return titleText;
                    }

                    function ensureModalHeader(modal) {
                        if (!modal || modal.querySelector(".modal-header")) {
                            return;
                        }

                        var header = document.createElement("div");
                        header.className = "modal-header";

                        var title = document.createElement("div");
                        title.className = "modal-title";
                        title.textContent = getModalTitle(modal);

                        var closeBtn = document.createElement("button");
                        closeBtn.type = "button";
                        closeBtn.className = "modal-close";
                        closeBtn.setAttribute("aria-label", "Fechar janela");
                        closeBtn.textContent = "x";

                        header.appendChild(title);
                        header.appendChild(closeBtn);
                        modal.insertBefore(header, modal.firstChild);
                    }

                    function getFocusable(modal) {
                        var selector = "a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type='hidden']), select:not([disabled]), [tabindex]:not([tabindex='-1'])";
                        return Array.prototype.slice.call(modal.querySelectorAll(selector)).filter(function (el) {
                            return el.offsetParent !== null || el === document.activeElement;
                        });
                    }

                    function focusModal(modal) {
                        var focusables = getFocusable(modal);
                        if (focusables.length > 0) {
                            focusables[0].focus();
                            return;
                        }
                        modal.focus();
                    }

                    function prepareModal(modal) {
                        if (!modal) {
                            return;
                        }
                        ensureModalHeader(modal);
                        modal.setAttribute("role", "dialog");
                        modal.setAttribute("aria-modal", "true");
                        if (!modal.hasAttribute("tabindex")) {
                            modal.setAttribute("tabindex", "-1");
                        }
                    }

                    function updateModalState() {
                        var openModals = getOpenModals();
                        var hasOpen = openModals.length > 0;
                        var modalBackdrop = ensureBackdrop();

                        activeModal = hasOpen ? openModals[openModals.length - 1] : null;

                        if (hasOpen) {
                            openModals.forEach(function (modal) {
                                prepareModal(modal);
                            });
                        }

                        modalBackdrop.classList.toggle("is-open", hasOpen);
                        document.body.classList.toggle("modal-open", hasOpen);
                    }

                    function openModal(modal) {
                        if (!modal) {
                            return;
                        }
                        var openModals = getOpenModals();
                        openModals.forEach(function (openItem) {
                            if (openItem !== modal) {
                                openItem.classList.remove("is-open");
                            }
                        });
                        modal.__triggerElement = document.activeElement;
                        prepareModal(modal);
                        modal.classList.add("is-open");
                        updateModalState();
                        setTimeout(function () {
                            focusModal(modal);
                        }, 0);
                    }

                    function closeModal(modal) {
                        if (!modal) {
                            return;
                        }
                        var trigger = modal.__triggerElement;
                        modal.classList.remove("is-open");
                        updateModalState();
                        if (trigger && typeof trigger.focus === "function") {
                            setTimeout(function () {
                                try {
                                    trigger.focus();
                                } catch (e) {}
                            }, 0);
                        }
                    }

                    function toggleModal(modal) {
                        if (!modal) {
                            return;
                        }
                        if (modal.classList.contains("is-open")) {
                            closeModal(modal);
                            return;
                        }
                        openModal(modal);
                    }

                    function closeTopModal() {
                        var openModals = getOpenModals();
                        if (openModals.length === 0) {
                            return;
                        }
                        closeModal(openModals[openModals.length - 1]);
                    }

                    function getModalById(idOrElement) {
                        if (!idOrElement) {
                            return null;
                        }
                        if (typeof idOrElement !== "string") {
                            return idOrElement;
                        }
                        return document.getElementById(idOrElement);
                    }

                    document.addEventListener("click", function (event) {
                        if (hasClosest(event.target)) {
                            var closeBtn = event.target.closest(".modal-close");
                            if (closeBtn) {
                                var modalToClose = closeBtn.closest(MODAL_SELECTOR);
                                if (modalToClose) {
                                    closeModal(modalToClose);
                                }
                                return;
                            }

                            var closeTrigger = event.target.closest("[data-modal-close]");
                            if (closeTrigger) {
                                var closeId = closeTrigger.getAttribute("data-modal-close");
                                closeModal(getModalById(closeId));
                                return;
                            }

                            var openTrigger = event.target.closest("[data-modal-open]");
                            if (openTrigger) {
                                var openId = openTrigger.getAttribute("data-modal-open");
                                openModal(getModalById(openId));
                            }
                        }
                    });

                    document.addEventListener("keydown", function (event) {
                        var isEscape = event.key === "Escape" || event.keyCode === 27;
                        var isTab = event.key === "Tab" || event.keyCode === 9;

                        if (isEscape && activeModal) {
                            event.preventDefault();
                            closeTopModal();
                            return;
                        }

                        if (!isTab || !activeModal) {
                            return;
                        }

                        var focusables = getFocusable(activeModal);
                        if (focusables.length === 0) {
                            event.preventDefault();
                            activeModal.focus();
                            return;
                        }

                        var first = focusables[0];
                        var last = focusables[focusables.length - 1];

                        if (event.shiftKey && document.activeElement === first) {
                            event.preventDefault();
                            last.focus();
                        } else if (!event.shiftKey && document.activeElement === last) {
                            event.preventDefault();
                            first.focus();
                        }
                    });

                    window.openModalById = function (idOrElement) {
                        openModal(getModalById(idOrElement));
                    };

                    window.closeModalById = function (idOrElement) {
                        closeModal(getModalById(idOrElement));
                    };

                    window.toggleModalById = function (idOrElement) {
                        toggleModal(getModalById(idOrElement));
                    };

                    window.invisibleON = function (idOrElement) {
                        toggleModal(getModalById(idOrElement));
                    };

                    window.syncModalState = updateModalState;

                    function initModalSystem() {
                        ensureBackdrop();
                        updateModalState();
                    }

                    if (document.readyState === "loading") {
                        document.addEventListener("DOMContentLoaded", initModalSystem);
                    } else {
                        initModalSystem();
                    }
                })();

                function testeoCpf(Q) {
                    let x=0;
                    let caracter = [Q.substr(0, 1), Q.substr(1, 1), Q.substr(2, 1), Q.substr(3, 1), Q.substr(4, 1), Q.substr(5, 1), Q.substr(6, 1), Q.substr(7, 1), Q.substr(8, 1), Q.substr(9, 1), Q.substr(10, 1), Q.substr(11, 1), Q.substr(12, 1), Q.substr(13, 1), Q.substr(14, 1)];
                    var soma1=(10*caracter[0])+(9*caracter[1])+(8*caracter[2])+(7*caracter[4])+(6*caracter[5])+(5*caracter[6])+(4*caracter[8])+(3*caracter[9])+(2*caracter[10]);
                    var resto1=soma1%11;
                    var soma2=(11*caracter[0])+(10*caracter[1])+(9*caracter[2])+(8*caracter[4])+(7*caracter[5])+(6*caracter[6])+(5*caracter[8])+(4*caracter[9])+(3*caracter[10])+(2*caracter[12]);
                    var resto2=soma2%11;
                    
                    if (resto1==0 || resto1==1) {
                        var $N1=0;
                    }
                    else {
                        var $N1=11-resto1;
                    }
                    if (resto2==0 || resto2==1) {
                        var $N2=0;
                    }
                    else {
                        var $N2=11-resto2;
                    }

                    if (caracter[12]==$N1 && caracter[13]==$N2) {
                        return true;

                    }
                    else {
                        return false;
                    }
                }

                //Obs: essa função IsNumber não é minha!

            </script>
