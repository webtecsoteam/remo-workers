<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$freelancerId = isset($data['freelancer_id']) ? (int)$data['freelancer_id'] : 0;
$jobId = isset($data['job_id']) ? (int)$data['job_id'] : 0;
$contractType = isset($data['contract_type']) ? $data['contract_type'] : 'hourly';
$amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
$message = isset($data['message']) ? trim($data['message']) : '';

if (!$freelancerId || !$jobId || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters. Please complete all fields.']);
    exit;
}

$db = getDB();

// 1. Verify freelancer exists and is active
$freelancerStmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'freelancer'");
$freelancerStmt->execute([$freelancerId]);
$freelancer = $freelancerStmt->fetch();
if (!$freelancer) {
    echo json_encode(['success' => false, 'message' => 'Freelancer not found.']);
    exit;
}

// 2. Verify job exists, is open and belongs to this client
$jobStmt = $db->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ? AND status = 'open'");
$jobStmt->execute([$jobId, $user['id']]);
$job = $jobStmt->fetch();
if (!$job) {
    echo json_encode(['success' => false, 'message' => 'Selected job is either closed or unauthorized.']);
    exit;
}

try {
    $db->beginTransaction();

    // 3. Create a pre-accepted proposal
    $propStmt = $db->prepare("
        INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status)
        VALUES (?, ?, ?, ?, 'accepted')
    ");
    $propStmt->execute([
        $jobId,
        $freelancerId,
        $amount,
        $message ?: "Direct hire offer sent by " . $user['name']
    ]);
    $proposalId = $db->lastInsertId();

    // 4. Create an active contract
    $contractStmt = $db->prepare("
        INSERT INTO contracts (job_id, client_id, freelancer_id, proposal_id, amount, contract_type, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");
    $contractStmt->execute([
        $jobId,
        $user['id'],
        $freelancerId,
        $proposalId,
        $amount,
        $contractType
    ]);
    $contractId = $db->lastInsertId();

    // 5. Move job to in_progress status
    $updateJobStmt = $db->prepare("UPDATE jobs SET status = 'in_progress' WHERE id = ?");
    $updateJobStmt->execute([$jobId]);

    // 6. Send automatic welcome message in chat thread
    $msgText = "Hello " . htmlspecialchars($freelancer['name']) . "! I have hired you directly for my project **" . htmlspecialchars($job['title']) . "**. The contract is now active.";
    if ($message) {
        $msgText .= "\n\n**Offer Terms & Message:**\n" . $message;
    }
    $sendMsgStmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $sendMsgStmt->execute([$user['id'], $freelancerId, $jobId, $msgText]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Contract activated successfully! Freelancer has been notified.',
        'contract_id' => $contractId
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
