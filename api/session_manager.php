<?php
// ==========================================
// Gerenciador de Sessões Seguras
// api/session_manager.php
// ==========================================

require_once __DIR__ . '/db_config.php';

// Cria tabela de sessões se não existir
function createSessionsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS quiz_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(64) NOT NULL UNIQUE,
        colaborador_id INT NOT NULL,
        email VARCHAR(200) NOT NULL,
        quiz_data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        used TINYINT DEFAULT 0,
        INDEX idx_token (token),
        INDEX idx_colaborador (colaborador_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return $conn->query($sql);
}

// Cria tabela de verificação de email se não existir
function createEmailVerificationTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS quiz_email_verification (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(200) NOT NULL,
        verification_code VARCHAR(6) NOT NULL,
        colaborador_id INT NOT NULL,
        verified TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        INDEX idx_email (email),
        INDEX idx_code (verification_code),
        INDEX idx_colaborador (colaborador_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return $conn->query($sql);
}

// Gera token seguro
function generateSecureToken() {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    } else {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Gera código de verificação de 6 dígitos
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Cria nova sessão para colaborador
function createSession($conn, $colaborador_id, $email) {
    $token = generateSecureToken();
    $expires_at = date('Y-m-d H:i:s', strtotime('+2 hours'));

    // Limpa sessões antigas do colaborador
    $stmt = $conn->prepare("DELETE FROM quiz_sessions WHERE colaborador_id = ? OR expires_at < NOW()");
    $stmt->bind_param('i', $colaborador_id);
    $stmt->execute();
    $stmt->close();

    // Cria nova sessão
    $stmt = $conn->prepare("INSERT INTO quiz_sessions (token, colaborador_id, email, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('siss', $token, $colaborador_id, $email, $expires_at);

    if ($stmt->execute()) {
        $stmt->close();
        return $token;
    }

    $stmt->close();
    return null;
}

// Valida token e retorna dados da sessão
function validateToken($conn, $token) {
    if (empty($token)) {
        return null;
    }

    // Limpa sessões expiradas
    $conn->query("DELETE FROM quiz_sessions WHERE expires_at < NOW()");

    $stmt = $conn->prepare("SELECT id, colaborador_id, email, quiz_data, used FROM quiz_sessions WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }

    $session = $result->fetch_assoc();
    $stmt->close();

    return $session;
}

// Salva dados do quiz na sessão
function saveQuizDataToSession($conn, $token, $quizData) {
    // Valida conexão
    if (!$conn || !($conn instanceof mysqli) || $conn->connect_errno) {
        error_log("saveQuizDataToSession: Conexão inválida");
        return false;
    }

    $quizDataJson = json_encode($quizData);
    $stmt = $conn->prepare("UPDATE quiz_sessions SET quiz_data = ? WHERE token = ?");

    if (!$stmt) {
        error_log("saveQuizDataToSession: Erro no prepare - " . $conn->error);
        return false;
    }

    $stmt->bind_param('ss', $quizDataJson, $token);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Obtém dados do quiz da sessão
function getQuizDataFromSession($conn, $token) {
    // Valida conexão
    if (!$conn || !($conn instanceof mysqli) || $conn->connect_errno) {
        error_log("getQuizDataFromSession: Conexão inválida");
        return null;
    }

    $stmt = $conn->prepare("SELECT quiz_data FROM quiz_sessions WHERE token = ? AND expires_at > NOW()");

    if (!$stmt) {
        error_log("getQuizDataFromSession: Erro no prepare - " . $conn->error);
        return null;
    }

    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['quiz_data'] ? json_decode($row['quiz_data'], true) : null;
}

// Marca sessão como usada (após completar o quiz)
function markSessionAsUsed($conn, $token) {
    $stmt = $conn->prepare("UPDATE quiz_sessions SET used = 1 WHERE token = ?");
    $stmt->bind_param('s', $token);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Obtém token do cabeçalho Authorization
function getTokenFromHeader() {
    // Função compatível com cPanel e Apache CGI/FastCGI
    if (!function_exists('getallheaders')) {
        function getallheaders() {
            $headers = array();
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
    }

    $headers = getallheaders();

    // Tenta Authorization header
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
    }

    // Tenta variáveis alternativas do servidor
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
    }

    // Tenta redirect do Apache
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
    }

    return null;
}

// Cria código de verificação de email
function createEmailVerification($conn, $email, $colaborador_id) {
    $code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Remove verificações antigas para este email
    $stmt = $conn->prepare("DELETE FROM quiz_email_verification WHERE email = ? OR expires_at < NOW()");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();

    // Cria nova verificação
    $stmt = $conn->prepare("INSERT INTO quiz_email_verification (email, verification_code, colaborador_id, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssis', $email, $code, $colaborador_id, $expires_at);

    if ($stmt->execute()) {
        $stmt->close();
        return $code;
    }

    $stmt->close();
    return null;
}

// Verifica código de verificação de email
function verifyEmailCode($conn, $email, $code) {
    $stmt = $conn->prepare("SELECT id, colaborador_id FROM quiz_email_verification WHERE email = ? AND verification_code = ? AND verified = 0 AND expires_at > NOW()");
    $stmt->bind_param('ss', $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $row = $result->fetch_assoc();
    $verificationId = $row['id'];
    $colaboradorId = $row['colaborador_id'];
    $stmt->close();

    // Marca como verificado
    $stmt = $conn->prepare("UPDATE quiz_email_verification SET verified = 1 WHERE id = ?");
    $stmt->bind_param('i', $verificationId);
    $stmt->execute();
    $stmt->close();

    // Ativa o colaborador
    $stmt = $conn->prepare("UPDATE quiz_colaboradores SET ativo = 1 WHERE id = ?");
    $stmt->bind_param('i', $colaboradorId);
    $stmt->execute();
    $stmt->close();

    return true;
}

// Verifica se email já foi verificado
function isEmailVerified($conn, $colaborador_id) {
    $stmt = $conn->prepare("SELECT verified FROM quiz_email_verification WHERE colaborador_id = ? AND verified = 1 LIMIT 1");
    $stmt->bind_param('i', $colaborador_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $verified = $result->num_rows > 0;
    $stmt->close();

    return $verified;
}
