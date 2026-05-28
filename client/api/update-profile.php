<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? null;
$company = $input['company'] ?? null;
$bio = $input['bio'] ?? null;
$country = $input['country'] ?? null;

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET name = ?, title = ?, bio = ?, country = ? WHERE id = ?");
    $stmt->execute([$name, $company, $bio, $country, $user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
