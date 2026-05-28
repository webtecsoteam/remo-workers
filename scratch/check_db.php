<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
echo "--- USERS TABLE ---\n";
$stmt = $db->query("DESCRIBE users");
print_r($stmt->fetchAll());
echo "\n--- JOBS TABLE ---\n";
$stmt = $db->query("DESCRIBE jobs");
print_r($stmt->fetchAll());
