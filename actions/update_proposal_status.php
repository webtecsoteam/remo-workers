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

$proposal_id = intval($_POST['proposal_id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$proposal_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Proposal ID and status are required']);
    exit;
}

$allowed = ['pending', 'shortlisted', 'archived', 'rejected'];
if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

$db = getDB();
try {
    // Verify client owns the job for this proposal
    $check = $db->prepare("SELECT p.id FROM proposals p JOIN jobs j ON p.job_id = j.id WHERE p.id = ? AND j.client_id = ?");
    $check->execute([$proposal_id, $user['id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Proposal not found or unauthorized']);
        exit;
    }

    $stmt = $db->prepare("UPDATE proposals SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $proposal_id]);

    echo json_encode(['success' => true, 'message' => "Proposal status updated to $status"]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
