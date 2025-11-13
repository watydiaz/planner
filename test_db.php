<?php
// Test de conexión a base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=planificador_kanban;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "✅ Conexión exitosa a la base de datos!<br><br>";
    
    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Tablas encontradas:<br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    echo "<br>";
    
    // Verificar tableros
    $boards = $pdo->query("SELECT * FROM boards")->fetchAll();
    echo "🗂️ Tableros: " . count($boards) . "<br>";
    foreach ($boards as $board) {
        echo "- ID: {$board['id']}, Nombre: {$board['nombre']}<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
