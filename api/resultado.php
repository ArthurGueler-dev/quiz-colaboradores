<?php
require __DIR__ . '/config.php';
require __DIR__ . '/utils.php';
cors();

$participanteId = bearer_participante_id($pdo);
if (!$participanteId) json_response(['error' => 'Não autorizado'], 401);

$total = (int)$pdo->prepare('SELECT COUNT(*) AS c FROM Respostas WHERE participante_id = ?')->execute([$participanteId]) && $pdo->query("SELECT COUNT(*) AS c FROM Respostas WHERE participante_id = {$participanteId}")->fetch()['c'];
$acertos = (int)$pdo->prepare('SELECT COUNT(*) AS c FROM Respostas WHERE participante_id = ? AND acertou = 1')->execute([$participanteId]) && $pdo->query("SELECT COUNT(*) AS c FROM Respostas WHERE participante_id = {$participanteId} AND acertou = 1")->fetch()['c'];

$pdo->prepare('UPDATE Participantes SET ja_jogou = 1 WHERE id = ?')->execute([$participanteId]);
$pdo->prepare('DELETE FROM Tokens WHERE participante_id = ?')->execute([$participanteId]);

json_response(['acertos' => $acertos, 'total' => $total]);
