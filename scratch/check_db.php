<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "- $table\n";
    $columns = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  * {$col['Field']} ({$col['Type']})\n";
    }
}
