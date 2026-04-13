<?php
/**
 * Configuración de Base de Datos - Sistema de Punto de Venta
 */

class DatabaseConfig {
    // Configuración para diferentes entornos
    private static $environments = [
        'development' => [
            'host' => 'localhost',
            'database' => 'sistema_pos_development',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'port' => 3306
        ],
        'production' => [
            'host' => 'localhost',
            'database' => 'sistema_pos_production',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'port' => 3306
        ],
        'testing' => [
            'host' => 'localhost',
            'database' => 'sistema_pos_testing',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'port' => 3306
        ]
    ];

    // Obtener configuración según el entorno
    public static function getConfig($environment = null) {
        if ($environment === null) {
            $environment = self::getCurrentEnvironment();
        }

        if (!isset(self::$environments[$environment])) {
            throw new Exception("Entorno de base de datos no válido: {$environment}");
        }

        return self::$environments[$environment];
    }

    // Obtener el entorno actual
    public static function getCurrentEnvironment() {
        // Verificar variable de entorno
        if ($env = getenv('APP_ENV')) {
            return $env;
        }

        // Verificar en archivo de configuración
        if (defined('APP_ENVIRONMENT')) {
            return APP_ENVIRONMENT;
        }

        // Detectar automáticamente por nombre de servidor
        $hostname = gethostname();
        if (strpos(strtolower($hostname), 'local') !== false || strpos(strtolower($hostname), 'dev') !== false) {
            return 'development';
        } elseif (strpos(strtolower($hostname), 'test') !== false) {
            return 'testing';
        } else {
            // Por seguridad en entornos de desarrollo locales, devolver 'development' por defecto
            return 'development';
        }
    }

    // Crear DSN para PDO
    public static function getDSN($environment = null) {
        $config = self::getConfig($environment);
        return "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']};port={$config['port']}";
    }

    // Obtener opciones de PDO
    public static function getPDOOptions() {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_LOCAL_INFILE => true
        ];
    }

    // Verificar conexión a la base de datos
    public static function testConnection($environment = null) {
        try {
            $config = self::getConfig($environment);
            $dsn = self::getDSN($environment);
            $pdo = new PDO($dsn, $config['username'], $config['password'], self::getPDOOptions());
            
            // Ejecutar consulta simple para verificar
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            return false;
        }
    }

    // Backup de base de datos
    public static function getBackupConfig() {
        return [
            'backup_path' => __DIR__ . '/../backups/database/',
            'max_backups' => 30,
            'compress' => true,
            'include_data' => true,
            'include_structure' => true
        ];
    }
}

// Clase de conexión a la base de datos
class Database {
    private static $instance = null;
    private $connection;
    private $environment;

    private function __construct($environment = null) {
        $this->environment = $environment ?: DatabaseConfig::getCurrentEnvironment();
        $this->connect();
    }

    public static function getInstance($environment = null) {
        if (self::$instance === null) {
            self::$instance = new self($environment);
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $config = DatabaseConfig::getConfig($this->environment);
            $dsn = DatabaseConfig::getDSN($this->environment);
            $options = DatabaseConfig::getPDOOptions();

            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
            
            // Configurar zona horaria
            $this->connection->exec("SET time_zone = '-06:00'");
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }

    private function handleConnectionError($exception) {
        $error_message = "Error de conexión a la base de datos: " . $exception->getMessage();

        // Log del error
        error_log($error_message);

        // Mostrar mensaje apropiado según el entorno
        if ($this->environment === 'production') {
            // En producción, mostrar mensaje genérico
            die("Error del sistema. Contacte al administrador.");
        } else {
            // En desarrollo, mostrar detalles del error
            die($error_message);
        }
    }

    public function getConnection() {
        // Verificar si la conexión sigue activa
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconectar si la conexión se perdió
            $this->connect();
        }

        return $this->connection;
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollBack() {
        return $this->connection->rollBack();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    // Métodos de utilidad para consultas comunes
    public function fetchAll($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchOne($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function execute($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }

    // Método para crear backup
    public function createBackup() {
        $backupConfig = DatabaseConfig::getBackupConfig();
        $config = DatabaseConfig::getConfig($this->environment);
        
        // Crear directorio de backups si no existe
        if (!is_dir($backupConfig['backup_path'])) {
            mkdir($backupConfig['backup_path'], 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupConfig['backup_path'] . "backup_{$config['database']}_{$timestamp}.sql";

        // Comando mysqldump (requiere que mysqldump esté en el PATH)
        $command = "mysqldump --user={$config['username']} --password={$config['password']} --host={$config['host']} --port={$config['port']} {$config['database']} > {$backupFile}";

        system($command, $output);

        if ($output === 0 && file_exists($backupFile)) {
            // Comprimir si está configurado
            if ($backupConfig['compress']) {
                $compressedFile = $backupFile . '.gz';
                $gz = gzopen($compressedFile, 'w9');
                gzwrite($gz, file_get_contents($backupFile));
                gzclose($gz);
                unlink($backupFile);
                $backupFile = $compressedFile;
            }

            // Limpiar backups antiguos
            $this->cleanOldBackups($backupConfig);
            
            return $backupFile;
        }

        return false;
    }

    private function cleanOldBackups($backupConfig) {
        $files = glob($backupConfig['backup_path'] . "backup_*.sql*");
        
        // Ordenar por fecha de modificación (más reciente primero)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Eliminar backups excedentes
        if (count($files) > $backupConfig['max_backups']) {
            for ($i = $backupConfig['max_backups']; $i < count($files); $i++) {
                unlink($files[$i]);
            }
        }
    }
}

// Función helper global para obtener la conexión
function getDB() {
    return Database::getInstance()->getConnection();
}

// Inicializar conexión automáticamente si se solicita
if (defined('AUTO_INIT_DB') && AUTO_INIT_DB) {
    try {
        $db = Database::getInstance();
    } catch (Exception $e) {
        error_log("Error al inicializar base de datos: " . $e->getMessage());
    }
}
?>