<?php
$config = require 'config/database.php';

$db = new PDO(
    "mysql:host={$config['mysql']['host']};dbname={$config['mysql']['database']};charset={$config['mysql']['charset']}",
    $config['mysql']['username'],
    $config['mysql']['password'],
    $config['mysql']['options']
);

// Verificar las tarjetas #3, #12, #15
$stmt = $db->query("
    SELECT c.id, c.title, c.list_id, l.title as list_name, l.board_id, b.nombre as board_name 
    FROM cards c 
    JOIN lists l ON c.list_id = l.id 
    JOIN boards b ON l.board_id = b.id 
    WHERE c.id IN (3, 12, 15)
    ORDER BY c.id
");

echo "=== Verificando tarjetas #3, #12, #15 ===\n\n";

while ($row = $stmt->fetch()) {
    echo "Card #{$row['id']}: {$row['title']}\n";
    echo "  └─ Lista: {$row['list_name']} (list_id={$row['list_id']})\n";
    echo "  └─ Tablero: {$row['board_name']} (board_id={$row['board_id']})\n\n";
}

// Ver TODAS las tarjetas en listas que se llaman "Completado"
echo "\n=== Tarjetas en listas llamadas 'Completado' ===\n\n";
$stmt2 = $db->query("
    SELECT c.id, c.title, l.title as list_name, l.id as list_id, b.nombre as board_name
    FROM cards c
    JOIN lists l ON c.list_id = l.id
    JOIN boards b ON l.board_id = b.id
    WHERE l.title = 'Completado'
    ORDER BY b.id, c.id
");

$found = false;
while ($row = $stmt2->fetch()) {
    $found = true;
    echo "Card #{$row['id']}: {$row['title']} [{$row['board_name']}]\n";
}

if (!$found) {
    echo "No se encontraron tarjetas en listas 'Completado'\n";
}

// Ver nombres de todas las listas
echo "\n\n=== Todas las listas en la BD ===\n";
$stmt3 = $db->query("SELECT id, board_id, title FROM lists ORDER BY board_id, position");
while ($row = $stmt3->fetch()) {
    echo "List ID {$row['id']} (board {$row['board_id']}): \"{$row['title']}\"\n";
}
