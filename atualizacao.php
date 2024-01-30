<?php
require 'include/functions.php';

if (!isUserLoggedIn()) {
    header('Location: painel_admin.php'); // Redirecione para a página de login
    exit();
}


// Verifique se há uma solicitação de desfazer
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['desfazer'])) {
    $confrontoId = $_GET['desfazer'];

    // Obtenha os dados do confronto que você deseja desfazer
    $_confronto = obterConfrontoPorId($confrontoId);

    if ($_confronto !== null) {
        // Obtenha os ratings originais dos atletas antes da atualização do confronto
        $desafiadoRatingOriginal = $_confronto['a_desafiado_rating'];
        $desafianteRatingOriginal = $_confronto['a_desafiante_rating'];

        if ($desafiadoRatingOriginal !== null && $desafianteRatingOriginal !== null) {
            // Atualize os ratings dos atletas com os valores originais
            $_updateP1Query = "UPDATE t_atletas SET a_rating_atual = ? WHERE a_index = ?";
            $_updateP1Stmt = $conn->prepare($_updateP1Query);
            $_updateP1Stmt->bind_param("ii", $desafiadoRatingOriginal, $_confronto['a_desafiado']);

            if (!$_updateP1Stmt->execute()) {
                echo "Erro ao atualizar o rating do desafiado. Por favor, tente novamente. Erro: " . $_updateP1Stmt->error;
            }

            $_updateP2Query = "UPDATE t_atletas SET a_rating_atual = ? WHERE a_index = ?";
            $_updateP2Stmt = $conn->prepare($_updateP2Query);
            $_updateP2Stmt->bind_param("ii", $desafianteRatingOriginal, $_confronto['a_desafiante']);

            if (!$_updateP2Stmt->execute()) {
                echo "Erro ao atualizar o rating do desafiante. Por favor, tente novamente. Erro: " . $_updateP2Stmt->error;
            }

            // Defina a_resultado como NULL para o confronto
            $updateQuery = "UPDATE t_confrontos SET a_resultado = NULL, a_desafiado_pontos = NULL, a_desafiante_pontos = NULL WHERE a_index = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $confrontoId);

            if ($stmt->execute()) {
                // Redirecione de volta para a página principal ou exiba uma mensagem de sucesso
                header('Location: atualizacao.php');
                exit();
            } else {
                echo "Erro ao desfazer o confronto. Por favor, tente novamente. Erro: " . $stmt->error;
            }
        } else {
            echo "Erro ao obter os ratings originais dos atletas.";
        }
    } else {
        echo "Confronto não encontrado.";
    }
}elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confrontoId = $_POST['confronto_id'];
    $resultado = $_POST['resultado'];
    
    // Obter os dados do confronto
    $_confronto = obterConfrontoPorId($confrontoId);
    $_p1NewRating = $_confronto['a_desafiado_rating'];
    $_p2NewRating = $_confronto['a_desafiante_rating'];

    $_p1RatingDiff = 0;
    $_p2RatingDiff = 0;

    // o $resultado pode ser "desafiado", "desafiante", "empate" ou "WO escreva uma logica que atualize o rating dos jogadores sendo que o Calc precisa receber o rating1, rating2 e o resultado que pode ser 1, 0.5 ou 0 dependendo se o desafiado ou desafiante ganhou e deve calcular separadamente o rating de cada um
    switch ($resultado) {
        case 'desafiado':
            $_p1NewRating = Calc($_confronto['a_desafiado_rating'], $_confronto['a_desafiante_rating'], 1);
            $_p2NewRating = Calc($_confronto['a_desafiante_rating'], $_confronto['a_desafiado_rating'], 0);
            break;
        case 'desafiante':
            $_p1NewRating = Calc($_confronto['a_desafiado_rating'], $_confronto['a_desafiante_rating'], 0);
            $_p2NewRating = Calc($_confronto['a_desafiante_rating'], $_confronto['a_desafiado_rating'], 1);
            break;
        case 'empate':
            $_p1NewRating = Calc($_confronto['a_desafiado_rating'], $_confronto['a_desafiante_rating'], 0.5);
            $_p2NewRating = Calc($_confronto['a_desafiante_rating'], $_confronto['a_desafiado_rating'], 0.5);
            break;
        default:
            break;
    }

    // Calcular a diferença de rating
    $_p1RatingDiff = $_p1NewRating - $_confronto['a_desafiado_rating'];
    $_p2RatingDiff = $_p2NewRating - $_confronto['a_desafiante_rating'];

    // Adicionar o sinal "+" para números positivos
    if ($_p1RatingDiff > 0) {
        $_p1RatingDiff = '+' . $_p1RatingDiff;
    }

    if ($_p2RatingDiff > 0) {
        $_p2RatingDiff = '+' . $_p2RatingDiff;
    }

    // Atualizar o rating do jogador 1 no banco de dados
    $_updateP1Query = "UPDATE t_atletas SET a_rating_atual = ? WHERE a_index = ?";
    $_updateP1Stmt = $conn->prepare($_updateP1Query);
    $_updateP1Stmt->bind_param("ii", $_p1NewRating, $_confronto['a_desafiado']);

    if (!$_updateP1Stmt->execute()) {
        echo "Erro ao atualizar o rating do jogador 1. Por favor, tente novamente. Erro: " . $_updateP1Stmt->error;
    }

    // Atualizar o rating do jogador 2 no banco de dados
    $_updateP2Query = "UPDATE t_atletas SET a_rating_atual = ? WHERE a_index = ?";
    $_updateP2Stmt = $conn->prepare($_updateP2Query);
    $_updateP2Stmt->bind_param("ii", $_p2NewRating, $_confronto['a_desafiante']);

    if (!$_updateP2Stmt->execute()) {
        echo "Erro ao atualizar o rating do jogador 2. Por favor, tente novamente. Erro: " . $_updateP2Stmt->error;
    }

    // Atualizar o resultado do confronto no banco de dados
    $updateQuery = "UPDATE t_confrontos SET a_resultado = ?, a_desafiado_pontos = ?, a_desafiante_pontos = ? WHERE a_index = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sssi", $resultado, $_p1RatingDiff, $_p2RatingDiff, $confrontoId);

    if ($stmt->execute()) {
        echo "Resultado atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o resultado. Por favor, tente novamente. Erro: " . $stmt->error;
    }

    // Atualizar o rating em todos os confrontos futuros em que o jogador 1 estiver envolvido como desafiado e o resultado ainda não tiver sido definido
    $_updateFuturosP1_1Query = "UPDATE t_confrontos SET a_desafiado_rating = ? WHERE a_resultado IS NULL AND a_desafiado = ?";
    $_updateFuturosP1_1Stmt = $conn->prepare($_updateFuturosP1_1Query);
    $_updateFuturosP1_1Stmt->bind_param("ii", $_p1NewRating, $_confronto['a_desafiado']);

    if (!$_updateFuturosP1_1Stmt->execute()) {
        echo "Erro ao atualizar o rating do jogador 1 em confrontos futuros como desafiado. Por favor, tente novamente. Erro: " . $_updateFuturosP1_1Stmt->error;
    }

    // Atualizar o rating em todos os confrontos futuros em que o jogador 1 estiver envolvido como desafiante e o resultado ainda não tiver sido definido
    $_updateFuturosP1_2Query = "UPDATE t_confrontos SET a_desafiante_rating = ? WHERE a_resultado IS NULL AND a_desafiante = ?";
    $_updateFuturosP1_2Stmt = $conn->prepare($_updateFuturosP1_2Query);
    $_updateFuturosP1_2Stmt->bind_param("ii", $_p1NewRating, $_confronto['a_desafiado']);

    if (!$_updateFuturosP1_2Stmt->execute()) {
        echo "Erro ao atualizar o rating do jogador 1 em confrontos futuros como desafiante. Por favor, tente novamente. Erro: " . $_updateFuturosP1_2Stmt->error;
    }

    // Atualizar o rating em todos os confrontos futuros em que o jogador 2 estiver envolvido como desafiado e o resultado ainda não tiver sido definido
    $_updateFuturosP2_1Query = "UPDATE t_confrontos SET a_desafiado_rating = ? WHERE a_resultado IS NULL AND a_desafiado = ?";
    $_updateFuturosP2_1Stmt = $conn->prepare($_updateFuturosP2_1Query);
    $_updateFuturosP2_1Stmt->bind_param("ii", $_p2NewRating, $_confronto['a_desafiante']);

    if (!$_updateFuturosP2_1Stmt->execute()) {
        echo "Erro ao atualizar o rating do jogador 2 em confrontos futuros como desafiado. Por favor, tente novamente. Erro: " . $_updateFuturosP2_1Stmt->error;
    }

    // Atualizar o rating em todos os confrontos futuros em que o jogador 2 estiver envolvido como desafiante e o resultado ainda não tiver sido definido
    $_updateFuturosP2_2Query = "UPDATE t_confrontos SET a_desafiante_rating = ? WHERE a_resultado IS NULL AND a_desafiante = ?";
    $_updateFuturosP2_2Stmt = $conn->prepare($_updateFuturosP2_2Query);
    $_updateFuturosP2_2Stmt->bind_param("ii", $_p2NewRating, $_confronto['a_desafiante']);

    if (!$_updateFuturosP2_2Stmt->execute()) {
        echo "Erro ao atualizar o rating do jogador 2 em confrontos futuros como desafiante. Por favor, tente novamente. Erro: " . $_updateFuturosP2_2Stmt->error;
    }

    
}

// Consulta para obter a lista de confrontos pendentes (sem resultado) com os nicks dos jogadores
$query = "SELECT a_index, a_desafiado, a_desafiante, a_data FROM t_confrontos WHERE a_resultado IS NULL";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tituloSite; ?> - Atualização de Confrontos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="template/style.css" id="stylesheet"> <!-- Estilo padrão -->
    <link rel="stylesheet" type="text/css" href="template/style-dark.css" id="dark-stylesheet" disabled> <!-- Estilo do Modo Escuro desabilitado por padrão -->
</head>
<body>
    <div class="container">
        <h1>Atualização de Confrontos</h1>

        <form action="atualizacao.php" method="post">
            <div class="form-group">
                <label for="confronto_id">Selecione o confronto:</label>
                <select id="confronto_id" name="confronto_id" required>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        $desafiado = obterNomeJogador($row['a_desafiado']);
                        $desafiante = obterNomeJogador($row['a_desafiante']);
                        echo '<option value="' . $row['a_index'] . '">' . $desafiado . ' vs ' . $desafiante . ' (' . $row['a_data'] . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="resultado">Resultado:</label>
                <select id="resultado" name="resultado" required>
                    <option value="desafiado">Desafiado Venceu</option>
                    <option value="desafiante">Desafiante Venceu</option>
                    <option value="WO">WO (Um dos jogadores não apareceu)</option>
                    <option value="empate">Empate</option>
                </select>
            </div>
            <button type="submit" class="button">Atualizar Resultado</button>
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
                    $lastUpdatedQuery = "SELECT a_index FROM t_confrontos WHERE a_resultado IS NOT NULL ORDER BY a_index DESC LIMIT 1";
                    $lastUpdatedResult = $conn->query($lastUpdatedQuery);

                    $lastUpdatedRow = $lastUpdatedResult->fetch_assoc();


                    $confrontosQuery = "SELECT * FROM t_confrontos ORDER BY a_index DESC LIMIT 20";
                    $confrontosResult = $conn->query($confrontosQuery);
                    
                    while ($row = $confrontosResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><span>" . $row['a_index'] . "</span></td>";
                        echo "<td><span style='color: " . $_cor_desafiado . ";'>" . obterNomeJogador($row['a_desafiado']) . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'], $row['a_desafiado'], true) : '') . "</span></td>";
                        echo "<td><span style='color: " . $_cor_desafiante . ";'>" . obterNomeJogador($row['a_desafiante']) . ($row['a_resultado'] !== null ? ' ' . getStatus($row['a_index'], $row['a_desafiante'], true) : '') . "</span></td>";
                        echo "<td><span>" . date('d/m/Y', strtotime($row['a_data'])) . "</span></td>";
                        echo getStatusColor($row['a_resultado']);
                        
                        if ($row['a_index'] === $lastUpdatedRow['a_index']) {
                            // Se o resultado não for nulo, mostre o botão "Desfazer"
                            echo "<td><a class='button-desfazer' href='atualizacao.php?desfazer={$row['a_index']}'><img src='template/img/desfazer.png' alt='Editar' style='height: 1em; width: auto;'></a></td>'";
                        } else {
                            // Se o resultado for nulo, deixe a coluna em branco
                            echo "<td></td>";
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