<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/init.php';
require_once '../models/Usuario.php';

SecurityService::requiredAuth();
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        throw new Exception('La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.');
    }

    $passwordActual = $_POST['password_actual'] ?? '';
    $passwordNueva = $_POST['password_nueva'] ?? '';
    $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

    if ($passwordActual === '' || $passwordNueva === '' || $passwordConfirmacion === '') {
        throw new Exception('Todos los campos de contraseña son obligatorios.');
    }

    if (strlen($passwordNueva) < 8) {
        throw new Exception('La nueva contraseña debe tener al menos 8 caracteres.');
    }

    if ($passwordNueva !== $passwordConfirmacion) {
        throw new Exception('La confirmación no coincide con la nueva contraseña.');
    }

    $db = Database::getInstance()->getConnection();
    $usuarioModel = new Usuario($db);
    $id = (int) $_SESSION['user_id'];

    if (!$usuarioModel->cambiarPassword($id, $passwordActual, $passwordNueva)) {
        throw new Exception('La contraseña actual no es correcta.');
    }

    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
