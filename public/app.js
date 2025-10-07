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

function buildMockQuestions(total = 10){
	const targets = shuffled(MOCK_PEOPLE).slice(0, Math.min(total, MOCK_PEOPLE.length));
	return targets.map(t => {
		const decoys = shuffled(MOCK_PEOPLE.filter(p => p.id !== t.id)).slice(0,2);
		const opcoes = shuffled([t, ...decoys]).map(o => ({ id: o.id, nome: o.nome, foto_adulto: o.foto_adulto }));
		return { pergunta_id: t.id, foto_crianca: t.foto_crianca, opcoes };
	});
}

const state = {
	token: null,
	participante: null,
	questions: [],
	currentIndex: 0,
	acertos: 0,
	total: 0
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
			return { questions: buildMockQuestions(10) };
		}
		if (path.includes('responder')) {
			const acertou = body && body.pergunta_id === body.colaborador_escolhido_id;
			return { ok: true, acertou };
		}
		if (path.includes('resultado')) {
			return { success: true, acertos: state.acertos, total: state.total };
		}
		throw new Error('Mock endpoint não encontrado');
	}

	const res = await fetch(`${API_BASE}${path}`, {
		method,
		headers: {
			'Content-Type': 'application/json',
			...(state.token ? { 'Authorization': `Bearer ${state.token}` } : {})
		},
		body: body ? JSON.stringify(body) : undefined
	});

	const text = await res.text();
	console.log('[API] URL:', `${API_BASE}${path}`);
	console.log('[API] Status:', res.status);
	console.log('[API] Response:', text.substring(0, 200));

	let data;
	try {
		data = JSON.parse(text);
	} catch(e) {
		console.error('[API] Parse error:', e);
		console.error('[API] Full response:', text);
		throw new Error('Erro de comunicação com o servidor');
	}

	// Verifica se há erro explícito
	if (data.error) {
		throw new Error(data.error);
	}

	// Se não tem success nem ok, mas também não tem erro, tudo bem
	return data;
}

function renderQuestion(){
	const q = state.questions[state.currentIndex];
	$('#contador').textContent = `${state.currentIndex+1}/${state.total}`;
	$('#foto-crianca').src = q.foto_crianca;
	$('#foto-crianca').alt = `Quem é esta criança?`;
	const opcoes = $('#opcoes');
	opcoes.innerHTML='';
	q.opcoes.forEach(opt=>{
		const btn = document.createElement('button');
		btn.className = 'opcao';
		btn.innerHTML = `
			<img src="${opt.foto_adulto}" alt="Foto de colaborador" onerror="this.src='https://placehold.co/600x400?text=Foto'" draggable="false" oncontextmenu="return false;">
			<div class="nome">${opt.nome}</div>
		`;
		btn.addEventListener('click', async ()=>{
			btn.disabled = true;
			try{
				const resp = await api('/responder.php','POST',{ pergunta_id: q.pergunta_id, colaborador_escolhido_id: opt.id });
				if (resp.acertou) state.acertos++;
				state.currentIndex++;
				if (state.currentIndex < state.total) {
					renderQuestion();
				} else {
					// Salva o resultado no banco
					try {
						await api('/salvar_resultado.php','POST',{
							colaborador_id: state.participante?.id || 0,
							email: state.participante?.email || '',
							acertos: state.acertos,
							total: state.total
						});
					} catch(e) {
						console.error('Erro ao salvar resultado:', e);
					}
					$('#acertos').textContent = state.acertos;
					$('#total').textContent = state.total;
					show('#tela-resultado');
				}
			}catch(e){
				alert(e.message);
			}finally{
				btn.disabled = false;
			}
		});
		opcoes.appendChild(btn);
	});
}

async function startQuiz(){
	try{
		const data = await api('/quiz.php','GET');
		state.questions = data.questions || [];
		state.currentIndex = 0;
		state.acertos = 0;
		state.total = state.questions.length;
		show('#tela-quiz');
		renderQuestion();
	}catch(e){
		$('#login-msg').textContent = e.message;
	}
}

function resetAll(){
	state.token = null;
	state.participante = null;
	state.questions = [];
	state.currentIndex = 0;
	state.acertos = 0;
	state.total = 0;
	localStorage.removeItem('token');
	localStorage.removeItem('participante');
	$('#email').value = '';
	$('#senha').value = '';
	$('#login-msg').textContent = '';
	show('#tela-login');
}

window.addEventListener('DOMContentLoaded', ()=>{
	// Inicializa mostrando apenas a tela de login
	show('#tela-login');

	// Preenche email da URL se existir
	const urlParams = new URLSearchParams(window.location.search);
	const emailUrl = urlParams.get('email');
	if (emailUrl) {
		$('#email').value = emailUrl;
	}

	$('#btn-fullscreen').addEventListener('click', requestFullscreen);

	// Sempre limpa sessão anterior ao carregar a página
	localStorage.removeItem('token');
	localStorage.removeItem('participante');
	resetAll();

	$('#form-login').addEventListener('submit', async (e)=>{
		e.preventDefault();
		const email = $('#email').value.trim();
		const senha = $('#senha').value;

		if (!email || !senha) return;
		$('#login-msg').textContent = 'Verificando...';

		try{
			await requestFullscreen();
			const data = await api('/colaborador_login.php','POST',{ email, senha });

			if (data.ja_jogou) {
				// Usuário já participou
				const r = data.resultado;
				$('#login-msg').textContent = '';
				alert(`Você já participou do quiz!\n\nSeu resultado:\nAcertos: ${r.acertos}/${r.total}\nData: ${new Date(r.data).toLocaleDateString('pt-BR')}`);
				return;
			}

			if (data.success && data.participante) {
				state.token = data.token;
				state.participante = data.participante;
				if (!USE_MOCKS) {
					localStorage.setItem('token', state.token);
					localStorage.setItem('participante', JSON.stringify(data.participante));
				}
				$('#login-msg').textContent = '';
				show('#tela-instrucoes');
			} else {
				throw new Error('Falha no login');
			}
		}catch(err){
			$('#login-msg').textContent = err.message || 'Erro ao fazer login';
			console.error('Erro no login:', err);
		}
	});

	$('#btn-comecar')?.addEventListener('click', ()=>{
		startQuiz();
	});

	$('#btn-reiniciar').addEventListener('click', ()=>{
		resetAll();
	});
});
