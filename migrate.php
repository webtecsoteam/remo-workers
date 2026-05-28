<?php
require_once __DIR__ . '/includes/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_DATABASE);
    echo "Database " . DB_DATABASE . " checked/created.\n";
} catch (PDOException $e) {
    die("Database creation failed: " . $e->getMessage());
}

$db = getDB();

$migrationsDir = __DIR__ . '/database/migrations';
$files = scandir($migrationsDir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
        echo "Running migration: $file...\n";
        $sql = file_get_contents($migrationsDir . '/' . $file);
        
        try {
            $db->exec($sql);
            echo "Successfully ran migration: $file\n";
        } catch (PDOException $e) {
            echo "Error running migration $file: " . $e->getMessage() . "\n";
        }
    }
}
