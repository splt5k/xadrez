<?php
require 'include/functions.php';

// Verifique se um ID de jogador é fornecido
if (isset($_GET['id'])) {
    $playerId = $_GET['id'];
    $playerData = obterDadosJogador($playerId);
} else {
    // Redirecione ou trate o caso em que nenhum ID de jogador é fornecido
    // Você pode redirecionar o usuário ou exibir uma mensagem de erro
    header('Location: index.php'); // Redirecionar para a página inicial
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tituloSite; ?> - Perfil do Jogador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="template/style.css" id="stylesheet"> <!-- Estilo padrão -->
    <link rel="stylesheet" type="text/css" href="template/style-dark.css" id="dark-stylesheet" disabled> <!-- Estilo do Modo Escuro desabilitado por padrão -->
</head>
<body>
    <div class="container">
    <div class="header">
            <!-- Botão de Acesso ao inicio -->
            <a href="index.php" class="admin-button">Home</a>
        </div>

        <div class="player-profile">
    <h2>Informações do Jogador</h2>
    <p><strong>Nome:</strong> <?php echo $playerData['a_nick']; ?></p>
    <p><strong>Idade:</strong> <?php echo calcularIdade($playerData['a_data_nascimento']); ?> anos</p>
    <p><strong>Rating Inicial:</strong> <?php echo $playerData['a_rating_inicial']; ?></p>
    <p><strong>Rating Atual:</strong> <?php echo $playerData['a_rating_atual'] . getStatus(0,$playerId,false); ?></p>

</div>
        
<div class="datagrid">
    <?php
    $agendadosQuery = "SELECT * FROM t_confrontos WHERE a_resultado IS NULL AND (a_desafiado = ? OR a_desafiante = ?) ORDER BY a_index DESC";
    $agendadosStmt = $conn->prepare($agendadosQuery);
    $agendadosStmt->bind_param("ii", $playerId, $playerId);
    $agendadosStmt->execute();
    $agendadosResult = $agendadosStmt->get_result();
    if ($agendadosResult->num_rows > 0) {
        echo '<h2>Confrontos Agendados</h2>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Desafiado</th>';
        echo '<th>Desafiante</th>';
        echo '<th>Data</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($row = $agendadosResult->fetch_assoc()) {
            echo '<tr>';
            echo "<td><span>" . $row['a_index'] . "</span></td>";
            echo "<td><span style='color: " . $_cor_desafiado . ";'>" . obterNomeJogador($row['a_desafiado']) . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'],$row['a_desafiado'], true) : '') . "</span></td>";
            echo "<td><span style='color: " . $_cor_desafiante . ";'>" . obterNomeJogador($row['a_desafiante']) . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'],$row['a_desafiante'], true) : '') . "</span></td>";
            echo "<td><span>" . date('d/m/Y', strtotime($row['a_data'])) . "</span></td>";
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    ?>
</div>

        <div class="datagrid">
            <h2>Histórico de Confrontos</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Desafiado</th>
                        <th>Desafiante</th>
                        <th>Data</th>
                        <!--<th>Vencedor</th>-->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $historicoQuery = "SELECT * FROM t_confrontos WHERE a_resultado IS NOT NULL AND a_desafiado = ? OR a_desafiante = ? ORDER BY a_index DESC";
                    $historicoStmt = $conn->prepare($historicoQuery);
                    $historicoStmt->bind_param("ii", $playerId, $playerId);
                    $historicoStmt->execute();
                    $historicoResult = $historicoStmt->get_result();

                    while ($row = $historicoResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><span>" . $row['a_index'] . "</span></td>";
                        echo "<td><span style='color: " . $_cor_desafiado . ";'>" . obterNomeJogador($row['a_desafiado']) ." (".$row['a_desafiado_rating'].")" . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'],$row['a_desafiado'], true) : '') . "</span></td>";
                        echo "<td><span style='color: " . $_cor_desafiante . ";'>" . obterNomeJogador($row['a_desafiante']) ." (".$row['a_desafiante_rating'].")" . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'],$row['a_desafiante'], true) : '') . "</span></td>";
                        echo "<td><span>" . date('d/m/Y', strtotime($row['a_data'])) . "</span></td>";
                        //echo getStatusColor($row['a_resultado']);
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
