<?php
session_start();
require_once '../config/init.php';

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

    // Buscar ítems por código o nombre (Productos con stock > 0, o Servicios/Licencias que son intangibles)
    $query = "SELECT id, codigo, COALESCE(tipo, 'producto') as tipo, nombre, precio, stock, stock_minimo
              FROM productos
              WHERE activo = 1 
                AND (tipo != 'producto' OR stock > 0) 
                AND (codigo LIKE :terminoCodigo OR nombre LIKE :terminoNombre)
              ORDER BY nombre
              LIMIT 15";

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
        'message' => 'Error al buscar ítems: ' . $e->getMessage()
    ]);
}
?>
