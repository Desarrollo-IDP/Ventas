<?php
// Forzamos buffer para capturar cualquier salida accidental (warnings/notices)
ob_start();

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/Cotizacion.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/ConversacionCotizacion.php';
require_once __DIR__ . '/../models/Notificacion.php';

// Configuración de errores: no mostrar en output, solo loguear
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Helper para logging común
function log_info($msg, array $context = []): void {
    $line = '[enviar_mensaje_conversacion] ' . $msg;
    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    error_log($line);
}

// Siempre retornamos JSON
header('Content-Type: application/json; charset=utf-8');

function respond_json(int $statusCode, array $payload): void {
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

    // Obtener datos POST: cotizacion_id y mensaje
    $cotizacion_id = $_POST['cotizacion_id'] ?? null;
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (!$cotizacion_id || !is_numeric($cotizacion_id)) {
        respond_json(400, [
            'success' => false,
            'message' => 'ID de cotización inválido o ausente'
        ]);
    }

    if (empty($mensaje)) {
        respond_json(400, [
            'success' => false,
            'message' => 'El mensaje no puede estar vacío'
        ]);
    }

    $database = Database::getInstance();
    $db = $database->getConnection();
    if (!$db) {
        throw new RuntimeException('No se pudo conectar a la base de datos');
    }

    $cotizacionModel = new Cotizacion($db);
    $clienteModel = new Cliente($db);
    $conversacionModel = new ConversacionCotizacion($db);

    $cotizacion = $cotizacionModel->obtenerPorId((int)$cotizacion_id);
    if (!$cotizacion) {
        respond_json(404, [
            'success' => false,
            'message' => 'Cotización no encontrada'
        ]);
    }

    $cliente = $clienteModel->obtenerPorId($cotizacion['cliente_id']);
    if (!$cliente || empty($cliente['email'])) {
        respond_json(404, [
            'success' => false,
            'message' => 'Cliente no encontrado o sin correo electrónico'
        ]);
    }

    // Guardar mensaje como conversación interna (es_interno=1)
    $crear_data = [
        'cotizacion_id' => (int)$cotizacion_id,
        'usuario_id' => null, // usuario del sistema
        'tipo' => 'respuesta_sistema',
        'mensaje' => $mensaje,
        'autor' => 'Sistema',
        'es_interno' => 1
    ];

    $insert_id = $conversacionModel->crear($crear_data);
    if (!$insert_id) {
        respond_json(500, [
            'success' => false,
            'message' => 'Error al guardar mensaje en conversación'
        ]);
    }

    // Enviar email al cliente con el mensaje nuevo
    $notificacion = new Notificacion($db);

    // Construir asunto y cuerpo del mensaje personalizado
    $asunto = "Respuesta sobre su cotización: " . $cotizacion['folio'];
    $cuerpo = "<p>Estimado/a <strong>" . htmlspecialchars($cliente['nombre']) . "</strong>,</p>";
    $cuerpo .= "<p>Ha recibido una nueva respuesta del sistema respecto a su cotización <strong>" . htmlspecialchars($cotizacion['folio']) . "</strong>:</p>";
    $cuerpo .= "<blockquote style='border-left: 4px solid #ccc; padding-left: 10px; margin: 10px 0;'>";
    $cuerpo .= nl2br(htmlspecialchars($mensaje));
    $cuerpo .= "</blockquote>";
    $cuerpo .= "<p>Por favor, responda a este correo para continuar la conversación o contacte con nosotros si necesita más información.</p>";

    // Registrar y enviar notificación manualmente
    $registrar_result = $notificacion->registrar($cotizacion_id, 'respuesta_sistema', $cliente['email'], $asunto, $cuerpo);

    // Enviar email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: sistema@empresa.com\r\n";

    $mail_result = mail($cliente['email'], $asunto, $cuerpo, $headers);

    if (!$mail_result) {
        log_info('Error al enviar email al cliente', ['cliente_email' => $cliente['email']]);
    }

    respond_json(200, [
        'success' => true,
        'message' => 'Mensaje enviado y guardado en conversación correctamente'
    ]);
} catch (Throwable $e) {
    error_log('[enviar_mensaje_conversacion] Excepción: ' . $e->getMessage());
    respond_json(500, [
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
