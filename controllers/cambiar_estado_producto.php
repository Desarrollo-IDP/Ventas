<?php
session_start();
require_once '../config/init.php';
require_once '../models/Producto.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $producto_id = $_POST['producto_id'] ?? null;
    $activo = $_POST['activo'] ?? null;

    if (!$producto_id || !isset($activo)) {
        throw new Exception('Datos incompletos');
    }

    $productoModel = new Producto($db);

    if ($productoModel->cambiarEstado($producto_id, $activo)) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado del producto actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el estado del producto');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
