<?php
// ==========================================
// Configuração centralizada do banco de dados
// api/db_config.php
// ==========================================

// Credenciais do banco
define('DB_HOST', '187.49.226.10');
define('DB_PORT', 3306);
define('DB_USER', 'f137049_tool');
define('DB_PASS', 'In9@1234qwer');
define('DB_NAME', 'f137049_in9aut');

// Função para criar conexão segura
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        error_log("Erro de conexão: " . $conn->connect_error);
        return null;
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}
