<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/classes/Mailer.php';

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
$applyAs = strtolower(trim((string)($data['apply_as'] ?? '')));
$applyAs = in_array($applyAs, ['individual', 'agency'], true) ? $applyAs : '';
$connectsCost = Auth::CONNECTS_PER_APPLICATION;

if ($jobId <= 0 || $bidAmount <= 0 || $coverLetter === '') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Please complete all required proposal fields.']);
    exit;
}

$db = getDB();
ensureAgencySchema();
$activeAgency = getActiveAgencyForUser((int)$user['id']);
$canApplyAsAgency = !empty($activeAgency['id']);
if ($applyAs === 'agency' && !$canApplyAsAgency) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Agency profile not found. Create or join an agency first.']);
    exit;
}
if ($applyAs === '') {
    $applyAs = (!empty($user['account_mode']) && $user['account_mode'] === 'agency' && $canApplyAsAgency) ? 'agency' : 'individual';
}
$agencyIdForProposal = ($applyAs === 'agency' && $canApplyAsAgency) ? (int)$activeAgency['id'] : null;

// Check if there is a pending invitation to bypass connects deduction
$hasInvite = false;
try {
    $inviteCheck = $db->prepare("SELECT id FROM job_invitations WHERE job_id = ? AND freelancer_id = ? AND status = 'pending'");
    $inviteCheck->execute([$jobId, $user['id']]);
    if ($inviteCheck->fetch()) {
        $hasInvite = true;
    }
} catch (Exception $e) {}

$connectsCost = $hasInvite ? 0 : Auth::CONNECTS_PER_APPLICATION;

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
        INSERT INTO proposals (job_id, freelancer_id, agency_id, bid_amount, cover_letter, estimated_days, attachments, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $jobId,
        (int)$user['id'],
        $agencyIdForProposal,
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

    // Check if there is a pending invitation for this job & freelancer
    $inviteQuery = $db->prepare("
        SELECT i.id, i.client_id, u.email as client_email, u.name as client_name, j.title as job_title
        FROM job_invitations i
        JOIN users u ON i.client_id = u.id
        JOIN jobs j ON i.job_id = j.id
        WHERE i.job_id = ? AND i.freelancer_id = ? AND i.status = 'pending'
    ");
    $inviteQuery->execute([$jobId, $user['id']]);
    $pendingInvite = $inviteQuery->fetch(PDO::FETCH_ASSOC);

    if ($pendingInvite) {
        $acceptInvite = $db->prepare("UPDATE job_invitations SET status = 'accepted' WHERE id = ?");
        $acceptInvite->execute([$pendingInvite['id']]);

        // Send email to client
        $subject = "Invitation Accepted: " . $user['name'] . " applied to " . $pendingInvite['job_title'];
        $logoUrl = baseUrl('favicon.png');
        $proposalUrl = baseUrl('remoworkers-dashboard?page=proposals');
        
        $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
            <div style='text-align: center; margin-bottom: 25px;'>
                <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
                <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
            </div>
            <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
                <p>Hello " . htmlspecialchars($pendingInvite['client_name']) . ",</p>
                <p>Great news! Freelancer <strong>" . htmlspecialchars($user['name']) . "</strong> has <strong>accepted</strong> your invitation and submitted a proposal for your job: <strong style='color: #14a800;'>" . htmlspecialchars($pendingInvite['job_title']) . "</strong>.</p>
                
                <p>You can review their proposal and start interviewing them on your dashboard.</p>

                <div style='text-align: center; margin: 35px 0;'>
                    <a href='" . $proposalUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Review Proposal</a>
                </div>
                
                <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
                <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Team</strong></p>
            </div>
        </div>";

        try {
            Mailer::send($pendingInvite['client_email'], $subject, $emailBody);
        } catch (Exception $mailEx) {
            error_log("Proposal submission email failed: " . $mailEx->getMessage());
        }
    }

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
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
