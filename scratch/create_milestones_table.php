<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    $sql = "CREATE TABLE IF NOT EXISTS milestones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proposal_id INT NULL,
        contract_id INT NULL,
        description TEXT NOT NULL,
        amount DECIMAL(12, 2) NOT NULL,
        status ENUM('pending', 'requested', 'completed', 'paid') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE CASCADE,
        FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Milestones table created successfully!\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
