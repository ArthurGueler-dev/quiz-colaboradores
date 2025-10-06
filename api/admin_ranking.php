<?php
// ==========================================
// Admin - Rankings e Análise de Dificuldade
// api/admin_ranking.php (PHP 5.6 Compatible)
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

	// ===== RANKING DE JOGADORES =====
	// Ranking por melhor pontuação única
	$result = $conn->query("
		SELECT
			c.nome,
			p.email,
			MAX(p.acertos) as melhor_pontuacao,
			COUNT(p.id) as total_partidas,
			AVG(p.acertos) as media_pontuacao,
			MAX(p.data_participacao) as ultima_partida
		FROM quiz_colaboradores c
		INNER JOIN quiz_participacoes p ON c.id = p.colaborador_id
		GROUP BY c.id, c.nome, p.email
		ORDER BY melhor_pontuacao DESC, media_pontuacao DESC
		LIMIT 20
	");

	$rankingMelhorPontuacao = array();
	$posicao = 1;
	while ($row = $result->fetch_assoc()) {
		$rankingMelhorPontuacao[] = array(
			'posicao' => $posicao++,
			'nome' => $row['nome'],
			'email' => $row['email'],
			'melhor_pontuacao' => (int)$row['melhor_pontuacao'],
			'media_pontuacao' => round($row['media_pontuacao'], 1),
			'total_partidas' => (int)$row['total_partidas'],
			'ultima_partida' => $row['ultima_partida']
		);
	}

	// Ranking por média (mínimo 3 partidas)
	$result = $conn->query("
		SELECT
			c.nome,
			p.email,
			AVG(p.acertos) as media_pontuacao,
			COUNT(p.id) as total_partidas,
			MAX(p.acertos) as melhor_pontuacao,
			MIN(p.acertos) as pior_pontuacao
		FROM quiz_colaboradores c
		INNER JOIN quiz_participacoes p ON c.id = p.colaborador_id
		GROUP BY c.id, c.nome, p.email
		HAVING total_partidas >= 3
		ORDER BY media_pontuacao DESC
		LIMIT 20
	");

	$rankingMedia = array();
	$posicao = 1;
	while ($row = $result->fetch_assoc()) {
		$rankingMedia[] = array(
			'posicao' => $posicao++,
			'nome' => $row['nome'],
			'email' => $row['email'],
			'media_pontuacao' => round($row['media_pontuacao'], 1),
			'total_partidas' => (int)$row['total_partidas'],
			'melhor_pontuacao' => (int)$row['melhor_pontuacao'],
			'pior_pontuacao' => (int)$row['pior_pontuacao']
		);
	}

	// ===== ANÁLISE DE DIFICULDADE DOS COLABORADORES =====
	// Simplificado - sem tabela quiz_perguntas_respondidas
	$colaboradoresDificeis = array();
	$colaboradoresFaceis = array();

	$conn->close();

	echo json_encode(array(
		'success' => true,
		'rankings' => array(
			'melhor_pontuacao' => $rankingMelhorPontuacao,
			'media' => $rankingMedia
		),
		'dificuldade' => array(
			'mais_dificeis' => $colaboradoresDificeis,
			'mais_faceis' => $colaboradoresFaceis
		)
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
