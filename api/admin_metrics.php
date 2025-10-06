<?php
require __DIR__ . '/config.php';
require __DIR__ . '/utils.php';
session_start();
cors();

if (!isset($_SESSION['admin'])) {
	http_response_code(401);
	header('Content-Type: application/json');
	echo json_encode(['error' => 'Não autorizado']);
	exit;
}

// KPIs principais
$totParticipantes = (int)$pdo->query('SELECT COUNT(*) AS c FROM Participantes')->fetch()['c'];
$totJogaram = (int)$pdo->query('SELECT COUNT(*) AS c FROM Participantes WHERE ja_jogou = 1')->fetch()['c'];
$totNaoJogaram = $totParticipantes - $totJogaram;

$totRespostas = (int)$pdo->query('SELECT COUNT(*) AS c FROM Respostas')->fetch()['c'];
$totAcertos = (int)$pdo->query('SELECT COUNT(*) AS c FROM Respostas WHERE acertou = 1')->fetch()['c'];
$acuraciaGlobal = $totRespostas > 0 ? round(($totAcertos / $totRespostas) * 100, 2) : 0.0;

// Ranking por participante
$rankingStmt = $pdo->query(
	"SELECT p.id, p.nome,
		COALESCE(SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END), 0) AS acertos,
		COUNT(r.id) AS total,
		CASE WHEN COUNT(r.id) > 0 THEN ROUND(100.0 * SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) / COUNT(r.id), 2) ELSE 0 END AS taxa
	FROM Participantes p
	LEFT JOIN Respostas r ON r.participante_id = p.id
	GROUP BY p.id, p.nome
	ORDER BY acertos DESC, p.nome ASC"
);
$ranking = $rankingStmt->fetchAll();

// Distribuição de acertos
$distStmt = $pdo->query(
	"SELECT acertos, COUNT(*) AS quantidade FROM (
		SELECT p.id, COALESCE(SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END), 0) AS acertos
		FROM Participantes p
		LEFT JOIN Respostas r ON r.participante_id = p.id
		GROUP BY p.id
	) t GROUP BY acertos ORDER BY acertos ASC"
);
$distribuicao = $distStmt->fetchAll();

// Séries por dia
$porDiaStmt = $pdo->query(
	"SELECT DATE(r.created_at) AS dia,
		COUNT(DISTINCT r.participante_id) AS participantes,
		COUNT(*) AS respostas,
		SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) AS acertos
	FROM Respostas r
	GROUP BY DATE(r.created_at)
	ORDER BY dia ASC"
);
$porDia = $porDiaStmt->fetchAll();

json_response([
	'kpis' => [
		'total_participantes' => $totParticipantes,
		'total_jogaram' => $totJogaram,
		'total_nao_jogaram' => $totNaoJogaram,
		'total_respostas' => $totRespostas,
		'total_acertos' => $totAcertos,
		'acuracia_global_pct' => $acuraciaGlobal,
	],
	'ranking' => $ranking,
	'distribuicao_acertos' => $distribuicao,
	'por_dia' => $porDia,
]);
