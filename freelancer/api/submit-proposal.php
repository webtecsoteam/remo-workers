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

if (!Auth::isEmailVerified($user)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please verify your email before applying to jobs.',
        'code' => 'email_unverified',
    ]);
    exit;
}

if (!Auth::isIdentityVerified($user)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please complete identity verification before applying to jobs.',
        'code' => 'identity_unverified',
    ]);
    exit;
}

if (empty($user['avatar_url'])) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please upload a profile photo before applying to jobs.',
        'code' => 'photo_required',
    ]);
    exit;
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
$coverLetter = trim($data['cover_letter'] ?? '');
$attachments = $data['attachments'] ?? '';
$connectsCost = Auth::CONNECTS_PER_APPLICATION;

if ($jobId <= 0 || $bidAmount <= 0 || $coverLetter === '') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Please complete all required proposal fields.']);
    exit;
}

$db = getDB();
try {
    $db->beginTransaction();

    $check = $db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
    $check->execute([$jobId, $user['id']]);
    if ($check->fetch()) {
        throw new Exception('You have already submitted a proposal for this job.');
    }

    $uStmt = $db->prepare("SELECT connects FROM users WHERE id = ? FOR UPDATE");
    $uStmt->execute([$user['id']]);
    $currentConnects = (int)$uStmt->fetchColumn();

    if ($currentConnects < $connectsCost) {
        throw new Exception('Not enough connects. You need ' . $connectsCost . ' connects to apply.');
    }

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
        $attachments,
    ]);

    $proposalId = $db->lastInsertId();

    if (isset($data['milestones']) && is_array($data['milestones'])) {
        $mStmt = $db->prepare("INSERT INTO milestones (proposal_id, description, amount, status) VALUES (?, ?, ?, 'pending')");
        foreach ($data['milestones'] as $ms) {
            $desc = trim($ms['description'] ?? '');
            $amt = (float)($ms['amount'] ?? 0);
            if ($desc && $amt > 0) {
                $mStmt->execute([$proposalId, $desc, $amt]);
            }
        }
    }

    $upd = $db->prepare("UPDATE users SET connects = connects - ? WHERE id = ?");
    $upd->execute([$connectsCost, $user['id']]);

    // Log connects deduction in connects_history
    $jobStmt = $db->prepare("SELECT title FROM jobs WHERE id = ?");
    $jobStmt->execute([$jobId]);
    $jobTitle = $jobStmt->fetchColumn() ?: 'Job Application';
    
    $logConnects = $db->prepare("
        INSERT INTO connects_history (user_id, action, description, amount) 
        VALUES (?, 'proposal_submission', ?, ?)
    ");
    $logConnects->execute([
        $user['id'], 
        'Applied to Job: ' . $jobTitle, 
        -$connectsCost
    ]);

    $db->commit();
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Proposal submitted!',
        'id' => $proposalId,
        'new_connects' => $currentConnects - $connectsCost,
    ]);
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
