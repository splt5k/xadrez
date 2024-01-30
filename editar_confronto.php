<?php
require 'include/functions.php';

if (!isUserLoggedIn()) {
    header('Location: painel_admin.php'); // Redirecione para a página de login
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // Verificar se o confronto com o ID especificado existe
    $confrontoId = $_GET['id'];
    $confrontoQuery = "SELECT * FROM t_confrontos WHERE a_index = ?";
    $stmt = $conn->prepare($confrontoQuery);
    $stmt->bind_param("i", $confrontoId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $confronto = $result->fetch_assoc();
    } else {
        // Redirecionar ou mostrar uma mensagem de erro, pois o confronto não existe
        header('Location: painel_admin.php');
        exit();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confrontoId = $_GET['id'];
    $desafiado = $_POST['desafiado'];
    $desafiante = $_POST['desafiante'];
    $data = $_POST['data'];

    if ($desafiado == $desafiante) {
        echo "O desafiado não pode ser igual ao desafiante. Por favor, selecione jogadores diferentes.";
        exit();
    }
    else
    {
        // Verifique se o confronto pode ser atualizado com base no valor de a_resultado
        $checkQuery = "SELECT a_resultado FROM t_confrontos WHERE a_index = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("i", $confrontoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['a_resultado'] === null) {
            // Atualizar o confronto no banco de dados
            $updateQuery = "UPDATE t_confrontos SET a_desafiado = ?, a_desafiante = ?, a_data = ?,a_desafiado_rating = (SELECT a_rating_atual FROM t_atletas WHERE a_index = ?), a_desafiante_rating = (SELECT a_rating_atual FROM t_atletas WHERE a_index = ?) WHERE a_index = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("iisiii", $desafiado, $desafiante, $data, $desafiado, $desafiante, $confrontoId);

            if ($stmt->execute()) {
                echo "Confronto atualizado com sucesso!";
                header('Location: cadastro_confronto.php');
                // Redirecionar para alguma página, se necessário
            } else {
                echo "Erro ao atualizar o confronto. Por favor, tente novamente. Erro: " . $stmt->error;
            }
        } else {
            echo "O confronto não pode ser atualizado, pois o resultado já foi definido.";
        }
    }
}

else {
    // Redirecionar ou mostrar uma mensagem de erro, pois não foi fornecido o ID do confronto
    header('Location: painel_admin.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tituloSite; ?> - Editar Confronto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="template/style.css" id="stylesheet"> <!-- Estilo padrão -->
    <link rel="stylesheet" type="text/css" href="template/style-dark.css" id="dark-stylesheet" disabled> <!-- Estilo do Modo Escuro desabilitado por padrão -->
</head>
<body>
    <div class="container">
        <h1>Editar Confronto</h1>
        <form action="editar_confronto.php?id=<?php echo $confronto['a_index']; ?>" method="post">
            <div class="form-group">
                <label for="desafiado">Desafiado:</label>
                <select id="desafiado" name="desafiado" required>
                    <?php
                    $query = "SELECT a_index, a_nick FROM t_atletas ORDER BY a_nick ASC";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        $selected = ($row['a_index'] == $confronto['a_desafiado']) ? 'selected' : '';
                        echo '<option value="' . $row['a_index'] . '" ' . $selected . '>' . $row['a_nick'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="desafiante">Desafiante:</label>
                <select id="desafiante" name="desafiante" required>
                    <?php
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()) {
                        $selected = ($row['a_index'] == $confronto['a_desafiante']) ? 'selected' : '';
                        echo '<option value="' . $row['a_index'] . '" ' . $selected . '>' . $row['a_nick'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required value="<?php echo $confronto['a_data']; ?>">
            </div>
            <button type="submit" class="button">Atualizar Confronto</button>
            <button type="button" class="button" id="voltarButton">Voltar</button>
        </form>
        <script>
            const voltarButton = document.getElementById("voltarButton");

            voltarButton.addEventListener("click", function() {
                window.location.href = "cadastro_confronto.php";
            });
        </script>
    </div>
    <script>
        // Função para verificar a preferência de cor do usuário
        const checkColorScheme = () => {
            const darkStyleSheet = document.getElementById('dark-stylesheet');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (systemPrefersDark) {
                // Se o sistema do usuário preferir o modo escuro, habilite o estilo do modo escuro
                darkStyleSheet.removeAttribute('disabled');
            } else {
                // Caso contrário, mantenha o estilo padrão
                darkStyleSheet.setAttribute('disabled', 'true');
            }
        };

        // Verifique a preferência de cor do usuário quando a página é carregada
        checkColorScheme();

        // Assine alterações na preferência de cor do sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', checkColorScheme);
    </script>  
</body>
</html>
