<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    // Modify status column of milestones table
    $sql = "ALTER TABLE milestones MODIFY COLUMN status ENUM('pending', 'funded', 'requested', 'completed', 'paid') DEFAULT 'pending'";
    $db->exec($sql);
    echo "Milestones status column updated successfully!\n";
} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage() . "\n";
}
