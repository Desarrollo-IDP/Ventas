<?php
session_start();
require_once '../config/database.php';
require_once '../models/Producto.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $filtros = $_GET ?? [];

    $productoModel = new Producto($db);
    $productos = $productoModel->listarConFiltros($filtros);

    if (!$productos) {
        throw new Exception('No se pudieron obtener los productos.');
    }

    if (ob_get_length()) ob_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=productos_' . date('Y-m-d_H-i-s') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'Código', 'Nombre', 'Descripción', 'Stock', 'Stock Mínimo',
        'Estado', 'Fecha Creación', 'Fecha Modificación'
    ]);

    while ($row = $productos->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['codigo'],
            $row['nombre'],
            $row['descripcion'] ?? '',
            $row['stock'],
            $row['stock_minimo'],
            $row['activo'] ? 'Activo' : 'Inactivo',
            $row['created_at'],
            $row['updated_at']
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error al exportar productos: ' . $e->getMessage();
}
