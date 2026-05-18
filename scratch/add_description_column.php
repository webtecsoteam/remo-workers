<?php
/**
 * Scratch script to add 'description' column to 'payments' table.
 */

require_once __DIR__ . '/../includes/config.php';

try {
    $db = getDB();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM payments LIKE 'description'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Adding 'description' column to 'payments' table...\n";
        $db->exec("ALTER TABLE payments ADD COLUMN description VARCHAR(255) NULL AFTER payment_method");
        echo "Column 'description' successfully added!\n";
    } else {
        echo "Column 'description' already exists in 'payments' table.\n";
    }
} catch (Exception $e) {
    echo "Error altering table: " . $e->getMessage() . "\n";
}
