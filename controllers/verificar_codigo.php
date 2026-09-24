<?php
require_once '../config/init.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['codigo']) || empty($_GET['codigo'])) {
        throw new Exception('Código no proporcionado');
    }

    $codigo = trim($_GET['codigo']);

    $database = Database::getInstance();
    $db = $database->getConnection();

    $productoModel = new Producto($db);
    $producto_existente = $productoModel->obtenerPorCodigo($codigo);

    echo json_encode([
        'disponible' => !$producto_existente,
        'codigo' => $codigo
    ]);

} catch (Exception $e) {
    echo json_encode([
        'disponible' => false,
        'error' => $e->getMessage()
    ]);
}