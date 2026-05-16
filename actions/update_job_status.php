<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$job_id = intval($_POST['job_id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$job_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Job ID and status are required']);
    exit;
}

// Allowed statuses for toggle
$allowed = ['open', 'paused'];
if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status transition']);
    exit;
}

$db = getDB();
try {
    // Verify ownership
    $check = $db->prepare("SELECT id FROM jobs WHERE id = ? AND client_id = ?");
    $check->execute([$job_id, $user['id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Job not found or unauthorized']);
        exit;
    }

    $stmt = $db->prepare("UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $job_id]);

    echo json_encode(['success' => true, 'message' => "Job status updated to $status"]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
