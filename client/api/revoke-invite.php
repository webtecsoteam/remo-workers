<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');
ob_start();

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'client') {
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
    // Verify invitation belongs to this client and is pending
    $check = $db->prepare("SELECT id FROM job_invitations WHERE id = ? AND client_id = ? AND status = 'pending'");
    $check->execute([$invitationId, $user['id']]);
    if (!$check->fetch()) {
        throw new Exception('Invitation not found or already processed.');
    }

    // Delete or mark status as revoked (let's delete it so it removes from active lists clean, or mark as 'declined'/'revoked')
    // Marking as 'declined' makes sure database history is consistent, or we can just delete it.
    // Let's delete it to keep it simple, or update status = 'declined' so it's not active anymore.
    $stmt = $db->prepare("UPDATE job_invitations SET status = 'declined' WHERE id = ?");
    $stmt->execute([$invitationId]);

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Invitation revoked successfully.']);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
