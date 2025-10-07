const express = require('express');
const path = require('path');
const fs = require('fs');
const Database = require('better-sqlite3');
const { v4: uuidv4 } = require('uuid');

const app = express();
const PORT = process.env.PORT || 3000;

// DB setup
const dbDir = path.join(__dirname, 'db');
const dbPath = path.join(dbDir, 'quiz.db');
fs.mkdirSync(dbDir, { recursive: true });
const db = new Database(dbPath);

// Apply schema
const schemaPath = path.join(dbDir, 'schema.sql');
if (fs.existsSync(schemaPath)) {
	const schemaSql = fs.readFileSync(schemaPath, 'utf8');
	if (schemaSql.trim().length > 0) {
		db.exec(schemaSql);
	}
}

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Simple in-memory session store: token -> participante_id
const tokenToParticipant = new Map();

// Static frontend with optimized caching
app.use(express.static(path.join(__dirname, 'public'), {
	maxAge: '1h', // Cache static files for 1 hour
	setHeaders: (res, path) => {
		// Don't cache JS/CSS to ensure updates are seen
		if (path.endsWith('.js') || path.endsWith('.css')) {
			res.set('Cache-Control', 'no-cache');
		}
	}
}));

// Helpers
function getAllCollaborators() {
	const stmt = db.prepare('SELECT id, nome, foto_adulto, foto_crianca FROM Participantes');
	return stmt.all();
}

function getParticipantByCode(codigo) {
	const stmt = db.prepare('SELECT * FROM Participantes WHERE codigo_unico = ?');
	return stmt.get(codigo);
}

function markParticipantPlayed(id) {
	const stmt = db.prepare('UPDATE Participantes SET ja_jogou = 1 WHERE id = ?');
	stmt.run(id);
}

function ensureAuth(req, res, next) {
	const auth = req.headers['authorization'] || '';
	const parts = auth.split(' ');
	if (parts.length === 2 && parts[0] === 'Bearer') {
		const token = parts[1];
		if (tokenToParticipant.has(token)) {
			req.participanteId = tokenToParticipant.get(token);
			return next();
		}
	}
	return res.status(401).json({ error: 'Não autorizado' });
}

function buildQuizForParticipant(currentParticipantId, totalQuestions = 5) {
	const people = getAllCollaborators();
	const others = people.filter(p => p.id !== currentParticipantId);
	if (others.length < 3) {
		throw new Error('É necessário pelo menos 3 colaboradores além do jogador para montar o quiz.');
	}
	// Shuffle helper
	const shuffled = arr => arr.map(v => ({ v, r: Math.random() }))
		.sort((a, b) => a.r - b.r)
		.map(({ v }) => v);

	const targets = shuffled(others).slice(0, Math.min(totalQuestions, others.length));

	const questions = targets.map(target => {
		// pick 2 decoys not equal target
		const decoysPool = others.filter(p => p.id !== target.id);
		const decoys = shuffled(decoysPool).slice(0, 2);
		const options = shuffled([target, ...decoys]).map(o => ({
			id: o.id,
			nome: o.nome,
			foto_adulto: o.foto_adulto
		}));
		return {
			pergunta_id: target.id,
			foto_crianca: target.foto_crianca,
			opcoes: options
		};
	});
	return questions;
}

// Routes
app.post('/api/colaborador_login.php', (req, res) => {
	const { email, senha } = req.body || {};
	if (!email || !senha) {
		return res.json({ success: false, error: 'E-mail e senha são obrigatórios' });
	}

	const stmt = db.prepare('SELECT * FROM Participantes WHERE email = ? AND senha = ?');
	const participante = stmt.get(email, senha);

	if (!participante) {
		return res.json({ success: false, error: 'E-mail ou senha incorretos' });
	}

	// Verifica se já jogou
	if (participante.ja_jogou) {
		const resultStmt = db.prepare('SELECT COUNT(*) as total, SUM(acertou) as acertos, MAX(created_at) as data FROM Respostas WHERE participante_id = ?');
		const resultado = resultStmt.get(participante.id);
		return res.json({
			success: false,
			ja_jogou: true,
			resultado: {
				acertos: resultado.acertos || 0,
				total: resultado.total || 0,
				data: resultado.data
			}
		});
	}

	const token = uuidv4();
	tokenToParticipant.set(token, participante.id);
	return res.json({
		success: true,
		token,
		participante: {
			id: participante.id,
			nome: participante.nome,
			email: participante.email,
			cpf: participante.cpf || ''
		}
	});
});

app.post('/login', (req, res) => {
	const { codigo } = req.body || {};
	if (!codigo) {
		return res.status(400).json({ error: 'Código é obrigatório' });
	}
	const participante = getParticipantByCode(codigo);
	if (!participante) {
		return res.status(404).json({ error: 'Código não encontrado' });
	}
	if (participante.ja_jogou) {
		return res.status(409).json({ error: 'Você já participou' });
	}
	const token = uuidv4();
	tokenToParticipant.set(token, participante.id);
	return res.json({ token, participante: { id: participante.id, nome: participante.nome } });
});

app.get('/api/quiz.php', ensureAuth, (req, res) => {
	try {
		const questions = buildQuizForParticipant(req.participanteId);
		return res.json({ questions });
	} catch (e) {
		return res.status(500).json({ error: e.message });
	}
});

app.get('/quiz', ensureAuth, (req, res) => {
	try {
		const questions = buildQuizForParticipant(req.participanteId);
		return res.json({ questions });
	} catch (e) {
		return res.status(500).json({ error: e.message });
	}
});

app.post('/api/responder.php', ensureAuth, (req, res) => {
	const { pergunta_id, colaborador_escolhido_id } = req.body || {};
	if (!pergunta_id || !colaborador_escolhido_id) {
		return res.status(400).json({ error: 'Campos obrigatórios ausentes' });
	}
	const acertou = Number(colaborador_escolhido_id) === Number(pergunta_id) ? 1 : 0;
	const insert = db.prepare('INSERT INTO Respostas (participante_id, colaborador_escolhido_id, acertou) VALUES (?, ?, ?)');
	insert.run(req.participanteId, colaborador_escolhido_id, acertou);
	return res.json({ ok: true, acertou: !!acertou });
});

app.post('/responder', ensureAuth, (req, res) => {
	const { pergunta_id, colaborador_escolhido_id } = req.body || {};
	if (!pergunta_id || !colaborador_escolhido_id) {
		return res.status(400).json({ error: 'Campos obrigatórios ausentes' });
	}
	const acertou = Number(colaborador_escolhido_id) === Number(pergunta_id) ? 1 : 0;
	const insert = db.prepare('INSERT INTO Respostas (participante_id, colaborador_escolhido_id, acertou) VALUES (?, ?, ?)');
	insert.run(req.participanteId, colaborador_escolhido_id, acertou);
	return res.json({ ok: true, acertou: !!acertou });
});

app.post('/api/salvar_resultado.php', ensureAuth, (req, res) => {
	const totalStmt = db.prepare('SELECT COUNT(*) as total FROM Respostas WHERE participante_id = ?');
	const acertosStmt = db.prepare('SELECT COUNT(*) as acertos FROM Respostas WHERE participante_id = ? AND acertou = 1');
	const total = totalStmt.get(req.participanteId).total;
	const acertos = acertosStmt.get(req.participanteId).acertos;
	markParticipantPlayed(req.participanteId);
	for (const [token, pid] of tokenToParticipant.entries()) {
		if (pid === req.participanteId) tokenToParticipant.delete(token);
	}
	return res.json({ success: true, acertos, total });
});

app.post('/resultado', ensureAuth, (req, res) => {
	const totalStmt = db.prepare('SELECT COUNT(*) as total FROM Respostas WHERE participante_id = ?');
	const acertosStmt = db.prepare('SELECT COUNT(*) as acertos FROM Respostas WHERE participante_id = ? AND acertou = 1');
	const total = totalStmt.get(req.participanteId).total;
	const acertos = acertosStmt.get(req.participanteId).acertos;
	markParticipantPlayed(req.participanteId);
	for (const [token, pid] of tokenToParticipant.entries()) {
		if (pid === req.participanteId) tokenToParticipant.delete(token);
	}
	return res.json({ acertos, total });
});

app.get('/health', (_req, res) => res.json({ ok: true }));

app.listen(PORT, () => {
	console.log(`Servidor ouvindo em http://localhost:${PORT}`);
});
