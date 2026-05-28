<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

$sql = "
CREATE TABLE IF NOT EXISTS connects_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    badge_text VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";
$db->exec($sql);
echo "Table created.\n";

$check = $db->query("SELECT COUNT(*) FROM connects_packages")->fetchColumn();
if ($check == 0) {
    $insert = "INSERT INTO connects_packages (amount, price, badge_text) VALUES 
        (10, 1.50, NULL),
        (20, 3.00, NULL),
        (40, 6.00, 'Most Popular'),
        (80, 12.00, 'Best Value');";
    $db->exec($insert);
    echo "Seed data inserted.\n";
} else {
    echo "Seed data already exists.\n";
}
