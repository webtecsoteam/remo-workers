<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    $stmt = $db->prepare("SELECT id, name, role, balance FROM users WHERE id = 18");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($user);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
