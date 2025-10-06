<?php
// ==========================================
// Quiz - Buscar Perguntas
// api/quiz.php
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit(0);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Credenciais do banco
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
	$conn = new mysqli($host, $user, $password, $database, $port);
	if ($conn->connect_error) {
		echo json_encode(array('success' => false, 'error' => 'Erro de conexão com banco de dados'));
		exit();
	}

	// Busca todos os colaboradores que têm fotos (ambas)
	$result = $conn->query("
		SELECT id, nome, foto_adulto, foto_crianca
		FROM quiz_colaboradores
		WHERE foto_crianca IS NOT NULL
		AND foto_crianca != ''
		AND foto_adulto IS NOT NULL
		AND foto_adulto != ''
		ORDER BY RAND()
	");

	$colaboradores = array();
	while ($row = $result->fetch_assoc()) {
		$colaboradores[] = array(
			'id' => (int)$row['id'],
			'nome' => $row['nome'],
			'foto_adulto' => $row['foto_adulto'],
			'foto_crianca' => $row['foto_crianca']
		);
	}

	// Se não houver colaboradores suficientes, retorna dados mock
	if (count($colaboradores) < 3) {
		// Dados mock para demonstração
		$colaboradores = array(
			array('id' => 1, 'nome' => 'Ana Souza', 'foto_adulto' => 'https://placehold.co/600x400?text=Ana+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Ana+Criança'),
			array('id' => 2, 'nome' => 'Bruno Lima', 'foto_adulto' => 'https://placehold.co/600x400?text=Bruno+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Bruno+Criança'),
			array('id' => 3, 'nome' => 'Carla Dias', 'foto_adulto' => 'https://placehold.co/600x400?text=Carla+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Carla+Criança'),
			array('id' => 4, 'nome' => 'Diego Nunes', 'foto_adulto' => 'https://placehold.co/600x400?text=Diego+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Diego+Criança'),
			array('id' => 5, 'nome' => 'Elisa Prado', 'foto_adulto' => 'https://placehold.co/600x400?text=Elisa+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Elisa+Criança'),
			array('id' => 6, 'nome' => 'Fábio Alves', 'foto_adulto' => 'https://placehold.co/600x400?text=Fabio+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Fabio+Criança'),
			array('id' => 7, 'nome' => 'Gabi Melo', 'foto_adulto' => 'https://placehold.co/600x400?text=Gabi+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Gabi+Criança'),
			array('id' => 8, 'nome' => 'Hugo Reis', 'foto_adulto' => 'https://placehold.co/600x400?text=Hugo+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Hugo+Criança'),
			array('id' => 9, 'nome' => 'Iara Brito', 'foto_adulto' => 'https://placehold.co/600x400?text=Iara+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Iara+Criança'),
			array('id' => 10, 'nome' => 'João Pedro', 'foto_adulto' => 'https://placehold.co/600x400?text=Joao+Adulto', 'foto_crianca' => 'https://placehold.co/600x400?text=Joao+Criança')
		);
	}

	// Gera perguntas (máximo 10)
	$totalPerguntas = min(10, count($colaboradores));
	$questions = array();

	// Função para detectar gênero pelo nome
	function detectarGenero($nome) {
		$nomeUpper = strtoupper($nome);
		// Nomes/palavras femininas comuns
		$femininos = array('MARIA', 'ANA', 'CARLA', 'JULIANA', 'FERNANDA', 'PATRICIA', 'AMANDA', 'CAMILA', 'BEATRIZ', 'LETICIA', 'GABRIELA', 'CAROLINA', 'RAFAELA', 'JULIA', 'LARISSA', 'THAIS', 'NATALIA', 'BRUNA', 'MARIANA', 'JESSICA', 'DEBORA', 'MONICA', 'SANDRA', 'ANDREA', 'CLAUDIA', 'SIMONE', 'VANESSA', 'LUCIANA', 'PRISCILA', 'RENATA', 'SABRINA', 'THAYNARA', 'VICTORIA', 'ALZIRENE', 'CAROLINE', 'DANIELLE', 'DIANNE', 'EVELIN', 'KARLIANY', 'LAIS', 'RAYANE');

		foreach ($femininos as $fem) {
			if (strpos($nomeUpper, $fem) !== false) {
				return 'F';
			}
		}
		return 'M'; // Padrão masculino
	}

	// Embaralha colaboradores
	shuffle($colaboradores);

	// Array para rastrear IDs já usados
	$idsUsados = array();

	for ($i = 0; $i < $totalPerguntas; $i++) {
		$correto = $colaboradores[$i];
		$generoCorreto = detectarGenero($correto['nome']);

		// Marca como usado
		$idsUsados[] = $correto['id'];

		// Separa colaboradores por gênero, excluindo já usados
		$mesmoGenero = array_filter($colaboradores, function($c) use ($correto, $generoCorreto, $idsUsados) {
			return $c['id'] != $correto['id']
				&& !in_array($c['id'], $idsUsados)
				&& detectarGenero($c['nome']) === $generoCorreto;
		});

		// Seleciona 2 decoys do mesmo gênero
		$decoys = array();
		$tentativas = 0;

		// Se não tiver colaboradores suficientes do mesmo gênero, usa todos (exceto já usados)
		if (count($mesmoGenero) < 2) {
			$mesmoGenero = array_filter($colaboradores, function($c) use ($correto, $idsUsados) {
				return $c['id'] != $correto['id'] && !in_array($c['id'], $idsUsados);
			});
		}

		$mesmoGeneroArray = array_values($mesmoGenero);
		shuffle($mesmoGeneroArray);

		while (count($decoys) < 2 && $tentativas < 100 && count($mesmoGeneroArray) > 0) {
			if (isset($mesmoGeneroArray[$tentativas % count($mesmoGeneroArray)])) {
				$candidato = $mesmoGeneroArray[$tentativas % count($mesmoGeneroArray)];

				// Verifica se não está repetido
				$jaAdicionado = false;
				foreach ($decoys as $d) {
					if ($d['id'] === $candidato['id']) {
						$jaAdicionado = true;
						break;
					}
				}

				if (!$jaAdicionado) {
					$decoys[] = $candidato;
					$idsUsados[] = $candidato['id']; // Marca como usado
				}
			}
			$tentativas++;
		}

		// Monta opções (1 correta + 2 erradas)
		$opcoes = array_merge(array($correto), $decoys);
		shuffle($opcoes); // Embaralha para não ser sempre a primeira

		// Monta a pergunta
		$questions[] = array(
			'pergunta_id' => $correto['id'],
			'foto_crianca' => $correto['foto_crianca'],
			'opcoes' => array_map(function($opt) {
				return array(
					'id' => $opt['id'],
					'nome' => $opt['nome'],
					'foto_adulto' => $opt['foto_adulto']
				);
			}, $opcoes)
		);
	}

	$conn->close();

	echo json_encode(array(
		'success' => true,
		'questions' => $questions,
		'total' => count($questions)
	));

} catch (Exception $e) {
	echo json_encode(array('success' => false, 'error' => 'Erro interno', 'message' => $e->getMessage()));
}
