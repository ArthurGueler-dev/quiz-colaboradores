<?php
// ==========================================
// Quiz - Verificar Código de Email
// api/verificar_codigo_email.php
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/session_manager.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(array('success' => false, 'error' => 'Método não permitido'));
        exit();
    }

    $conn = getDbConnection();
    if (!$conn) {
        echo json_encode(array('success' => false, 'error' => 'Erro de conexão'));
        exit();
    }

    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    $body = is_array($decoded) ? $decoded : array();

    $email = isset($body['email']) ? trim($body['email']) : '';
    $codigo = isset($body['codigo']) ? trim($body['codigo']) : '';

    if (empty($email) || empty($codigo)) {
        $conn->close();
        echo json_encode(array('success' => false, 'error' => 'E-mail e código são obrigatórios'));
        exit();
    }

    // Verifica o código
    $verified = verifyEmailCode($conn, $email, $codigo);

    if ($verified) {
        $conn->close();
        echo json_encode(array(
            'success' => true,
            'message' => 'E-mail verificado com sucesso! Você já pode fazer login.'
        ));
    } else {
        $conn->close();
        echo json_encode(array(
            'success' => false,
            'error' => 'Código inválido ou expirado'
        ));
    }

} catch (Exception $e) {
    echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
