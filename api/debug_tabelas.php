<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

$conn = new mysqli($host, $user, $password, $database, $port);
if ($conn->connect_error) {
	echo json_encode(array('error' => 'Erro de conexão'));
	exit();
}

// Verifica se tabela existe
$result = $conn->query("SHOW TABLES LIKE 'quiz_participacoes'");
if ($result->num_rows == 0) {
	echo json_encode(array('error' => 'Tabela quiz_participacoes não existe'));
	exit();
}

// Mostra estrutura da tabela
$result = $conn->query("DESCRIBE quiz_participacoes");
$colunas = array();
while ($row = $result->fetch_assoc()) {
	$colunas[] = $row;
}

// Mostra um registro de exemplo
$result = $conn->query("SELECT * FROM quiz_participacoes LIMIT 1");
$exemplo = $result->fetch_assoc();

echo json_encode(array(
	'success' => true,
	'colunas' => $colunas,
	'exemplo' => $exemplo,
	'total_registros' => $conn->query("SELECT COUNT(*) as c FROM quiz_participacoes")->fetch_assoc()['c']
));

$conn->close();
