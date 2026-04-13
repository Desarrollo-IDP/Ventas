<?php
// Indicar que este script devuelve JSON para errores del sistema
global $json_response;
$json_response = true;

require_once '../config/init.php';

header('Content-Type: application/json');

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Token de seguridad inválido');
    }

    // Validar datos requeridos
    $required_fields = ['producto_id', 'nombre', 'codigo', 'precio', 'stock', 'stock_minimo'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    $producto_id = intval($_POST['producto_id']);
    $nombre = trim($_POST['nombre']);
    $codigo = trim($_POST['codigo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $stock_minimo = intval($_POST['stock_minimo']);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 0;

    // Validaciones adicionales
    if (strlen($nombre) > 255) {
        throw new Exception('El nombre no puede tener más de 255 caracteres');
    }

    if (strlen($codigo) > 50) {
        throw new Exception('El código no puede tener más de 50 caracteres');
    }

    if (!preg_match('/^[A-Za-z0-9\-_]+$/', $codigo)) {
        throw new Exception('El código solo puede contener letras, números, guiones y guiones bajos');
    }

    if ($precio <= 0) {
        throw new Exception('El precio debe ser mayor a 0');
    }

    if ($stock < 0) {
        throw new Exception('El stock no puede ser negativo');
    }

    if ($stock_minimo < 0) {
        throw new Exception('El stock mínimo no puede ser negativo');
    }

    if (strlen($descripcion) > 500) {
        throw new Exception('La descripción no puede tener más de 500 caracteres');
    }

    // Forzar stock a 0 si es negativo (medida defensiva adicional)
    if ($stock < 0) {
        $stock = 0;
    }

    if ($stock_minimo < 0) {
        $stock_minimo = 0;
    }

    // Conectar a la base de datos
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    // Verificar si el producto existe
    $productoModel = new Producto($db);
    $producto_existente = $productoModel->obtenerPorId($producto_id);
    
    if (!$producto_existente) {
        throw new Exception('Producto no encontrado');
    }

    // Verificar si el código ya existe en otro producto
    $producto_con_codigo = $productoModel->obtenerPorCodigo($codigo);
    if ($producto_con_codigo && $producto_con_codigo['id'] != $producto_id) {
        throw new Exception('El código ya está en uso por otro producto');
    }

    // Preparar datos para actualizar
    $datos_actualizar = [
        'nombre' => $nombre,
        'codigo' => $codigo,
        'descripcion' => $descripcion,
        'precio' => $precio,
        'stock' => $stock,
        'stock_minimo' => $stock_minimo,
        'activo' => $activo,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Actualizar producto
    $actualizado = $productoModel->actualizar($producto_id, $datos_actualizar);

    if ($actualizado) {
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el producto en la base de datos');
    }

} catch (Exception $e) {
    error_log("Error en actualizar_producto.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}