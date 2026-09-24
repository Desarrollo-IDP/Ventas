<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Cliente.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar datos requeridos
    $required_fields = ['nombre', 'email', 'telefono', 'direccion'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    // Limpiar datos
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;

    // Validaciones
    if (strlen($nombre) > 255) {
        throw new Exception('El nombre no puede tener más de 255 caracteres');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Correo inválido');
    }

    if (!preg_match("/^\+?[\d\s]{10,15}$/", $telefono)) {
        throw new Exception('El número de teléfono no tiene un formato válido');
    }

    if (empty($direccion)) {
        throw new Exception('La dirección no puede estar vacía');
    } elseif (!preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ#.,\/\s-]+$/u", $direccion)) {
        throw new Exception('La dirección contiene caracteres no válidos');
    }

    // Conectar a la base de datos
    $database = Database::getInstance();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    // Crear instancia del modelo
    $clienteModel = new Cliente($db);

    // Datos a insertar
    $datos_cliente = [
        'nombre' => $nombre,
        'email' => $email,
        'telefono' => $telefono,
        'direccion' => $direccion,
        'activo' => $activo,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Crear cliente
    $cliente_id = $clienteModel->crear($datos_cliente);

    if ($cliente_id) {
        echo json_encode([
            'success' => true,
            'message' => 'Cliente creado correctamente',
            'cliente_id' => $cliente_id
        ]);
    } else {
        throw new Exception('Error al crear el cliente en la base de datos');
    }


} catch (Exception $e) {
    error_log("Error en crear_cliente.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
