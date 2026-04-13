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
    $required_fields = ['cliente_id', 'nombre', 'email', 'telefono', 'direccion', 'activo'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    $cliente_id = intval($_POST['cliente_id']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = floatval($_POST['direccion']);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 0;

    // Validaciones adicionales
    if (strlen($nombre) > 255) {
        throw new Exception('El nombre no puede tener más de 255 caracteres');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Correo inválido.";
    }

    if (!preg_match("/^\+?[\d\s]{10,15}$/", $telefono)) {
        echo "El número de teléfono no tiene un formato válido.";
    } else {
        echo "Teléfono válido.";
    }

    if (empty($direccion)) {
        echo "La dirección no puede estar vacía.";
    }elseif (!preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ#.,\s-]+$/", $direccion)) {
        echo "La dirección contiene caracteres no válidos.";
    } else {
        echo "Dirección válida.";
    }

    // Conectar a la base de datos
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    // Verificar si el cliente existe
    $clienteModel = new Cliente($db);
    $cliente_existente = $clienteModel->obtenerPorId($cliente_id);
    
    if (!$producto_existente) {
        throw new Exception('Cliente no encontrado');
    }

    // Preparar datos para actualizar
    $datos_actualizar = [
        'nombre' => $nombre,
        'email' => $email,
        'telefono' => $telefono,
        'direccion' => $direccion,
        'activo' => $activo,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Actualizar cliente
    $actualizado = $clienteModel->actualizar($cliente_id, $datos_actualizar);

    if ($actualizado) {
        echo json_encode([
            'success' => true,
            'message' => 'Cliente actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el cliente en la base de datos');
    }

} catch (Exception $e) {
    error_log("Error en actualizar_cliente.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}