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
        if (!array_key_exists($field, $_POST) || ($field !== 'activo' && trim((string) $_POST[$field]) === '')) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    $cliente_id = intval($_POST['cliente_id']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion']);
    $activo = (int) $_POST['activo'];

    if (!in_array($activo, [0, 1], true)) {
        throw new Exception('El estado del cliente no es válido');
    }

    // Validaciones adicionales
    if (strlen($nombre) > 255) {
        throw new Exception('El nombre no puede tener más de 255 caracteres');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido');
    }

    if (!preg_match("/^\+?[\d\s-]{10,20}$/", $telefono)) {
        throw new Exception('El número de teléfono no tiene un formato válido');
    }

    if (!preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ#.,\s-]+$/u", $direccion)) {
        throw new Exception('La dirección contiene caracteres no válidos');
    }

    // Conectar a la base de datos
    $database = Database::getInstance('development');
    $db = $database->getConnection();

    // Verificar si el cliente existe
    $clienteModel = new Cliente($db);
    $cliente_existente = $clienteModel->obtenerPorId($cliente_id);
    
    if (!$cliente_existente) {
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