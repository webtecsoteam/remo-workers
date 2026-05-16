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
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $job_id = intval($_POST['job_id'] ?? 0);

    if (!$receiver_id || !$message) {
        json_response(['success' => false, 'error' => 'Receiver and message are required']);
    }

    $db = getDB();
    try {
        $stmt = $db->prepare("
            INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read) 
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([$user['id'], $receiver_id, $job_id ?: null, $message]);
        
        json_response(['success' => true, 'message_id' => $db->lastInsertId()]);
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
