<?php
// ==========================================
// Quiz - Cadastro de Colaborador v2
// api/cadastro_colaborador.php
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

	// Cria a tabela de colaboradores se não existir
	$checkTable = $conn->query("SHOW TABLES LIKE 'quiz_colaboradores'");
	if ($checkTable->num_rows === 0) {
		$createTable = "CREATE TABLE quiz_colaboradores (
			id INT AUTO_INCREMENT PRIMARY KEY,
			nome VARCHAR(200) NOT NULL,
			email VARCHAR(200) NOT NULL,
			cpf VARCHAR(14) NOT NULL,
			senha VARCHAR(255) NOT NULL,
			foto_adulto VARCHAR(500),
			foto_crianca VARCHAR(500),
			ativo TINYINT(1) DEFAULT 1,
			data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY unique_email (email),
			UNIQUE KEY unique_cpf (cpf)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

		if (!$conn->query($createTable)) {
			echo json_encode(array('success' => false, 'error' => 'Erro ao criar tabela de colaboradores'));
			exit();
		}
	}

	$method = $_SERVER['REQUEST_METHOD'];

	if ($method === 'POST') {
		$raw = file_get_contents('php://input');
		$decoded = json_decode($raw, true);
		$body = is_array($decoded) ? $decoded : array();

		$nome = isset($body['nome']) ? trim($body['nome']) : '';
		$email = isset($body['email']) ? trim($body['email']) : '';
		$cpf = isset($body['cpf']) ? trim($body['cpf']) : '';
		$senha = isset($body['senha']) ? trim($body['senha']) : '';
		$foto_adulto = isset($body['foto_adulto']) ? trim($body['foto_adulto']) : '';
		$foto_crianca = isset($body['foto_crianca']) ? trim($body['foto_crianca']) : '';

		// Validações
		if ($nome === '') {
			echo json_encode(array('success' => false, 'error' => 'Nome é obrigatório'));
			exit();
		}

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('success' => false, 'error' => 'E-mail válido é obrigatório'));
			exit();
		}

		if ($cpf === '') {
			echo json_encode(array('success' => false, 'error' => 'CPF é obrigatório'));
			exit();
		}

		// Remove formatação do CPF (deixa só números)
		$cpf = preg_replace('/[^0-9]/', '', $cpf);

		if (strlen($cpf) !== 11) {
			echo json_encode(array('success' => false, 'error' => 'CPF deve ter 11 dígitos'));
			exit();
		}

		if ($senha === '' || strlen($senha) < 6) {
			echo json_encode(array('success' => false, 'error' => 'Senha deve ter no mínimo 6 caracteres'));
			exit();
		}

		// Verifica se o e-mail já existe
		$stmtCheckEmail = $conn->prepare("SELECT id FROM quiz_colaboradores WHERE email = ?");
		$stmtCheckEmail->bind_param('s', $email);
		$stmtCheckEmail->execute();
		$resultCheckEmail = $stmtCheckEmail->get_result();

		if ($resultCheckEmail->num_rows > 0) {
			echo json_encode(array('success' => false, 'error' => 'Este e-mail já está cadastrado'));
			$stmtCheckEmail->close();
			exit();
		}
		$stmtCheckEmail->close();

		// Verifica se o CPF já existe
		$stmtCheckCPF = $conn->prepare("SELECT id FROM quiz_colaboradores WHERE cpf = ?");
		$stmtCheckCPF->bind_param('s', $cpf);
		$stmtCheckCPF->execute();
		$resultCheckCPF = $stmtCheckCPF->get_result();

		if ($resultCheckCPF->num_rows > 0) {
			echo json_encode(array('success' => false, 'error' => 'Este CPF já está cadastrado'));
			$stmtCheckCPF->close();
			exit();
		}
		$stmtCheckCPF->close();

		// Hash da senha (bcrypt)
		$senhaHash = password_hash($senha, PASSWORD_BCRYPT);

		// Insere o novo colaborador
		$stmt = $conn->prepare("INSERT INTO quiz_colaboradores (nome, email, cpf, senha, foto_adulto, foto_crianca) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param('ssssss', $nome, $email, $cpf, $senhaHash, $foto_adulto, $foto_crianca);

		if (!$stmt->execute()) {
			echo json_encode(array('success' => false, 'error' => 'Erro ao cadastrar colaborador'));
			$stmt->close();
			exit();
		}

		$colaborador_id = $conn->insert_id;
		$stmt->close();

		echo json_encode(array(
			'success' => true,
			'message' => 'Colaborador cadastrado com sucesso',
			'colaborador' => array(
				'id' => $colaborador_id,
				'nome' => $nome,
				'email' => $email
			)
		));

	} elseif ($method === 'GET') {
		// Lista todos os colaboradores (para admin) - SEM MOSTRAR SENHA
		$result = $conn->query("SELECT id, nome, email, cpf, foto_adulto, foto_crianca, ativo, data_cadastro FROM quiz_colaboradores ORDER BY nome ASC");

		$colaboradores = array();
		while ($row = $result->fetch_assoc()) {
			// Mascara CPF (mostra só primeiros 3 e últimos 2 dígitos)
			$cpfMascarado = substr($row['cpf'], 0, 3) . '.***.***-' . substr($row['cpf'], -2);

			$colaboradores[] = array(
				'id' => (int)$row['id'],
				'nome' => $row['nome'],
				'email' => $row['email'],
				'cpf' => $cpfMascarado,
				'foto_adulto' => $row['foto_adulto'],
				'foto_crianca' => $row['foto_crianca'],
				'ativo' => (bool)$row['ativo'],
				'data_cadastro' => $row['data_cadastro']
			);
		}

		echo json_encode(array(
			'success' => true,
			'colaboradores' => $colaboradores,
			'total' => count($colaboradores)
		));

	} else {
		echo json_encode(array(
			'status' => 'API de Cadastro de Colaboradores',
			'usage' => array(
				'POST' => 'Cadastrar novo colaborador {"nome":"...", "email":"...", "cpf":"...", "senha":"..."}',
				'GET' => 'Listar todos os colaboradores'
			),
			'timestamp' => date('Y-m-d H:i:s')
		));
	}

	$conn->close();

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
