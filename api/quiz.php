<?php
// ==========================================
// Quiz - API de Perguntas (Segura)
// api/quiz.php
// ==========================================

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/session_manager.php';

$conn = getDbConnection();
if (!$conn) {
    die('{"success":false,"error":"Erro de conexão com banco de dados"}');
}

// Cria tabela de sessões se não existir
createSessionsTable($conn);

// Valida token
$token = getTokenFromHeader();
if (!$token) {
    $conn->close();
    die('{"success":false,"error":"Token não fornecido"}');
}

$session = validateToken($conn, $token);
if (!$session) {
    $conn->close();
    die('{"success":false,"error":"Sessão inválida ou expirada"}');
}

// Verifica se já foi usado
if ($session['used']) {
    $conn->close();
    die('{"success":false,"error":"Você já completou o quiz"}');
}

$result = $conn->query("SELECT id, nome, foto_adulto, foto_crianca FROM quiz_colaboradores WHERE foto_crianca != '' AND foto_adulto != '' ORDER BY RAND()");

$mulheres = array('ALZIRENE RAMBO','CAROLINE LIMA VIEIRA','DANIELLE CARDOSO','DIANNE MOURA','EVELIN MARTINS','KARLIANY DOS SANTOS','LAIS MELLO','MARIA EDUARDA AGUIAR','RAYANE GUSS MACHADO','SABRINA SARMENTO','VICTORIA SANTOS','THAYNARA OLIVEIRA');

$base = 'https://floripa.in9automacao.com.br';
$mulheresColabs = array();
$homensColabs = array();

while ($row = $result->fetch_assoc()) {
    $colab = array(
        'id' => $row['id'],
        'nome' => utf8_encode($row['nome']),
        'foto_adulto' => $base . $row['foto_adulto'],
        'foto_crianca' => $base . $row['foto_crianca']
    );

    $ehMulher = false;
    $nomeUpper = strtoupper(trim($row['nome']));
    foreach ($mulheres as $m) {
        if ($nomeUpper === strtoupper(trim($m))) {
            $ehMulher = true;
            break;
        }
    }

    if ($ehMulher) {
        $mulheresColabs[] = $colab;
    } else {
        $homensColabs[] = $colab;
    }
}

// NÃO feche a conexão aqui! Ainda precisamos usar para salvar dados da sessão

shuffle($mulheresColabs);
shuffle($homensColabs);

$questions = array();

// Array para armazenar respostas corretas (NUNCA enviado ao frontend)
$respostas_corretas = array();

// Cria pergunta para CADA mulher
foreach ($mulheresColabs as $idx => $correta) {
    // Seleciona 2 mulheres aleatórias diferentes da correta
    $decoys = array();
    $tentativas = 0;
    while (count($decoys) < 2 && $tentativas < 100) {
        $randomIdx = array_rand($mulheresColabs);
        if ($randomIdx !== $idx && !in_array($randomIdx, $decoys)) {
            $decoys[] = $randomIdx;
        }
        $tentativas++;
    }

    // Se não conseguiu 2 decoys, pula esta pergunta
    if (count($decoys) < 2) continue;

    $opcoes = array(
        array('id' => $correta['id'], 'nome' => $correta['nome'], 'foto_adulto' => $correta['foto_adulto']),
        array('id' => $mulheresColabs[$decoys[0]]['id'], 'nome' => $mulheresColabs[$decoys[0]]['nome'], 'foto_adulto' => $mulheresColabs[$decoys[0]]['foto_adulto']),
        array('id' => $mulheresColabs[$decoys[1]]['id'], 'nome' => $mulheresColabs[$decoys[1]]['nome'], 'foto_adulto' => $mulheresColabs[$decoys[1]]['foto_adulto'])
    );
    shuffle($opcoes);

    $pergunta_uid = uniqid('q_', true);
    $respostas_corretas[$pergunta_uid] = $correta['id'];

    $questions[] = array(
        'id' => $pergunta_uid,
        'foto_crianca' => $correta['foto_crianca'],
        'opcoes' => $opcoes
    );
}

// Cria pergunta para CADA homem
foreach ($homensColabs as $idx => $correta) {
    // Seleciona 2 homens aleatórios diferentes do correto
    $decoys = array();
    $tentativas = 0;
    while (count($decoys) < 2 && $tentativas < 100) {
        $randomIdx = array_rand($homensColabs);
        if ($randomIdx !== $idx && !in_array($randomIdx, $decoys)) {
            $decoys[] = $randomIdx;
        }
        $tentativas++;
    }

    // Se não conseguiu 2 decoys, pula esta pergunta
    if (count($decoys) < 2) continue;

    $opcoes = array(
        array('id' => $correta['id'], 'nome' => $correta['nome'], 'foto_adulto' => $correta['foto_adulto']),
        array('id' => $homensColabs[$decoys[0]]['id'], 'nome' => $homensColabs[$decoys[0]]['nome'], 'foto_adulto' => $homensColabs[$decoys[0]]['foto_adulto']),
        array('id' => $homensColabs[$decoys[1]]['id'], 'nome' => $homensColabs[$decoys[1]]['nome'], 'foto_adulto' => $homensColabs[$decoys[1]]['foto_adulto'])
    );
    shuffle($opcoes);

    $pergunta_uid = uniqid('q_', true);
    $respostas_corretas[$pergunta_uid] = $correta['id'];

    $questions[] = array(
        'id' => $pergunta_uid,
        'foto_crianca' => $correta['foto_crianca'],
        'opcoes' => $opcoes
    );
}

shuffle($questions);

// Salva as respostas corretas na sessão (SEGURO - apenas no backend)
saveQuizDataToSession($conn, $token, array('respostas_corretas' => $respostas_corretas));

// AGORA sim, fecha a conexão (depois de salvar os dados)
$conn->close();

// Envia apenas as perguntas ao frontend (SEM as respostas corretas)
echo json_encode(array('success' => true, 'questions' => $questions, 'total' => count($questions)));
