<?php
// ==========================================
// Quiz - Salvar Resultado
// api/salvar_resultado.php
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		echo json_encode(array('success' => false, 'error' => 'Método não permitido'));
		exit();
	}

	$raw = file_get_contents('php://input');
	$decoded = json_decode($raw, true);
	$body = is_array($decoded) ? $decoded : array();

	$colaborador_id = isset($body['colaborador_id']) ? (int)$body['colaborador_id'] : 0;
	$email = isset($body['email']) ? trim($body['email']) : '';
	$acertos = isset($body['acertos']) ? (int)$body['acertos'] : 0;
	$total = isset($body['total']) ? (int)$body['total'] : 0;

	if ($colaborador_id === 0) {
		echo json_encode(array('success' => false, 'error' => 'ID do colaborador é obrigatório'));
		exit();
	}

	// Verifica se o colaborador existe
	$stmtUser = $conn->prepare("SELECT id, email FROM quiz_colaboradores WHERE id = ?");
	$stmtUser->bind_param('i', $colaborador_id);
	$stmtUser->execute();
	$resultUser = $stmtUser->get_result();

	if ($resultUser->num_rows === 0) {
		echo json_encode(array('success' => false, 'error' => 'Colaborador não encontrado'));
		$stmtUser->close();
		exit();
	}

	$colaboradorData = $resultUser->fetch_assoc();
	$stmtUser->close();

	// Verifica se a tabela existe
	$checkTable = $conn->query("SHOW TABLES LIKE 'quiz_participacoes'");
	if ($checkTable->num_rows === 0) {
		$createTable = "CREATE TABLE quiz_participacoes (
			id INT AUTO_INCREMENT PRIMARY KEY,
			colaborador_id INT NOT NULL,
			email VARCHAR(200) NOT NULL,
			acertos INT NOT NULL DEFAULT 0,
			total INT NOT NULL DEFAULT 0,
			data_participacao DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY unique_colaborador (colaborador_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

		if (!$conn->query($createTable)) {
			echo json_encode(array('success' => false, 'error' => 'Erro ao criar tabela de participações'));
			exit();
		}
	}

	// Verifica se já existe registro
	$stmtCheck = $conn->prepare("SELECT id FROM quiz_participacoes WHERE colaborador_id = ?");
	$stmtCheck->bind_param('i', $colaborador_id);
	$stmtCheck->execute();
	$resultCheck = $stmtCheck->get_result();

	if ($resultCheck->num_rows > 0) {
		echo json_encode(array('success' => false, 'error' => 'Resultado já foi salvo anteriormente'));
		$stmtCheck->close();
		exit();
	}
	$stmtCheck->close();

	// Insere o resultado
	$stmtInsert = $conn->prepare("INSERT INTO quiz_participacoes (colaborador_id, email, acertos, total) VALUES (?, ?, ?, ?)");
	$stmtInsert->bind_param('isii', $colaborador_id, $colaboradorData['email'], $acertos, $total);

	if (!$stmtInsert->execute()) {
		echo json_encode(array('success' => false, 'error' => 'Erro ao salvar resultado'));
		$stmtInsert->close();
		exit();
	}

	$stmtInsert->close();
	$conn->close();

	echo json_encode(array(
		'success' => true,
		'message' => 'Resultado salvo com sucesso',
		'resultado' => array(
			'colaborador_id' => $colaborador_id,
			'email' => $colaboradorData['email'],
			'acertos' => $acertos,
			'total' => $total
		)
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
