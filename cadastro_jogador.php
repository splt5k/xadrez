<?php
require 'include/functions.php';

if (!isUserLoggedIn()) {
    header('Location: painel_admin.php'); // Redirecione para a página de login
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = $_POST['nickname'];
    $rating_inicial = $_POST['rating_inicial'];
    $data_nascimento = $_POST['data_nascimento'];

    // Verifique se o nome de jogador já existe
    $checkQuery = "SELECT COUNT(*) as count FROM t_atletas WHERE a_nick = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $nickname);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo "O nome de jogador '$nickname' já existe. Por favor, escolha outro nome.";
    } else {
        // Inserir o novo jogador no banco de dados
        $insertQuery = "INSERT INTO t_atletas (a_nick, a_rating_inicial, a_rating_atual, a_vitorias, a_data_nascimento) VALUES (?, ?, ?, 0, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("siss", $nickname, $rating_inicial, $rating_inicial, $data_nascimento);
        
        if ($stmt->execute()) {
            echo "Jogador '$nickname' cadastrado com sucesso!";
        } else {
            echo "Erro ao cadastrar o jogador. Por favor, tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tituloSite; ?> - Cadastro de Jogador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="template/style.css" id="stylesheet"> <!-- Estilo padrão -->
    <link rel="stylesheet" type="text/css" href="template/style-dark.css" id="dark-stylesheet" disabled> <!-- Estilo do Modo Escuro desabilitado por padrão -->
</head>
<body>
    <div class="container">
        <h1>Cadastro de Jogador</h1>
        <form action="cadastro_jogador.php" method="post">
            <div class="form-group">
                <label for="nickname">Nome de Jogador:</label>
                <input type="text" id="nickname" name="nickname" required>
            </div>
            <div class="form-group">
                <label for="rating_inicial">Rating Inicial:</label>
                <input type="number" id="rating_inicial" name="rating_inicial" required>
            </div>
            <div class="form-group">
                <label for="data_nascimento">Data de Nascimento:</label>
                <input type="date" id="data_nascimento" name="data_nascimento" required>
            </div>
            <button type="submit" class="button">Cadastrar Jogador</button>
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

        
    <!-- Datagridview com os últimos 10 jogadores cadastrados -->
    <div class="datagrid">
        <h2>Últimos 10 Jogadores Cadastrados</h2>
        <table>
            <tr>
                <th>Nome de Jogador</th>
                <th>Rating Inicial</th>
                <th>Rating Atual</th>
                <th>Vitórias</th>
                <th></th>
            </tr>

            <?php
            // Consulta para obter os últimos 10 jogadores cadastrados
            $query = "SELECT * FROM t_atletas ORDER BY a_index DESC";
            $result = $conn->query($query);

            // Loop para exibir os jogadores
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td><span>' . $row['a_nick'] . '</span></td>';
                echo '<td><span>' . $row['a_rating_inicial'] . '</span></td>';
                echo '<td><span>' . $row['a_rating_atual'] . '</span></td>';
                echo '<td><span>' . $row['a_vitorias'] . '</span></td>';
                echo '<td><a href="editar_jogador.php?id=' . $row['a_index'] . '"><img src="template/img/edit_icon.png" alt="Editar" style="height: 1em; width: auto;"></a></td>';
                echo '</tr>';
            }
            ?>
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
