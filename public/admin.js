// API relativa - usa o próprio backend Node.js
const API_BASE = '';

function api(path){
	return `${API_BASE}${path}`;
}

function fmt(n){ return new Intl.NumberFormat('pt-BR').format(n); }

function getAdminUser(){
	try { return JSON.parse(localStorage.getItem('admin_user')||'null'); } catch(e){ return null; }
}

async function adminLogin(user, pass){
	const res = await fetch(api('/admin_login.php'), {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ user: user, pass: pass })
	});

	const text = await res.text();
	let data;
	try {
		data = JSON.parse(text);
	} catch(e) {
		throw new Error('Resposta inválida do servidor');
	}

	if (!data.ok) {
		throw new Error(data.error || 'Login inválido');
	}

	const userData = { username: user };
	try { localStorage.setItem('admin_user', JSON.stringify(userData)); } catch(e){}
	return { success: true, user: userData };
}

async function fetchStats(){
	try{
		const res = await fetch(api('/admin_stats.php'));
		if (!res.ok) throw new Error('Erro ao carregar estatísticas');
		const data = await res.json();
		if (!data.success) throw new Error(data.error || 'Erro desconhecido');
		return data.stats;
	}catch(e){
		console.error('[STATS] Erro:', e);
		throw e;
	}
}

async function fetchRanking(){
	try{
		const res = await fetch(api('/admin_ranking.php'));
		if (!res.ok) throw new Error('Erro ao carregar ranking');
		const data = await res.json();
		if (!data.success) throw new Error(data.error || 'Erro desconhecido');
		return data;
	}catch(e){
		console.error('[RANKING] Erro:', e);
		throw e;
	}
}

let currentPage = 1;
async function fetchParticipacoes(page = 1){
	try{
		const res = await fetch(api(`/admin_participacoes.php?page=${page}&limit=20`));
		if (!res.ok) throw new Error('Erro ao carregar participações');
		const data = await res.json();
		if (!data.success) throw new Error(data.error || 'Erro desconhecido');
		return data;
	}catch(e){
		console.error('[PARTICIPACOES] Erro:', e);
		throw e;
	}
}

function renderKPIs(stats){
	const el = document.getElementById('kpis');
	el.innerHTML = '';
	const items = [
		{ l: 'Participantes', v: stats.total_participantes },
		{ l: 'Total de Partidas', v: stats.total_partidas },
		{ l: 'Pontuação Média', v: stats.pontuacao_media },
		{ l: 'Melhor Pontuação', v: stats.melhor_pontuacao }
	];
	for (const it of items){
		const div = document.createElement('div');
		div.className = 'kpi';
		div.innerHTML = `<div class="v">${fmt(it.v)}</div><div class="l">${it.l}</div>`;
		el.appendChild(div);
	}
}

function renderRankingMelhor(rows){
	const tbody = document.querySelector('#rankingMelhor tbody');
	tbody.innerHTML = '';
	if (!rows || rows.length === 0) {
		tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999">Nenhum dado disponível</td></tr>';
		return;
	}
	rows.slice(0, 10).forEach((r)=>{
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>${r.posicao}</td>
			<td>${r.nome}</td>
			<td>${r.melhor_pontuacao}</td>
			<td>${r.media_pontuacao}</td>
			<td>${r.total_partidas}</td>
		`;
		tbody.appendChild(tr);
	});
}

function renderRankingMedia(rows){
	const tbody = document.querySelector('#rankingMedia tbody');
	tbody.innerHTML = '';
	if (!rows || rows.length === 0) {
		tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#999">Nenhum dado disponível (mínimo 3 partidas)</td></tr>';
		return;
	}
	rows.slice(0, 10).forEach((r)=>{
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>${r.posicao}</td>
			<td>${r.nome}</td>
			<td>${r.media_pontuacao}</td>
			<td>${r.total_partidas}</td>
		`;
		tbody.appendChild(tr);
	});
}

function renderDificeis(rows){
	const tbody = document.querySelector('#dificeis tbody');
	tbody.innerHTML = '';
	if (!rows || rows.length === 0) {
		tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#999">Nenhum dado disponível</td></tr>';
		return;
	}
	rows.slice(0, 10).forEach((r)=>{
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>${r.nome}</td>
			<td>${r.vezes_acertado}</td>
			<td>${r.vezes_mostrado}</td>
			<td>${r.taxa_acerto}%</td>
		`;
		tbody.appendChild(tr);
	});
}

function renderFaceis(rows){
	const tbody = document.querySelector('#faceis tbody');
	tbody.innerHTML = '';
	if (!rows || rows.length === 0) {
		tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#999">Nenhum dado disponível</td></tr>';
		return;
	}
	rows.slice(0, 10).forEach((r)=>{
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>${r.nome}</td>
			<td>${r.vezes_acertado}</td>
			<td>${r.vezes_mostrado}</td>
			<td>${r.taxa_acerto}%</td>
		`;
		tbody.appendChild(tr);
	});
}

function renderParticipacoes(participacoes, pagination){
	const tbody = document.querySelector('#participacoes tbody');
	tbody.innerHTML = '';

	if (!participacoes || participacoes.length === 0) {
		tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999">Nenhuma participação registrada</td></tr>';
		return;
	}

	participacoes.forEach((r)=>{
		const tr = document.createElement('tr');
		const dt = new Date(r.data_hora);
		const dtStr = dt.toLocaleString('pt-BR');
		tr.innerHTML = `
			<td>${dtStr}</td>
			<td>${r.nome}</td>
			<td>${r.email}</td>
			<td>${r.pontuacao}/${r.total_perguntas}</td>
			<td>${r.percentual}%</td>
		`;
		tbody.appendChild(tr);
	});

	const pageInfo = document.getElementById('pageInfo');
	if (pagination) {
		pageInfo.textContent = `Página ${pagination.page} de ${pagination.pages} (${pagination.total} registros)`;

		const btnPrev = document.getElementById('btnPrevPage');
		const btnNext = document.getElementById('btnNextPage');
		btnPrev.disabled = pagination.page <= 1;
		btnNext.disabled = pagination.page >= pagination.pages;
	}
}

let chartDia, chartDist;
function renderCharts(partidasPorDia, distribuicao){
	const ctxDia = document.getElementById('chartDia');
	const ctxDist = document.getElementById('chartDist');

	const labelsDia = partidasPorDia.map(r=>r.dia);
	const dataPart = partidasPorDia.map(r=>Number(r.quantidade));

	chartDia && chartDia.destroy();
	chartDia = new Chart(ctxDia, {
		type: 'bar',
		data: {
			labels: labelsDia,
			datasets: [
				{ label: 'Partidas', data: dataPart, backgroundColor: '#cc1b28' }
			]
		},
		options: { responsive: true, plugins: { legend: { labels: { color: '#cbd5e1' } } }, scales: { x: { ticks: { color: '#cbd5e1' } }, y: { ticks: { color: '#cbd5e1' } } } }
	});

	const labelsDist = distribuicao.map(r=>r.faixa);
	const dataDist = distribuicao.map(r=>Number(r.quantidade));
	chartDist && chartDist.destroy();
	chartDist = new Chart(ctxDist, {
		type: 'bar',
		data: { labels: labelsDist, datasets: [{ label: 'Quantidade', data: dataDist, backgroundColor: '#cc1b28' }] },
		options: { responsive: true, plugins: { legend: { labels: { color: '#cbd5e1' } } }, scales: { x: { ticks: { color: '#cbd5e1' } }, y: { ticks: { color: '#cbd5e1' } } } }
	});
}

async function loadDashboard(){
	const user = getAdminUser();
	const loginEl = document.getElementById('admin-login');

	if (!user) {
		console.log('[DASHBOARD] Usuário não autenticado, mostrando login');
		if (loginEl) loginEl.style.display = 'flex';
		return;
	}

	console.log('[DASHBOARD] Usuário autenticado, carregando métricas...');

	try{
		const stats = await fetchStats();
		const ranking = await fetchRanking();
		const participacoesData = await fetchParticipacoes(currentPage);

		// Esconde a tela de login
		if (loginEl) loginEl.style.display = 'none';

		console.log('[DASHBOARD] Renderizando dashboard...');
		renderKPIs(stats);
		renderRankingMelhor(ranking.rankings.melhor_pontuacao);
		renderRankingMedia(ranking.rankings.media);
		renderDificeis(ranking.dificuldade.mais_dificeis);
		renderFaceis(ranking.dificuldade.mais_faceis);
		renderParticipacoes(participacoesData.participacoes, participacoesData.pagination);
		renderCharts(stats.partidas_por_dia, stats.distribuicao_pontuacoes);
		console.log('[DASHBOARD] Dashboard carregado!');
	}catch(e){
		console.error('[DASHBOARD] Erro ao carregar:', e);
		alert('Erro ao carregar dados do painel: ' + e.message);
	}
}

window.addEventListener('DOMContentLoaded', ()=>{
	document.getElementById('form-admin-login')?.addEventListener('submit', async (ev)=>{
		ev.preventDefault();

		const userEl = document.getElementById('admin-user');
		const passEl = document.getElementById('admin-pass');
		const errEl = document.getElementById('admin-err');

		if (!userEl || !passEl){
			errEl.textContent = 'Erro no formulário de login. Recarregue a página (Ctrl+R).';
			return;
		}

		const user = userEl.value.trim();
		const pass = passEl.value.trim();

		if (!user || !pass) {
			errEl.textContent = 'Preencha usuário e senha';
			return;
		}

		errEl.textContent = 'Aguarde...';

		try{
			const resp = await adminLogin(user, pass);

			if (resp && resp.success) {
				errEl.textContent = '';
				loadDashboard();
				return;
			}
			throw new Error('Login inválido');
		}catch(err){
			console.error('[FORM] Erro no login:', err);
			errEl.textContent = err.message || 'Usuário ou senha inválidos';
		}
	});

	document.getElementById('btnPrevPage')?.addEventListener('click', async ()=>{
		if (currentPage > 1) {
			currentPage--;
			const data = await fetchParticipacoes(currentPage);
			renderParticipacoes(data.participacoes, data.pagination);
		}
	});

	document.getElementById('btnNextPage')?.addEventListener('click', async ()=>{
		currentPage++;
		const data = await fetchParticipacoes(currentPage);
		renderParticipacoes(data.participacoes, data.pagination);
		if (data.pagination.page > data.pagination.pages) {
			currentPage = data.pagination.pages;
		}
	});

	loadDashboard();
});
