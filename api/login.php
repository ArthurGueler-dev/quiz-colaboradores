<?php
require __DIR__ . '/config.php';
require __DIR__ . '/utils.php';
cors();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$codigo = trim($body['codigo'] ?? '');
if ($codigo === '') {
	json_response(['error' => 'Código é obrigatório'], 400);
}

$stmt = $pdo->prepare('SELECT * FROM Participantes WHERE codigo_unico = ?');
$stmt->execute([$codigo]);
$participante = $stmt->fetch();
if (!$participante) {
	json_response(['error' => 'Código não encontrado'], 404);
}
if ((int)$participante['ja_jogou'] === 1) {
	json_response(['error' => 'Você já participou'], 409);
}

$token = bin2hex(random_bytes(16));
$pdo->prepare('INSERT INTO Tokens (token, participante_id) VALUES (?, ?)')->execute([$token, $participante['id']]);

json_response(['token' => $token, 'participante' => ['id' => (int)$participante['id'], 'nome' => $participante['nome']]]);
