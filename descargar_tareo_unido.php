<?php
// Clave secreta incluida en el enlace de descarga directa
$TOKEN_VALIDO = '1234';

$tokenRecibido = $_GET['token'] ?? '';
if (!hash_equals($TOKEN_VALIDO, $tokenRecibido)) {
    http_response_code(403);
    exit('Acceso denegado: enlace de descarga inválido.');
}

$rutaGz = __DIR__ . '/tareo_consolidado.json.gz';
if (!file_exists($rutaGz)) {
    http_response_code(404);
    exit('El archivo consolidado aún no ha sido subido al servidor.');
}

// 1. Descomprimir datos en memoria
$jsonRaw = gzdecode(file_get_contents($rutaGz));
$payload = json_decode($jsonRaw, true);

$columnas = $payload['columnas'] ?? [];
$datos = $payload['datos'] ?? [];

if (empty($columnas)) {
    http_response_code(500);
    exit('El archivo consolidado está vacío.');
}

// 2. Generar archivo Excel (.xlsx) nativo
function xmlEscape($val) {
    return htmlspecialchars((string)$val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function colLetra($index) {
    $letra = '';
    while ($index >= 0) {
        $letra = chr($index % 26 + 65) . $letra;
        $index = intval($index / 26) - 1;
    }
    return $letra;
}

$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
          . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

// Encabezados (Fila 1)
$sheetXml .= '<row r="1">';
foreach ($columnas as $cIdx => $colName) {
    $ref = colLetra($cIdx) . '1';
    $sheetXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . xmlEscape($colName) . '</t></is></c>';
}
$sheetXml .= '</row>';

// Registros (Filas 2 en adelante)
$rowNum = 2;
foreach ($datos as $fila) {
    $sheetXml .= '<row r="' . $rowNum . '">';
    foreach ($columnas as $cIdx => $colName) {
        $ref = colLetra($cIdx) . $rowNum;
        $valor = $fila[$colName] ?? '';
        if ($valor !== '' && is_numeric($valor) && !preg_match('/^0\d+/', (string)$valor)) {
            $sheetXml .= '<c r="' . $ref . '"><v>' . $valor . '</v></c>';
        } else {
            $sheetXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . xmlEscape($valor) . '</t></is></c>';
        }
    }
    $sheetXml .= '</row>';
    $rowNum++;
}
$sheetXml .= '</sheetData></worksheet>';

// Empaquetar .xlsx
$tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
$zip = new ZipArchive();
$zip->open($tmpFile, ZipArchive::OVERWRITE);

$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');

$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/workbook" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="CONSOLIDADO" sheetId="1" r:id="rId1"/></sheets>
</workbook>');

$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');

$zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
$zip->close();

$nombreArchivo = 'TAREO_CONSOLIDADO_' . date('Y-m-d_Hi') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

readfile($tmpFile);
unlink($tmpFile);
exit;