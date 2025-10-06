<?php
// CORS headers primeiro
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
if ($origin !== '*') {
	header('Access-Control-Allow-Origin: ' . $origin);
	header('Vary: Origin');
	header('Access-Control-Allow-Credentials: true');
} else {
	header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit(0);
}

require __DIR__ . '/config.php';
require __DIR__ . '/utils.php';
session_start();

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
	$body = array();
}
$username = isset($body['user']) ? trim($body['user']) : '';
$pass = isset($body['pass']) ? trim($body['pass']) : '';

if (empty($username) || empty($pass)) {
	json_response(array('error' => 'Usuário e senha são obrigatórios'), 400);
}

try {
	// Busca usuário na tabela Users
	$stmt = $pdo->prepare("SELECT id, username, password, isAdmin FROM Users WHERE username = ? LIMIT 1");
	$stmt->execute(array($username));
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$user) {
		json_response(array('error' => 'Usuário não encontrado'), 401);
	}

	// Verifica se é admin
	if ($user['isAdmin'] != 1) {
		json_response(array('error' => 'Acesso negado. Usuário não é administrador'), 403);
	}

	// Verifica senha (bcrypt)
	if (!password_verify($pass, $user['password'])) {
		json_response(array('error' => 'Senha incorreta'), 401);
	}

	// Login bem-sucedido
	$_SESSION['admin'] = array('user' => $username, 'id' => $user['id'], 'ts' => time());
	json_response(array('ok' => true, 'user' => array('id' => $user['id'], 'username' => $username)));

} catch (Exception $e) {
	json_response(array('error' => 'Erro interno: ' . $e->getMessage()), 500);
}
