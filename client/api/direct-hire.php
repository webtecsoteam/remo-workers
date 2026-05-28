<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

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

if (!Auth::isIdentityVerified($freelancer)) {
    echo json_encode(['success' => false, 'message' => 'This freelancer has not completed identity verification.']);
    exit;
}

// 2. Verify job exists, is open/active and belongs to this client
$jobStmt = $db->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ? AND status IN ('open', 'in_progress')");
$jobStmt->execute([$jobId, $user['id']]);
$job = $jobStmt->fetch();
if (!$job) {
    echo json_encode(['success' => false, 'message' => 'Selected job is either closed or unauthorized.']);
    exit;
}

if ($contractType === 'hourly') {
    $balanceError = Auth::hourlyContractBalanceError($user['id'], $db);
    if ($balanceError) {
        echo json_encode(['success' => false, 'message' => $balanceError]);
        exit;
    }
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

    // Send congratulatory email to the freelancer
    $subject = "Congratulations! You've been hired on RemoWorkers";
    $contractUrl = baseUrl('remoworkers-dashboard?page=contracts');
    $logoUrl = baseUrl('favicon.png');
    
    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($freelancer['name']) . ",</p>
            <p style='font-size: 18px; color: #14a800; font-weight: bold; margin-bottom: 20px;'>Congratulations! You've been hired!</p>
            <p><strong>" . htmlspecialchars($user['name']) . "</strong> has hired you for the project: <strong style='color: #111827;'>" . htmlspecialchars($job['title']) . "</strong>.</p>
            
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f9fafb; border-radius: 8px; overflow: hidden;'>
                <tr>
                    <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #4b5563; width: 40%;'>Contract Type:</td>
                    <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #111827; text-transform: capitalize;'>" . htmlspecialchars($contractType) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px 15px; font-weight: bold; color: #4b5563;'>Budget / Rate:</td>
                    <td style='padding: 12px 15px; color: #111827; font-weight: bold;'>$" . number_format($amount, 2) . "</td>
                </tr>
            </table>

            <p>Your contract is now active. You can view your contract details, start tracking time or request milestones directly from your dashboard.</p>

            <div style='text-align: center; margin: 35px 0;'>
                <a href='" . $contractUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Go to My Contracts</a>
            </div>
            
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Team</strong></p>
        </div>
    </div>";

    try {
        Mailer::send($freelancer['email'], $subject, $emailBody);
    } catch (Exception $mailEx) {
        error_log("Direct hire congratulations email failed: " . $mailEx->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Contract activated successfully! Freelancer has been notified.',
        'contract_id' => $contractId
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
