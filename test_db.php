<?php
$servidor = "localhost";
$usuario  = "root";
$senha    = "";            // deixe vazio se você resetou o root para sem senha
$dbname   = "relampagoservice";

$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);
if (!$conn) {
    echo "ERRO_CONEXAO: " . mysqli_connect_error();
    exit;
}
echo "OK_CONECTADO";
?>
