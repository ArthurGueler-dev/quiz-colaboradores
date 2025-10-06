<?php
// Mostra todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Teste de Importação</h1>";

// Teste 1: Conexão com banco
echo "<h2>1. Teste de Conexão com Banco</h2>";
try {
    $conn = new PDO("mysql:host=187.49.226.10;port=3306;dbname=f137049_in9aut;charset=utf8mb4", 'f137049_tool', 'In9@1234qwer');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexão OK<br>";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 2: Localizar pasta
echo "<h2>2. Teste de Localização da Pasta</h2>";
$possiveisCaminhos = [
    __DIR__ . '/../Dia das Crianças',
    __DIR__ . '/../../Dia das Crianças',
    $_SERVER['DOCUMENT_ROOT'] . '/Dia das Crianças',
];

$basePath = null;
foreach ($possiveisCaminhos as $caminho) {
    echo "Testando: $caminho<br>";
    if (is_dir($caminho)) {
        echo "✅ Encontrado!<br>";
        $basePath = $caminho;
        break;
    } else {
        echo "❌ Não existe<br>";
    }
}

if (!$basePath) {
    echo "<strong>❌ Pasta não encontrada!</strong><br>";
    exit;
}

// Teste 3: Listar pastas dos colaboradores
echo "<h2>3. Pastas de Colaboradores</h2>";
$folders = scandir($basePath);
$count = 0;
foreach ($folders as $folder) {
    if ($folder === '.' || $folder === '..') continue;
    $folderPath = $basePath . '/' . $folder;
    if (is_dir($folderPath)) {
        echo "📁 $folder<br>";
        $count++;
    }
}
echo "<strong>Total: $count colaboradores</strong><br>";

// Teste 4: Verificar estrutura da tabela
echo "<h2>4. Verificar Tabela quiz_colaboradores</h2>";
try {
    $result = $conn->query("SHOW COLUMNS FROM quiz_colaboradores");
    echo "<table border='1' cellpadding='5'><tr><th>Campo</th><th>Tipo</th></tr>";
    while ($row = $result->fetch()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "❌ Erro ao verificar tabela: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ Todos os testes OK! Pronto para importar.</h2>";
