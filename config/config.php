<?php
/**
 * Configuración General del Sistema - Punto de Venta
 */

class AppConfig {
    // Configuración de la aplicación
    private static $config = [
        // Información básica de la aplicación
        'app' => [
            'name' => 'Sistema Punto de Venta',
            'version' => '1.0.0',
            'description' => 'Sistema completo de punto de venta con cotizaciones',
            'environment' => 'production',
            'url' => 'https://securiti.dyndns.tv:93',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
            'currency' => 'MXN'
        ],

        // Configuración de sesión
        'session' => [
            'name' => 'POS_SESSION',
            'lifetime' => 7200, // 2 horas en segundos
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ],

        // Configuración de uploads
        'uploads' => [
            'path' => __DIR__ . '/../uploads/',
            'max_size' => 5242880, // 5MB
            'allowed_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
            'image' => [
                'max_width' => 1920,
                'max_height' => 1080,
                'quality' => 85
            ]
        ],

        // Configuración de paginación
        'pagination' => [
            'per_page' => 25,
            'max_links' => 5
        ],

        // Configuración de cache
        'cache' => [
            'enabled' => true,
            'path' => __DIR__ . '/../cache/',
            'lifetime' => 3600 // 1 hora
        ],

        // Configuración de logs
        'logging' => [
            'enabled' => true,
            'path' => __DIR__ . '/../logs/',
            'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
            'max_files' => 30
        ],

        // Configuración de API
        'api' => [
            'enabled' => false,
            'rate_limit' => 100, // requests por hora
            'cors' => [
                'allowed_origins' => ['http://localhost:3000'],
                'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
                'allowed_headers' => ['Content-Type', 'Authorization']
            ]
        ],

        // Configuración de reportes
        'reports' => [
            'path' => __DIR__ . '/../reports/',
            'default_format' => 'pdf', // pdf, excel, csv
            'retention_days' => 365
        ],

        // Configuración de notificaciones
        'notifications' => [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => false,
            'default_channel' => 'email'
        ],

        // Configuración de facturación (México)
        'billing' => [
            'iva_rate' => 0.16,
            'ieps_rate' => 0.00,
            'isr_rate' => 0.00,
            'currency_symbol' => '$',
            'currency_code' => 'MXN',
            'decimal_places' => 2,
            'thousands_separator' => ',',
            'decimal_separator' => '.'
        ],

        // Configuración de inventario
        'inventory' => [
            'low_stock_threshold' => 5,
            'critical_stock_threshold' => 2,
            'allow_negative_stock' => false,
            'auto_adjust_prices' => false,
            'default_category' => 'General'
        ],

        // Configuración de cotizaciones
        'quotes' => [
            'default_expiry_days' => 15,
            'auto_reminder_days' => 3,
            'number_prefix' => 'COT',
            'number_format' => 'COT-{year}{month}{day}-{sequence}',
            'default_notes' => 'Cotización válida por {days} días. Precios sujetos a disponibilidad.'
        ],

        // Configuración de clientes
        'clients' => [
            'default_type' => 'Individual',
            'auto_generate_code' => true,
            'code_prefix' => 'CLI',
            'code_format' => 'CLI-{sequence}',
            'credit_limit_default' => 0,
            'payment_terms_default' => 'Contado'
        ],

        // Configuración de usuarios
        'users' => [
            'min_password_length' => 8,
            'require_special_chars' => true,
            'require_numbers' => true,
            'require_uppercase' => true,
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutos
            'session_timeout' => 1800 // 30 minutos
        ]
    ];

    // Obtener configuración
    public static function get($key = null, $default = null) {
        if ($key === null) {
            return self::$config;
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    // Establecer configuración (solo en tiempo de ejecución)
    public static function set($key, $value) {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    // Cargar configuración desde archivo externo
    public static function loadFromFile($filePath) {
        if (file_exists($filePath)) {
            $externalConfig = include $filePath;
            if (is_array($externalConfig)) {
                self::$config = array_replace_recursive(self::$config, $externalConfig);
            }
        }
    }

    // Validar configuración requerida
    public static function validate() {
        $required = [
            'app.name',
            'app.url',
            'app.timezone',
            'uploads.path',
            'billing.iva_rate'
        ];

        $errors = [];
        foreach ($required as $key) {
            if (self::get($key) === null) {
                $errors[] = "Configuración requerida faltante: {$key}";
            }
        }

        return $errors;
    }

    // Inicializar aplicación
    public static function initialize() {
        // Establecer zona horaria
        date_default_timezone_set(self::get('app.timezone'));

        // Establecer locale
        setlocale(LC_ALL, self::get('app.locale'));

        // Configurar manejo de errores según el entorno
        if (self::get('app.environment') === 'production') {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

        // Configurar sesión
        self::configureSession();

        // Crear directorios necesarios
        self::createRequiredDirectories();

        // Inicializar logging
        self::initializeLogging();
    }

    // Configurar sesión
    private static function configureSession() {
        // Solo configurar si la sesión no está activa
        if (session_status() === PHP_SESSION_NONE) {
            $sessionConfig = self::get('session');

            session_name($sessionConfig['name']);
            session_set_cookie_params([
                'lifetime' => $sessionConfig['lifetime'],
                'path' => $sessionConfig['path'],
                'domain' => $sessionConfig['domain'],
                'secure' => $sessionConfig['secure'],
                'httponly' => $sessionConfig['httponly'],
                'samesite' => $sessionConfig['samesite']
            ]);

            // Iniciar sesión si no está activa
            session_start();
        }
    }

    // Crear directorios requeridos
    private static function createRequiredDirectories() {
        $directories = [
            self::get('uploads.path'),
            self::get('cache.path'),
            self::get('logging.path'),
            self::get('reports.path'),
            __DIR__ . '/../backups/database/',
            __DIR__ . '/../temp/'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    // Inicializar sistema de logging
    private static function initializeLogging() {
        if (self::get('logging.enabled')) {
            $logPath = self::get('logging.path') . 'app_' . date('Y-m-d') . '.log';
            ini_set('error_log', $logPath);
        }
    }

    // Obtener información del sistema
    public static function getSystemInfo() {
        return [
            'app_name' => self::get('app.name'),
            'app_version' => self::get('app.version'),
            'environment' => self::get('app.environment', 'development'),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_driver' => 'MySQL',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
    }

    // Métodos de utilidad para configuraciones específicas
    public static function getCurrencyFormat() {
        $billing = self::get('billing');
        return [
            'symbol' => $billing['currency_symbol'],
            'code' => $billing['currency_code'],
            'decimals' => $billing['decimal_places'],
            'thousands' => $billing['thousands_separator'],
            'decimal' => $billing['decimal_separator']
        ];
    }

    public static function formatCurrency($amount) {
        $format = self::getCurrencyFormat();
        return $format['symbol'] . number_format(
            $amount, 
            $format['decimals'], 
            $format['decimal'], 
            $format['thousands']
        );
    }

    public static function calculateIVA($amount) {
        return $amount * self::get('billing.iva_rate');
    }

    public static function calculateTotalWithIVA($amount) {
        return $amount + self::calculateIVA($amount);
    }
}

// Resolver el entorno y los valores públicos antes de inicializar la aplicación
$environment = defined('APP_ENVIRONMENT') ? APP_ENVIRONMENT : (getenv('APP_ENV') ?: 'development');
AppConfig::set('app.environment', $environment);
if ($appUrl = getenv('APP_URL')) {
    AppConfig::set('app.url', $appUrl);
}

// Cargar configuración de entorno específico
$envFile = __DIR__ . "/environment/{$environment}.php";
if (file_exists($envFile)) {
    AppConfig::loadFromFile($envFile);
}

// Inicializar configuración automáticamente
AppConfig::initialize();

// Función helper global para acceder a configuración
function config($key = null, $default = null) {
    return AppConfig::get($key, $default);
}

// Función helper para formatear moneda
function format_money($amount) {
    return AppConfig::formatCurrency($amount);
}

// Función helper para calcular IVA (removida para evitar conflicto con constants.php)
?>