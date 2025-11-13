<?php
$config = require 'config/database.php';

$db = new PDO(
    "mysql:host={$config['mysql']['host']};dbname={$config['mysql']['database']};charset={$config['mysql']['charset']}",
    $config['mysql']['username'],
    $config['mysql']['password'],
    $config['mysql']['options']
);

echo "=== MIGRANDO DE 4 LISTAS A 3 LISTAS ===\n\n";

// Paso 1: Mover todas las tareas de "En Revisión" a "Completado"
echo "Paso 1: Moviendo tareas de 'En Revisión' a 'Completado'...\n";

$boards = $db->query("SELECT id, nombre FROM boards ORDER BY id")->fetchAll();

foreach ($boards as $board) {
    echo "\nTablero #{$board['id']}: {$board['nombre']}\n";
    
    // Obtener IDs de las listas
    $revision = $db->query("SELECT id FROM lists WHERE board_id = {$board['id']} AND title = 'En Revisión'")->fetch();
    $completado = $db->query("SELECT id FROM lists WHERE board_id = {$board['id']} AND title = 'Completado'")->fetch();
    
    if ($revision && $completado) {
        // Contar tareas a mover
        $count = $db->query("SELECT COUNT(*) as total FROM cards WHERE list_id = {$revision['id']}")->fetch()['total'];
        
        if ($count > 0) {
            // Mover tareas
            $db->exec("UPDATE cards SET list_id = {$completado['id']} WHERE list_id = {$revision['id']}");
            echo "  ✅ {$count} tareas movidas de 'En Revisión' a 'Completado'\n";
        } else {
            echo "  ℹ️  No hay tareas en 'En Revisión'\n";
        }
    }
}

// Paso 2: Eliminar las listas "En Revisión"
echo "\n\nPaso 2: Eliminando listas 'En Revisión'...\n";
$deleted = $db->exec("DELETE FROM lists WHERE title = 'En Revisión'");
echo "✅ {$deleted} listas eliminadas\n";

// Paso 3: Reajustar posiciones de las listas restantes
echo "\nPaso 3: Reajustando posiciones...\n";
foreach ($boards as $board) {
    $lists = $db->query("SELECT id FROM lists WHERE board_id = {$board['id']} ORDER BY position")->fetchAll();
    foreach ($lists as $index => $list) {
        $db->exec("UPDATE lists SET position = {$index} WHERE id = {$list['id']}");
    }
    echo "  ✅ Tablero #{$board['id']} reajustado\n";
}

// Verificar resultado final
echo "\n\n=== RESULTADO FINAL ===\n";
$result = $db->query("
    SELECT b.nombre as board, l.title, COUNT(c.id) as total
    FROM boards b
    LEFT JOIN lists l ON l.board_id = b.id
    LEFT JOIN cards c ON c.list_id = l.id
    GROUP BY b.id, l.id
    ORDER BY b.id, l.position
")->fetchAll();

$currentBoard = null;
foreach ($result as $row) {
    if ($currentBoard !== $row['board']) {
        $currentBoard = $row['board'];
        echo "\n📋 {$row['board']}\n";
    }
    if ($row['title']) {
        echo "   {$row['title']}: {$row['total']} tareas\n";
    }
}

echo "\n✅ Migración completada exitosamente!\n";
