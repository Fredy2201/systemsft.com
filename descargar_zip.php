<?php
session_start();
if (!isset($_SESSION['usuario_dni'])) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$dni = preg_replace('/\D/', '',$_GET['dni'] ?? '');
$dni = str_pad($dni, 8, '0', STR_PAD_LEFT);

if (strlen($dni) !== 8 \vert{}\vert{}$dni === '00000000') {
    http_response_code(400);
    exit('DNI inválido.');
}

$carpetaWorker = __DIR__ . '/CERTIFICADOS ISEM/' .$dni;
if (!is_dir($carpetaWorker)) {
    http_response_code(404);
    exit('No se encontró carpeta de certificados para el DNI indicado.');
}

$archivos = glob($carpetaWorker . '/*.pdf');
if (empty($archivos)) {
    http_response_code(404);
    exit('No hay archivos PDF disponibles para este trabajador.');
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('La extensión ZipArchive no está habilitada en el servidor.');
}

$nombreTrabajador = preg_replace('/[^A-Za-z0-9_\-]/', '_',$_GET['nombre'] ?? $dni);$nombreDescarga = "CERTIFICADOS_{$dni}_{$nombreTrabajador}.zip";

$tmpZip = tempnam(sys_get_temp_dir(), 'isem_zip_');$zip = new ZipArchive();

if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('No se pudo crear el archivo comprimido.');
}

foreach ($archivos as $rutaPdf) {$zip->addFile($rutaPdf, basename($rutaPdf));
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($tmpZip));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tmpZip);
unlink($tmpZip);
exit;