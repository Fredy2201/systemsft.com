<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_dni'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$SHAREPOINT_URL = 'https://myscia-my.sharepoint.com/:x:/g/personal/administrador_condorcocha_myssa_com_pe/IQBsIzBnnOJHRYCqpG7z8HcKAT9R7G3oksioxtTrSQuqZs8?e=2zSaqj&download=1';
$CACHE_JSON     = __DIR__ . '/datos_industry.json';
$TEMP_XLSX      = __DIR__ . '/temp_industry_sharepoint.xlsx';

function parseExcelDate($val) {
    $val = trim((string)$val);
    if ($val === '' || $val === '0') return ['', ''];

    if (is_numeric($val) && (float)$val > 20000) {
        $unix = ((int)$val - 25569) * 86400;
        return [gmdate('d-m-Y', $unix), gmdate('Y-m-d', $unix)];
    }

    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $m)) {
        $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $y = $m[3];
        return ["$d-$mo-$y", "$y-$mo-$d"];
    }

    return [$val, ''];
}

function colLettersToIndex($letters) {
    $letters = strtoupper($letters);
    $num = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $num = $num * 26 + (ord($letters[$i]) - 64);
    }
    return $num - 1;
}

function parseXlsxSheetCuadro($xlsxPath) {
    if (!class_exists('ZipArchive')) {
        throw new Exception('La extensión ZipArchive de PHP no está activa en el servidor.');
    }

    $zip = new ZipArchive();
    if ($zip->open($xlsxPath) !== true) {
        throw new Exception('El archivo descargado no es un Excel válido.');
    }

    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $xml = simplexml_load_string($ssXml);
        if ($xml && isset($xml->si)) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } elseif (isset($si->r)) {
                    $txt = '';
                    foreach ($si->r as $r) {
                        $txt .= (string)$r->t;
                    }
                    $sharedStrings[] = $txt;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
    }

    $targetSheetPath = 'xl/worksheets/sheet1.xml';
    $wbXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

    if ($wbXml !== false && $relsXml !== false) {
        $wb = simplexml_load_string($wbXml);
        $rels = simplexml_load_string($relsXml);
        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $relMap[(string)$rel['Id']] = (string)$rel['Target'];
        }
        foreach ($wb->sheets->sheet as $sheet) {
            if (strtoupper(trim((string)$sheet['name'])) === 'CUADRO') {
                $rId = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                if (isset($relMap[$rId])) {
                    $target = ltrim($relMap[$rId], '/');
                    $targetSheetPath = strpos($target, 'xl/') === 0 ? $target : 'xl/' . $target;
                }
                break;
            }
        }
    }

    $sheetContent = $zip->getFromName($targetSheetPath);
    $zip->close();

    if ($sheetContent === false) {
        throw new Exception('No se encontró la hoja CUADRO dentro del archivo Excel.');
    }

    $sheetXml = simplexml_load_string($sheetContent);
    $registros = [];

    foreach ($sheetXml->sheetData->row as $row) {
        $rowNum = (int)$row['r'];
        if ($rowNum < 3) continue;

        $cols = array_fill(0, 8, '');
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            preg_match('/^([A-Z]+)/', $ref, $m);
            $colIdx = colLettersToIndex($m[1] ?? 'A');
            if ($colIdx > 7) continue;

            $type = (string)$c['t'];
            $val = isset($c->v) ? (string)$c->v : '';

            if ($type === 's' && $val !== '') {
                $val = $sharedStrings[(int)$val] ?? '';
            } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                $val = (string)$c->is->t;
            }
            $cols[$colIdx] = trim($val);
        }

        $dniRaw = preg_replace('/\D/', '', $cols[0]);
        $nombre = trim($cols[1]);
        $curso  = trim($cols[3]);

        if ($dniRaw === '' || (int)$dniRaw === 0 || $nombre === '' || $curso === '') {
            continue;
        }

        $dni = str_pad($dniRaw, 8, '0', STR_PAD_LEFT);
        [$fechaEmision, $fechaEmisionIso] = parseExcelDate($cols[4]);
        [$fechaVenc, $fechaVencIso]       = parseExcelDate($cols[5]);

        $registros[] = [
            'd'            => $dni,
            'n'            => mb_strtoupper($nombre, 'UTF-8'),
            'isem'         => $cols[2],
            't'            => mb_strtoupper($curso, 'UTF-8'),
            'f'            => $fechaEmision,
            'fi'           => $fechaEmisionIso,
            'v'            => $fechaVenc,
            'vi'           => $fechaVencIso,
            'empresa'      => mb_strtoupper($cols[6], 'UTF-8'),
            'estado_excel' => mb_strtoupper($cols[7], 'UTF-8')
        ];
    }

    return $registros;
}

function downloadFromSharePoint($url, $destPath) {
    $cookieFile = __DIR__ . '/sp_cookies.txt';
    $ch = curl_init($url);
    $fp = fopen($destPath, 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 15,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
    ]);
    $ok = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    @unlink($cookieFile);

    if (!$ok || $httpCode >= 400) {
        @unlink($destPath);
        throw new Exception("No se pudo descargar desde SharePoint (HTTP $httpCode $err).");
    }

    $head = file_get_contents($destPath, false, null, 0, 4);
    if ($head !== "PK\x03\x04") {
        @unlink($destPath);
        throw new Exception("SharePoint devolvió una pantalla de inicio de sesión. Verifique que el vínculo sea público ('Cualquier persona con el vínculo') o use 'Subir .xlsx'.");
    }
}

$action = $_GET['action'] ?? 'get';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
        if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo Excel.');
        }
        move_uploaded_file($_FILES['excel_file']['tmp_name'], $TEMP_XLSX);
        $registros = parseXlsxSheetCuadro($TEMP_XLSX);
        @unlink($TEMP_XLSX);

        $payload = [
            'actualizado' => date('d/m/Y H:i'),
            'fuente'      => 'Carga manual Excel',
            'total'       => count($registros),
            'registros'   => $registros
        ];
        file_put_contents($CACHE_JSON, json_encode($payload, JSON_UNESCAPED_UNICODE));
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $forceSync = ($action === 'sync') || !file_exists($CACHE_JSON);

    if ($forceSync) {
        downloadFromSharePoint($SHAREPOINT_URL, $TEMP_XLSX);
        $registros = parseXlsxSheetCuadro($TEMP_XLSX);
        @unlink($TEMP_XLSX);

        $payload = [
            'actualizado' => date('d/m/Y H:i'),
            'fuente'      => 'SharePoint en línea',
            'total'       => count($registros),
            'registros'   => $registros
        ];
        file_put_contents($CACHE_JSON, json_encode($payload, JSON_UNESCAPED_UNICODE));
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo file_get_contents($CACHE_JSON);

} catch (Exception $e) {
    if (file_exists($CACHE_JSON) && $action === 'sync') {
        $cached = json_decode(file_get_contents($CACHE_JSON), true);
        $cached['warning'] = $e->getMessage();
        echo json_encode($cached, JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}