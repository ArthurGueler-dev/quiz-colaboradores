<?php
// ==========================================
// Admin - Lista de Participações
// api/admin_participacoes.php
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

	// Parâmetros de paginação
	$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
	$limit = isset($_GET['limit']) ? min(100, max(10, (int)$_GET['limit'])) : 20;
	$offset = ($page - 1) * $limit;

	// Total de registros
	$sql = "SELECT COUNT(*) as total FROM quiz_participacoes p";
	$result = $conn->query($sql);
	$totalRegistros = $result->fetch_assoc();
	$totalRegistros = $totalRegistros['total'];

	// Lista de participações
	$sql = "
		SELECT
			p.id,
			p.colaborador_id,
			c.nome,
			p.email,
			p.acertos,
			p.total,
			p.data_participacao,
			ROUND((p.acertos / p.total) * 100, 1) as percentual
		FROM quiz_participacoes p
		INNER JOIN quiz_colaboradores c ON p.colaborador_id = c.id
		ORDER BY p.data_participacao DESC
		LIMIT $limit OFFSET $offset
	";

	$result = $conn->query($sql);

	$participacoes = array();
	while ($row = $result->fetch_assoc()) {
		$participacoes[] = array(
			'id' => (int)$row['id'],
			'colaborador_id' => (int)$row['colaborador_id'],
			'nome' => $row['nome'],
			'email' => $row['email'],
			'pontuacao' => (int)$row['acertos'],
			'total_perguntas' => (int)$row['total'],
			'percentual' => (float)$row['percentual'],
			'data_hora' => $row['data_participacao']
		);
	}

	$conn->close();

	echo json_encode(array(
		'success' => true,
		'participacoes' => $participacoes,
		'pagination' => array(
			'page' => $page,
			'limit' => $limit,
			'total' => (int)$totalRegistros,
			'pages' => ceil($totalRegistros / $limit)
		)
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
