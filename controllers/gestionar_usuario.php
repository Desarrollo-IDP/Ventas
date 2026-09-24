<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Usuario.php';

SecurityService::requiredRole('admin');

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $db = Database::getInstance()->getConnection();
    $usuarioModel = new Usuario($db);

    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'vendedor';
    $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;

    if (empty($nombre) || empty($email)) {
        throw new Exception('El nombre y el correo son obligatorios');
    }

    if ($id > 0) {
        // Actualizar
        $datos = [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol,
            'estado' => $estado
        ];
        if (!empty($password)) {
            $datos['password'] = $password;
        }

        if ($usuarioModel->actualizar($id, $datos)) {
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } else {
            throw new Exception('No se realizaron cambios');
        }
    } else {
        // Crear
        if (empty($password)) {
            throw new Exception('La contraseña es obligatoria para un nuevo usuario');
        }

        $datos = [
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'rol' => $rol,
            'estado' => $estado
        ];

        $uId = $usuarioModel->crear($datos);
        if ($uId) {
            echo json_encode(['success' => true, 'message' => 'Usuario registrado con éxito', 'usuario_id' => $uId]);
        } else {
            throw new Exception('No se pudo guardar el usuario');
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
