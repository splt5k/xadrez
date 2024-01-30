<?php
require 'include/functions.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tituloSite; ?> - Ranking de Xadrez</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="template/style.css" id="stylesheet"> <!-- Estilo padrão -->
    <link rel="stylesheet" type="text/css" href="template/style-dark.css" id="dark-stylesheet" disabled> <!-- Estilo do Modo Escuro desabilitado por padrão -->
    <style>
        /* Adicione um estilo de cursor apontando para toda a linha da tabela */
        .chess-table tbody tr {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Botão de Acesso ao Painel de Administração -->
            <a href="painel_admin.php" class="admin-button">Admin</a>
        </div>

        <h1>Ranking de Xadrez</h1>
        <div class="search-box">
            <input type="text" id="search" placeholder="Pesquisar por nome...">
        </div>
        <table class="chess-table">
            <thead>
                <tr>
                    <th>Posição</th>
                    <th>Nick</th>
                    <th>Classificação Atual</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $players = getPlayers();
                foreach ($players as $position => $player) {
                    echo "<tr onclick='redirectToProfile(" . $player['a_index'] . ")'>";
                    echo "<td>" . $position . "</td>";
                    echo "<td>" . $player['a_nick'] . "</td>";
                    echo "<td>" . $player['a_rating_atual'] . getStatus(0,$player['a_index'], false). "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <script src="template/script.js"></script>
    <script>
        // Função para redirecionar para o perfil do jogador
        function redirectToProfile(playerId) {
            window.location.href = 'profile.php?id=' + playerId;
        }
    </script>
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
