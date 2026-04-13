<?php
/**
 * Proceso de Inicio de Sesión
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);

// No forzar redirección aquí ya que estamos en el proceso de login
define('IS_AUTH_PROCESS', true);

require_once '../config/init.php';
require_once '../models/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar datos requeridos
    if (empty($_POST['email']) || empty($_POST['password'])) {
        throw new Exception('El email y la contraseña son requeridos');
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Conectar a la base de datos
    $db = Database::getInstance()->getConnection();

    // Instanciar modelo
    $usuarioModel = new Usuario($db);

    // Autenticar
    $usuario = $usuarioModel->autenticar($email, $password);

    if ($usuario) {
        // Regenerar ID de sesión para prevenir Session Fixation
        session_regenerate_id(true);

        // Guardar datos en sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nombre'] = $usuario['nombre'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_rol'] = $usuario['rol'];
        $_SESSION['last_activity'] = time();

        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'redirect' => '/index.php' // Redirigir al dashboard
        ]);
    } else {
        throw new Exception('Credenciales incorrectas o cuenta inactiva');
    }

} catch (Exception $e) {
    error_log("Error en login_process.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
