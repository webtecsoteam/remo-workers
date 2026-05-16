<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $name = $_POST['name'] ?? '';
    $title = $_POST['title'] ?? '';
    $rate = $_POST['hourly_rate'] ?? 0;
    $country = $_POST['country'] ?? '';
    $bio = $_POST['bio'] ?? '';

    if (empty($name)) {
        json_response(['success' => false, 'error' => 'Name is required']);
    }

    try {
        $stmt = $db->prepare("UPDATE users SET name = ?, title = ?, hourly_rate = ?, country = ?, bio = ? WHERE id = ?");
        $stmt->execute([$name, $title, $rate, $country, $bio, $user['id']]);
        
        json_response(['success' => true]);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
