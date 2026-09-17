<?php
session_start();
require_once '../config/init.php';
require_once '../models/Cotizacion.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $cotizacion_id = $_POST['cotizacion_id'] ?? null;

    if (!$cotizacion_id) {
        throw new Exception('ID de cotización requerido');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    $cotizacion_model = new Cotizacion($db);
    
    // Obtener la cotización original
    $cotizacion_original = $cotizacion_model->obtenerPorId($cotizacion_id);
    if (!$cotizacion_original) {
        throw new Exception('Cotización no encontrada');
    }

    // Obtener detalles
    $detalles = $cotizacion_model->obtenerDetalles($cotizacion_id);

    // Crear nueva cotización con los mismos datos
    $datos_nueva = [
        'cliente_id' => $cotizacion_original['cliente_id'],
        'fecha_vencimiento' => date('Y-m-d', strtotime('+15 days')),
        'subtotal' => $cotizacion_original['subtotal'],
        'iva' => $cotizacion_original['iva'],
        'total' => $cotizacion_original['total'],
        'notas' => $cotizacion_original['notas'],
        'detalles' => array_map(function($d) {
            return [
                'producto_id' => $d['producto_id'],
                'cantidad' => $d['cantidad'],
                'precio_unitario' => $d['precio_unitario'],
                'importe' => $d['importe']
            ];
        }, $detalles)
    ];

    $nueva_cotizacion_id = $cotizacion_model->crear($datos_nueva);

    echo json_encode([
        'success' => true,
        'message' => 'Cotización duplicada correctamente',
        'nueva_cotizacion_id' => $nueva_cotizacion_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    error_log("Error al duplicar cotización: " . $e->getMessage());
}
?>
