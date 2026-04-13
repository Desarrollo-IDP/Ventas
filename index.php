<?php
/**
 * Archivo Principal del Sistema de Punto de Venta
 * Punto de entrada de la aplicación
 */

// Incluir configuración inicial
require_once 'config/init.php';

// Verificar si hay una sesión activa
session_start();

// Si no hay usuario logueado, redirigir al login (por ahora directo al dashboard)
if (!isset($_SESSION['usuario_id'])) {
    // Por simplicidad, creamos una sesión temporal para desarrollo
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nombre'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@sistema.com';
}

// Redirigir al dashboard
header('Location: views/dashboard/index.php');
exit();
?>
