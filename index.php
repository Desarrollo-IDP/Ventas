<?php
/**
 * Archivo Principal del Sistema de Punto de Venta
 * Punto de entrada de la aplicación
 */

// Incluir configuración inicial
require_once 'config/init.php';

// Exigir autenticación antes de acceder al dashboard
if (!isset($_SESSION['user_id'])) {
    header('Location: views/auth/login.php');
    exit();
}

// Redirigir al dashboard
header('Location: views/dashboard/index.php');
exit();
?>
