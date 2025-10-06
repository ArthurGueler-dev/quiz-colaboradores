<?php
// ==========================================
// Admin - Autenticação via tabela Users (cPanel)
// api/admin_login_cpanel.php
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

// Ajuste as credenciais abaixo para o seu cPanel/phpMyAdmin
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

	$method = $_SERVER['REQUEST_METHOD'];
	// Suporta GET (?username=&password=) e POST (JSON)
	if ($method === 'GET' || $method === 'POST') {
		$username = '';
		$passwordInput = '';

		if ($method === 'GET') {
			$username = isset($_GET['username']) ? trim($_GET['username']) : '';
			$passwordInput = isset($_GET['password']) ? trim($_GET['password']) : '';
		} else {
			$raw = file_get_contents('php://input');
			$decoded = json_decode($raw, true);
			$body = is_array($decoded) ? $decoded : array();
			$username = isset($body['username']) ? trim($body['username']) : '';
			$passwordInput = isset($body['password']) ? trim($body['password']) : '';
		}

		if ($username === '' || $passwordInput === '') {
			echo json_encode(array('success' => false, 'error' => 'Username e senha são obrigatórios'));
			exit();
		}

		$stmt = $conn->prepare("SELECT Username, Password, Aplicativos, IsAdmin, IsRoot FROM Users WHERE Username = ?");
		if (!$stmt) {
			echo json_encode(array('success' => false, 'error' => 'Erro ao preparar query'));
			exit();
		}
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows === 0) {
			echo json_encode(array('success' => false, 'error' => 'Usuário não encontrado'));
			$stmt->close();
			exit();
		}

		$userData = $result->fetch_assoc();

		if (empty($userData['Password'])) {
			echo json_encode(array('success' => false, 'error' => 'Usuário sem senha configurada'));
			$stmt->close();
			exit();
		}

		// Hash SHA-256 repetido 1000x (compatível com seu exemplo)
		$hash = $passwordInput;
		for ($i = 0; $i < 1000; $i++) {
			$hash = hash('sha256', $hash);
		}

		if ($hash !== $userData['Password']) {
			echo json_encode(array('success' => false, 'error' => 'Senha incorreta'));
			$stmt->close();
			exit();
		}

		$aplicativos = $userData['Aplicativos'] ? $userData['Aplicativos'] : '';
		$hasAdmin = (strpos($aplicativos, 'COL_ADM') !== false) || (bool)$userData['IsAdmin'] || (bool)$userData['IsRoot'];

		// Exemplo: exigir perfil admin para painel (altere conforme sua regra)
		if (!$hasAdmin) {
			echo json_encode(array('success' => false, 'error' => 'Sem permissão para o painel'));
			$stmt->close();
			exit();
		}

		$role = $hasAdmin ? 'admin' : 'operator';
		$responseUser = array(
			'id' => $userData['Username'] . '_' . time(),
			'username' => $userData['Username'],
			'name' => ucfirst($userData['Username']),
			'role' => $role,
			'isAdmin' => (bool)$userData['IsAdmin'],
			'isRoot' => (bool)$userData['IsRoot'],
			'aplicativos' => $userData['Aplicativos'],
			'permissions' => array(
				'col_admin' => (strpos($aplicativos, 'COL_ADM') !== false),
				'col_user' => (strpos($aplicativos, 'COL_USER') !== false)
			),
			'lastLogin' => date('Y-m-d\TH:i:s\Z')
		);

		echo json_encode(array('success' => true, 'message' => 'Login ok', 'user' => $responseUser));
		$stmt->close();
	} else {
		echo json_encode(array(
			'status' => 'API Admin funcionando',
			'usage' => array('GET ?username=USUARIO&password=SENHA', 'POST {"username":"USUARIO","password":"SENHA"}'),
			'timestamp' => date('Y-m-d H:i:s')
		));
	}

	$conn->close();

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
