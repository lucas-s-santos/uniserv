<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
            <meta name="author" content="Gabriel Nepomuceno de Almeida dos Santos">
            <meta http-equiv="Cache-Control" content="no-cache" />
            <!--<meta http-equiv="refresh" content="30">-->
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

                function invisibleON(X) {
                    let edit = document.getElementById(X);

                    if (!edit) {
                        return;
                    }

                    let backdrop = document.getElementById("modal-backdrop");
                    if (!backdrop) {
                        backdrop = document.createElement("div");
                        backdrop.id = "modal-backdrop";
                        backdrop.className = "modal-backdrop";
                        backdrop.addEventListener("click", function () {
                            let openModal = document.querySelector(".hidden.is-open, .hidden_two.is-open");
                            if (openModal) {
                                openModal.classList.remove("is-open");
                            }
                            updateBackdrop(backdrop);
                        });
                        document.body.appendChild(backdrop);
                    }

                    ensureModalHeader(edit, backdrop);
                    edit.classList.toggle("is-open");
                    updateBackdrop(backdrop);
                }

                function updateBackdrop(backdrop) {
                    let hasOpen = document.querySelector(".hidden.is-open, .hidden_two.is-open");
                    if (hasOpen) {
                        backdrop.classList.add("is-open");
                    } else {
                        backdrop.classList.remove("is-open");
                    }
                }

                function ensureModalHeader(modal, backdrop) {
                    if (modal.querySelector(".modal-header")) {
                        return;
                    }

                    let titleText = modal.getAttribute("data-title");
                    if (!titleText) {
                        let titleEl = modal.querySelector(".title");
                        if (titleEl) {
                            titleText = titleEl.textContent.trim();
                        }
                    }
                    if (!titleText) {
                        titleText = "Detalhes";
                    }

                    let header = document.createElement("div");
                    header.className = "modal-header";

                    let title = document.createElement("div");
                    title.className = "modal-title";
                    title.textContent = titleText;

                    let closeBtn = document.createElement("button");
                    closeBtn.type = "button";
                    closeBtn.className = "modal-close";
                    closeBtn.textContent = "Fechar";
                    closeBtn.addEventListener("click", function () {
                        modal.classList.remove("is-open");
                        updateBackdrop(backdrop);
                    });

                    header.appendChild(title);
                    header.appendChild(closeBtn);
                    modal.insertBefore(header, modal.firstChild);
                }

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
            <title>Menu</title>
    </head>