<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$stmt = $db->query("SELECT * FROM work_logs");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total work logs: " . count($logs) . "\n";
print_r($logs);
