<?php
session_start();
require 'config.php';

function getPlayers() {
    global $conn;
    $sql = "SELECT * FROM t_atletas ORDER BY a_rating_atual DESC";
    $result = $conn->query($sql);
    $players = array();
    $position = 1;
    while ($row = $result->fetch_assoc()) {
        $players[$position] = $row;
        $position++;
    }
    return $players;
}

function obterNomeJogador($id) {
    global $conn;
    $query = "SELECT a_nick FROM t_atletas WHERE a_index = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['a_nick'];
}


function obterDadosJogador($id) {
    global $conn;
    $query = "SELECT * FROM t_atletas WHERE a_index = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row;
}

function obterDadosConfronto($id) {
    global $conn;
    $query = "SELECT * FROM t_confrontos WHERE a_index = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row;
}

// Calcule a idade com base na data de nascimento
function calcularIdade($dataNascimento) {
    $dataNascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($dataNascimento);
    return $idade->y; // Obtém a parte dos anos da diferença
}

// Setinha para cima ou para baixo com base no status do jogador
function getStatus($confrontoId, $playerId, $sendPontos) {
    global $conn; // Certifique-se de que $conn esteja disponível neste escopo
    $historicoQuery = "";
    
    if($confrontoId == 0){
        $historicoQuery = "SELECT a_desafiado, a_desafiante, a_desafiado_pontos, a_desafiante_pontos
                      FROM t_confrontos
                      WHERE (a_desafiado = ? OR a_desafiante = ?)
                      AND a_resultado IS NOT NULL
                      ORDER BY a_index DESC
                      LIMIT 1";
    }else{
        $historicoQuery = "SELECT a_desafiado, a_desafiante, a_desafiado_pontos, a_desafiante_pontos
                    FROM t_confrontos
                    WHERE (a_desafiado = ? OR a_desafiante = ?)
                    AND a_resultado IS NOT NULL
                    AND a_index = ?";
    }


    $historicoStmt = $conn->prepare($historicoQuery);
    if($confrontoId == 0){
        $historicoStmt->bind_param("ii", $playerId, $playerId);
    }else{
        $historicoStmt->bind_param("iii", $playerId, $playerId, $confrontoId);
    }
    
    $historicoStmt->execute();
    $historicoResult = $historicoStmt->get_result();

    if ($historicoResult->num_rows > 0) {
        $row = $historicoResult->fetch_assoc();

        $pontos = 0;
        // Detectar se o jogador é desafiado ou desafiante baseado no $playerId
        if ($row['a_desafiado'] == $playerId) {
            $pontos = $row['a_desafiado_pontos'];
        } else {
            $pontos = $row['a_desafiante_pontos'];
        }

        $seta = '';
        global $_cor_status_positivo, $_cor_status_negativo;
        if ($pontos > 0) {
            $seta = '<span style="color: ' . $_cor_status_positivo . ';  font-size:60%;"> ↑' . ($sendPontos ? ' ' . $pontos : '') . '</span>';
        } elseif ($pontos < 0) {
            $seta = '<span style="color: ' . $_cor_status_negativo . ';  font-size:60%;"> ↓' . ($sendPontos ? ' ' . $pontos : '') . '</span>';
        }

        return $seta;
    }

    return ''; // Nenhuma seta se não houver histórico de confrontos com resultado não nulo
}

// Função para atualizar as configurações do site
function atualizarConfiguracoesDoSite($nomeSite, $tituloSite, $_cor_desafiado, $_cor_desafiante, $_cor_status_positivo, $_cor_status_negativo) {
    global $conn;

    // Preparar a consulta para atualizar as configurações do site
    $updateQuery = "UPDATE t_site_settings SET a_nome = ?, a_title = ?, a_cor_desafiado = ?, a_cor_desafiante = ?, a_cor_status_positivo = ?, a_cor_status_negativo = ? WHERE a_index = 1";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssss", $nomeSite, $tituloSite, $_cor_desafiado, $_cor_desafiante, $_cor_status_positivo, $_cor_status_negativo);

    if ($stmt->execute()) {
        // Configurações atualizadas com sucesso
        return true;
    } else {
        // Ocorreu um erro ao atualizar as configurações
        return false;
    }
}


// Função para obter o código HTML com base no resultado
function getStatusColor($resultado) {
    $html = '';
    global $_cor_desafiado, $_cor_desafiante;
    switch ($resultado) {
        case 'desafiado':
            $html = '<span style="color: '.$_cor_desafiado.';">' . $resultado . '</span>';
            break;
        case 'desafiante':
            $html = '<span style="color: '.$_cor_desafiante.';">' . $resultado . '</span>';
            break;
        default:
            $html = $resultado;
            break;
    }

    return '<td>' . $html . '</td>';
}

// No arquivo functions.php
function isUserLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        // Se a variável de sessão 'user_id' estiver definida, o usuário está logado
        return true;
    } else {
        return false;
    }
}

function loginUser($username, $password) {
    global $conn;

    // Consulta SQL para verificar se o nome de usuário e senha coincidem
    $sql = "SELECT a_index FROM t_users WHERE a_usuario = ? AND a_password = MD5(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // Autenticação bem-sucedida
            return true;
        }
    }

    return false;
}

// Função para obter informações do confronto por ID
function obterConfrontoPorId($confrontoId) {
    global $conn;

    $query = "SELECT * FROM t_confrontos WHERE a_index = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $confrontoId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    return null;
}

// Função para obter o rating atual de um jogador
function obterRatingAtual($jogadorId) {
    global $conn;

    $query = "SELECT a_rating_atual FROM t_atletas WHERE a_index = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $jogadorId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['a_rating_atual'];
    }

    return null;
}

// Função para atualizar o rating de um jogador
function atualizarRating($jogadorId, $novoRating) {
    global $conn;

    $query = "UPDATE t_atletas SET a_rating_atual = ? WHERE a_index = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("di", $novoRating, $jogadorId);

    $stmt->execute();
}


// ELO
// Function to calculate the Probability
function probability($rating1, $rating2) {
    return ((1.0 * 1.0) / (1 + 1.0 * pow(10, (1.0 * ($rating1 - $rating2)) / 400)));
}


// Function to calculate Elo rating
// K is a constant.
// d determines whether Player A wins or Player B.
function eloRating($rating1, $rating2, $k, $result) {
    // To calculate the Winning Probability of Player B
    $pb = probability($rating1, $rating2);

    // To calculate the Winning Probability of Player A
    $pa = probability($rating2, $rating1);

    // Case 1 When Player A wins
    // Updating the Elo Ratings
    if ($result === true) {
        $rating1 = $rating1 + $k * (1 - $pa);
        $rating2 = $rating2 + $k * (0 - $pb);
    } else {
        // Case 2 When Player B wins
        // Updating the Elo Ratings
        $rating1 = $rating1 + $k * (0 - $pa);
        $rating2 = $rating2 + $k * (1 - $pb);
    }

    return [round($rating1), round($rating2)];
}

// Function to calculate and return new ratings
function Calc($rating1, $rating2, $resultado) {
    $ptot = 0;
    $soma = 0;
    $grelo = 0;
    $medadv = 0;
    $jogjog = 0;
    $dp = [800,677,589,538,501,470,444,422,401,383,366,351,336,322,309,296,284,273,262,251,240,230,220,211,202,193,184,175,166,158,149,141,133,125,117,110,102,95,87,80,72,65,57,50,43,36,29,21,14,7,0];
    $k = 40;
    $regra400 = 1;

    if ($rating2) {
        $medadv += $rating2;
        if ($regra400 == 1) {
            if ($rating2 - $rating1 > 400) {
                $rating2 = $rating1 + 400;
            }
            if ($rating1 - $rating2 > 400) {
                $rating2 = $rating1 - 400;
            }
        }
        $grelo += $rating2;
        $jogjog++;
        $ptot += $resultado;
        $perc = 1 / (1 + pow(10, ($rating2 - $rating1) / 400));
        $diferenca = ($k * ($resultado - $perc));
        $soma += $diferenca;
    }

    $grelo /= $jogjog;
    $medadv /= $jogjog;

    $pd = (100 * $ptot / $jogjog);
    if ($pd <= 50) {
        $grelo -= $dp[$pd];
    } else {
        $grelo += $dp[100 - $pd];
    }

    $rating1 += round($soma);
    return $rating1;
}
// ELO
?>




    
