<?php
    include_once("conexao.php");
?>

<?php

    for ($x=1; $x<108; $x++) {
        $func = rand(1,6);
        $inserir = "INSERT INTO trabalhador_funcoes(funcoes_id_funcoes, registro_id_registro, certificado, valor_hora, disponivel)
        values ('$func','$x', '', '30', '0')";
    $resultado = mysqli_query($conn, $inserir);
    }
    
    

    header("Location: index.php");
?>