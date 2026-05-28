<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$stmt = $db->query("SELECT id, LENGTH(email_verification_token) as bytes, CHAR_LENGTH(email_verification_token) as chars, email_verification_token FROM users WHERE id = 22");
$res = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
