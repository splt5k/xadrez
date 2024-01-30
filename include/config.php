<?php
$servername = "127.0.0.1";
$username = "root";
$password = ""; // Insira a senha do seu banco de dados
$dbname = "db_chess";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    //die("Conexão com o banco de dados falhou: " . $conn->connect_error);
    die("Website em manutenção.");
}

// Consulta SQL para obter as configurações do site
$sql = "SELECT * FROM t_site_settings WHERE a_index = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Armazena as configurações do site em variáveis
    $row = $result->fetch_assoc();
    $nomeSite = $row['a_nome'];
    $tituloSite = $row['a_title'];
    $_cor_desafiado = $row['a_cor_desafiado'];
    $_cor_desafiante = $row['a_cor_desafiante'];
    $_cor_status_positivo = $row['a_cor_status_positivo'];
    $_cor_status_negativo = $row['a_cor_status_negativo'];
} else {
    // Caso não haja configurações do site, define valores padrão
    $nomeSite = "Nome do site";
    $tituloSite = "Título do site";
    $_cor_desafiado = "red";
    $_cor_desafiante = "blue";
    $_cor_status_positivo = "green";
    $_cor_status_negativo = "orange";
}

?>