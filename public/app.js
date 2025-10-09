const $ = (sel) => document.querySelector(sel);
const $$ = (sel) => document.querySelectorAll(sel);

// API PHP externa - usa as APIs em floripa.in9automacao.com.br
const API_BASE = 'https://floripa.in9automacao.com.br';
// Força modo simulado para funcionar sem backend (servidor estático)
// Altere para false para usar a API real APÓS fazer upload das APIs
const USE_MOCKS = false; // false = usa APIs reais no servidor

// Dados de mock para testes locais sem backend
const MOCK_PEOPLE = [
	{ id: 1, nome: 'Ana Souza', foto_adulto: 'https://placehold.co/600x400?text=Ana+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Ana+Criança' },
	{ id: 2, nome: 'Bruno Lima', foto_adulto: 'https://placehold.co/600x400?text=Bruno+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Bruno+Criança' },
	{ id: 3, nome: 'Carla Dias', foto_adulto: 'https://placehold.co/600x400?text=Carla+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Carla+Criança' },
	{ id: 4, nome: 'Diego Nunes', foto_adulto: 'https://placehold.co/600x400?text=Diego+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Diego+Criança' },
	{ id: 5, nome: 'Elisa Prado', foto_adulto: 'https://placehold.co/600x400?text=Elisa+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Elisa+Criança' },
	{ id: 6, nome: 'Fábio Alves', foto_adulto: 'https://placehold.co/600x400?text=Fabio+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Fabio+Criança' },
	{ id: 7, nome: 'Gabi Melo', foto_adulto: 'https://placehold.co/600x400?text=Gabi+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Gabi+Criança' },
	{ id: 8, nome: 'Hugo Reis', foto_adulto: 'https://placehold.co/600x400?text=Hugo+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Hugo+Criança' },
	{ id: 9, nome: 'Iara Brito', foto_adulto: 'https://placehold.co/600x400?text=Iara+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Iara+Criança' },
	{ id: 10, nome: 'João Pedro', foto_adulto: 'https://placehold.co/600x400?text=Joao+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Joao+Criança' },
	{ id: 11, nome: 'Karen Silva', foto_adulto: 'https://placehold.co/600x400?text=Karen+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Karen+Criança' },
	{ id: 12, nome: 'Leo Rocha', foto_adulto: 'https://placehold.co/600x400?text=Leo+Adulto', foto_crianca: 'https://placehold.co/600x400?text=Leo+Criança' }
];

function shuffled(arr){
	return [...arr].map(v => ({ v, r: Math.random() })).sort((a,b)=>a.r-b.r).map(x=>x.v);
}

function buildMockQuestions(total = 15){
	const targets = shuffled(MOCK_PEOPLE).slice(0, Math.min(total, MOCK_PEOPLE.length));
	return targets.map((t, idx) => {
		const decoys = shuffled(MOCK_PEOPLE.filter(p => p.id !== t.id)).slice(0,2);
		const opcoes = shuffled([t, ...decoys]).map(o => ({ id: o.id, nome: o.nome, foto_adulto: o.foto_adulto }));
		return { id: `q_mock_${idx}`, foto_crianca: t.foto_crianca, opcoes };
	});
}

const state = {
	token: null,
	participante: null,
	questions: [],
	currentIndex: 0,
	acertos: 0,
	total: 0,
	tempoTotal: 0,           // Tempo total acumulado em segundos
	tempoInicio: null,       // Timestamp de início da pergunta atual
	timerInterval: null,     // Referência do intervalo do timer
	tempoRestante: 20        // Tempo restante para a pergunta atual (20 segundos)
};

function show(id){
	$$('.tela').forEach(el=>{
		el.classList.add('hidden');
		el.classList.remove('active');
	});
	const target = $(id);
	if(target){
		target.classList.remove('hidden');
		target.classList.add('active');
	}
}

async function requestFullscreen(){
	try{
		const el = document.documentElement;
		if (el.requestFullscreen) await el.requestFullscreen();
		else if (el.webkitRequestFullscreen) await el.webkitRequestFullscreen();
	}catch(e){/* ignore */}
}

async function api(path, method='GET', body){
	if (USE_MOCKS) {
		await new Promise(r => setTimeout(r, 120));
		if (path.includes('login')) {
			// Simula login com email/senha
			if (!body || !body.email || !body.senha) {
				throw new Error('E-mail e senha são obrigatórios');
			}
			// Aceita qualquer email/senha em modo mock
			return {
				success: true,
				token: 'mock-token',
				participante: {
					id: 999,
					email: body.email,
					nome: 'Participante Mock',
					cpf: '12345678900'
				}
			};
		}
		if (path.includes('quiz')) {
			return { questions: buildMockQuestions(15) };
		}
		if (path.includes('responder')) {
			// Em modo mock, sempre simula 70% de acerto
			const acertou = Math.random() > 0.3;
			return { ok: true, acertou };
		}
		if (path.includes('resultado')) {
			return { success: true, acertos: state.acertos, total: state.total };
		}
		throw new Error('Mock endpoint não encontrado');
	}

	// Retry automático até 3 tentativas
	for (let attempt = 1; attempt <= 3; attempt++) {
		try {
			// Timeout de 15 segundos
			const controller = new AbortController();
			const timeout = setTimeout(() => controller.abort(), 15000);

			const res = await fetch(`${API_BASE}${path}?_=${Date.now()}`, {
				method,
				headers: {
					'Content-Type': 'application/json',
					...(state.token ? { 'Authorization': `Bearer ${state.token}` } : {})
				},
				body: body ? JSON.stringify(body) : undefined,
				signal: controller.signal
			}).finally(() => clearTimeout(timeout));

			const text = await res.text();

			// Se resposta vazia, tenta novamente
			if (!text || text.trim().length === 0) {
				console.warn(`[API] Resposta vazia (tentativa ${attempt}/3) - URL: ${API_BASE}${path}`);
				if (attempt < 3) {
					// Delay maior: 2s, 4s
					await new Promise(r => setTimeout(r, 2000 * attempt));
					continue;
				}
				throw new Error('Servidor não respondeu. Tente novamente.');
			}

			console.log('[API] URL:', `${API_BASE}${path}`);
			console.log('[API] Status:', res.status);
			console.log('[API] Response length:', text.length);

			let data;
			try {
				data = JSON.parse(text);
			} catch(e) {
				console.error(`[API] Parse error (tentativa ${attempt}/3):`, e);
				console.error('[API] Response preview:', text.substring(0, 300));

				// Se não é a última tentativa, tenta novamente
				if (attempt < 3) {
					await new Promise(r => setTimeout(r, 2000 * attempt));
					continue;
				}
				throw new Error('Resposta inválida do servidor. Tente novamente.');
			}

			// Verifica se há erro explícito
			if (data.error) {
				throw new Error(data.error);
			}

			// Sucesso!
			return data;

		} catch(e) {
			// Se é timeout ou rede, tenta novamente
			if (e.name === 'AbortError' && attempt < 3) {
				console.warn(`[API] Timeout (tentativa ${attempt}/3)`);
				await new Promise(r => setTimeout(r, 2000 * attempt));
				continue;
			}

			// Se é erro de rede e não é última tentativa
			if ((e.message.includes('Failed to fetch') || e.message.includes('NetworkError')) && attempt < 3) {
				console.warn(`[API] Erro de rede (tentativa ${attempt}/3)`);
				await new Promise(r => setTimeout(r, 2000 * attempt));
				continue;
			}

			// Se é a última tentativa, lança erro
			if (attempt === 3) {
				throw e;
			}
		}
	}
	throw new Error('Falha na comunicação com o servidor após 3 tentativas');
}

function preloadImages(urls) {
	urls.forEach(url => {
		const img = new Image();
		img.src = url;
	});
}

// Funções de gerenciamento do timer
function startTimer() {
	// Para qualquer timer anterior
	stopTimer();

	// Inicia novo timer
	state.tempoInicio = Date.now();
	state.tempoRestante = 20;

	// Atualiza display imediatamente
	updateTimerDisplay();

	// Atualiza a cada segundo
	state.timerInterval = setInterval(() => {
		const tempoDecorrido = Math.floor((Date.now() - state.tempoInicio) / 1000);
		state.tempoRestante = Math.max(0, 20 - tempoDecorrido);

		updateTimerDisplay();

		// Se o tempo acabou, força próxima pergunta (resposta errada)
		if (state.tempoRestante === 0) {
			stopTimer();
			state.currentIndex++;

			if (state.currentIndex < state.total) {
				renderQuestion();
			} else {
				finalizarQuiz();
			}
		}
	}, 1000);
}

function stopTimer() {
	if (state.timerInterval) {
		clearInterval(state.timerInterval);
		state.timerInterval = null;
	}

	// Calcula e acumula tempo gasto nesta pergunta
	if (state.tempoInicio) {
		const tempoGasto = Math.floor((Date.now() - state.tempoInicio) / 1000);
		state.tempoTotal += Math.min(tempoGasto, 20); // Máximo 20 segundos por pergunta
		state.tempoInicio = null;
	}
}

function updateTimerDisplay() {
	const timerEl = $('#timer-display');
	if (timerEl) {
		timerEl.textContent = state.tempoRestante;

		// Adiciona classe de alerta quando faltam 5 segundos ou menos
		if (state.tempoRestante <= 5) {
			timerEl.classList.add('timer-alert');
		} else {
			timerEl.classList.remove('timer-alert');
		}
	}
}

async function finalizarQuiz() {
	// Salva o resultado no banco (agora com tempo)
	try {
		await api('/salvar_resultado.php','POST',{
			acertos: state.acertos,
			total: state.total,
			tempo_total_segundos: state.tempoTotal
		});
	} catch(e) {
		console.error('Erro ao salvar resultado:', e);
	}

	$('#acertos').textContent = state.acertos;
	$('#total').textContent = state.total;
	show('#tela-resultado');
}

function renderQuestion(){
	const q = state.questions[state.currentIndex];
	$('#contador').textContent = `${state.currentIndex+1}/${state.total}`;
	$('#foto-crianca').src = q.foto_crianca;
	$('#foto-crianca').alt = `Quem é esta criança?`;

	// Preload das próximas imagens
	if (state.currentIndex + 1 < state.total) {
		const nextQ = state.questions[state.currentIndex + 1];
		preloadImages([nextQ.foto_crianca, ...nextQ.opcoes.map(o => o.foto_adulto)]);
	}

	const opcoes = $('#opcoes');
	opcoes.innerHTML='';
	q.opcoes.forEach(opt=>{
		const btn = document.createElement('button');
		btn.className = 'opcao';
		btn.innerHTML = `
			<img src="${opt.foto_adulto}" alt="Foto de colaborador" loading="eager" onerror="this.src='https://placehold.co/600x400?text=Foto'" draggable="false" oncontextmenu="return false;">
			<div class="nome">${opt.nome}</div>
		`;
		btn.addEventListener('click', async ()=>{
			// Para o timer e acumula tempo
			stopTimer();

			// Desabilita todos os botões e mostra loading
			$$('.opcao').forEach(b => b.disabled = true);
			btn.classList.add('loading');

			try{
				// Usa q.id (novo formato) ao invés de q.pergunta_id
				const resp = await api('/responder.php','POST',{ pergunta_id: q.id, colaborador_escolhido_id: opt.id });
				if (resp.acertou) state.acertos++;
				state.currentIndex++;

				if (state.currentIndex < state.total) {
					renderQuestion();
				} else {
					finalizarQuiz();
				}
			}catch(e){
				alert(e.message);
				$$('.opcao').forEach(b => b.disabled = false);
				btn.classList.remove('loading');
				// Reinicia o timer se houve erro
				startTimer();
			}
		});
		opcoes.appendChild(btn);
	});

	// Inicia o timer de 20 segundos para esta pergunta
	startTimer();
}

async function startQuiz(){
	try{
		console.log('[QUIZ] Carregando perguntas...');
		console.log('[QUIZ] Token disponível:', state.token ? 'Sim' : 'Não');
		console.log('[QUIZ] Token:', state.token);
		const data = await api('/quiz.php','GET');
		state.questions = data.questions || [];

		if (state.questions.length === 0) {
			throw new Error('Nenhuma pergunta disponível');
		}

		state.currentIndex = 0;
		state.acertos = 0;
		state.total = state.questions.length;
		state.tempoTotal = 0;
		state.tempoInicio = null;
		state.tempoRestante = 20;
		console.log('[QUIZ] Carregado com sucesso:', state.total, 'perguntas');
		show('#tela-quiz');
		renderQuestion();
	}catch(e){
		console.error('[QUIZ] Erro ao carregar:', e);
		alert('⚠️ ' + e.message + '\n\nClique em "Começar" novamente.');
		throw e; // Propaga erro para não resetar botão
	}
}

function resetAll(){
	// Para o timer se estiver rodando
	stopTimer();

	state.token = null;
	state.participante = null;
	state.questions = [];
	state.currentIndex = 0;
	state.acertos = 0;
	state.total = 0;
	state.tempoTotal = 0;
	state.tempoInicio = null;
	state.tempoRestante = 20;
	localStorage.removeItem('token');
	localStorage.removeItem('participante');
	$('#nome').value = '';
	$('#cpf').value = '';
	$('#cadastro-msg').textContent = '';
	show('#tela-cadastro');
}

// Validação de CPF
function validaCPF(cpf) {
	cpf = cpf.replace(/\D/g, '');
	if (cpf.length !== 11) return false;

	// Verifica se todos os dígitos são iguais
	if (/^(\d)\1{10}$/.test(cpf)) return false;

	// Valida primeiro dígito verificador
	let soma = 0;
	for (let i = 0; i < 9; i++) {
		soma += parseInt(cpf.charAt(i)) * (10 - i);
	}
	let resto = 11 - (soma % 11);
	let digito1 = resto >= 10 ? 0 : resto;

	if (digito1 !== parseInt(cpf.charAt(9))) return false;

	// Valida segundo dígito verificador
	soma = 0;
	for (let i = 0; i < 10; i++) {
		soma += parseInt(cpf.charAt(i)) * (11 - i);
	}
	resto = 11 - (soma % 11);
	let digito2 = resto >= 10 ? 0 : resto;

	return digito2 === parseInt(cpf.charAt(10));
}

window.addEventListener('DOMContentLoaded', ()=>{
	// Inicializa mostrando apenas a tela de cadastro
	show('#tela-cadastro');

	$('#btn-fullscreen').addEventListener('click', requestFullscreen);

	// Máscara de CPF
	$('#cpf').addEventListener('input', (e) => {
		let value = e.target.value.replace(/\D/g, '');
		if (value.length <= 11) {
			value = value.replace(/(\d{3})(\d)/, '$1.$2');
			value = value.replace(/(\d{3})(\d)/, '$1.$2');
			value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
			e.target.value = value;
		}
	});

	// Form de cadastro (SEM senha)
	$('#form-cadastro').addEventListener('submit', async (e)=>{
		e.preventDefault();
		const nome = $('#nome').value.trim();
		const cpf = $('#cpf').value.trim();

		if (!nome || !cpf) {
			$('#cadastro-msg').textContent = 'Preencha todos os campos';
			return;
		}

		// Valida CPF
		if (!validaCPF(cpf)) {
			$('#cadastro-msg').textContent = 'CPF inválido';
			return;
		}

		$('#cadastro-msg').textContent = 'Cadastrando...';

		try{
			await requestFullscreen();

			// Chama API de cadastro simplificado (sem senha)
			const data = await api('/cadastro_colaborador.php','POST',{
				nome,
				cpf: cpf.replace(/\D/g, '') // Remove formatação
			});

			if (data.ja_jogou) {
				// Usuário já participou
				const r = data.resultado;
				$('#cadastro-msg').textContent = '';
				alert(`Você já participou do quiz!\n\nSeu resultado:\nAcertos: ${r.acertos}/${r.total}\nData: ${new Date(r.data).toLocaleDateString('pt-BR')}`);
				return;
			}

			if (data.success && data.token) {
				state.token = data.token;
				state.participante = data.participante;
				console.log('[CADASTRO] Token recebido:', state.token ? 'Sim' : 'Não');
				if (!USE_MOCKS) {
					localStorage.setItem('token', state.token);
					localStorage.setItem('participante', JSON.stringify(data.participante));
					console.log('[CADASTRO] Token salvo no localStorage');
				}
				$('#cadastro-msg').textContent = '';
				show('#tela-instrucoes');
			} else {
				throw new Error(data.error || 'Erro ao cadastrar');
			}
		}catch(err){
			$('#cadastro-msg').textContent = err.message || 'Erro ao cadastrar';
			console.error('Erro no cadastro:', err);
		}
	});

	$('#btn-comecar')?.addEventListener('click', async ()=>{
		const btn = $('#btn-comecar');
		btn.disabled = true;
		btn.textContent = 'Carregando...';
		try {
			await startQuiz();
		} finally {
			btn.disabled = false;
			btn.textContent = 'Começar';
		}
	});

	$('#btn-reiniciar').addEventListener('click', ()=>{
		resetAll();
	});
});
