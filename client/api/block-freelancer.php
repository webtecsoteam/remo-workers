<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/client_blocks.php';

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
    $check = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'freelancer'");
    $check->execute([$freelancerId]);
    if (!$check->fetch()) {
        throw new Exception('Freelancer not found.');
    }

    $stmt = $db->prepare(
        'INSERT IGNORE INTO client_blocked_freelancers (client_id, freelancer_id) VALUES (?, ?)'
    );
    $stmt->execute([(int) $user['id'], $freelancerId]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Freelancer blocked. They can no longer message you.',
    ]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
