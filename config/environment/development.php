<?php
/**
 * Configuraci�n para Entorno de Desarrollo
 */

return [
    'app' => [
        'environment' => 'development',
        'debug' => true,
        'url' => 'http://localhost/sistema-pos'
    ],
    'database' => [
        'host' => 'localhost',
        'database' => 'sistema_pos_development',
        'username' => 'root',
        'password' => '',
        'port' => 3306
    ],
    'email' => [
        'driver' => 'smtp',
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'your_mailtrap_username',
        'password' => 'your_mailtrap_password',
        'encryption' => 'tls',
        'testing' => false
    ],
    'logging' => [
        'path' => realpath(__DIR__ . '/../../') . '/logs/',
        'level' => 'DEBUG',
        'enabled' => true,
        'max_files' => 30
    ],
    'cache' => [
        'enabled' => false
    ]
];
?>
