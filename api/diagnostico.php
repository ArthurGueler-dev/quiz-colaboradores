<?php
header('Content-Type: application/json; charset=utf-8');

// Lista todos os diretórios e arquivos no servidor
$info = [
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'current_dir' => __DIR__,
    'parent_dir' => dirname(__DIR__),
    'dirs_in_document_root' => [],
    'dirs_in_parent' => [],
    'busca_dia_criancas' => []
];

// Lista diretórios no document root
if (is_dir($_SERVER['DOCUMENT_ROOT'])) {
    $files = scandir($_SERVER['DOCUMENT_ROOT']);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_dir($_SERVER['DOCUMENT_ROOT'] . '/' . $file)) {
            $info['dirs_in_document_root'][] = $file;
        }
    }
}

// Lista diretórios no parent
$parent = dirname(__DIR__);
if (is_dir($parent)) {
    $files = scandir($parent);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_dir($parent . '/' . $file)) {
            $info['dirs_in_parent'][] = $file;
        }
    }
}

// Busca recursiva por pastas que contenham "dia" ou "criança"
function buscarPastas($dir, $maxDepth = 3, $currentDepth = 0) {
    $resultado = [];
    if ($currentDepth >= $maxDepth) return $resultado;

    if (!is_dir($dir) || !is_readable($dir)) return $resultado;

    $files = @scandir($dir);
    if (!$files) return $resultado;

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            // Verifica se o nome contém "dia" ou "crian"
            if (stripos($file, 'dia') !== false || stripos($file, 'crian') !== false) {
                $resultado[] = $path;
            }
            // Busca recursivamente
            $resultado = array_merge($resultado, buscarPastas($path, $maxDepth, $currentDepth + 1));
        }
    }

    return $resultado;
}

$info['busca_dia_criancas'] = buscarPastas($_SERVER['DOCUMENT_ROOT'], 3);

echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
