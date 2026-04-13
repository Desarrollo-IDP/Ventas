<?php
/**
 * Configuración para Entorno de Producción
 */

return [
    'app' => [
        'environment' => 'production',
        'debug' => false,
        'url' => 'https://tudominio.com'
    ],
    'database' => [
        'host' => 'localhost',
        'database' => 'sistema_pos_prod',
        'username' => 'pos_user',
        'password' => 'your_secure_password',
        'port' => 3306
    ],
    'email' => [
        'driver' => 'smtp',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'sistema@tudominio.com',
        'password' => 'your_app_password',
        'encryption' => 'tls',
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