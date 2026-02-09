<?php
    include_once "includes/bootstrap.php";
    include_once "includes/auth.php";
    require_login('login.php', 'Voce precisa estar logado para acessar esta area.');
    require_role([1], 'login.php', 'Acesso restrito para administradores.');
?>

<?php

    for ($x=1; $x<100; $x++) {
        $nome_choose = rand(1,100);
        switch (true) {
            case $nome_choose == 100:
                $nome = 'Gabriel';
                break;
            case $nome_choose < 30:
                $nome = 'João';
                break;
            case $nome_choose < 60:
                $nome = 'Maria';
                break;
            default:
                $nome = 'Lucas';
                break;
        }
        $cidade_choose = rand(1,100);
        switch (true) {
            case $cidade_choose == 100:
                $cidade = 'Varginha';
                break;
            case $nome_choose < 30:
                $cidade = 'Machado';
                break;
            case $cidade_choose < 60:
                $cidade = 'Belo Horizonte';
                break;
            case $cidade_choose < 70:
                $cidade = 'Nepomuceno';
                break;
            case $cidade_choose < 80:
                $cidade = 'Patopolis';
                break;
            default:
                $cidade = 'Alfenas';
                break;
        }
        $genero ='M';

        $inserir = "INSERT INTO registro(nome, cpf, estado, cidade, sexo, cnpj, email, telefone, senha, servicos_ok, data_ani, funcao, atualizar)
        values ('$nome', '$x', '', '$cidade', '$genero', '0', '', '', '1234', '0', '1020-10-11', '2', '0')";
    $resultado = mysqli_query($conn, $inserir);
    }
    
    

    header("Location: index.php");
?>

<script>
    if (window.showToast) {
        showToast("<?php echo $result; ?>", "info");
    }
</script>