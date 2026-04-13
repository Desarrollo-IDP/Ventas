<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/email.php';

// Autoload de modelos
spl_autoload_register(function ($class_name) {
    $models_path = __DIR__ . '/../models/' . $class_name . '.php';
    if (file_exists($models_path)) {
        require_once $models_path;
    }
});
/**
 * Archivo de Inicialización del Sistema
 * Incluir este archivo al inicio de cada script
 */

// Flag global para determinar si la respuesta debe ser JSON
// Verificar requisitos del sistema
function check_system_requirements() {
    $errors = [];
    
    // Verificar versión de PHP
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $errors[] = "Se requiere PHP 7.4.0 o superior. Versión actual: " . PHP_VERSION;
    }
    
    // Verificar extensiones requeridas
    $required_extensions = [
        'pdo',
        'pdo_mysql',
        'mbstring',
        'json',
        'openssl',
        'session'
        // 'gd' // Temporalmente deshabilitado para desarrollo
    ];
    
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extensión requerida no cargada: {$ext}";
        }
    }
    
    // Verificar permisos de directorios
    $writable_dirs = [
        config('uploads.path'),
        config('cache.path'),
        config('logging.path'),
        config('reports.path')
    ];

    foreach ($writable_dirs as $dir) {
        if ($dir && !is_writable($dir) && !is_null($dir)) {
            $errors[] = "Directorio no escribible: {$dir}";
        }
    }
    
    return $errors;
}

// Función para manejar errores del sistema
function system_error($message, $is_requirements_error = false) {
    global $json_response;

    if ($json_response) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $is_requirements_error ? 'Error de configuración del sistema' : $message
        ]);
        exit;
    } else {
        if (is_development()) {
            die($message);
        } else {
            error_log($message);
            die("Error del sistema. Contacte al administrador.");
        }
    }
}

// Inicializar sistema
function initialize_system() {
    global $json_response;

    // Verificar requisitos
    $requirements_errors = check_system_requirements();
    if (!empty($requirements_errors)) {
        $error_message = "Errores de requisitos del sistema:<br>" . implode("<br>", $requirements_errors);
        system_error($error_message, true);
    }

    // Verificar conexión a base de datos (solo si no estamos en producción o si la BD existe)
    try {
        $db = Database::getInstance('development');
        $connection = $db->getConnection();
        // Probar la conexión con una consulta simple
        $stmt = $connection->query("SELECT 1");
        if (!$stmt) {
            throw new Exception("No se pudo ejecutar consulta de prueba");
        }
    } catch (Exception $e) {
        $error_message = "Error de conexión a la base de datos: " . $e->getMessage();
        system_error($error_message);
    }

    // Inicializar seguridad (solo si no hay errores)
    try {
        // Verificar que AppConfig esté disponible antes de usarlo
        if (class_exists('AppConfig')) {
            SecurityConfig::initialize();
        }
    } catch (Exception $e) {
        $error_message = "Error al inicializar seguridad: " . $e->getMessage();
        system_error($error_message);
    }

    // Log de inicio del sistema
    if (config('logging.enabled')) {
        error_log("Sistema iniciado - " . date('Y-m-d H:i:s'));
    }
}

// Ejecutar inicialización
initialize_system();

// Función de cierre para logging de fin de script
function shutdown_function() {
    if (config('logging.enabled')) {
        $execution_time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        error_log("Script finalizado - Tiempo de ejecución: " . round($execution_time, 3) . "s");
    }
}

register_shutdown_function('shutdown_function');
?>