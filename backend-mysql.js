const express = require('express');
const mysql = require('mysql2/promise');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3002;

// Configuração do MySQL (mesma do PHP)
const pool = mysql.createPool({
	host: process.env.DB_HOST || 'localhost',
	user: process.env.DB_USER || 'root',
	password: process.env.DB_PASS || '',
	database: process.env.DB_NAME || 'quiz_colaboradores',
	waitForConnections: true,
	connectionLimit: 10,
	queueLimit: 0
});

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// CORS
app.use((req, res, next) => {
	res.header('Access-Control-Allow-Origin', '*');
	res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
	res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
	if (req.method === 'OPTIONS') return res.sendStatus(200);
	next();
});

// Cache otimizado
app.use(express.static(path.join(__dirname, 'public'), {
	maxAge: '1h',
	setHeaders: (res, path) => {
		if (path.endsWith('.js') || path.endsWith('.css')) {
			res.set('Cache-Control', 'no-cache');
		}
	}
}));

// Helper para embaralhar array
function shuffle(array) {
	const arr = [...array];
	for (let i = arr.length - 1; i > 0; i--) {
		const j = Math.floor(Math.random() * (i + 1));
		[arr[i], arr[j]] = [arr[j], arr[i]];
	}
	return arr;
}

// API: Login de colaborador
app.post('/api/colaborador_login.php', async (req, res) => {
	try {
		const { email, senha } = req.body;

		if (!email || !senha) {
			return res.json({ success: false, error: 'E-mail e senha são obrigatórios' });
		}

		const [rows] = await pool.query(
			'SELECT * FROM quiz_colaboradores WHERE email = ? AND senha = ? AND ativo = 1',
			[email, senha]
		);

		if (rows.length === 0) {
			return res.json({ success: false, error: 'E-mail ou senha incorretos' });
		}

		const colaborador = rows[0];

		// Verifica se já jogou
		const [participacoes] = await pool.query(
			'SELECT * FROM quiz_participacoes WHERE username = ?',
			[colaborador.codigo]
		);

		if (participacoes.length > 0) {
			const resultado = participacoes[0];
			return res.json({
				success: false,
				ja_jogou: true,
				resultado: {
					acertos: resultado.acertos,
					total: resultado.total,
					data: resultado.data_participacao
				}
			});
		}

		// Login bem-sucedido
		return res.json({
			success: true,
			token: 'token_' + Date.now(),
			participante: {
				id: colaborador.id,
				codigo: colaborador.codigo,
				username: colaborador.codigo,
				name: colaborador.nome,
				email: colaborador.email
			}
		});

	} catch (error) {
		console.error('[LOGIN] Erro:', error);
		return res.status(500).json({ success: false, error: 'Erro no servidor' });
	}
});

// API: Buscar perguntas do quiz
app.get('/api/quiz.php', async (req, res) => {
	try {
		console.log('[QUIZ] Buscando colaboradores...');

		// Busca todos os colaboradores ativos com fotos
		const [colaboradores] = await pool.query(
			'SELECT id, nome, foto_adulto, foto_crianca FROM quiz_colaboradores WHERE ativo = 1 AND foto_crianca IS NOT NULL AND foto_crianca != ""'
		);

		console.log('[QUIZ] Encontrados:', colaboradores.length, 'colaboradores');

		if (colaboradores.length < 4) {
			return res.json({
				success: false,
				error: 'Não há colaboradores suficientes cadastrados'
			});
		}

		// Embaralha e seleciona 10 para as perguntas
		const embaralhados = shuffle(colaboradores);
		const totalPerguntas = Math.min(10, colaboradores.length);
		const selecionados = embaralhados.slice(0, totalPerguntas);

		// Monta as perguntas
		const questions = selecionados.map(target => {
			// Seleciona 2 opções erradas (diferentes do target)
			const opcoes_erradas = shuffle(
				colaboradores.filter(c => c.id !== target.id)
			).slice(0, 2);

			// Junta com a correta e embaralha
			const opcoes = shuffle([
				target,
				...opcoes_erradas
			]).map(c => ({
				id: c.id,
				nome: c.nome,
				foto_adulto: c.foto_adulto
			}));

			return {
				pergunta_id: target.id,
				foto_crianca: target.foto_crianca,
				opcoes: opcoes
			};
		});

		console.log('[QUIZ] Enviando', questions.length, 'perguntas');

		return res.json({
			success: true,
			questions: questions
		});

	} catch (error) {
		console.error('[QUIZ] Erro:', error);
		return res.status(500).json({
			success: false,
			error: 'Erro ao carregar perguntas'
		});
	}
});

// API: Responder pergunta (não salva nada, apenas valida)
app.post('/api/responder.php', async (req, res) => {
	try {
		const { pergunta_id, colaborador_escolhido_id } = req.body;

		if (!pergunta_id || !colaborador_escolhido_id) {
			return res.json({ ok: false, error: 'Dados incompletos' });
		}

		const acertou = parseInt(pergunta_id) === parseInt(colaborador_escolhido_id);

		return res.json({ ok: true, acertou });

	} catch (error) {
		console.error('[RESPONDER] Erro:', error);
		return res.status(500).json({ ok: false, error: 'Erro no servidor' });
	}
});

// API: Salvar resultado final
app.post('/api/salvar_resultado.php', async (req, res) => {
	try {
		const { email, acertos, total } = req.body;

		if (!email) {
			return res.json({ success: false, error: 'E-mail não fornecido' });
		}

		// Busca código do colaborador pelo email
		const [colaboradores] = await pool.query(
			'SELECT codigo FROM quiz_colaboradores WHERE email = ?',
			[email]
		);

		if (colaboradores.length === 0) {
			return res.json({ success: false, error: 'Colaborador não encontrado' });
		}

		const username = colaboradores[0].codigo;

		// Salva resultado
		await pool.query(
			'INSERT INTO quiz_participacoes (username, acertos, total) VALUES (?, ?, ?)',
			[username, acertos || 0, total || 0]
		);

		console.log('[RESULTADO] Salvo:', username, acertos, '/', total);

		return res.json({
			success: true,
			message: 'Resultado salvo com sucesso'
		});

	} catch (error) {
		console.error('[RESULTADO] Erro:', error);
		return res.status(500).json({ success: false, error: 'Erro ao salvar resultado' });
	}
});

// Health check
app.get('/health', (req, res) => {
	res.json({ ok: true, mysql: 'connected', timestamp: new Date().toISOString() });
});

app.listen(PORT, () => {
	console.log(`🚀 Servidor rodando em http://localhost:${PORT}`);
	console.log(`📊 Conectado ao MySQL: ${process.env.DB_NAME || 'quiz_colaboradores'}`);
});
