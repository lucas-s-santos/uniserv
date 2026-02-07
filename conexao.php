<?php
    $servidor = "localhost";
    $usuario = "root";
    $senha = "";                // senha vazia para XAMPP local
    $dbname = "relampagoservice";

    $conn = mysqli_connect($servidor, $usuario, $senha, $dbname);
    // debug temporário:
    // if (!$conn) { die('Erro de conexão (' . mysqli_connect_errno() . '): ' . mysqli_connect_error()); }
?>
