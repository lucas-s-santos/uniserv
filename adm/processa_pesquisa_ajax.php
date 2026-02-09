<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['cpf']) || (int)$_SESSION['funcao'] !== 1) {
        http_response_code(403);
        exit;
    }

    include_once __DIR__ . '/../conexao.php';

    $nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
    $cpf = isset($_GET['cpf']) ? trim($_GET['cpf']) : '';

    $nome_like = "%" . $nome . "%";
    $cpf_like = "%" . $cpf . "%";

    $stmt = $conn->prepare("SELECT * FROM registro WHERE nome LIKE ? AND cpf LIKE ?");
    $stmt->bind_param("ss", $nome_like, $cpf_like);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $cont = 0;
    while ($linha = mysqli_fetch_array($resultado)) {
        $cont++;
        echo "<tr><td data-label='ID'>$linha[id_registro]</td><td data-label='Nome'>$linha[nome]</td><td data-label='CPF'>$linha[cpf]</td><td data-label='Estado'>$linha[estado]</td><td data-label='Cidade'>$linha[cidade]</td><td data-label='Genero'>";
        switch ($linha['sexo']) {
            case 'M': echo "Masculino"; break;
            case 'F': echo "Feminino"; break;
            case 'P': echo "Não falar"; break;
            default: echo "Outro"; break;
        }
        echo "</td><td data-label='CNPJ'>$linha[cnpj]</td><td data-label='Email'>$linha[email]</td><td data-label='Telefone'>$linha[telefone]</td><td data-label='Servicos prestados'>$linha[servicos_ok]</td><td data-label='Funcao'>";
        switch ($linha['funcao']) {
            case '1': echo "Administrador"; break;
            case '2': echo "Colaborador"; break;
            default: echo "Cliente"; break;
        }
                echo "</td><td data-label='Acoes'>
                                <div class='admin-table-actions'>
                                        <button type='button' class='btn btn-small btn-ghost admin-edit' data-id='$linha[id_registro]'>Editar</button>
                                        <button type='button' class='btn btn-small btn-ghost admin-delete' data-id='$linha[id_registro]'>Excluir</button>
                                </div>
                            </td></tr>";
    }

    $stmt->close();
    echo "<tr><td data-label='ID'>(X)</td><td data-label='Nome'>($cont) Resultados</td></tr>";
?>
