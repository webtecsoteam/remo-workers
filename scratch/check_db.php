<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db = getDB();
    $db->query("SELECT 1 FROM user_documents LIMIT 1");
    echo "Table user_documents exists.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
try {
    $db->query("SELECT is_verified FROM users LIMIT 1");
    echo "Column is_verified exists in users.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
