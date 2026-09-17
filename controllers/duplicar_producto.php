<?php
session_start();
require_once '../config/init.php';
require_once '../models/Producto.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    $producto_id = $_POST['producto_id'] ?? null;

    if (!$producto_id) {
        throw new Exception('ID de producto requerido');
    }

    $productoModel = new Producto($db);

    $nuevo_id = $productoModel->duplicar($producto_id);

    if ($nuevo_id) {
        echo json_encode([
            'success' => true,
            'message' => 'Producto duplicado correctamente',
            'nuevo_producto_id' => $nuevo_id
        ]);
    } else {
        throw new Exception('Error al duplicar el producto');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
