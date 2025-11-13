<?php
/**
 * Configuración general de la aplicación
 */

return [
    'app_name' => 'Planner Karol Diaz',
    'version' => '2.0.0',
    'timezone' => 'America/Bogota',
    'locale' => 'es_ES',
    
    // Seguridad
    'csrf_enabled' => true,
    
    // Rutas
    'base_path' => dirname(__DIR__),
    'public_path' => dirname(__DIR__) . '/public',
    'uploads_path' => dirname(__DIR__) . '/public/uploads',
    
    // URLs
    'base_url' => 'http://localhost/planificador',
    'assets_url' => 'http://localhost/planificador/public',
];
