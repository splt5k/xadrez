<?php
require 'include/functions.php';

// Verificar se o usuário está autenticado como administrador (coloque sua lógica de autenticação aqui)

// Processar o formulário se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeSite = $_POST['nomeSite'];
    $tituloSite = $_POST['tituloSite'];
    $_cor_desafiado = $_POST['corDesafiado'];
    $_cor_desafiante = $_POST['corDesafiante'];
    $_cor_status_positivo = $_POST['corStatusPositivo'];
    $_cor_status_negativo = $_POST['corStatusNegativo'];

    // Atualize as configurações do site no banco de dados (implemente essa função)
    atualizarConfiguracoesDoSite($nomeSite, $tituloSite, $_cor_desafiado, $_cor_desafiante, $_cor_status_positivo, $_cor_status_negativo);

    header('Location: painel_admin.php'); // Redirecione para a página de login
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $tituloSite; ?> - Configurações do Site</title>
    <link rel="stylesheet" type="text/css" href="template/style.css" id="stylesheet"> <!-- Estilo padrão -->
    <link rel="stylesheet" type="text/css" href="template/style-dark.css" id="dark-stylesheet" disabled> <!-- Estilo do Modo Escuro desabilitado por padrão -->

    <style>
        /* Estilos de amostra para as cores */
        .color-sample {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 1px solid #000;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Botão de Acesso ao Painel de Administração -->
            <a href="painel_admin.php" class="admin-button">Painel Admin</a>
        </div>
        <h1>Configurações do Site</h1>

        <!-- Adicione aqui a lógica de autenticação do administrador se necessário -->

        <form method="post" action="site_config.php">
            <label for="nomeSite">Nome do Site:</label>
            <input type="text" id="nomeSite" name="nomeSite" value="<?php echo $nomeSite; ?>" required>
            <br>

            <label for="tituloSite">Título do Site:</label>
            <input type="text" id="tituloSite" name="tituloSite" value="<?php echo $tituloSite; ?>" required>
            <br>

            <label for="corDesafiado">Cor do Desafiado:</label>
            <input type="color" id="corDesafiado" name="corDesafiado" value="<?php echo $_cor_desafiado; ?>" required>
            <span class="color-sample" style="background-color: <?php echo $_cor_desafiado; ?>"></span>
            <br>

            <label for="corDesafiante">Cor do Desafiante:</label>
            <input type="color" id="corDesafiante" name="corDesafiante" value="<?php echo $_cor_desafiante; ?>" required>
            <span class="color-sample" style="background-color: <?php echo $_cor_desafiante; ?>"></span>
            <br>

            <label for="corStatusPositivo">Cor do Status Positivo:</label>
            <input type="color" id="corStatusPositivo" name="corStatusPositivo" value="<?php echo $_cor_status_positivo; ?>" required>
            <span class="color-sample" style="background-color: <?php echo $_cor_status_positivo; ?>"></span>
            <br>

            <label for="corStatusNegativo">Cor do Status Negativo:</label>
            <input type="color" id="corStatusNegativo" name="corStatusNegativo" value="<?php echo $_cor_status_negativo; ?>" required>
            <span class="color-sample" style="background-color: <?php echo $_cor_status_negativo; ?>"></span>
            <br>

            <input type="submit" value="Aplicar" class="button">
        </form>
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
