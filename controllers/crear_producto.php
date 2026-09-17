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

    // Validar datos requeridos
    $required_fields = ['codigo', 'nombre', 'precio'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    // Obtener y limpiar datos
    $codigo = trim($_POST['codigo']);
    $tipo = trim($_POST['tipo'] ?? 'producto');
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;

    // Validar tipo de ítem
    if (!in_array($tipo, ['producto', 'servicio', 'licencia'])) {
        $tipo = 'producto';
    }

    // Validaciones
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

    // Verificar si el código ya existe
    $productoModel = new Producto($db);
    $producto_existente = $productoModel->obtenerPorCodigo($codigo);
    
    if ($producto_existente) {
        throw new Exception('El código ya está en uso por otro ítem');
    }

    // Preparar datos para insertar
    $datos_producto = [
        'codigo' => $codigo,
        'tipo' => $tipo,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'precio' => $precio,
        'stock' => $stock,
        'stock_minimo' => $stock_minimo,
        'activo' => $activo,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Crear producto
    $producto_id = $productoModel->crear($datos_producto);

    if ($producto_id) {
        // Registrar movimiento de stock inicial solo si es un producto físico y hay stock
        if ($tipo === 'producto' && $stock > 0) {
            try {
                require_once '../models/MovimientoStock.php';
                $movimientoModel = new MovimientoStock($db);
                
                $movimientoData = [
                    'producto_id' => $producto_id,
                    'tipo' => 'entrada',
                    'cantidad' => $stock,
                    'stock_anterior' => 0,
                    'stock_nuevo' => $stock,
                    'motivo' => 'Stock inicial',
                    'usuario_id' => $_SESSION['usuario_id'] ?? null
                ];

                $movimientoModel->crear($movimientoData);
            } catch (Exception $e) {
                error_log("Error al registrar movimiento inicial: " . $e->getMessage());
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Ítem creado correctamente',
            'producto_id' => $producto_id
        ]);
    } else {
        throw new Exception('Error al crear el ítem en la base de datos');
    }

} catch (Exception $e) {
    error_log("Error en crear_producto.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}