<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/classes/Mailer.php';

header('Content-Type: application/json');

$suspendedError = Auth::suspendedClientError();
if ($suspendedError) {
    echo json_encode(['success' => false, 'message' => $suspendedError]);
    exit;
}

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$freelancerId = isset($data['freelancer_id']) ? (int)$data['freelancer_id'] : 0;
$jobId = isset($data['job_id']) ? (int)$data['job_id'] : 0;
$message = isset($data['message']) ? trim($data['message']) : '';

if (!$freelancerId || !$jobId) {
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

if (!Auth::isIdentityVerified($freelancer)) {
    echo json_encode(['success' => false, 'message' => 'This freelancer has not completed identity verification.']);
    exit;
}

// 2. Verify job exists, is open and belongs to this client
$jobStmt = $db->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ? AND status = 'open'");
$jobStmt->execute([$jobId, $user['id']]);
$job = $jobStmt->fetch();
if (!$job) {
    echo json_encode(['success' => false, 'message' => 'Selected job is either closed, in progress, or unauthorized.']);
    exit;
}

// 3. Check if an invitation already exists for this job and freelancer
$checkStmt = $db->prepare("SELECT id FROM job_invitations WHERE job_id = ? AND freelancer_id = ?");
$checkStmt->execute([$jobId, $freelancerId]);
if ($checkStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already invited this freelancer to this job.']);
    exit;
}

try {
    $db->beginTransaction();

    // 4. Create the invitation record
    $inviteStmt = $db->prepare("
        INSERT INTO job_invitations (job_id, client_id, freelancer_id, message, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $inviteStmt->execute([
        $jobId,
        $user['id'],
        $freelancerId,
        $message ?: "I would like to invite you to apply to my job post: " . $job['title']
    ]);
    $invitationId = $db->lastInsertId();

    // 5. Send automatic invite message in chat thread
    $msgText = "AUTOMATED MESSAGE: I have invited you to apply to my job post **" . htmlspecialchars($job['title']) . "**.";
    if ($message) {
        $msgText .= "\n\n**Invitation Message:**\n" . $message;
    }
    
    $sendMsgStmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $sendMsgStmt->execute([$user['id'], $freelancerId, $jobId, $msgText]);

    $db->commit();

    // 6. Send the styled email notification to the freelancer
    $subject = "You've been invited to apply to a job on RemoWorkers";
    $jobUrl = baseUrl('remoworkers-dashboard/j/' . encodeJobId($jobId) . '?apply=1');
    $logoUrl = baseUrl('favicon.png');
    
    $budgetFormatted = "$" . number_format($job['budget']);
    $budgetTypeFormatted = $job['budget_type'] === 'fixed' ? 'Fixed Price' : 'Hourly Rate';
    
    $customMessageHtml = "";
    if ($message) {
        $customMessageHtml = "
        <div style='background-color: #f9fafb; border-left: 4px solid #14a800; border-radius: 4px; padding: 15px; margin: 20px 0; font-style: italic; color: #4b5563; font-size: 14px;'>
            \"" . nl2br(htmlspecialchars($message)) . "\"
        </div>";
    }

    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($freelancer['name']) . ",</p>
            <p><strong>" . htmlspecialchars($user['name']) . "</strong> has invited you to apply for their job post: <strong style='color: #14a800;'>" . htmlspecialchars($job['title']) . "</strong>.</p>
            
            " . $customMessageHtml . "

            <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f9fafb; border-radius: 8px; overflow: hidden;'>
                <tr>
                    <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #4b5563; width: 40%;'>Job Type:</td>
                    <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #111827;'>" . $budgetTypeFormatted . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px 15px; font-weight: bold; color: #4b5563;'>Budget:</td>
                    <td style='padding: 12px 15px; color: #111827;'>" . $budgetFormatted . "</td>
                </tr>
            </table>

            <div style='text-align: center; margin: 35px 0;'>
                <a href='" . $jobUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>View Job & Apply</a>
            </div>
            
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>You received this email because you are a registered freelancer on RemoWorkers.<br>Best regards,<br><strong>The RemoWorkers Team</strong></p>
        </div>
    </div>";

    Mailer::send($freelancer['email'], $subject, $emailBody);

    echo json_encode([
        'success' => true,
        'message' => 'Invitation sent successfully! Freelancer has been notified.'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
