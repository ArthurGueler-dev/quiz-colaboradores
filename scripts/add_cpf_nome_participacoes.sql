-- ==========================================
-- Adiciona colunas CPF e Nome na tabela quiz_participacoes
-- ==========================================

USE f137049_in9aut;

-- Adiciona coluna nome (caso não exista)
ALTER TABLE quiz_participacoes
ADD COLUMN IF NOT EXISTS nome VARCHAR(200) DEFAULT NULL AFTER colaborador_id;

-- Adiciona coluna cpf (caso não exista)
ALTER TABLE quiz_participacoes
ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) DEFAULT NULL AFTER nome;

-- Cria índice para facilitar busca por CPF
ALTER TABLE quiz_participacoes
ADD INDEX IF NOT EXISTS idx_cpf (cpf);

-- Atualiza registros existentes com dados dos colaboradores
UPDATE quiz_participacoes p
INNER JOIN quiz_colaboradores c ON p.colaborador_id = c.id
SET p.nome = c.nome, p.cpf = c.cpf
WHERE p.nome IS NULL OR p.cpf IS NULL;

-- Exibe estrutura atualizada
DESCRIBE quiz_participacoes;
