<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
	$conn = new mysqli($host, $user, $password, $database, $port);

	if ($conn->connect_error) {
		die(json_encode(array('error' => 'Conexão falhou: ' . $conn->connect_error)));
	}

	$result = $conn->query("SELECT COUNT(*) as total FROM quiz_colaboradores WHERE foto_crianca IS NOT NULL AND foto_crianca != ''");
	$row = $result->fetch_assoc();

	echo json_encode(array(
		'success' => true,
		'total_colaboradores' => $row['total'],
		'message' => 'Conexão OK'
	));

} catch (Exception $e) {
	echo json_encode(array('error' => $e->getMessage()));
}
