<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

try {
    echo "Updating jobs table status column...\n";
    
    $db->exec("ALTER TABLE jobs MODIFY COLUMN status ENUM('pending', 'open', 'in_progress', 'closed', 'rejected', 'paused', 'cancelled') DEFAULT 'pending'");
    
    echo "Success! Added 'paused' and 'cancelled' to jobs status.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
