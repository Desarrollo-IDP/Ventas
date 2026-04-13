<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $termino = $_GET['termino'] ?? '';

    if (strlen($termino) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Ingrese al menos 2 caracteres para buscar'
        ]);
        exit;
    }

    // Buscar productos por código o nombre (solo productos con stock > 0)
    $query = "SELECT id, codigo, nombre, precio, stock, stock_minimo
              FROM productos
              WHERE activo = 1 AND stock > 0 AND (codigo LIKE :terminoCodigo OR nombre LIKE :terminoNombre)
              ORDER BY nombre
              LIMIT 10";

    $stmt = $db->prepare($query);
    $like = '%' . $termino . '%';
    $stmt->bindValue(':terminoCodigo', $like, PDO::PARAM_STR);
    $stmt->bindValue(':terminoNombre', $like, PDO::PARAM_STR);
    $stmt->execute();

    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'productos' => $productos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar productos: ' . $e->getMessage()
    ]);
}
?>
