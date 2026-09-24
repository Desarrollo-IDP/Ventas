<?php
// Indicar que este script devuelve JSON para errores del sistema
global $json_response;
$json_response = true;

require_once '../config/init.php';

// Verificar si hay errores de PHP
error_reporting(0); // Desactivar reporting para evitar output no deseado
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar datos requeridos
    if (empty($_POST['cliente_id'])) {
        throw new Exception('ID de cliente no proporcionado');
    }

    $cliente_id = intval($_POST['cliente_id']);

    if ($cliente_id <= 0) {
        throw new Exception('ID de cliente inválido');
    }

    // Conectar a la base de datos
    $database = Database::getInstance();
    $db = $database->getConnection();

    // Verificar si el cliente existe
    $clienteModel = new Cliente($db);
    $cliente = $clienteModel->obtenerPorId($cliente_id);
    
    if (!$cliente) {
        throw new Exception('Cliente no encontrado');
    }

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Eliminar el cliente directamente
        $eliminado = $clienteModel->eliminar($cliente_id);
        
        if (!$eliminado) {
            throw new Exception('Error al eliminar el cliente de la base de datos');
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Cliente eliminado correctamente'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Asegurarse de que solo se envía JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}