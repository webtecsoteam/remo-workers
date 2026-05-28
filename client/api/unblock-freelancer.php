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
$freelancerId = isset($data['freelancer_id']) ? (int) $data['freelancer_id'] : 0;

if ($freelancerId <= 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid freelancer.']);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare(
        'DELETE FROM client_blocked_freelancers WHERE client_id = ? AND freelancer_id = ?'
    );
    $stmt->execute([(int) $user['id'], $freelancerId]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Freelancer unblocked. You can chat again.',
    ]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
