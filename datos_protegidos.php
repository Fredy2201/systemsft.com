<?php
session_start();
if (!isset($_SESSION['usuario_dni'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$archivo = __DIR__ . '/datos_isem.json';
if (file_exists($archivo)) {
    readfile($archivo);
} else {
    echo json_encode(['registros' => []]);
}