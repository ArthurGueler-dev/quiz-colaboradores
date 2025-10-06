<?php
// Configuração de banco MySQL (ajuste as credenciais no cPanel)
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'seu_banco';
$DB_USER = getenv('DB_USER') ?: 'seu_usuario';
$DB_PASS = getenv('DB_PASS') ?: 'sua_senha';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
];

try {
	$pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode(['error' => 'Falha na conexão com o banco']);
	exit;
}
