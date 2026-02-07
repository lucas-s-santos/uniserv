<?php
	session_start();
	
	unset(
		$_SESSION['nome'],
		$_SESSION['cpf'],
		$_SESSION['funcao'],
		$_SESSION['id_acesso']
	);
	header("Location: index.php");
?>