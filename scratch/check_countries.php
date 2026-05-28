<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$stmt = $db->query("SELECT id, name, role, country FROM users ORDER BY id DESC LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users, JSON_PRETTY_PRINT);
