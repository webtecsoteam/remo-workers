<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$jobId = $data['job_id'] ?? null;

if (!$jobId) {
    echo json_encode(['success' => false, 'message' => 'Job ID missing']);
    exit;
}

$db = getDB();
try {
    // Check if already saved
    $stmt = $db->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$user['id'], $jobId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Unsave
        $del = $db->prepare("DELETE FROM saved_jobs WHERE id = ?");
        $del->execute([$existing['id']]);
        echo json_encode(['success' => true, 'action' => 'unsaved']);
    } else {
        // Save
        $ins = $db->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)");
        $ins->execute([$user['id'], $jobId]);
        echo json_encode(['success' => true, 'action' => 'saved']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
