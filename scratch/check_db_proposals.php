<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
echo "--- PROPOSALS TABLE ---\n";
$stmt = $db->query("DESCRIBE proposals");
print_r($stmt->fetchAll());
