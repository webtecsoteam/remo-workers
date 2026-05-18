<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

try {
    echo "Updating proposals table status column...\n";
    
    // Add shortlisted, archived, interviewing to the ENUM
    $db->exec("ALTER TABLE proposals MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'withdrawn', 'shortlisted', 'archived', 'interviewing') DEFAULT 'pending'");
    
    echo "Success! Added 'shortlisted', 'archived', and 'interviewing' to proposals status.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
