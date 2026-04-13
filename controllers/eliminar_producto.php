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
    if (empty($_POST['producto_id'])) {
        throw new Exception('ID de producto no proporcionado');
    }

    $producto_id = intval($_POST['producto_id']);

    if ($producto_id <= 0) {
        throw new Exception('ID de producto inválido');
    }

    // Conectar a la base de datos
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    // Verificar si el producto existe
    $productoModel = new Producto($db);
    $producto = $productoModel->obtenerPorId($producto_id);
    
    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Primero eliminar movimientos de stock relacionados (si existe la tabla)
        try {
            // Verificar si la tabla existe antes de intentar eliminar
            $check_table = $db->query("SHOW TABLES LIKE 'movimientos_stock'");
            if ($check_table->rowCount() > 0) {
                $query_movimientos = "DELETE FROM movimientos_stock WHERE producto_id = ?";
                $stmt_movimientos = $db->prepare($query_movimientos);
                $stmt_movimientos->execute([$producto_id]);
            }
        } catch (Exception $e) {
            // Si hay error, continuar sin problema
            error_log("Info: No se pudieron eliminar movimientos - " . $e->getMessage());
        }

        // Eliminar el producto
        $eliminado = $productoModel->eliminar($producto_id);
        
        if (!$eliminado) {
            throw new Exception('Error al eliminar el producto de la base de datos');
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
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