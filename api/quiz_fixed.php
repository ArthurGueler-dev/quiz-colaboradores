<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit(0);
}

$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
	$conn = new mysqli($host, $user, $password, $database, $port);
	if ($conn->connect_error) {
		echo json_encode(array('success' => false, 'error' => 'Erro de conexão'));
		exit();
	}

	// Busca colaboradores
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

	if (count($colaboradores) < 3) {
		echo json_encode(array('success' => false, 'error' => 'Não há colaboradores suficientes'));
		exit();
	}

	// Função para detectar gênero
	function detectarGenero($nome) {
		$nomeUpper = strtoupper($nome);
		$femininos = array('MARIA', 'ANA', 'JULIANA', 'FERNANDA', 'AMANDA', 'SABRINA', 'THAYNARA', 'VICTORIA', 'ALZIRENE', 'CAROLINE', 'DANIELLE', 'DIANNE', 'EVELIN', 'KARLIANY', 'LAIS', 'RAYANE');

		foreach ($femininos as $fem) {
			if (strpos($nomeUpper, $fem) !== false) {
				return 'F';
			}
		}
		return 'M';
	}

	$totalPerguntas = min(10, count($colaboradores));
	$questions = array();

	shuffle($colaboradores);

	for ($i = 0; $i < $totalPerguntas; $i++) {
		$correto = $colaboradores[$i];
		$generoCorreto = detectarGenero($correto['nome']);

		// Filtra por gênero
		$mesmoGenero = array();
		foreach ($colaboradores as $c) {
			if ($c['id'] != $correto['id'] && detectarGenero($c['nome']) === $generoCorreto) {
				$mesmoGenero[] = $c;
			}
		}

		// Se não tiver suficientes, usa todos
		if (count($mesmoGenero) < 2) {
			$mesmoGenero = array();
			foreach ($colaboradores as $c) {
				if ($c['id'] != $correto['id']) {
					$mesmoGenero[] = $c;
				}
			}
		}

		shuffle($mesmoGenero);

		// Pega 2 decoys
		$decoys = array();
		for ($j = 0; $j < min(2, count($mesmoGenero)); $j++) {
			$decoys[] = $mesmoGenero[$j];
		}

		// Monta opções
		$opcoes = array_merge(array($correto), $decoys);
		shuffle($opcoes);

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
	echo json_encode(array('success' => false, 'error' => $e->getMessage()));
}
