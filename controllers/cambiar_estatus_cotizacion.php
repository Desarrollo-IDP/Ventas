<?php
// Start output buffering immediately to capture any warnings from includes
ob_start();

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/Cotizacion.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../models/Cliente.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Validar que es una solicitud POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        throw new Exception('Método no permitido');
    }

    // Obtener parámetros
    $cotizacion_id = $_POST['cotizacion_id'] ?? null;
    $nuevo_estatus = $_POST['estatus'] ?? null;

    if (!$cotizacion_id || !$nuevo_estatus) {
        ob_end_clean();
        throw new Exception('Parámetros incompletos');
    }

    // Validar que el estatus sea válido
    $estatus_validos = ['pendiente', 'aceptada', 'rechazada', 'expirada', 'cancelada'];
    if (!in_array($nuevo_estatus, $estatus_validos)) {
        ob_end_clean();
        throw new Exception('Estatus no válido');
    }

    // Conectar a la base de datos
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Cambiar estatus
    $cotizacion = new Cotizacion($db);
    $resultado = $cotizacion->cambiarEstatus($cotizacion_id, $nuevo_estatus);

    if ($resultado) {
        // Obtener datos de la cotización
        $cotizacion_datos = $cotizacion->obtenerPorId($cotizacion_id);
        
        // Enviar correo HTML según el estatus (no bloquear si falla)
        // Enviar correo HTML según el estatus (no bloquear si falla)
        try {
            $notificacion = new Notificacion($db);
            $tipo_notificacion = '';
            
            switch ($nuevo_estatus) {
                case 'aceptada':
                    $tipo_notificacion = 'aceptacion';
                    break;
                case 'rechazada':
                    $tipo_notificacion = 'rechazo';
                    break;
                case 'cancelada':
                    $tipo_notificacion = 'cancelacion';
                    break;
            }
            
            if ($tipo_notificacion) {
                $notificacion->enviarEmailCotizacion($cotizacion_id, $tipo_notificacion);
            }
        } catch (Exception $ee) {
            error_log('Warning sending status change email: ' . $ee->getMessage());
        }

        // Limpiar buffer y enviar JSON
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Cotización actualizada correctamente',
            'cotizacion' => $cotizacion_datos
        ]);
    } else {
        ob_end_clean();
        throw new Exception('Error al actualizar la cotización');
    }

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
