<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Seguimiento.php';
require_once '../models/Prospecto.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (empty($_POST['resumen'])) {
        throw new Exception('El resumen o nota de la llamada/interacción es obligatorio');
    }

    $vendedor_id = !empty($_POST['vendedor_id']) ? intval($_POST['vendedor_id']) : ($_SESSION['user_id'] ?? 1);

    $db = Database::getInstance('development')->getConnection();
    $seguimientoModel = new Seguimiento($db);

    $datos = [
        'tipo' => $_POST['tipo'] ?? 'llamada',
        'cliente_id' => !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null,
        'prospecto_id' => !empty($_POST['prospecto_id']) ? intval($_POST['prospecto_id']) : null,
        'vendedor_id' => $vendedor_id,
        'cotizacion_id' => !empty($_POST['cotizacion_id']) ? intval($_POST['cotizacion_id']) : null,
        'fecha_llamada' => !empty($_POST['fecha_llamada']) ? $_POST['fecha_llamada'] : date('Y-m-d H:i:s'),
        'duracion_minutos' => !empty($_POST['duracion_minutos']) ? intval($_POST['duracion_minutos']) : 0,
        'resultado' => $_POST['resultado'] ?? 'exitoso',
        'resumen' => trim($_POST['resumen']),
        'proxima_accion' => trim($_POST['proxima_accion'] ?? ''),
        'fecha_proxima_accion' => !empty($_POST['fecha_proxima_accion']) ? $_POST['fecha_proxima_accion'] : null,
        'productos_presentados' => !empty($_POST['productos_presentados']) ? trim($_POST['productos_presentados']) : null
    ];

    $segId = $seguimientoModel->crear($datos);

    if ($segId) {
        // Los resultados terminales actualizan la etapa correspondiente del prospecto.
        if (!empty($datos['prospecto_id']) && in_array($datos['resultado'], ['venta_cerrada', 'rechazado'], true)) {
            $pModel = new Prospecto($db);
            $pModel->cambiarEstado($datos['prospecto_id'], $datos['resultado'] === 'venta_cerrada' ? 'ganada' : 'no_viable');
        } elseif (!empty($datos['prospecto_id'])) {
            $pModel = new Prospecto($db);
            $prospecto = $pModel->obtenerPorId($datos['prospecto_id']);
            if (($prospecto['estado'] ?? null) === 'lead') {
                $pModel->cambiarEstado($datos['prospecto_id'], 'prospecto', 'Primera llamada registrada');
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Seguimiento registrado exitosamente',
            'seguimiento_id' => $segId
        ]);
    } else {
        throw new Exception('No se pudo guardar el seguimiento');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
