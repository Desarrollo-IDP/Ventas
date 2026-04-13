<?php
/**
 * Constantes del Sistema - Punto de Venta
 */

// ===== CONSTANTES DE LA APLICACIÓN =====
define('APP_NAME', 'Sistema Punto de Venta');
define('APP_VERSION', '1.0.0');
define('APP_BUILD', '2023121501');
define('APP_AUTHOR', 'Tu Empresa');
define('APP_URL', 'http://13.0.0.49:1993');
define('APP_ROOT', dirname(__DIR__));

// ===== CONSTANTES DE ENTORNO =====
define('ENV_DEVELOPMENT', 'development');
define('ENV_PRODUCTION', 'production');
define('ENV_TESTING', 'testing');

// Determinar entorno actual
if (!defined('APP_ENVIRONMENT')) {
    // Verificar variable de entorno del sistema
    $env_var = getenv('APP_ENV');
    
    if ($env_var && in_array($env_var, [ENV_DEVELOPMENT, ENV_TESTING, ENV_PRODUCTION])) {
        define('APP_ENVIRONMENT', $env_var);
    } else {
        // Detectar por hostname (XAMPP siempre es localhost)
        $hostname = gethostname();
        if (strpos(strtolower($hostname), 'localhost') !== false || 
            strpos(strtolower($hostname), 'local') !== false || 
            strpos(strtolower($hostname), 'dev') !== false || 
            strpos(strtolower($hostname), 'sti') !== false ||
            strpos(strtolower($_SERVER['HTTP_HOST'] ?? ''), 'localhost') !== false ||
            strpos(strtolower($_SERVER['HTTP_HOST'] ?? ''), '127.0.0.1') !== false) {
            define('APP_ENVIRONMENT', ENV_DEVELOPMENT);
        } elseif (strpos(strtolower($hostname), 'test') !== false) {
            define('APP_ENVIRONMENT', ENV_TESTING);
        } else {
            // Por defecto, usar desarrollo si no hay indicios claros
            define('APP_ENVIRONMENT', ENV_DEVELOPMENT);
        }
    }
}

// ===== CONSTANTES DE BASE DE DATOS =====
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');
define('DB_TIMESTAMP_FORMAT', 'Y-m-d H:i:s');
define('DB_DATE_FORMAT', 'Y-m-d');

// ===== CONSTANTES DE FECHAS Y HORAS =====
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('TIMEZONE', 'America/Mexico_City');

// ===== CONSTANTES DE FORMATO =====
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'MXN');
define('DECIMAL_SEPARATOR', '.');
define('THOUSANDS_SEPARATOR', ',');
define('DECIMAL_PLACES', 2);

// ===== CONSTANTES DE IMPUESTOS (México) =====
define('IVA_RATE', 0.16);
define('IEPS_RATE', 0.00);
define('ISR_RATE', 0.00);

// ===== CONSTANTES DE INVENTARIO =====
define('LOW_STOCK_THRESHOLD', 5);
define('CRITICAL_STOCK_THRESHOLD', 2);
define('MAX_STOCK_QUANTITY', 9999);
define('ALLOW_NEGATIVE_STOCK', false);

// ===== CONSTANTES DE COTIZACIONES =====
define('QUOTE_EXPIRY_DAYS', 15);
define('QUOTE_REMINDER_DAYS', 3);
define('QUOTE_NUMBER_PREFIX', 'COT');
define('QUOTE_MAX_ITEMS', 50);

// ===== CONSTANTES DE CLIENTES =====
define('CLIENT_CODE_PREFIX', 'CLI');
define('CLIENT_CREDIT_LIMIT_DEFAULT', 0);
define('CLIENT_MAX_CREDIT_LIMIT', 1000000);

// ===== CONSTANTES DE USUARIOS =====
define('USER_MIN_PASSWORD_LENGTH', 8);
define('USER_MAX_LOGIN_ATTEMPTS', 5);
define('USER_LOCKOUT_DURATION', 900); // 15 minutos en segundos
define('USER_SESSION_TIMEOUT', 1800); // 30 minutos en segundos

// ===== CONSTANTES DE ARCHIVOS =====
define('MAX_FILE_SIZE', 5242880); // 5MB en bytes
define('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx');
define('UPLOAD_PATH', APP_ROOT . '/uploads/');
define('BACKUP_PATH', APP_ROOT . '/backups/');

// ===== CONSTANTES DE SEGURIDAD =====
define('ENCRYPTION_KEY', 'your-secure-encryption-key-here');
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hora

// ===== CONSTANTES DE PAGINACIÓN =====
define('ITEMS_PER_PAGE', 25);
define('MAX_PAGINATION_LINKS', 5);

// ===== CONSTANTES DE ESTADOS =====
// Estados de cotizaciones
define('QUOTE_STATUS_PENDING', 'pendiente');
define('QUOTE_STATUS_ACCEPTED', 'aceptada');
define('QUOTE_STATUS_REJECTED', 'rechazada');
define('QUOTE_STATUS_EXPIRED', 'expirada');

// Estados de productos
define('PRODUCT_STATUS_ACTIVE', 1);
define('PRODUCT_STATUS_INACTIVE', 0);

// Estados de clientes
define('CLIENT_STATUS_ACTIVE', 1);
define('CLIENT_STATUS_INACTIVE', 0);

// Tipos de cliente
define('CLIENT_TYPE_INDIVIDUAL', 'Individual');
define('CLIENT_TYPE_COMPANY', 'Empresa');

// ===== CONSTANTES DE MENSAJES =====
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

// ===== CONSTANTES DE ROLES =====
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_SELLER', 'seller');
define('ROLE_VIEWER', 'viewer');

// ===== CONSTANTES DE API =====
define('API_RATE_LIMIT', 100); // requests por hora
define('API_TOKEN_LIFETIME', 86400); // 24 horas en segundos

// ===== CONSTANTES DE REPORTES =====
define('REPORT_DEFAULT_FORMAT', 'pdf');
define('REPORT_RETENTION_DAYS', 365);

// ===== CONSTANTES DE EMAIL =====
define('EMAIL_FROM_ADDRESS', 'sistema@miempresa.com');
define('EMAIL_FROM_NAME', 'Sistema Punto de Venta');
define('EMAIL_TEST_MODE', APP_ENVIRONMENT !== ENV_PRODUCTION);

// ===== CONSTANTES DE LOGGING =====
define('LOG_LEVEL_DEBUG', 100);
define('LOG_LEVEL_INFO', 200);
define('LOG_LEVEL_WARNING', 300);
define('LOG_LEVEL_ERROR', 400);
define('LOG_LEVEL_CRITICAL', 500);

define('LOG_PATH', APP_ROOT . '/logs/');
define('LOG_MAX_FILES', 30);

// ===== CONSTANTES DE CACHE =====
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hora en segundos
define('CACHE_PATH', APP_ROOT . '/cache/');

// ===== CONSTANTES DE VALIDACIÓN =====
define('VALIDATION_REQUIRED', 'required');
define('VALIDATION_EMAIL', 'email');
define('VALIDATION_NUMERIC', 'numeric');
define('VALIDATION_INTEGER', 'integer');
define('VALIDATION_DATE', 'date');
define('VALIDATION_MIN_LENGTH', 'min_length');
define('VALIDATION_MAX_LENGTH', 'max_length');

// ===== CONSTANTES DE MÓDULOS =====
define('MODULE_QUOTES', 'quotes');
define('MODULE_PRODUCTS', 'products');
define('MODULE_CLIENTS', 'clients');
define('MODULE_USERS', 'users');
define('MODULE_REPORTS', 'reports');
define('MODULE_SETTINGS', 'settings');

// ===== FUNCIONES GLOBALES BASADAS EN CONSTANTES =====
if (!function_exists('format_currency')) {
    function format_currency($amount) {
        return CURRENCY_SYMBOL . number_format(
            $amount, 
            DECIMAL_PLACES, 
            DECIMAL_SEPARATOR, 
            THOUSANDS_SEPARATOR
        );
    }
}

if (!function_exists('calculate_iva')) {
    function calculate_iva($amount) {
        return $amount * IVA_RATE;
    }
}

if (!function_exists('get_quote_statuses')) {
    function get_quote_statuses() {
        return [
            QUOTE_STATUS_PENDING => 'Pendiente',
            QUOTE_STATUS_ACCEPTED => 'Aceptada',
            QUOTE_STATUS_REJECTED => 'Rechazada',
            QUOTE_STATUS_EXPIRED => 'Expirada'
        ];
    }
}

if (!function_exists('get_client_types')) {
    function get_client_types() {
        return [
            CLIENT_TYPE_INDIVIDUAL => 'Individual',
            CLIENT_TYPE_COMPANY => 'Empresa'
        ];
    }
}

if (!function_exists('get_user_roles')) {
    function get_user_roles() {
        return [
            ROLE_ADMIN => 'Administrador',
            ROLE_MANAGER => 'Gerente',
            ROLE_SELLER => 'Vendedor',
            ROLE_VIEWER => 'Solo Lectura'
        ];
    }
}

// ===== DETECCIÓN DE ENTORNO =====
if (!function_exists('is_development')) {
    function is_development() {
        return APP_ENVIRONMENT === ENV_DEVELOPMENT;
    }
}

if (!function_exists('is_production')) {
    function is_production() {
        return APP_ENVIRONMENT === ENV_PRODUCTION;
    }
}

if (!function_exists('is_testing')) {
    function is_testing() {
        return APP_ENVIRONMENT === ENV_TESTING;
    }
}

// ===== INICIALIZACIÓN =====
// Establecer zona horaria
date_default_timezone_set(TIMEZONE);

// Configurar manejo de errores según el entorno
if (is_production()) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Configurar límites de memoria y tiempo de ejecución
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 30);
ini_set('max_input_time', 60);

// Configurar sesión
session_name('POS_SYSTEM');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => is_production(),
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>