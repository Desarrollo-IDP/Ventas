<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Prospecto.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id = intval($_POST['prospecto_id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID de prospecto inválido');
    }

    $db = Database::getInstance()->getConnection();
    $prospectoModel = new Prospecto($db);

    if ($prospectoModel->eliminar($id)) {
        echo json_encode([
            'success' => true,
            'message' => 'Prospecto eliminado correctamente'
        ]);
    } else {
        throw new Exception('No se pudo eliminar el prospecto');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
