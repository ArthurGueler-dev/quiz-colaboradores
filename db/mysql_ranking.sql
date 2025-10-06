SELECT p.nome AS nome_participante,
       COALESCE(SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END), 0) AS total_acertos
FROM Participantes p
LEFT JOIN Respostas r ON r.participante_id = p.id
GROUP BY p.id, p.nome
ORDER BY total_acertos DESC, p.nome ASC;
