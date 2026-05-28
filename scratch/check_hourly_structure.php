<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    // 1. Show tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in Database:\n" . implode("\n", $tables) . "\n\n";

    // 2. Describe relevant tables
    $relevant = ['contracts', 'work_logs', 'payments', 'invoices'];
    foreach ($relevant as $t) {
        if (in_array($t, $tables)) {
            echo "--- Table: {$t} ---\n";
            $desc = $db->query("DESCRIBE `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($desc as $col) {
                echo "  {$col['Field']} - {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']} - Default: {$col['Default']}\n";
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
