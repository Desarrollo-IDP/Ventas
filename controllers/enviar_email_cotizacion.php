<?php
// Forzamos buffer para capturar cualquier salida accidental (warnings/notices)
ob_start();

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/Cotizacion.php';
require_once __DIR__ . '/../models/Notificacion.php';

// Configuración de errores: no mostrar en output, solo loguear
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Preparar carpeta de logs y verificar permisos
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0775, true);
}
if (!is_writable($logsDir)) {
    // Intentar ajustar permisos, y si no, usar sys_temp_dir
    @chmod($logsDir, 0775);
}
$defaultLog = $logsDir . '/php-error.log';
if (!@touch($defaultLog)) {
    // Fallback a directorio temporal si no se puede escribir
    $defaultLog = sys_get_temp_dir() . '/php-error.log';
}
ini_set('error_log', $defaultLog);

// Helper para logging común
function log_info($msg, array $context = []): void {
    $line = '[enviar_email_cotizacion] ' . $msg;
    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    error_log($line);
}

// Siempre retornamos JSON
header('Content-Type: application/json; charset=utf-8');

function respond_json(int $statusCode, array $payload): void {
    // Limpiar cualquier salida previa (HTML/notices) para no romper JSON
    @ob_clean();
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        log_info('Método no permitido', ['method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown']);
        respond_json(405, [
            'success' => false,
            'message' => 'Método no permitido'
        ]);
    }

    // Obtener datos del request: soporta multipart/form-data (POST) y application/json (php://input)
    $cotizacion_id = null;

    // 1) Intentar desde $_POST (FormData)
    if (isset($_POST['cotizacion_id'])) {
        $cotizacion_id = $_POST['cotizacion_id'];
    }

    // 2) Si no viene por POST tradicional, intentar JSON body
    if ($cotizacion_id === null) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $data = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $cotizacion_id = $data['cotizacion_id'] ?? null;
            } else {
                log_info('Cuerpo no es JSON válido', ['raw' => substr($raw, 0, 200)]);
            }
        }
    }

    // Normalizar/validar
    if ($cotizacion_id === null || $cotizacion_id === '') {
        log_info('ID de cotización ausente', ['post' => $_POST, 'query' => $_GET]);
        respond_json(400, [
            'success' => false,
            'message' => 'ID de cotización requerido'
        ]);
    }

    // Opcional: castear a entero si corresponde
    if (is_numeric($cotizacion_id)) {
        $cotizacion_id = (int)$cotizacion_id;
        if ($cotizacion_id <= 0) {
            log_info('ID de cotización inválido tras cast', ['cotizacion_id' => $cotizacion_id]);
            respond_json(400, [
                'success' => false,
                'message' => 'ID de cotización inválido'
            ]);
        }
    } else {
        log_info('ID de cotización no numérico', ['cotizacion_id' => $cotizacion_id]);
    }

    log_info('Request recibido', ['cotizacion_id' => $cotizacion_id]);

    // Conexión a DB con manejo de errores explícito
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        if (!$db) {
            throw new RuntimeException('No se obtuvo conexión DB');
        }
    } catch (Throwable $dbE) {
        error_log('[DB] ' . $dbE->getMessage());
        respond_json(500, [
            'success' => false,
            'message' => 'Error de conexión a base de datos'
        ]);
    }

    // Ejecutar reenviar y medir resultado
    try {
        $notificacion = new Notificacion($db);
        if (!method_exists($notificacion, 'reenviar')) {
            log_info('Método reenviar no existe en Notificacion');
            respond_json(500, [
                'success' => false,
                'message' => 'Configuración de notificaciones inválida'
            ]);
        }

        log_info('Invocando Notificacion->reenviar', ['cotizacion_id' => $cotizacion_id, 'tipo' => 'creacion']);
        $resultado = $notificacion->reenviar($cotizacion_id, 'creacion');

        if ($resultado) {
            log_info('Reenvío exitoso', ['cotizacion_id' => $cotizacion_id]);
            respond_json(200, [
                'success' => true,
                'message' => 'Email enviado correctamente'
            ]);
        }

        // Si reenviar devolvió false, logueamos y respondemos 500
        log_info('Reenvío devolvió false', ['cotizacion_id' => $cotizacion_id]);
        respond_json(500, [
            'success' => false,
            'message' => 'Error al enviar el email'
        ]);
    } catch (Throwable $srvE) {
        // Error dentro del proceso de reenvío
        log_info('Excepción en reenviar', ['error' => $srvE->getMessage()]);
        respond_json(500, [
            'success' => false,
            'message' => 'Error al enviar el email'
        ]);
    }

} catch (Throwable $e) {
    // Log completo del error
    error_log('[enviar_email_cotizacion] Exception: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());

    respond_json(500, [
        'success' => false,
        'message' => 'Error interno'
    ]);
}
?>