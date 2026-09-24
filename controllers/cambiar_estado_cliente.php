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

    $cliente_id = $_POST['cliente_id'] ?? null;
    $activo = $_POST['activo'] ?? null;

    if (!$cliente_id || !isset($activo)) {
        throw new Exception('Datos incompletos');
    }

    $clienteModel = new Cliente($db);

    if ($clienteModel->cambiarEstado($cliente_id, $activo)) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado del cliente actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el estado del cliente');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
