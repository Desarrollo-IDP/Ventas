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

    $datos = [];
    $campos = ['nombre', 'empresa', 'email', 'telefono', 'cargo_contacto', 'origen', 'estado', 'vendedor_id', 'notas', 'fecha_primer_contacto'];
    foreach ($campos as $campo) {
        if (isset($_POST[$campo])) {
            $datos[$campo] = $_POST[$campo];
        }
    }

    $clienteId = null;
    $convertirACliente = ($datos['estado'] ?? null) === 'ganada';
    if ($convertirACliente) {
        unset($datos['estado']);
    }

    if (!empty($datos) && !$prospectoModel->actualizar($id, $datos)) {
        throw new Exception('No se pudieron guardar los cambios del prospecto');
    }

    if ($convertirACliente) {
        $clienteId = $prospectoModel->convertirACliente($id);
    }

    if ($convertirACliente && $clienteId) {
        echo json_encode([
            'success' => true,
            'message' => 'Prospecto convertido a cliente correctamente',
            'cliente_id' => $clienteId
        ]);
    } elseif (!$convertirACliente && !empty($datos)) {
        echo json_encode([
            'success' => true,
            'message' => 'Prospecto actualizado correctamente'
        ]);
    } else {
        throw new Exception('No se realizaron cambios en el prospecto');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
