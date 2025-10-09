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

$conn->close();

shuffle($mulheresColabs);
shuffle($homensColabs);

$questions = array();
$maxMulheres = min(5, floor(count($mulheresColabs) / 3));
$maxHomens = min(10, floor(count($homensColabs) / 3));

// Array para armazenar respostas corretas (NUNCA enviado ao frontend)
$respostas_corretas = array();

for ($i = 0; $i < $maxMulheres; $i++) {
    $idx = $i * 3;
    $c = $mulheresColabs[$idx];
    $opcoes = array(
        array('id' => $mulheresColabs[$idx]['id'], 'nome' => $mulheresColabs[$idx]['nome'], 'foto_adulto' => $mulheresColabs[$idx]['foto_adulto']),
        array('id' => $mulheresColabs[$idx+1]['id'], 'nome' => $mulheresColabs[$idx+1]['nome'], 'foto_adulto' => $mulheresColabs[$idx+1]['foto_adulto']),
        array('id' => $mulheresColabs[$idx+2]['id'], 'nome' => $mulheresColabs[$idx+2]['nome'], 'foto_adulto' => $mulheresColabs[$idx+2]['foto_adulto'])
    );
    shuffle($opcoes);

    // Gera ID único para a pergunta (NÃO revela a resposta correta)
    $pergunta_uid = uniqid('q_', true);

    // Armazena resposta correta no backend
    $respostas_corretas[$pergunta_uid] = $c['id'];

    // Envia ao frontend SEM a resposta correta
    $questions[] = array(
        'id' => $pergunta_uid,  // ID único da pergunta (não revela resposta)
        'foto_crianca' => $c['foto_crianca'],
        'opcoes' => $opcoes
    );
}

for ($i = 0; $i < $maxHomens; $i++) {
    $idx = $i * 3;
    $c = $homensColabs[$idx];
    $opcoes = array(
        array('id' => $homensColabs[$idx]['id'], 'nome' => $homensColabs[$idx]['nome'], 'foto_adulto' => $homensColabs[$idx]['foto_adulto']),
        array('id' => $homensColabs[$idx+1]['id'], 'nome' => $homensColabs[$idx+1]['nome'], 'foto_adulto' => $homensColabs[$idx+1]['foto_adulto']),
        array('id' => $homensColabs[$idx+2]['id'], 'nome' => $homensColabs[$idx+2]['nome'], 'foto_adulto' => $homensColabs[$idx+2]['foto_adulto'])
    );
    shuffle($opcoes);

    // Gera ID único para a pergunta (NÃO revela a resposta correta)
    $pergunta_uid = uniqid('q_', true);

    // Armazena resposta correta no backend
    $respostas_corretas[$pergunta_uid] = $c['id'];

    // Envia ao frontend SEM a resposta correta
    $questions[] = array(
        'id' => $pergunta_uid,  // ID único da pergunta (não revela resposta)
        'foto_crianca' => $c['foto_crianca'],
        'opcoes' => $opcoes
    );
}

shuffle($questions);

// Salva as respostas corretas na sessão (SEGURO - apenas no backend)
saveQuizDataToSession($conn, $token, array('respostas_corretas' => $respostas_corretas));

$conn->close();

// Envia apenas as perguntas ao frontend (SEM as respostas corretas)
echo json_encode(array('success' => true, 'questions' => $questions, 'total' => count($questions)));
