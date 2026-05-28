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
    $skills = $_POST['skills'] ?? '[]';
    
    // Validate JSON
    $decoded = json_decode($skills, true);
    if (!is_array($decoded)) {
        json_response(['success' => false, 'error' => 'Invalid skills format']);
    }

    try {
        $stmt = $db->prepare("UPDATE users SET skills = ? WHERE id = ?");
        $stmt->execute([$skills, $user['id']]);
        
        json_response(['success' => true]);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
