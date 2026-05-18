<?php
require_once __DIR__ . '/includes/config.php';
$db = getDB();

echo "Starting database repair...\n";

try {
    // 1. Update jobs table status ENUM
    echo "Updating jobs table status enum...\n";
    $db->exec("ALTER TABLE jobs MODIFY COLUMN status ENUM('pending', 'open', 'in_progress', 'closed', 'rejected', 'paused', 'cancelled') DEFAULT 'pending'");
    echo "Jobs table updated.\n";

    // 2. Update proposals table status ENUM
    echo "Updating proposals table status enum...\n";
    $db->exec("ALTER TABLE proposals MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'withdrawn', 'shortlisted', 'archived', 'interviewing') DEFAULT 'pending'");
    echo "Proposals table updated.\n";

    // 3. Ensure users table has necessary columns
    echo "Checking users table columns...\n";
    $cols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('skills', $cols)) {
        echo "Adding skills column to users...\n";
        $db->exec("ALTER TABLE users ADD COLUMN skills JSON NULL AFTER bio");
    }
    
    if (!in_array('connects', $cols)) {
        echo "Adding connects column to users...\n";
        $db->exec("ALTER TABLE users ADD COLUMN connects INT NOT NULL DEFAULT 50 AFTER balance");
    }

    echo "Database repair completed successfully!\n";
} catch (PDOException $e) {
    die("Database repair failed: " . $e->getMessage() . "\n");
}
