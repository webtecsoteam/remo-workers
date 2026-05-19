<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');
ob_start();

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'freelancer') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if ($data === null) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$invitationId = isset($data['invitation_id']) ? (int)$data['invitation_id'] : 0;

if ($invitationId <= 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid invitation ID.']);
    exit;
}

$db = getDB();
try {
    // Verify invitation belongs to this freelancer and is pending
    $check = $db->prepare("SELECT id FROM job_invitations WHERE id = ? AND freelancer_id = ? AND status = 'pending'");
    $check->execute([$invitationId, $user['id']]);
    if (!$check->fetch()) {
        throw new Exception('Invitation not found or already processed.');
    }

    // Update status to declined
    $stmt = $db->prepare("UPDATE job_invitations SET status = 'declined' WHERE id = ?");
    $stmt->execute([$invitationId]);

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Invitation declined successfully.']);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
