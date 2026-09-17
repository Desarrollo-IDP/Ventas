<?php
session_start();
require_once '../config/init.php';
require_once '../models/Cotizacion.php';

header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $cotizacion_id = $_POST['cotizacion_id'] ?? null;
    if (!$cotizacion_id) {
        throw new Exception('ID de cotización requerido');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    $cotizacionModel = new Cotizacion($db);
    $cotizacion = $cotizacionModel->obtenerPorId($cotizacion_id);
    if (!$cotizacion) {
        throw new Exception('Cotización no encontrada');
    }

    // Solo permitir edición si está en estado 'pendiente'
    if (($cotizacion['estatus'] ?? '') !== 'pendiente') {
        throw new Exception('No se puede modificar la cotización en su estado actual.');
    }

    // Obtener datos enviados (FormData)
    $cliente_id = $_POST['cliente_id'] ?? null;
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    $notas = $_POST['notas'] ?? '';
    $detalles = json_decode($_POST['detalles'] ?? '[]', true);

    if (!$cliente_id) {
        throw new Exception('Cliente requerido');
    }

    if (empty($detalles)) {
        throw new Exception('Debe agregar al menos un producto');
    }

    // Calcular totales
    $subtotal = 0;
    foreach ($detalles as $d) {
        $importe = ($d['cantidad'] ?? 0) * ($d['precio_unitario'] ?? 0);
        $subtotal += $importe;
    }
    $iva = $subtotal * 0.16;
    $total = $subtotal + $iva;

    // Actualizar tabla cotizaciones
    $query = "UPDATE cotizaciones SET cliente_id = :cliente_id, fecha_vencimiento = :fecha_vencimiento,
                subtotal = :subtotal, iva = :iva, total = :total, notas = :notas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cliente_id', $cliente_id);
    $stmt->bindParam(':fecha_vencimiento', $fecha_vencimiento);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':iva', $iva);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':notas', $notas);
    $stmt->bindParam(':id', $cotizacion_id);

    $db->beginTransaction();
    if (!$stmt->execute()) {
        $db->rollBack();
        throw new Exception('Error al actualizar la cotización');
    }

    // Reemplazar detalles: eliminar existentes e insertar nuevos
    $del = $db->prepare('DELETE FROM cotizacion_detalles WHERE cotizacion_id = :id');
    $del->bindParam(':id', $cotizacion_id);
    $del->execute();

    $ins = $db->prepare('INSERT INTO cotizacion_detalles SET cotizacion_id = :cotizacion_id, producto_id = :producto_id, cantidad = :cantidad, precio_unitario = :precio_unitario, importe = :importe');
    foreach ($detalles as $d) {
        $importe = ($d['cantidad'] ?? 0) * ($d['precio_unitario'] ?? 0);
        $ins->bindParam(':cotizacion_id', $cotizacion_id);
        $ins->bindParam(':producto_id', $d['producto_id']);
        $ins->bindParam(':cantidad', $d['cantidad']);
        $ins->bindParam(':precio_unitario', $d['precio_unitario']);
        $ins->bindParam(':importe', $importe);
        if (!$ins->execute()) {
            $db->rollBack();
            throw new Exception('Error al insertar detalles');
        }
    }

    $db->commit();

    // Limpiar buffer y responder
    $buffer = ob_get_clean();
    if (!empty($buffer)) error_log('Output before JSON in actualizar_cotizacion: ' . $buffer);

    echo json_encode([
        'success' => true,
        'message' => 'Cotización actualizada correctamente',
        'cotizacion_id' => $cotizacion_id
    ]);

} catch (Exception $e) {
    $buffer = ob_get_clean();
    if (!empty($buffer)) error_log('Output on exception in actualizar_cotizacion: ' . $buffer);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log('Error actualizar_cotizacion: ' . $e->getMessage());
}

?>
