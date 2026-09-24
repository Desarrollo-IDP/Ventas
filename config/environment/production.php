<?php
/**
 * Configuración para Entorno de Producción
 */

return [
    'app' => [
        'environment' => 'production',
        'debug' => false,
        'url' => getenv('APP_URL') ?: 'https://tudominio.com'
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_DATABASE') ?: 'sistema_pos_production',
        'username' => getenv('DB_USERNAME') ?: 'pos_user',
        'password' => getenv('DB_PASSWORD') ?: '',
        'port' => (int) (getenv('DB_PORT') ?: 3306)
    ],
    'email' => [
        'driver' => 'smtp',
        'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port' => (int) (getenv('MAIL_PORT') ?: 587),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'testing' => false
    ],
    'logging' => [
        'level' => 'ERROR',
        'enabled' => true
    ],
    'cache' => [
        'enabled' => true
    ],
    'security' => [
        'rate_limiting' => [
            'enabled' => true,
            'max_requests' => 100
        ]
    ]
];