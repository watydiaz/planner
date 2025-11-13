<?php
$config = require 'config/database.php';

$db = new PDO(
    "mysql:host={$config['mysql']['host']};dbname={$config['mysql']['database']};charset={$config['mysql']['charset']}",
    $config['mysql']['username'],
    $config['mysql']['password'],
    $config['mysql']['options']
);

// Ver distribución de tarjetas por tablero y estado
$sql = "SELECT b.id as board_id, b.nombre as board_name, l.title as list_name, COUNT(c.id) as total
        FROM boards b
        LEFT JOIN lists l ON l.board_id = b.id
        LEFT JOIN cards c ON c.list_id = l.id
        GROUP BY b.id, l.id
        ORDER BY b.id, l.position";

$stmt = $db->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== DISTRIBUCIÓN DE TARJETAS POR TABLERO Y ESTADO ===\n\n";

$currentBoard = null;
foreach ($results as $row) {
    if ($currentBoard !== $row['board_id']) {
        $currentBoard = $row['board_id'];
        echo "\n📋 Tablero #{$row['board_id']}: {$row['board_name']}\n";
        echo str_repeat("=", 50) . "\n";
    }
    
    if ($row['list_name']) {
        $icon = match($row['list_name']) {
            'Por Hacer' => '📝',
            'En Progreso' => '⚙️',
            'En Revisión' => '👀',
            'Completado' => '✅',
            default => '📌'
        };
        echo "  {$icon} {$row['list_name']}: {$row['total']} tarjetas\n";
    }
}
