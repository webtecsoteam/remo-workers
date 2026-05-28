<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$stmt = $db->query("SELECT j.id as job_id, j.title, j.client_id, u.name as client_name, u.country as client_country FROM jobs j JOIN users u ON j.client_id = u.id ORDER BY j.id DESC LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users, JSON_PRETTY_PRINT);
