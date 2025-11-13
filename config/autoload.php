<?php
/**
 * Autoloader PSR-4
 */

spl_autoload_register(function ($class) {
    $basePath = dirname(__DIR__);
    
    // Mapeo de clases a directorios
    $directories = [
        $basePath . '/app/models/',
        $basePath . '/app/controllers/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});
