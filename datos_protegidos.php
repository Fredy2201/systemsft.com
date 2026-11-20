<?php
session_start();
if (!isset($_SESSION['usuario_dni'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$archivoCerts = __DIR__ . '/datos_isem.json';$archivoPersonal = __DIR__ . '/personal_lista.json';

$certsData = file_exists($archivoCerts)
    ? json_decode(file_get_contents($archivoCerts), true)
    : ['actualizado' => '-', 'total_certificados' => 0, 'total_trabajadores' => 0, 'registros' => []];

$personalMap = [];
if (file_exists($archivoPersonal)) {
    $pData = json_decode(file_get_contents($archivoPersonal), true);
    foreach (($pData['personal'] ?? []) as$p) {
        $dni = str_pad(trim($p['DNI'] ?? ''), 8, '0', STR_PAD_LEFT);
        if ($dni !== '00000000') {
            $personalMap[$dni] = [
                'cargo'        => trim($p['CARGO'] ?? ''),
                'especialidad' => trim($p['ESPECIALIDAD'] ?? ''),
                'celular'      => trim($p['CELULAR'] ?? ''),
                'linea'        => trim($p['LINEA'] ?? ''),
                'ot'           => trim($p['OT'] ?? ''),
                'vigencia'     => strtoupper(trim($p['VIGENCIA'] ?? '')),
                'sangre'       => trim($p['T. SANGRE'] ?? ''),
                'area'         => trim($p['AREATB'] ?? '')
            ];
        }
    }
}

$certsData['personal_map'] =$personalMap;
echo json_encode($certsData, JSON_UNESCAPED_UNICODE);