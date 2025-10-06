<?php
// ==========================================
// Admin - Estatísticas Dashboard
// api/admin_stats.php
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit(0);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Credenciais do banco
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
	$conn = new mysqli($host, $user, $password, $database, $port);
	if ($conn->connect_error) {
		echo json_encode(array('success' => false, 'error' => 'Erro de conexão com banco de dados'));
		exit();
	}

	// Total de participantes únicos
	$result = $conn->query("SELECT COUNT(DISTINCT email) as total FROM quiz_colaboradores WHERE email LIKE '%@%' AND email NOT LIKE '%@quiz.local'");
	if (!$result) {
		echo json_encode(array('success' => false, 'error' => 'Erro na query: ' . $conn->error));
		exit();
	}
	$row = $result->fetch_assoc();
	$totalParticipantes = $row['total'];

	// Total de partidas jogadas
	$result = $conn->query("SELECT COUNT(*) as total FROM quiz_participacoes");
	if (!$result) {
		echo json_encode(array('success' => false, 'error' => 'Tabela quiz_participacoes não existe ou erro: ' . $conn->error));
		exit();
	}
	$row = $result->fetch_assoc();
	$totalPartidas = $row['total'];

	// Pontuação média
	$result = $conn->query("SELECT AVG(acertos) as media FROM quiz_participacoes");
	if (!$result) {
		echo json_encode(array('success' => false, 'error' => 'Erro ao calcular média: ' . $conn->error));
		exit();
	}
	$row = $result->fetch_assoc();
	$pontuacaoMedia = $row['media'] ? round($row['media'], 1) : 0;

	// Melhor pontuação
	$result = $conn->query("SELECT MAX(acertos) as melhor FROM quiz_participacoes");
	if (!$result) {
		echo json_encode(array('success' => false, 'error' => 'Erro ao buscar melhor pontuação: ' . $conn->error));
		exit();
	}
	$row = $result->fetch_assoc();
	$melhorPontuacao = $row['melhor'] ? $row['melhor'] : 0;

	// Top 5 melhores jogadores (por melhor pontuação)
	$result = $conn->query("
		SELECT
			c.nome,
			c.email,
			MAX(p.acertos) as melhor_pontuacao,
			AVG(p.acertos) as media_pontuacao,
			COUNT(p.id) as total_partidas
		FROM quiz_colaboradores c
		INNER JOIN quiz_participacoes p ON c.id = p.colaborador_id
		GROUP BY c.id, c.nome, c.email
		ORDER BY melhor_pontuacao DESC, media_pontuacao DESC
		LIMIT 5
	");

	$topJogadores = array();
	while ($row = $result->fetch_assoc()) {
		$topJogadores[] = array(
			'nome' => $row['nome'],
			'email' => $row['email'],
			'media' => round($row['media_pontuacao'], 1),
			'partidas' => (int)$row['total_partidas']
		);
	}

	// Distribuição de pontuações (para gráfico)
	$result = $conn->query("
		SELECT
			CASE
				WHEN acertos = 0 THEN '0'
				WHEN acertos BETWEEN 1 AND 3 THEN '1-3'
				WHEN acertos BETWEEN 4 AND 6 THEN '4-6'
				WHEN acertos BETWEEN 7 AND 9 THEN '7-9'
				WHEN acertos = 10 THEN '10'
			END as faixa,
			COUNT(*) as quantidade
		FROM quiz_participacoes
		GROUP BY faixa
		ORDER BY
			CASE faixa
				WHEN '0' THEN 1
				WHEN '1-3' THEN 2
				WHEN '4-6' THEN 3
				WHEN '7-9' THEN 4
				WHEN '10' THEN 5
			END
	");

	$distribuicao = array();
	while ($row = $result->fetch_assoc()) {
		$distribuicao[] = array(
			'faixa' => $row['faixa'],
			'quantidade' => (int)$row['quantidade']
		);
	}

	// Partidas por dia (últimos 7 dias)
	$result = $conn->query("
		SELECT
			DATE(data_participacao) as dia,
			COUNT(*) as quantidade
		FROM quiz_participacoes
		WHERE data_participacao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
		GROUP BY dia
		ORDER BY dia
	");

	$partidasPorDia = array();
	while ($row = $result->fetch_assoc()) {
		$partidasPorDia[] = array(
			'dia' => $row['dia'],
			'quantidade' => (int)$row['quantidade']
		);
	}

	$conn->close();

	echo json_encode(array(
		'success' => true,
		'stats' => array(
			'total_participantes' => (int)$totalParticipantes,
			'total_partidas' => (int)$totalPartidas,
			'pontuacao_media' => (float)$pontuacaoMedia,
			'melhor_pontuacao' => (int)$melhorPontuacao,
			'top_jogadores' => $topJogadores,
			'distribuicao_pontuacoes' => $distribuicao,
			'partidas_por_dia' => $partidasPorDia
		)
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
