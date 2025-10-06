<?php
function json_response($data, $status = 200) {
	http_response_code($status);
	header('Content-Type: application/json');
	echo json_encode($data);
	exit;
}

function cors() {
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
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
		exit;
	}
}

function bearer_participante_id(PDO $pdo) {
	$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
	if (stripos($auth, 'Bearer ') !== 0) return null;
	$token = substr($auth, 7);
	if (!$token) return null;
	$stmt = $pdo->prepare('SELECT participante_id FROM Tokens WHERE token = ?');
	$stmt->execute([$token]);
	$row = $stmt->fetch();
	return $row['participante_id'] ?? null;
}
