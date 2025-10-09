<?php
// ==========================================
// Quiz - Enviar Código de Verificação por Email
// api/enviar_codigo_verificacao.php
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

    createEmailVerificationTable($conn);

    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    $body = is_array($decoded) ? $decoded : array();

    $email = isset($body['email']) ? trim($body['email']) : '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        echo json_encode(array('success' => false, 'error' => 'E-mail inválido'));
        exit();
    }

    // Verifica se o email existe no banco
    $stmt = $conn->prepare("SELECT id, nome, ativo FROM quiz_colaboradores WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        echo json_encode(array('success' => false, 'error' => 'E-mail não cadastrado'));
        exit();
    }

    $colaborador = $result->fetch_assoc();
    $stmt->close();

    // Se já está ativo, não precisa verificar novamente
    if ($colaborador['ativo']) {
        $conn->close();
        echo json_encode(array('success' => false, 'error' => 'E-mail já verificado. Faça login normalmente.'));
        exit();
    }

    // Gera código de verificação
    $codigo = createEmailVerification($conn, $email, $colaborador['id']);

    if (!$codigo) {
        $conn->close();
        echo json_encode(array('success' => false, 'error' => 'Erro ao gerar código de verificação'));
        exit();
    }

    // ENVIA EMAIL COM O CÓDIGO
    $subject = "Código de Verificação - Quiz dos Colaboradores";
    $message = "
    Olá {$colaborador['nome']},

    Seu código de verificação é: {$codigo}

    Este código expira em 30 minutos.

    Se você não solicitou este código, ignore esta mensagem.

    Atenciosamente,
    Equipe IN9 Automação
    ";

    $headers = "From: noreply@floripa.in9automacao.com.br\r\n";
    $headers .= "Reply-To: noreply@floripa.in9automacao.com.br\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Tenta enviar o email
    if (mail($email, $subject, $message, $headers)) {
        $conn->close();
        echo json_encode(array(
            'success' => true,
            'message' => 'Código enviado para ' . $email,
            'codigo' => $codigo  // REMOVER EM PRODUÇÃO - apenas para testes
        ));
    } else {
        $conn->close();
        // Em desenvolvimento, retorna o código mesmo se o email falhar
        echo json_encode(array(
            'success' => true,
            'message' => 'Email não enviado (servidor não configurado), mas código gerado',
            'codigo' => $codigo  // Útil para testes
        ));
    }

} catch (Exception $e) {
    echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
