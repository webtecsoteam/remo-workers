<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $status = $_POST['availability'] ?? 'available';

    if (!in_array($status, ['available', 'limited', 'unavailable'])) {
        json_response(['success' => false, 'error' => 'Invalid status']);
    }

    try {
        $stmt = $db->prepare("UPDATE users SET availability = ? WHERE id = ?");
        $stmt->execute([$status, $user['id']]);
        
        json_response(['success' => true, 'message' => 'Availability updated']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
