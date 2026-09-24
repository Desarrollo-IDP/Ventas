<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Seguimiento.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id = intval($_POST['seguimiento_id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID de seguimiento inválido');
    }

    $db = Database::getInstance()->getConnection();
    $seguimientoModel = new Seguimiento($db);

    if ($seguimientoModel->eliminar($id)) {
        echo json_encode([
            'success' => true,
            'message' => 'Seguimiento eliminado correctamente'
        ]);
    } else {
        throw new Exception('No se pudo eliminar el seguimiento');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
