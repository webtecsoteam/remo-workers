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
    $id = $_POST['id'] ?? 0;

    if (empty($id)) {
        json_response(['success' => false, 'error' => 'Invalid project ID']);
    }

    try {
        // Verify ownership
        $check = $db->prepare("SELECT id FROM services WHERE id = ? AND freelancer_id = ?");
        $check->execute([$id, $user['id']]);
        if (!$check->fetch()) {
            json_response(['success' => false, 'error' => 'Unauthorized or project not found']);
        }

        $stmt = $db->prepare("DELETE FROM services WHERE id = ? AND freelancer_id = ?");
        $stmt->execute([$id, $user['id']]);
        
        json_response(['success' => true, 'message' => 'Project deleted successfully']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
