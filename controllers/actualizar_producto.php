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
    $required_fields = ['producto_id', 'nombre', 'codigo'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    $producto_id = intval($_POST['producto_id']);
    $tipo = trim($_POST['tipo'] ?? 'producto');
    $nombre = trim($_POST['nombre']);
    $codigo = trim($_POST['codigo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 0;

    // Validar tipo de ítem
    if (!in_array($tipo, ['producto', 'servicio', 'licencia'])) {
        $tipo = 'producto';
    }

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

    if ($stock < 0) {
        $stock = 0;
    }

    if ($stock_minimo < 0) {
        $stock_minimo = 0;
    }

    if (strlen($descripcion) > 500) {
        throw new Exception('La descripción no puede tener más de 500 caracteres');
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
        throw new Exception('El código ya está en uso por otro ítem');
    }

    // Preparar datos para actualizar
    $datos_actualizar = [
        'tipo' => $tipo,
        'nombre' => $nombre,
        'codigo' => $codigo,
        'descripcion' => $descripcion,
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
            'message' => 'Ítem actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el ítem en la base de datos');
    }

} catch (Exception $e) {
    error_log("Error en actualizar_producto.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}