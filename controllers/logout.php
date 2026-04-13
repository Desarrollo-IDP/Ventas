<?php
/**
 * Proceso de Cierre de Sesión
 */
require_once '../config/constants.php';

// Iniciar sesión si no lo está (aunque constants.php ya lo hace)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: /views/auth/login.php?logout=success");
exit;
?>
