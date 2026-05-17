<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$stmt = $db->query("SELECT c.*, j.title as job_title, u.name as freelancer_name FROM contracts c JOIN jobs j ON c.job_id = j.id JOIN users u ON c.freelancer_id = u.id");
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total contracts: " . count($contracts) . "\n";
print_r($contracts);
