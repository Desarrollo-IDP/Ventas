<?php
/**
 * Configuración de Seguridad - Sistema de Punto de Venta
 */

class SecurityConfig {
    // Configuración de seguridad
    private static $config = [
        'encryption' => [
            'key' => 'your-32-character-encryption-key-here',
            'cipher' => 'AES-256-CBC',
            'iv_length' => 16
        ],
        'hashing' => [
            'algorithm' => PASSWORD_BCRYPT,
            'cost' => 12
        ],
        'csrf' => [
            'token_name' => 'csrf_token',
            'token_length' => 32,
            'lifetime' => 3600 // 1 hora
        ],
        'xss' => [
            'enabled' => true,
            'strip_tags' => true,
            'htmlpurifier' => false
        ],
        'cors' => [
            'enabled' => true,
            'allowed_origins' => ['http://localhost:3000'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'max_age' => 86400
        ],
        'rate_limiting' => [
            'enabled' => true,
            'max_requests' => 100,
            'window' => 900, // 15 minutos
            'storage' => 'file' // file, database, redis
        ],
        'session' => [
            'regenerate_id' => true,
            'regenerate_interval' => 300, // 5 minutos
            'check_ip' => true,
            'check_user_agent' => true
        ],
        'headers' => [
            'hsts' => [
                'enabled' => true,
                'max_age' => 31536000,
                'include_subdomains' => true,
                'preload' => false
            ],
            'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;",
            'x_content_type_options' => 'nosniff',
            'x_frame_options' => 'SAMEORIGIN',
            'x_xss_protection' => '1; mode=block'
        ]
    ];

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

    public static function initialize() {
        try {
            self::setSecurityHeaders();
            self::initializeSessionSecurity();
            self::configureErrorHandling();
        } catch (Exception $e) {
            // En desarrollo, mostrar el error
            if (is_development()) {
                throw $e;
            } else {
                // En producción, loggear y continuar
                error_log("Error al inicializar seguridad: " . $e->getMessage());
            }
        }
    }

    private static function setSecurityHeaders() {
        // Solo establecer headers si no se han enviado aún
        if (!headers_sent()) {
            $headers = self::get('headers');

            // HSTS (solo en producción con HTTPS)
            if ($headers['hsts']['enabled'] && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $hsts = "max-age={$headers['hsts']['max_age']}";
                if ($headers['hsts']['include_subdomains']) {
                    $hsts .= "; includeSubDomains";
                }
                if ($headers['hsts']['preload']) {
                    $hsts .= "; preload";
                }
                header("Strict-Transport-Security: {$hsts}");
            }

            // Content Security Policy
            header("Content-Security-Policy: {$headers['content_security_policy']}");

            // X-Content-Type-Options
            header("X-Content-Type-Options: {$headers['x_content_type_options']}");

            // X-Frame-Options
            header("X-Frame-Options: {$headers['x_frame_options']}");

            // X-XSS-Protection
            header("X-XSS-Protection: {$headers['x_xss_protection']}");

            // Referrer Policy
            header("Referrer-Policy: strict-origin-when-cross-origin");

            // Permissions Policy
            header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

            // Cache Control - Previene que el contenido se guarde en cache y se vea con el botón "Atrás" después de logout
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        }
    }

    private static function initializeSessionSecurity() {
        $sessionConfig = self::get('session');

        if ($sessionConfig['regenerate_id']) {
            // Regenerar ID de sesión periódicamente
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > $sessionConfig['regenerate_interval']) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }

        // Verificar IP
        if ($sessionConfig['check_ip'] && isset($_SESSION['user_ip'])) {
            if ($_SESSION['user_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
                session_destroy();
                self::redirectToLogin('Sesión inválida');
            }
        } else {
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        // Verificar User Agent
        if ($sessionConfig['check_user_agent'] && isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
                session_destroy();
                self::redirectToLogin('Sesión inválida');
            }
        } else {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
    }

    private static function configureErrorHandling() {
        // Log de todos los errores
        ini_set('log_errors', '1');
        
        // Asegurar que el directorio de logs existe
        $log_dir = dirname(__DIR__) . '/logs/';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        ini_set('error_log', $log_dir . 'security_errors.log');
        
        // No mostrar errores al usuario en producción
        if (defined('APP_ENVIRONMENT') && APP_ENVIRONMENT === 'production') {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        }
    }

    public static function redirectToLogin($message = '') {
        if ($message) {
            $_SESSION['flash_error'] = $message;
        }
        
        // Evitar bucle de redirección si ya estamos en la página de login
        $current_page = $_SERVER['PHP_SELF'];
        if (strpos($current_page, 'login.php') === false) {
            header('Location: /views/auth/login.php');
            exit;
        }
    }
}

class SecurityService {
    // Encriptación
    public static function encrypt($data) {
        $config = SecurityConfig::get('encryption');
        $iv = random_bytes($config['iv_length']);
        $encrypted = openssl_encrypt(
            $data,
            $config['cipher'],
            $config['key'],
            0,
            $iv
        );
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data) {
        $config = SecurityConfig::get('encryption');
        $data = base64_decode($data);
        $iv = substr($data, 0, $config['iv_length']);
        $encrypted = substr($data, $config['iv_length']);
        return openssl_decrypt(
            $encrypted,
            $config['cipher'],
            $config['key'],
            0,
            $iv
        );
    }

    // Hashing de contraseñas
    public static function hashPassword($password) {
        $config = SecurityConfig::get('hashing');
        return password_hash($password, $config['algorithm'], ['cost' => $config['cost']]);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // CSRF Protection
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        $token = bin2hex(random_bytes(SecurityConfig::get('csrf.token_length')));
        $_SESSION['csrf_tokens'][$token] = time() + SecurityConfig::get('csrf.lifetime');

        // Limpiar tokens expirados
        self::cleanExpiredCSRFTokens();

        return $token;
    }

    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }

        if ($_SESSION['csrf_tokens'][$token] < time()) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }

        // El token es válido, eliminarlo para prevenir reuso
        unset($_SESSION['csrf_tokens'][$token]);
        return true;
    }

    private static function cleanExpiredCSRFTokens() {
        foreach ($_SESSION['csrf_tokens'] as $token => $expiry) {
            if ($expiry < time()) {
                unset($_SESSION['csrf_tokens'][$token]);
            }
        }
    }

    // XSS Protection
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }

        $config = SecurityConfig::get('xss');

        if ($config['strip_tags']) {
            $input = strip_tags($input);
        }

        if ($config['htmlpurifier']) {
            // Requiere HTML Purifier library
            // $purifier = new HTMLPurifier();
            // $input = $purifier->purify($input);
        }

        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // Rate Limiting
    public static function checkRateLimit($identifier, $maxRequests = null, $window = null) {
        if (!SecurityConfig::get('rate_limiting.enabled')) {
            return true;
        }

        $maxRequests = $maxRequests ?: SecurityConfig::get('rate_limiting.max_requests');
        $window = $window ?: SecurityConfig::get('rate_limiting.window');
        $storage = SecurityConfig::get('rate_limiting.storage');

        $key = "rate_limit_{$identifier}";
        $now = time();

        if ($storage === 'file') {
            return self::checkRateLimitFile($key, $maxRequests, $window, $now);
        } elseif ($storage === 'database') {
            return self::checkRateLimitDatabase($key, $maxRequests, $window, $now);
        }

        return true;
    }

    private static function checkRateLimitFile($key, $maxRequests, $window, $now) {
        $rateLimitFile = config('cache.path') . 'rate_limiting.json';
        $data = [];

        if (file_exists($rateLimitFile)) {
            $data = json_decode(file_get_contents($rateLimitFile), true) ?: [];
        }

        if (!isset($data[$key])) {
            $data[$key] = [
                'count' => 1,
                'window_start' => $now
            ];
        } else {
            // Si la ventana de tiempo ha expirado, reiniciar
            if ($now - $data[$key]['window_start'] > $window) {
                $data[$key] = [
                    'count' => 1,
                    'window_start' => $now
                ];
            } else {
                $data[$key]['count']++;
            }
        }

        file_put_contents($rateLimitFile, json_encode($data));

        return $data[$key]['count'] <= $maxRequests;
    }

    private static function checkRateLimitDatabase($key, $maxRequests, $window, $now) {
        // Implementar rate limiting con base de datos
        // Esta es una implementación básica
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO rate_limits (identifier, request_count, window_start, last_updated) 
                VALUES (?, 1, ?, ?)
                ON DUPLICATE KEY UPDATE 
                request_count = IF(window_start < ?, 1, request_count + 1),
                window_start = IF(window_start < ?, ?, window_start),
                last_updated = ?
            ");
            
            $windowStart = $now - $window;
            $stmt->execute([
                $key, $now, $now,
                $windowStart, $windowStart, $now, $now,
                $now
            ]);

            // Obtener el conteo actual
            $stmt = $db->prepare("SELECT request_count FROM rate_limits WHERE identifier = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();

            return $result && $result['request_count'] <= $maxRequests;
        } catch (Exception $e) {
            error_log("Error en rate limiting: " . $e->getMessage());
            return true; // En caso de error, permitir la solicitud
        }
    }

    // Validación de entrada
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function validateIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function validateInteger($value, $min = null, $max = null) {
        $options = [];
        if ($min !== null) $options['min_range'] = $min;
        if ($max !== null) $options['max_range'] = $max;

        return filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]) !== false;
    }

    public static function validateFloat($value, $min = null, $max = null) {
        $options = [];
        if ($min !== null) $options['min_range'] = $min;
        if ($max !== null) $options['max_range'] = $max;

        return filter_var($value, FILTER_VALIDATE_FLOAT, ['options' => $options]) !== false;
    }

    // Generación de tokens seguros
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    public static function generateAPIKey() {
        return 'pos_' . bin2hex(random_bytes(16));
    }

    // Auditoría de seguridad
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'details' => $details
        ];

        $logFile = config('logging.path') . 'security_audit.log';
        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    }

    // Métodos de autenticación y roles
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function hasRole($role) {
        if (!self::isAuthenticated()) return false;
        
        $user_rol = $_SESSION['user_rol'] ?? '';
        
        // Soporte para múltiples roles si se pasa un array
        if (is_array($role)) {
            return in_array($user_rol, $role);
        }
        
        // Admin tiene acceso a todo
        if ($user_rol === 'admin') return true;
        
        return $user_rol === $role;
    }

    public static function requiredAuth() {
        if (!self::isAuthenticated()) {
            SecurityConfig::redirectToLogin('Debe iniciar sesión para acceder a esta página');
        }
    }

    public static function requiredRole($role) {
        self::requiredAuth();
        if (!self::hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            die('Acceso denegado: No tiene permisos para acceder a esta sección.');
        }
    }
}

// Inicializar seguridad
SecurityConfig::initialize();

// Funciones helper globales
function sanitize_input($input) {
    return SecurityService::sanitize($input);
}

function generate_csrf_token() {
    return SecurityService::generateCSRFToken();
}

function validate_csrf_token($token) {
    return SecurityService::validateCSRFToken($token);
}

function check_rate_limit($identifier) {
    return SecurityService::checkRateLimit($identifier);
}
?>