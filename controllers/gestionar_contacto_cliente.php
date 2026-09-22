<?php
$json_response = true;
require_once '../config/init.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        throw new Exception('Token de seguridad inválido');
    }

    $accion = $_POST['accion'] ?? 'crear';
    $clienteId = (int) ($_POST['cliente_id'] ?? 0);
    $contactoId = (int) ($_POST['contacto_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');

    if ($clienteId <= 0) {
        throw new Exception('Cliente inválido');
    }

    $db = Database::getInstance('development')->getConnection();
    $clienteModel = new Cliente($db);
    if (!$clienteModel->obtenerPorId($clienteId)) {
        throw new Exception('Cliente no encontrado');
    }

    $contactoModel = new ClienteContacto($db);

    if ($accion === 'eliminar') {
        if ($contactoId <= 0 || !$contactoModel->eliminar($contactoId, $clienteId)) {
            throw new Exception('No se pudo eliminar el contacto');
        }
        echo json_encode(['success' => true, 'message' => 'Contacto eliminado correctamente']);
        exit;
    }

    if ($nombre === '') {
        throw new Exception('El nombre del contacto es requerido');
    }
    if (mb_strlen($nombre) > 255) {
        throw new Exception('El nombre no puede tener más de 255 caracteres');
    }

    $email = trim($_POST['email'] ?? '');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido');
    }

    $datos = [
        'cliente_id' => $clienteId,
        'nombre' => $nombre,
        'cargo' => trim($_POST['cargo'] ?? ''),
        'email' => $email,
        'telefono' => trim($_POST['telefono'] ?? ''),
        'es_principal' => !empty($_POST['es_principal'])
    ];

    if ($accion === 'crear') {
        $resultado = $contactoModel->crear($datos);
        $mensaje = 'Contacto agregado correctamente';
    } elseif ($accion === 'actualizar' && $contactoId > 0) {
        $resultado = $contactoModel->actualizar($contactoId, $datos);
        $mensaje = 'Contacto actualizado correctamente';
    } else {
        throw new Exception('Acción inválida');
    }

    if (!$resultado) {
        throw new Exception('No se pudo completar la operación');
    }

    echo json_encode(['success' => true, 'message' => $mensaje]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
