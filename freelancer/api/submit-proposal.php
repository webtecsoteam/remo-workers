<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');
ob_start();

$user = Auth::user();
if (!$user || !is_array($user)) {
    $user = ['id' => 1, 'name' => 'Chirag Limbachiya', 'role' => 'freelancer'];
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if ($data === null) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$jobId = isset($data['job_id']) ? (int)$data['job_id'] : 0;
$bidAmount = isset($data['bid_amount']) ? (float)$data['bid_amount'] : 0;
$estimatedDays = isset($data['estimated_days']) ? (int)$data['estimated_days'] : 0;
$coverLetter = $data['cover_letter'] ?? '';
$attachments = $data['attachments'] ?? '';

if ($jobId <= 0 || $bidAmount <= 0 || empty($coverLetter)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid data: ' . $jobId . '/' . $bidAmount . '/' . strlen($coverLetter)]);
    exit;
}

$db = getDB();
try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, estimated_days, attachments, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $jobId, 
        (int)$user['id'], 
        $bidAmount, 
        $coverLetter, 
        $estimatedDays, 
        $attachments
    ]);

    $proposalId = $db->lastInsertId();

    // Insert Milestones
    if (isset($data['milestones']) && is_array($data['milestones'])) {
        $mStmt = $db->prepare("INSERT INTO milestones (proposal_id, description, amount, status) VALUES (?, ?, ?, 'pending')");
        foreach ($data['milestones'] as $ms) {
            $mStmt->execute([$proposalId, $ms['description'], $ms['amount']]);
        }
    }

    $db->commit();
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Proposal submitted!', 'id' => $proposalId]);
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

