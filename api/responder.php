<?php
// ==========================================
// Quiz - Responder Pergunta (Seguro)
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

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/session_manager.php';

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		echo json_encode(array('success' => false, 'error' => 'Método não permitido'));
		exit();
	}

	// Valida token
	$conn = getDbConnection();
	if (!$conn) {
		echo json_encode(array('success' => false, 'error' => 'Erro de conexão'));
		exit();
	}

	$token = getTokenFromHeader();
	if (!$token) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Token não fornecido'));
		exit();
	}

	$session = validateToken($conn, $token);
	if (!$session) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Sessão inválida ou expirada'));
		exit();
	}

	// Obtém os dados do quiz da sessão
	$quizData = getQuizDataFromSession($conn, $token);
	if (!$quizData || !isset($quizData['respostas_corretas'])) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Dados do quiz não encontrados. Reinicie o quiz.'));
		exit();
	}

	$respostas_corretas = $quizData['respostas_corretas'];

	// Obtém dados enviados pelo frontend
	$raw = file_get_contents('php://input');
	$decoded = json_decode($raw, true);
	$body = is_array($decoded) ? $decoded : array();

	$pergunta_id = isset($body['pergunta_id']) ? trim($body['pergunta_id']) : '';
	$colaborador_escolhido_id = isset($body['colaborador_escolhido_id']) ? (int)$body['colaborador_escolhido_id'] : 0;

	if (empty($pergunta_id) || $colaborador_escolhido_id === 0) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Dados inválidos'));
		exit();
	}

	// Verifica se a pergunta existe nas respostas corretas
	if (!isset($respostas_corretas[$pergunta_id])) {
		$conn->close();
		echo json_encode(array('success' => false, 'error' => 'Pergunta não encontrada'));
		exit();
	}

	// Valida a resposta usando os dados SEGUROS do backend
	$resposta_correta = $respostas_corretas[$pergunta_id];
	$acertou = ($colaborador_escolhido_id === (int)$resposta_correta);

	$conn->close();

	echo json_encode(array(
		'ok' => true,
		'acertou' => $acertou
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
