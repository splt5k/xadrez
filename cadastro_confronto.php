<?php
require 'include/functions.php';

if (!isUserLoggedIn()) {
    header('Location: painel_admin.php'); // Redirecione para a página de login
    exit();
}

// Consulta para obter a lista de jogadores disponíveis
$query = "SELECT a_index, a_nick FROM t_atletas ORDER BY a_nick ASC";
$result = $conn->query($query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desafiado = $_POST['desafiado'];
    $desafiante = $_POST['desafiante'];
    $data = $_POST['data'];

    // Garantir que o desafiado seja diferente do desafiante
    if ($desafiado == $desafiante) {
        echo "O desafiado não pode ser igual ao desafiante. Por favor, selecione jogadores diferentes.";
    } else {
        // Inserir o novo confronto no banco de dados
        $insertQuery = "INSERT INTO t_confrontos (a_desafiado, a_desafiado_rating, a_desafiante, a_desafiante_rating, a_data) VALUES (?, (SELECT a_rating_atual FROM t_atletas WHERE a_index = ?),?,(SELECT a_rating_atual FROM t_atletas WHERE a_index = ?), ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iiiis", $desafiado,$desafiado,$desafiante, $desafiante, $data);

        if ($stmt->execute()) {
            echo "Confronto cadastrado com sucesso!";
        } else {
            echo "Erro ao cadastrar o confronto. Por favor, tente novamente. Erro: " . $stmt->error;;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tituloSite; ?> - Cadastro de Confronto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="template/style.css" id="stylesheet"> <!-- Estilo padrão -->
    <link rel="stylesheet" type="text/css" href="template/style-dark.css" id="dark-stylesheet" disabled> <!-- Estilo do Modo Escuro desabilitado por padrão -->
</head>
<body>
    <div class="container">
        <h1>Cadastro de Confronto</h1>
        <form action="cadastro_confronto.php" method="post">
            <div class="form-group">
                <label for="desafiado">Desafiado:</label>
                <select id="desafiado" name="desafiado" required>
                    <?php
                    // Loop para preencher o menu suspenso com os jogadores disponíveis
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['a_index'] . '">' . $row['a_nick'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="desafiante">Desafiante:</label>
                <select id="desafiante" name="desafiante" required>
                    <?php
                    // Reinicie a consulta para preencher o menu suspenso do desafiante
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['a_index'] . '">' . $row['a_nick'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            <button type="submit" class="button">Cadastrar Confronto</button>
            <button type="button" class="button" id="voltarButton">Voltar para o Painel</button>
        </form>
        <script>
            // Selecionar o botão "Voltar" pelo ID
            const voltarButton = document.getElementById("voltarButton");

            // Adicionar um evento de clique ao botão
            voltarButton.addEventListener("click", function() {
                // Redirecionar para a página "index.php" quando o botão for clicado
                window.location.href = "painel_admin.php";
            });
            </script>
        <br>

        
        <div class="datagrid">
            <h2>Últimos 10 Confrontos</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Desafiado</th>
                        <th>Desafiante</th>
                        <th>Data</th>
                        <th>Vencedor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $confrontosQuery = "SELECT * FROM t_confrontos ORDER BY a_index DESC LIMIT 10";
                    $confrontosResult = $conn->query($confrontosQuery);

                    while ($row = $confrontosResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><span>" . $row['a_index'] . "</span></td>";
                        echo "<td><span style='color: " . $_cor_desafiado . ";'>" . obterNomeJogador($row['a_desafiado']) . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'],$row['a_desafiado'], true) : '') . "</span></td>";
                        echo "<td><span style='color: " . $_cor_desafiante . ";'>" . obterNomeJogador($row['a_desafiante']) . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'],$row['a_desafiante'], true) : '') . "</span></td>";
                        echo "<td><span>" . date('d/m/Y', strtotime($row['a_data'])) . "</span></td>";
                        echo getStatusColor($row['a_resultado']);
                        if ($row['a_resultado'] === null)
                        {
                            echo '<td><a href="editar_confronto.php?id=' . $row['a_index'] . '"><img src="template/img/edit_icon.png" alt="Editar" style="height: 1em; width: auto;"></a></td>';
                        }
                        else
                        {
                            echo '<td></td>';
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

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
