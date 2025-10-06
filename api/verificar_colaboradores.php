<?php
header('Content-Type: text/html; charset=utf-8');

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
    die("Erro de conexão: " . $e->getMessage());
}

echo "<h1>Colaboradores no Banco de Dados</h1>";

$result = $conn->query("SELECT id, nome, email, foto_adulto, foto_crianca FROM quiz_colaboradores ORDER BY id DESC LIMIT 50");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Foto Adulto</th><th>Foto Criança</th><th>Preview Criança</th></tr>";

$count = 0;
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $count++;
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nome']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>" . substr($row['foto_adulto'], 0, 50) . "...</td>";
    echo "<td>" . substr($row['foto_crianca'], 0, 50) . "...</td>";

    // Preview da foto criança
    if ($row['foto_crianca']) {
        echo "<td><img src='{$row['foto_crianca']}' style='max-width:100px;max-height:100px;' onerror=\"this.src='https://via.placeholder.com/100x100?text=Erro'\"></td>";
    } else {
        echo "<td>-</td>";
    }
    echo "</tr>";
}

echo "</table>";
echo "<p><strong>Total: $count colaboradores</strong></p>";
