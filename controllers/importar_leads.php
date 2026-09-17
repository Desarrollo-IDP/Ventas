<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Prospecto.php';

header('Content-Type: application/json; charset=utf-8');

function normalizarEncabezado($valor) {
    $valor = trim((string) $valor);
    $valor = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) : $valor;
    return strtolower(preg_replace('/[^a-z0-9]+/', '_', $valor));
}

function leerCsv($ruta) {
    $handle = fopen($ruta, 'rb');
    if (!$handle) {
        throw new RuntimeException('No se pudo leer el archivo.');
    }
    $primeraLinea = fgets($handle);
    rewind($handle);
    $delimitador = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
    $encabezados = fgetcsv($handle, 0, $delimitador);
    $filas = [];
    while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
        if (count(array_filter($fila, static fn($valor) => trim((string) $valor) !== '')) > 0) {
            $filas[] = $fila;
        }
    }
    fclose($handle);
    return [$encabezados, $filas];
}

function leerXlsx($ruta) {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('El servidor no tiene habilitada la extensión ZIP para leer archivos XLSX. Guarda el archivo como CSV o habilita php_zip.');
    }
    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo XLSX.');
    }
    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml, 'SimpleXMLElement', LIBXML_NONET);
        foreach ($xml->si as $item) {
            $sharedStrings[] = (string) ($item->t ?? implode('', array_map('strval', $item->r->t ?? [])));
        }
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('El XLSX no contiene una primera hoja válida.');
    }
    $sheet = simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NONET);
    $namespaces = $sheet->getNamespaces(true);
    $mainNamespace = $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $sheet->registerXPathNamespace('x', $mainNamespace);
    $rows = [];
    foreach ($sheet->xpath('//x:sheetData/x:row') as $row) {
        $values = [];
        foreach ($row->xpath('./x:c') as $cell) {
            $attributes = $cell->attributes();
            $ref = (string) $attributes['r'];
            preg_match('/([A-Z]+)\d+/', $ref, $match);
            $column = 0;
            foreach (str_split($match[1] ?? 'A') as $letter) {
                $column = $column * 26 + ord($letter) - 64;
            }
            $valueNodes = $cell->xpath('./x:v');
            $value = (string) ($valueNodes[0] ?? '');
            if ((string) $attributes['t'] === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            }
            $values[$column - 1] = $value;
        }
        if ($values) {
            ksort($values);
            $rows[] = array_values($values);
        }
    }
    return [$rows[0] ?? [], array_slice($rows, 1)];
}

function valorDeFila($fila, $mapa, $nombres) {
    foreach ($nombres as $nombre) {
        $clave = normalizarEncabezado($nombre);
        if (isset($mapa[$clave])) {
            return trim((string) ($fila[$mapa[$clave]] ?? ''));
        }
    }
    return '';
}

function estadoImportado($valor) {
    $estado = normalizarEncabezado($valor);
    $estados = [
        'lead' => 'lead', 'bd_lead' => 'lead', 'nuevo' => 'lead',
        'contacto' => 'contacto', 'contactado' => 'contacto',
        'conectado' => 'conectado', 'prospecto' => 'prospecto',
        'oportunidad' => 'oportunidad', 'ganada' => 'ganada', 'ganado' => 'ganada',
        'perdida' => 'perdida', 'perdido' => 'perdida', 'no_viable' => 'no_viable'
    ];
    return $estados[$estado] ?? 'lead';
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo'])) {
        throw new RuntimeException('Selecciona un archivo Excel o CSV.');
    }
    $archivo = $_FILES['archivo'];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir el archivo.');
    }
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'csv'], true)) {
        throw new RuntimeException('Formato no válido. Usa un archivo .xlsx o .csv.');
    }
    [$encabezados, $filas] = $extension === 'xlsx' ? leerXlsx($archivo['tmp_name']) : leerCsv($archivo['tmp_name']);
    $mapa = [];
    foreach ($encabezados as $indice => $encabezado) {
        $mapa[normalizarEncabezado($encabezado)] = $indice;
    }
    $requeridos = ['empresa', 'numero', 'correo_electronico', 'ubicacion', 'informes_de_llamada', 'estatus'];
    $faltantes = array_values(array_filter($requeridos, static fn($campo) => !array_key_exists($campo, $mapa)));
    if (in_array('empresa', $faltantes, true)) {
        throw new RuntimeException('Falta la columna obligatoria "Empresa". Columnas esperadas: Empresa, Número, Correo electrónico, Ubicación, Informes de llamada, Estatus.');
    }

    $db = Database::getInstance('development')->getConnection();
    $modelo = new Prospecto($db);
    $importados = 0;
    $omitidos = [];
    foreach ($filas as $indice => $fila) {
        $numeroFila = $indice + 2;
        $empresa = valorDeFila($fila, $mapa, ['empresa']);
        if ($empresa === '') {
            $omitidos[] = "Fila {$numeroFila}: falta Empresa.";
            continue;
        }
        $datos = [
            'nombre' => $empresa,
            'empresa' => $empresa,
            'email' => valorDeFila($fila, $mapa, ['correo_electronico', 'correo', 'email']),
            'telefono' => valorDeFila($fila, $mapa, ['numero', 'telefono', 'teléfono']),
            'ubicacion' => valorDeFila($fila, $mapa, ['ubicacion', 'dirección', 'direccion']),
            'informes_llamada' => valorDeFila($fila, $mapa, ['informes_de_llamada', 'informes_llamada', 'informe_de_llamada']),
            'estado' => estadoImportado(valorDeFila($fila, $mapa, ['estatus', 'estado'])),
            'origen' => 'Base de datos',
            'vendedor_id' => $_SESSION['user_id'] ?? null,
            'fecha_primer_contacto' => date('Y-m-d')
        ];
        try {
            $modelo->crear($datos);
            $importados++;
        } catch (InvalidArgumentException $e) {
            $omitidos[] = "Fila {$numeroFila}: {$e->getMessage()}";
        } catch (PDOException $e) {
            $omitidos[] = "Fila {$numeroFila}: no se pudo guardar el registro.";
        }
    }
    echo json_encode(['success' => true, 'message' => "Importación terminada: {$importados} registros importados.", 'importados' => $importados, 'omitidos' => $omitidos]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
