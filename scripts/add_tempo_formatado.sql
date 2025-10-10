-- ==========================================
-- Adiciona coluna tempo_formatado e atualiza registros existentes
-- ==========================================

USE f137049_in9aut;

-- Adiciona coluna tempo_formatado (caso não exista)
ALTER TABLE quiz_participacoes
ADD COLUMN IF NOT EXISTS tempo_formatado VARCHAR(10) DEFAULT NULL AFTER tempo_total_segundos;

-- Atualiza registros existentes com tempo formatado
UPDATE quiz_participacoes
SET tempo_formatado = CONCAT(
    LPAD(FLOOR(tempo_total_segundos / 60), 2, '0'),
    ':',
    LPAD(tempo_total_segundos % 60, 2, '0')
)
WHERE tempo_formatado IS NULL OR tempo_formatado = '';

-- Verifica resultado
SELECT id, nome, cpf, acertos, total, tempo_total_segundos, tempo_formatado, data_participacao
FROM quiz_participacoes
ORDER BY acertos DESC, tempo_total_segundos ASC
LIMIT 10;
