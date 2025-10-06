<?php
header('Content-Type: text/html; charset=utf-8');

$host = '187.49.226.10';
$port = 3306;
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

$nomes = [
    'WALACE JUNIOR',
    'VICTORIA SANTOS',
    'SABRINA SARMENTO',
    'RENAN GOMES',
    'LAIS MELLO',
    'HELIO DE OLIVEIRA',
    'EVELIN MARTINS',
    'DIEGO SOUZA DOS ANJOS',
    'ANDRE CARNEIRO',
    'ALZIRENE RAMBO'
];

echo "<h1>Inverter Fotos</h1>";
echo "<p>Invertendo fotos dos seguintes colaboradores:</p><ul>";

$invertidos = 0;
foreach ($nomes as $nome) {
    $stmt = $conn->prepare("
        UPDATE quiz_colaboradores
        SET foto_adulto = @temp := foto_adulto,
            foto_adulto = foto_crianca,
            foto_crianca = @temp
        WHERE nome = ?
    ");

    // MySQL não suporta variáveis em prepared statements assim, então fazemos diferente:
    $stmt = $conn->prepare("SELECT foto_adulto, foto_crianca FROM quiz_colaboradores WHERE nome = ?");
    $stmt->execute([$nome]);
    $row = $stmt->fetch();

    if ($row) {
        $stmt = $conn->prepare("UPDATE quiz_colaboradores SET foto_adulto = ?, foto_crianca = ? WHERE nome = ?");
        $stmt->execute([$row['foto_crianca'], $row['foto_adulto'], $nome]);
        echo "<li>✅ $nome - Fotos invertidas</li>";
        $invertidos++;
    } else {
        echo "<li>❌ $nome - Não encontrado</li>";
    }
}

echo "</ul>";
echo "<p><strong>Total invertidos: $invertidos</strong></p>";
echo "<p><a href='verificar_colaboradores.php'>Ver colaboradores</a></p>";
