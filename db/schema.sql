PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS Participantes (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	nome TEXT NOT NULL,
	foto_adulto TEXT NOT NULL,
	foto_crianca TEXT NOT NULL,
	codigo_unico TEXT NOT NULL UNIQUE,
	email TEXT,
	senha TEXT,
	ja_jogou INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS Respostas (
	id INTEGER PRIMARY KEY AUTOINCREMENT,
	participante_id INTEGER NOT NULL,
	colaborador_escolhido_id INTEGER NOT NULL,
	acertou INTEGER NOT NULL CHECK (acertou IN (0,1)),
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (participante_id) REFERENCES Participantes(id) ON DELETE CASCADE,
	FOREIGN KEY (colaborador_escolhido_id) REFERENCES Participantes(id) ON DELETE CASCADE
);
