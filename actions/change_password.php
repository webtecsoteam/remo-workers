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
    $currentPw = $_POST['current_password'] ?? '';
    $newPw = $_POST['new_password'] ?? '';

    if (empty($currentPw) || empty($newPw)) {
        json_response(['success' => false, 'error' => 'Please fill all fields']);
    }

    if (strlen($newPw) < 8) {
        json_response(['success' => false, 'error' => 'New password must be at least 8 characters']);
    }

    try {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $storedPw = $stmt->fetchColumn();

        if (!password_verify($currentPw, $storedPw)) {
            json_response(['success' => false, 'error' => 'Incorrect current password']);
        }

        // Update to new password
        $hashedNewPw = password_hash($newPw, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$hashedNewPw, $user['id']]);
        
        json_response(['success' => true, 'message' => 'Password updated successfully']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
