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

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/session_manager.php';

try {
	$conn = getDbConnection();
	if (!$conn) {
		echo json_encode(array('success' => false, 'error' => 'Erro de conexão com banco de dados'));
		exit();
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		echo json_encode(array('success' => false, 'error' => 'Método não permitido'));
		exit();
	}

	// VALIDAÇÃO DE TOKEN - CRÍTICO PARA SEGURANÇA
	$token = getTokenFromHeader();
	if (!$token) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Token não fornecido'));
		exit();
	}

	$session = validateToken($conn, $token);
	if (!$session) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Sessão inválida ou expirada'));
		exit();
	}

	// Verifica se a sessão já foi usada (prevenção de replay attack)
	if ($session['used']) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Resultado já foi salvo anteriormente'));
		exit();
	}

	$raw = file_get_contents('php://input');
	$decoded = json_decode($raw, true);
	$body = is_array($decoded) ? $decoded : array();

	$acertos = isset($body['acertos']) ? (int)$body['acertos'] : 0;
	$total = isset($body['total']) ? (int)$body['total'] : 0;
	$tempo_total_segundos = isset($body['tempo_total_segundos']) ? (int)$body['tempo_total_segundos'] : 0;

	// USA OS DADOS DA SESSÃO (não confia no que vem do frontend)
	$colaborador_id = (int)$session['colaborador_id'];
	$email = $session['email'];

	// VALIDAÇÃO ADICIONAL: Verifica se o colaborador da sessão existe e está ativo
	$stmtUser = $conn->prepare("SELECT id, nome, cpf, email, ativo FROM quiz_colaboradores WHERE id = ?");
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

	// Verifica se o colaborador está ativo
	if (!$colaboradorData['ativo']) {
		echo json_encode(array('success' => false, 'error' => 'Colaborador inativo'));
		exit();
	}

	// Verifica se a tabela existe
	$checkTable = $conn->query("SHOW TABLES LIKE 'quiz_participacoes'");
	if ($checkTable->num_rows === 0) {
		$createTable = "CREATE TABLE quiz_participacoes (
			id INT AUTO_INCREMENT PRIMARY KEY,
			colaborador_id INT NOT NULL,
			email VARCHAR(200) NOT NULL,
			acertos INT NOT NULL DEFAULT 0,
			total INT NOT NULL DEFAULT 0,
			tempo_total_segundos INT NOT NULL DEFAULT 0,
			data_participacao DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY unique_colaborador (colaborador_id),
			INDEX idx_ranking (acertos DESC, tempo_total_segundos ASC)
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

	// Verifica se a coluna tempo_total_segundos existe, se não, adiciona
	$checkColumn = $conn->query("SHOW COLUMNS FROM quiz_participacoes LIKE 'tempo_total_segundos'");
	if ($checkColumn->num_rows === 0) {
		$conn->query("ALTER TABLE quiz_participacoes ADD COLUMN tempo_total_segundos INT NOT NULL DEFAULT 0 AFTER total");
		$conn->query("ALTER TABLE quiz_participacoes ADD INDEX idx_ranking (acertos DESC, tempo_total_segundos ASC)");
	}

	// Verifica e adiciona colunas nome e cpf se não existirem
	$checkNome = $conn->query("SHOW COLUMNS FROM quiz_participacoes LIKE 'nome'");
	if ($checkNome->num_rows === 0) {
		$conn->query("ALTER TABLE quiz_participacoes ADD COLUMN nome VARCHAR(200) DEFAULT NULL AFTER colaborador_id");
	}

	$checkCpf = $conn->query("SHOW COLUMNS FROM quiz_participacoes LIKE 'cpf'");
	if ($checkCpf->num_rows === 0) {
		$conn->query("ALTER TABLE quiz_participacoes ADD COLUMN cpf VARCHAR(14) DEFAULT NULL AFTER nome");
		$conn->query("ALTER TABLE quiz_participacoes ADD INDEX idx_cpf (cpf)");
	}

	// Verifica e adiciona coluna tempo_formatado se não existir
	$checkTempoFormatado = $conn->query("SHOW COLUMNS FROM quiz_participacoes LIKE 'tempo_formatado'");
	if ($checkTempoFormatado->num_rows === 0) {
		$conn->query("ALTER TABLE quiz_participacoes ADD COLUMN tempo_formatado VARCHAR(10) DEFAULT NULL AFTER tempo_total_segundos");
	}

	// Formata o tempo em MM:SS
	$minutos = floor($tempo_total_segundos / 60);
	$segundos = $tempo_total_segundos % 60;
	$tempo_formatado = sprintf('%02d:%02d', $minutos, $segundos);

	// Insere o resultado com nome, CPF e tempo formatado
	$stmtInsert = $conn->prepare("INSERT INTO quiz_participacoes (colaborador_id, nome, cpf, email, acertos, total, tempo_total_segundos, tempo_formatado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
	$stmtInsert->bind_param('isssiiis', $colaborador_id, $colaboradorData['nome'], $colaboradorData['cpf'], $colaboradorData['email'], $acertos, $total, $tempo_total_segundos, $tempo_formatado);

	if (!$stmtInsert->execute()) {
		echo json_encode(array('success' => false, 'error' => 'Erro ao salvar resultado'));
		$stmtInsert->close();
		exit();
	}

	$stmtInsert->close();

	// MARCA A SESSÃO COMO USADA (previne salvar múltiplas vezes)
	markSessionAsUsed($conn, $token);

	$conn->close();

	echo json_encode(array(
		'success' => true,
		'message' => 'Resultado salvo com sucesso',
		'resultado' => array(
			'colaborador_id' => $colaborador_id,
			'email' => $colaboradorData['email'],
			'acertos' => $acertos,
			'total' => $total,
			'tempo_total_segundos' => $tempo_total_segundos
		)
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
