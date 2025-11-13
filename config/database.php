<?php
/**
 * Configuración de base de datos
 */

return [
    // Tipo de base de datos: 'sqlite' o 'mysql'
    'driver' => 'mysql', // Cambiar a 'mysql' si usas MySQL
    
    // Configuración SQLite
    'sqlite' => [
        'database' => dirname(__DIR__) . '/kanban.db',
    ],
    
    // Configuración MySQL
    'mysql' => [
        'host' => 'localhost',
        'database' => 'planificador_kanban',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
