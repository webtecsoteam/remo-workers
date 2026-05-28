<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$db = getDB();

$jobId = $_POST['job_id'] ?? 0;
$bidAmount = (float)($_POST['bid_amount'] ?? 0);
$coverLetter = $_POST['cover_letter'] ?? '';

if (!$jobId || !$bidAmount || empty($coverLetter)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

try {
    // Check if already applied
    $check = $db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
    $check->execute([$jobId, $user['id']]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'You have already applied for this job']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $jobId,
        $user['id'],
        $bidAmount,
        $coverLetter
    ]);

    echo json_encode(['success' => true, 'message' => 'Proposal submitted successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
