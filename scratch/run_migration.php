<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    $db->exec("ALTER TABLE users ADD COLUMN skills JSON NULL AFTER bio");
    echo "Added skills column.\n";
} catch (Exception $e) {
    echo "Skills column might already exist.\n";
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        freelancer_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(12, 2) NOT NULL,
        delivery_days INT NOT NULL,
        image_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (freelancer_id) REFERENCES users(id)
    )");
    echo "Created services table.\n";
} catch (Exception $e) {
    echo "Failed to create services table: " . $e->getMessage() . "\n";
}
