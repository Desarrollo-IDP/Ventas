<?php
/**
 * Configuración para Entorno de Testing
 */

return [
    'app' => [
        'environment' => 'testing',
        'debug' => true,
        'url' => 'http://test.localhost'
    ],
    'database' => [
        'host' => 'localhost',
        'database' => 'sistema_pos_test',
        'username' => 'root',
        'password' => '',
        'port' => 3306
    ],
    'email' => [
        'driver' => 'log',
        'testing' => true
    ],
    'logging' => [
        'level' => 'DEBUG',
        'enabled' => true
    ],
    'cache' => [
        'enabled' => false
    ]
];