<?php
// Indicar que este script devuelve JSON para errores del sistema
global $json_response;
$json_response = true;

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

    // Buscar clientes por nombre o email
    $query = "SELECT id, nombre, email, telefono, direccion
              FROM clientes
              WHERE activo = 1 AND (nombre LIKE :termino OR email LIKE :termino OR telefono LIKE :termino OR direccion LIKE :termino)
              ORDER BY nombre
              LIMIT 10";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':termino', '%' . $termino . '%', PDO::PARAM_STR);
    $stmt->execute();

    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'clientes' => $clientes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar clientes: ' . $e->getMessage()
    ]); 
}
?>