<?php
// Teste local - simula o admin_login.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit(0);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['user'] ?? '');
$pass = trim($body['pass'] ?? '');

// Temporário: aceita arthurgueler com qualquer senha para teste
if ($username === 'arthurgueler') {
	echo json_encode(['ok' => true, 'user' => ['id' => 1, 'username' => $username]]);
} else {
	http_response_code(401);
	echo json_encode(['error' => 'Usuário não encontrado']);
}
