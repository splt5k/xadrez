<?php
session_start();
session_unset(); // Limpa todas as variáveis de sessão
session_destroy(); // Destroi a sessão
header('Location: painel_admin.php'); // Redireciona de volta para a página de login
exit();
?>
