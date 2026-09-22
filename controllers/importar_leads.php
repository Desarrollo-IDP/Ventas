<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Prospecto.php';

header('Content-Type: application/json; charset=utf-8');

function normalizarEncabezado($valor) {
    $valor = trim((string) $valor);
    $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor);
    $acentos = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u'
    ];
    $valor = strtr($valor, $acentos);
    $valor = strtolower($valor);
    return trim(preg_replace('/[^a-z0-9]+/', '_', $valor), '_');
}

function leerCsv($ruta) {
    $handle = fopen($ruta, 'rb');
    if (!$handle) {
        throw new RuntimeException('No se pudo leer el archivo.');
    }
    $primeraLinea = fgets($handle);
    $primeraLineaLimpia = preg_replace('/^\xEF\xBB\xBF/', '', trim($primeraLinea));
    if (stripos($primeraLineaLimpia, 'sep=') === 0) {
        $delimitador = substr($primeraLineaLimpia, 4, 1) ?: ';';
        $encabezados = fgetcsv($handle, 0, $delimitador);
    } else {
        rewind($handle);
        $delimitador = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
        $encabezados = fgetcsv($handle, 0, $delimitador);
    }
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
        $row->registerXPathNamespace('x', $mainNamespace);
        $values = [];
        foreach ($row->xpath('./x:c') as $cell) {
            $cell->registerXPathNamespace('x', $mainNamespace);
            $attributes = $cell->attributes();
            $ref = (string) ($attributes['r'] ?? '');
            preg_match('/([A-Z]+)\d+/', $ref, $match);
            $column = 0;
            foreach (str_split($match[1] ?? 'A') as $letter) {
                $column = $column * 26 + ord($letter) - 64;
            }
            $valueNodes = $cell->xpath('./x:v');
            $value = (string) ($valueNodes[0] ?? '');
            if ((string) ($attributes['t'] ?? '') === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            }
            $values[$column - 1] = $value;
        }
        if (count(array_filter($values, static fn($valor) => trim((string) $valor) !== '')) > 0) {
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
        throw new RuntimeException('Selecciona un archivo CSV.');
    }
    $archivo = $_FILES['archivo'];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir el archivo.');
    }
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        throw new RuntimeException('Formato no válido. Usa un archivo .csv.');
    }
    [$encabezados, $filas] = leerCsv($archivo['tmp_name']);
    $mapa = [];
    foreach ($encabezados as $indice => $encabezado) {
        $mapa[normalizarEncabezado($encabezado)] = $indice;
    }
    $requeridos = ['empresa'];
    $faltantes = array_values(array_filter($requeridos, static fn($campo) => !array_key_exists($campo, $mapa)));
    if (in_array('empresa', $faltantes, true)) {
        throw new RuntimeException('Falta la columna obligatoria "Empresa". Columnas esperadas: Empresa, Contacto, Teléfono, Correo electrónico, Ubicación, Informes de llamada, Estatus.');
    }

    $db = Database::getInstance('development')->getConnection();
    $modelo = new Prospecto($db);
    $importados = 0;
    $omitidos = [];
    foreach ($filas as $indice => $fila) {
        $numeroFila = $indice + 2;
        $empresa = valorDeFila($fila, $mapa, ['empresa', 'negocio', 'razon_social']);
        if ($empresa === '') {
            $omitidos[] = "Fila {$numeroFila}: falta Empresa.";
            continue;
        }
        $contacto = valorDeFila($fila, $mapa, ['contacto', 'nombre_de_contacto', 'nombre_del_contacto', 'nombre_contacto', 'persona_de_contacto', 'nombre']);
        $datos = [
            'nombre' => $contacto !== '' ? $contacto : $empresa,
            'empresa' => $empresa,
            'cargo_contacto' => valorDeFila($fila, $mapa, ['cargo', 'puesto', 'cargo_contacto']),
            'email' => valorDeFila($fila, $mapa, ['correo_electronico', 'correo', 'email']),
            'telefono' => valorDeFila($fila, $mapa, ['telefono', 'teléfono', 'numero', 'número', 'whatsapp', 'celular']),
            'ubicacion' => valorDeFila($fila, $mapa, ['ubicacion', 'dirección', 'direccion', 'ciudad']),
            'informes_llamada' => valorDeFila($fila, $mapa, ['informes_de_llamada', 'informes_llamada', 'informe_de_llamada', 'notas', 'comentarios']),
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
