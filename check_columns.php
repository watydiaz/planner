<?php
$config = require 'config/database.php';

$db = new PDO(
    "mysql:host={$config['mysql']['host']};dbname={$config['mysql']['database']};charset={$config['mysql']['charset']}",
    $config['mysql']['username'],
    $config['mysql']['password'],
    $config['mysql']['options']
);

echo "=== COLUMNAS DE LA TABLA CARDS ===\n\n";
$stmt = $db->query("SHOW COLUMNS FROM cards");
while ($row = $stmt->fetch()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n\n=== VALORES ÚNICOS EN CADA COLUMNA ===\n";

// Categorías
$cats = $db->query("SELECT DISTINCT categoria FROM cards WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
echo "\nCategorías (" . count($cats) . "):\n";
foreach ($cats as $cat) {
    echo "  - $cat\n";
}

// Tipos
$types = $db->query("SELECT DISTINCT tipo FROM cards WHERE tipo IS NOT NULL AND tipo != '' ORDER BY tipo")->fetchAll(PDO::FETCH_COLUMN);
echo "\nTipos (" . count($types) . "):\n";
foreach ($types as $type) {
    echo "  - $type\n";
}
