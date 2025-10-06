<?php
// Captura todos os erros
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer de saída para capturar qualquer saída indesejada
ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Função para retornar erro em JSON
function jsonError($message, $details = null) {
    ob_clean(); // Limpa qualquer saída anterior
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => $message,
        'detalhes' => $details
    ]);
    exit;
}

// Configurações do banco
$host = '187.49.226.10';
$port = 3306;
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    jsonError('Erro de conexão com banco de dados', $e->getMessage());
}

// Caminho base das fotos
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/Dia das Crianças';

// Verifica se o diretório existe
if (!is_dir($basePath)) {
    jsonError('Pasta "Dia das Crianças" não encontrada', $basePath);
}

// URL base para acessar as fotos
$baseUrl = '/Dia%20das%20Crian%C3%A7as';

// Limpa a tabela antes de importar (opcional - remova se quiser manter dados antigos)
// $conn->exec("TRUNCATE TABLE quiz_colaboradores");

$imported = 0;
$errors = [];

// Varre todas as pastas de colaboradores
$folders = scandir($basePath);

foreach ($folders as $folder) {
    if ($folder === '.' || $folder === '..') continue;

    $folderPath = $basePath . '/' . $folder;

    if (!is_dir($folderPath)) continue;

    // Pega todos os arquivos da pasta
    $files = scandir($folderPath);

    $fotoAdulto = null;
    $fotoCrianca = null;

    // Organiza arquivos em duas listas
    $arquivosAdulto = [];
    $arquivosCrianca = [];
    $arquivosIndefinidos = [];

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Verifica se é imagem
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;

        // Determina categoria pelo nome do arquivo
        if (preg_match('/(adulto|adulta)/i', $file)) {
            $arquivosAdulto[] = $file;
        } elseif (preg_match('/(crian[cç]a|crianca)/i', $file)) {
            $arquivosCrianca[] = $file;
        } else {
            $arquivosIndefinidos[] = $file;
        }
    }

    // Define fotos baseado na categorização
    if (count($arquivosAdulto) > 0) {
        $fotoAdulto = $baseUrl . '/' . rawurlencode($folder) . '/' . rawurlencode($arquivosAdulto[0]);
    }

    if (count($arquivosCrianca) > 0) {
        $fotoCrianca = $baseUrl . '/' . rawurlencode($folder) . '/' . rawurlencode($arquivosCrianca[0]);
    }

    // Se ainda falta alguma foto, usa os indefinidos em ordem alfabética
    sort($arquivosIndefinidos);
    if ($fotoAdulto === null && count($arquivosIndefinidos) > 0) {
        $fotoAdulto = $baseUrl . '/' . rawurlencode($folder) . '/' . rawurlencode($arquivosIndefinidos[0]);
        array_shift($arquivosIndefinidos);
    }

    if ($fotoCrianca === null && count($arquivosIndefinidos) > 0) {
        $fotoCrianca = $baseUrl . '/' . rawurlencode($folder) . '/' . rawurlencode($arquivosIndefinidos[0]);
    }

    // Se encontrou ambas as fotos, insere no banco
    if ($fotoAdulto && $fotoCrianca) {
        try {
            // Verifica se já existe pelo nome
            $stmt = $conn->prepare("SELECT id FROM quiz_colaboradores WHERE nome = ?");
            $stmt->execute([$folder]);
            $existe = $stmt->fetch();

            if ($existe) {
                // Atualiza apenas as fotos
                $stmt = $conn->prepare("UPDATE quiz_colaboradores SET foto_adulto = ?, foto_crianca = ? WHERE id = ?");
                $stmt->execute([$fotoAdulto, $fotoCrianca, $existe['id']]);
            } else {
                // Gera email e CPF únicos
                $timestamp = time() + $imported; // Incrementa para garantir unicidade
                $emailUnico = strtolower(str_replace(' ', '.', $folder)) . '.' . $timestamp . '@quiz.local';
                $cpfUnico = str_pad($timestamp % 99999999999, 11, '0', STR_PAD_LEFT); // CPF fake único

                // Insere novo
                $stmt = $conn->prepare("INSERT INTO quiz_colaboradores (nome, foto_adulto, foto_crianca, email, cpf, senha) VALUES (?, ?, ?, ?, ?, '')");
                $stmt->execute([$folder, $fotoAdulto, $fotoCrianca, $emailUnico, $cpfUnico]);
            }

            $imported++;
        } catch (PDOException $e) {
            $errors[] = "Erro ao importar $folder: " . $e->getMessage();
        }
    } else {
        $errors[] = "Pasta $folder não possui ambas as fotos (adulto e criança)";
    }
}

// Limpa buffer e retorna sucesso
ob_clean();
echo json_encode([
    'sucesso' => true,
    'importados' => $imported,
    'erros' => $errors
]);
