<?php
date_default_timezone_set('America/Lima');

$TOKEN_VALIDO = 'MYS_TAREO_2026';
$tokenRecibido = $_GET['token'] ?? '';
if (!hash_equals($TOKEN_VALIDO, $tokenRecibido)) {
    http_response_code(403);
    exit('Acceso denegado: enlace de descarga inválido.');
}

$rutaXlsx = __DIR__ . '/tareo_consolidado.xlsm';
if (!file_exists($rutaXlsx)) {
    http_response_code(404);
    exit('El archivo consolidado aún no ha sido subido al servidor.');
}

$nombreDescarga = 'TAREO_CONSOLIDADO_' . date('Y-m-d_Hi') . '.xlsm';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($rutaXlsx));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

readfile($rutaXlsx);
exit;