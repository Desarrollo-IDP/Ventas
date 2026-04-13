<?php
// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering immediately to capture any warnings from includes
ob_start();
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Cotizacion.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../models/ConversacionCotizacion.php';
require_once __DIR__ . '/../models/Cliente.php';

header('Content-Type: application/json; charset=utf-8');

// Buffer output to prevent stray PHP warnings/notices breaking JSON responses

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    // Obtener datos del POST (soporta FormData y JSON)
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'application/json') !== false) {
        $datos = json_decode(file_get_contents('php://input'), true);
    } else {
        // FormData
        $datos = [
            'cliente_id' => $_POST['cliente_id'] ?? null,
            'fecha_vencimiento' => $_POST['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+15 days')),
            'notas' => $_POST['notas'] ?? '',
            'detalles' => json_decode($_POST['detalles'] ?? '[]', true)
        ];
    }
    
    // Validar datos básicos
    if (!isset($datos['cliente_id']) || empty($datos['cliente_id'])) {
        throw new Exception('Cliente requerido');
    }

    if (!isset($datos['detalles']) || empty($datos['detalles'])) {
        throw new Exception('Debe agregar al menos un producto');
    }

    // Calcular totales
    $subtotal = 0;
    foreach ($datos['detalles'] as &$detalle) {
        $detalle['importe'] = $detalle['cantidad'] * $detalle['precio_unitario'];
        $subtotal += $detalle['importe'];
    }
    
    $iva = $subtotal * 0.16; // 16% IVA
    $total = $subtotal + $iva;

    $datos_cotizacion = [
        'cliente_id' => $datos['cliente_id'],
        'fecha_vencimiento' => $datos['fecha_vencimiento'],
        'subtotal' => $subtotal,
        'iva' => $iva,
        'total' => $total,
        'notas' => $datos['notas'],
        'detalles' => $datos['detalles']
    ];

    $cotizacion = new Cotizacion($db);
    $cotizacion_id = $cotizacion->crear($datos_cotizacion);

    // Registrar entrada inicial en el historial de conversaciones
    try {
        $conversacion = new ConversacionCotizacion($db);
        $detalles_productos = [];
        foreach ($datos['detalles'] as $det) {
            $detalles_productos[] = "- Producto ID: {$det['producto_id']}, Cantidad: {$det['cantidad']}, Precio: ${det['precio_unitario']}";
        }
        $mensaje_creacion = "Cotización creada con los siguientes productos:\n" . implode("\n", $detalles_productos) . "\n\nSubtotal: \${$subtotal}\nIVA: \${$iva}\nTotal: \${$total}";
        
        $conversacion->crear([
            'cotizacion_id' => $cotizacion_id,
            'usuario_id' => null,
            'tipo' => 'creacion',
            'mensaje' => $mensaje_creacion,
            'autor' => 'Sistema',
            'es_interno' => 1
        ]);
    } catch (Exception $ce) {
        error_log('Warning creating conversation history: ' . $ce->getMessage());
    }

    // La notificación se enviará a través de la clase Notificacion más abajo

    // Enviar notificación (no bloquear en caso de fallo)
    try {
        $notificacion = new Notificacion($db);
        $resultado_email = $notificacion->enviarEmailCotizacion($cotizacion_id, 'creacion');

    } catch (Exception $ne) {
        error_log('Warning sending notification: ' . $ne->getMessage());
    }

    // Clean any stray output (warnings/notices) so client gets pure JSON
    $buffer = ob_get_clean();
    if (!empty($buffer)) {
        error_log('Output captured before JSON response in crear_cotizacion: ' . $buffer);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cotización creada exitosamente',
        'cotizacion_id' => $cotizacion_id
    ]);
    
} catch (Exception $e) {
    // Capture and clear any buffer to avoid leaking HTML/error pages
    $buffer = ob_get_clean();
    if (!empty($buffer)) {
        error_log('Output captured on exception in crear_cotizacion: ' . $buffer);
    }

    http_response_code(400);
    $payload = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    echo json_encode($payload);
    error_log("Error al crear cotización: " . $e->getMessage());
}
?>
