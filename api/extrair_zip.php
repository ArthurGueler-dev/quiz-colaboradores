<?php
header('Content-Type: application/json; charset=utf-8');

$zipFile = $_SERVER['DOCUMENT_ROOT'] . '/Dia das Crianças.zip';
$destino = $_SERVER['DOCUMENT_ROOT'];

$info = [
    'zip_path' => $zipFile,
    'zip_existe' => file_exists($zipFile),
    'destino' => $destino,
    'destino_writeable' => is_writable($destino)
];

if (!file_exists($zipFile)) {
    // Tenta outros possíveis nomes
    $possiveis = [
        $_SERVER['DOCUMENT_ROOT'] . '/Dia das Criancas.zip',
        $_SERVER['DOCUMENT_ROOT'] . '/Dia_das_Criancas.zip',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/Dia das Crianças.zip',
    ];

    foreach ($possiveis as $p) {
        if (file_exists($p)) {
            $zipFile = $p;
            $info['zip_encontrado_em'] = $p;
            break;
        }
    }
}

if (file_exists($zipFile)) {
    $zip = new ZipArchive();
    $result = $zip->open($zipFile);

    if ($result === TRUE) {
        $zip->extractTo($destino);
        $zip->close();

        $info['sucesso'] = true;
        $info['mensagem'] = 'Arquivo extraído com sucesso!';

        // Lista o que foi extraído
        $extracted = scandir($destino);
        $info['arquivos_apos_extracao'] = array_filter($extracted, function($f) use ($destino) {
            return $f !== '.' && $f !== '..' && is_dir($destino . '/' . $f);
        });
    } else {
        $info['sucesso'] = false;
        $info['erro'] = 'Não foi possível abrir o arquivo ZIP. Código: ' . $result;
    }
} else {
    $info['sucesso'] = false;
    $info['erro'] = 'Arquivo ZIP não encontrado';

    // Lista todos os arquivos .zip no diretório
    $allFiles = scandir($_SERVER['DOCUMENT_ROOT']);
    $info['zips_disponiveis'] = array_filter($allFiles, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'zip';
    });
}

echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
