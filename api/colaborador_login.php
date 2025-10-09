<?php
// ==========================================
// Quiz - Login de Colaborador v2
// api/colaborador_login.php
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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

	// Cria tabelas necessárias se não existirem
	createSessionsTable($conn);
	createEmailVerificationTable($conn);

	$method = $_SERVER['REQUEST_METHOD'];

	if ($method === 'POST') {
		$raw = file_get_contents('php://input');
		$decoded = json_decode($raw, true);
		$body = is_array($decoded) ? $decoded : array();

		$email = isset($body['email']) ? trim($body['email']) : '';
		$senha = isset($body['senha']) ? trim($body['senha']) : '';

		if ($email === '') {
			echo json_encode(array('success' => false, 'error' => 'E-mail é obrigatório'));
			exit();
		}

		if ($senha === '') {
			echo json_encode(array('success' => false, 'error' => 'Senha é obrigatória'));
			exit();
		}

		// Busca o colaborador pelo e-mail
		$stmt = $conn->prepare("SELECT id, nome, email, cpf, senha, ativo FROM quiz_colaboradores WHERE email = ?");
		if (!$stmt) {
			echo json_encode(array('success' => false, 'error' => 'Erro ao preparar query'));
			exit();
		}

		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows === 0) {
			echo json_encode(array('success' => false, 'error' => 'E-mail ou senha incorretos'));
			$stmt->close();
			exit();
		}

		$userData = $result->fetch_assoc();
		$stmt->close();

		// Verifica a senha
		if (!password_verify($senha, $userData['senha'])) {
			echo json_encode(array('success' => false, 'error' => 'E-mail ou senha incorretos'));
			exit();
		}

		// Verifica se o colaborador está ativo
		if (!$userData['ativo']) {
			echo json_encode(array('success' => false, 'error' => 'Colaborador inativo. Entre em contato com o administrador.'));
			exit();
		}

		// Verifica se já jogou (tabela quiz_participacoes)
		// Primeiro verifica se a tabela existe, se não existe cria
		$checkTable = $conn->query("SHOW TABLES LIKE 'quiz_participacoes'");
		if ($checkTable->num_rows === 0) {
			// Cria a tabela se não existir
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

		// Verifica se já participou
		$stmtCheck = $conn->prepare("SELECT id, acertos, total, data_participacao FROM quiz_participacoes WHERE colaborador_id = ?");
		$stmtCheck->bind_param('i', $userData['id']);
		$stmtCheck->execute();
		$resultCheck = $stmtCheck->get_result();

		if ($resultCheck->num_rows > 0) {
			$participacao = $resultCheck->fetch_assoc();
			echo json_encode(array(
				'success' => false,
				'error' => 'Você já participou do quiz',
				'ja_jogou' => true,
				'resultado' => array(
					'acertos' => (int)$participacao['acertos'],
					'total' => (int)$participacao['total'],
					'data' => $participacao['data_participacao']
				)
			));
			$stmtCheck->close();
			exit();
		}
		$stmtCheck->close();

		// Cria sessão segura para o colaborador
		$token = createSession($conn, $userData['id'], $userData['email']);

		if (!$token) {
			echo json_encode(array('success' => false, 'error' => 'Erro ao criar sessão'));
			exit();
		}

		// Resposta de sucesso (SEM enviar senha)
		$responseUser = array(
			'id' => $userData['id'],
			'nome' => $userData['nome'],
			'email' => $userData['email'],
			'cpf' => $userData['cpf']
		);

		echo json_encode(array(
			'success' => true,
			'message' => 'Login realizado com sucesso',
			'participante' => $responseUser,
			'token' => $token
		));

	} else {
		echo json_encode(array(
			'status' => 'API Quiz - Login de Colaborador',
			'usage' => array('POST {"email":"email@example.com", "senha":"senha123"}'),
			'timestamp' => date('Y-m-d H:i:s')
		));
	}

	$conn->close();

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
