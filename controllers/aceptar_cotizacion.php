<?php
session_start();
require_once '../config/database.php';
require_once '../models/Cotizacion.php';
require_once '../models/Notificacion.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();

        $cotizacion_id = $_POST['cotizacion_id'] ?? null;
        
        if(!$cotizacion_id) {
            throw new Exception('ID de cotización requerido');
        }

        $cotizacion = new Cotizacion($db);
        
        // Verificar que la cotización existe y está pendiente
        $cotizacion_data = $cotizacion->obtenerPorId($cotizacion_id);
        if(!$cotizacion_data) {
            throw new Exception('Cotización no encontrada');
        }
        
        if($cotizacion_data['estatus'] != 'pendiente') {
            throw new Exception('La cotización ya fue procesada');
        }

        // Cambiar estatus a aceptada
        if($cotizacion->cambiarEstatus($cotizacion_id, 'aceptada')) {
            // Enviar notificación de aceptación
            $notificacion = new Notificacion($db);
            $notificacion->enviarEmailCotizacion($cotizacion_id, 'aceptacion');
            
            echo json_encode([
                'success' => true,
                'message' => 'Cotización aceptada y stock reservado'
            ]);
        } else {
            throw new Exception('Error al cambiar el estatus');
        }
        
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>