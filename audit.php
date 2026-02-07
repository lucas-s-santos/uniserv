<?php
    function audit_log($conn, $acao, $entidade, $entidade_id = null, $detalhes = null) {
        $usuario_id = isset($_SESSION['id_acesso']) ? (int)$_SESSION['id_acesso'] : null;
        $acao = mysqli_real_escape_string($conn, $acao);
        $entidade = mysqli_real_escape_string($conn, $entidade);
        $entidade_id = $entidade_id !== null ? (int)$entidade_id : null;
        $detalhes = $detalhes !== null ? mysqli_real_escape_string($conn, $detalhes) : null;
        $agora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO audit_log(registro_id_registro, acao, entidade, entidade_id, detalhes, data_acao) 
                VALUES (" . ($usuario_id !== null ? $usuario_id : 'NULL') . ", '$acao', '$entidade', " . ($entidade_id !== null ? $entidade_id : 'NULL') . ", " . ($detalhes !== null ? "'$detalhes'" : 'NULL') . ", '$agora')";
        mysqli_query($conn, $sql);
    }
?>