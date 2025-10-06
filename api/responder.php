<?php
// ==========================================
// Quiz - Responder Pergunta
// api/responder.php
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

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		echo json_encode(array('success' => false, 'error' => 'Método não permitido'));
		exit();
	}

	$raw = file_get_contents('php://input');
	$decoded = json_decode($raw, true);
	$body = is_array($decoded) ? $decoded : array();

	$pergunta_id = isset($body['pergunta_id']) ? (int)$body['pergunta_id'] : 0;
	$colaborador_escolhido_id = isset($body['colaborador_escolhido_id']) ? (int)$body['colaborador_escolhido_id'] : 0;

	if ($pergunta_id === 0 || $colaborador_escolhido_id === 0) {
		echo json_encode(array('success' => false, 'error' => 'Dados inválidos'));
		exit();
	}

	// A resposta está correta se o ID escolhido é igual ao ID da pergunta
	$acertou = ($pergunta_id === $colaborador_escolhido_id);

	echo json_encode(array(
		'ok' => true,
		'acertou' => $acertou,
		'pergunta_id' => $pergunta_id,
		'colaborador_escolhido_id' => $colaborador_escolhido_id
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
