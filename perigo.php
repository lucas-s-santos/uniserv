<?php
    include_once("conexao.php");
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
    alert("<?php echo $result; ?>");
</script>