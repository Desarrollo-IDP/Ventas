<?php
require_once '../config/init.php';
require_once '../models/MovimientoStock.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $required_fields = ['producto_id', 'tipo_ajuste', 'cantidad'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    $producto_id = intval($_POST['producto_id']);
    $tipo_ajuste = $_POST['tipo_ajuste'];
    $cantidad = intval($_POST['cantidad']);
    $motivo = trim($_POST['motivo'] ?? '');

    if (!in_array($tipo_ajuste, ['entrada', 'salida', 'ajuste'])) {
        throw new Exception('Tipo de ajuste inválido');
    }

    if ($cantidad <= 0) {
        throw new Exception('La cantidad debe ser mayor a 0');
    }

    $database = Database::getInstance('development');
    $db = $database->getConnection();

    // Obtener producto actual
    $productoModel = new Producto($db);
    $producto = $productoModel->obtenerPorId($producto_id);
    
    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }

    $stock_anterior = $producto['stock'];
    $stock_nuevo = $stock_anterior;

    // Calcular nuevo stock según el tipo de ajuste
    switch ($tipo_ajuste) {
        case 'entrada':
            $stock_nuevo = $stock_anterior + $cantidad;
            break;
        case 'salida':
            if ($cantidad > $stock_anterior) {
                throw new Exception('No hay suficiente stock para esta salida');
            }
            $stock_nuevo = $stock_anterior - $cantidad;
            break;
        case 'ajuste':
            $stock_nuevo = $cantidad;
            if ($stock_nuevo < 0) {
                throw new Exception('El stock no puede ser negativo');
            }
            $cantidad = abs($stock_nuevo - $stock_anterior);
            // Para ajuste manual, determinamos si es entrada o salida
            $tipo_ajuste = $stock_nuevo > $stock_anterior ? 'entrada' : 'salida';
            break;
    }

    // Validar que el stock final no sea negativo
    if ($stock_nuevo < 0) {
        $stock_nuevo = 0;
    }

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Actualizar stock del producto
        $actualizado = $productoModel->actualizarStock($producto_id, $stock_nuevo);
        
        if (!$actualizado) {
            throw new Exception('Error al actualizar el stock');
        }

        // Registrar movimiento
        $movimientoModel = new MovimientoStock($db);
        $movimientoData = [
            'producto_id' => $producto_id,
            'tipo' => $tipo_ajuste,
            'cantidad' => $cantidad,
            'stock_anterior' => $stock_anterior,
            'stock_nuevo' => $stock_nuevo,
            'motivo' => $motivo,
            'usuario_id' => null // No usar tabla usuarios por ahora
        ];

        $registrado = $movimientoModel->crear($movimientoData);
        
        if (!$registrado) {
            throw new Exception('Error al registrar el movimiento');
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Stock ajustado correctamente',
            'stock_anterior' => $stock_anterior,
            'stock_nuevo' => $stock_nuevo
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en ajustar_stock.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}