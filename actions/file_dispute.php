<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/Mailer.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$db = getDB();
$contractId = $_POST['contract_id'] ?? 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if (!$contractId) {
    echo json_encode(['success' => false, 'error' => 'Contract ID is required']);
    exit;
}

if ($reason === '') {
    echo json_encode(['success' => false, 'error' => 'Please provide a reason for the dispute']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Fetch contract details and check if user is client or freelancer
    $stmt = $db->prepare("
        SELECT c.*, j.title as job_title,
               cl.email as client_email, cl.name as client_name,
               fr.email as freelancer_email, fr.name as freelancer_name
        FROM contracts c
        JOIN jobs j ON c.job_id = j.id
        JOIN users cl ON c.client_id = cl.id
        JOIN users fr ON c.freelancer_id = fr.id
        WHERE c.id = ?
    ");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        throw new Exception("Contract not found.");
    }

    if ($contract['client_id'] != $user['id'] && $contract['freelancer_id'] != $user['id']) {
        throw new Exception("You are not authorized to file a dispute for this contract.");
    }

    if ($contract['status'] === 'disputed') {
        throw new Exception("A dispute has already been filed for this contract.");
    }

    if ($contract['status'] === 'cancelled') {
        throw new Exception("Cannot file a dispute on a cancelled contract.");
    }

    $isClient = (int)$contract['client_id'] === (int)$user['id'];
    if ($contract['status'] === 'completed' && !$isClient) {
        throw new Exception("Cannot file a dispute on a completed contract.");
    }

    // 2. Insert dispute record
    $insDispute = $db->prepare("
        INSERT INTO disputes (contract_id, raised_by, reason, status)
        VALUES (?, ?, ?, 'open')
    ");
    $insDispute->execute([$contractId, $user['id'], $reason]);

    // 3. Update contract status to 'disputed'
    $updContract = $db->prepare("UPDATE contracts SET status = 'disputed', updated_at = NOW() WHERE id = ?");
    $updContract->execute([$contractId]);

    // 4. Send chat message
    $msgText = "⚠️ **Dispute Filed**\nA dispute has been filed by **" . htmlspecialchars($user['name']) . "** for this contract.\n\n**Reason:**\n" . htmlspecialchars($reason) . "\n\n*Our arbitration team has been notified to mediate this contract.*";
    
    $sendMsg = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    // Message sender is the system/user who raised it, receiver is the other party
    $receiverId = ($user['id'] == $contract['client_id']) ? $contract['freelancer_id'] : $contract['client_id'];
    $sendMsg->execute([$user['id'], $receiverId, $contract['job_id'], $msgText]);

    $db->commit();

    // 5. Send notification emails
    $logoUrl = baseUrl('favicon.png');
    $subject = "Dispute Filed: " . $contract['job_title'];
    
    // Email to Client
    $clientBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($contract['client_name']) . ",</p>
            <p style='color: #dc2626; font-size: 18px; font-weight: bold; margin-bottom: 20px;'>Dispute Filed on Contract</p>
            <p>A dispute has been officially filed on your contract for: <strong>" . htmlspecialchars($contract['job_title']) . "</strong>.</p>
            
            <p><strong>Filed By:</strong> " . htmlspecialchars($user['name']) . "<br>
            <strong>Reason for Dispute:</strong><br>
            <span style='display: block; background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 12px; margin-top: 8px; font-style: italic; color: #7f1d1d;'>
                " . nl2br(htmlspecialchars($reason)) . "
            </span></p>

            <p>The contract has been locked (marked as Disputed). RemoWorkers support and arbitration team will review your chat logs, milestones, and work logs within 24-48 hours to make a fair resolution.</p>
            
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Arbitration Team</strong></p>
        </div>
    </div>";

    // Email to Freelancer
    $freelancerBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($contract['freelancer_name']) . ",</p>
            <p style='color: #dc2626; font-size: 18px; font-weight: bold; margin-bottom: 20px;'>Dispute Filed on Contract</p>
            <p>A dispute has been officially filed on your contract for: <strong>" . htmlspecialchars($contract['job_title']) . "</strong>.</p>
            
            <p><strong>Filed By:</strong> " . htmlspecialchars($user['name']) . "<br>
            <strong>Reason for Dispute:</strong><br>
            <span style='display: block; background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 12px; margin-top: 8px; font-style: italic; color: #7f1d1d;'>
                " . nl2br(htmlspecialchars($reason)) . "
            </span></p>

            <p>The contract status is updated to Disputed. RemoWorkers support and arbitration team will review your chat logs, milestones, and work logs within 24-48 hours to make a fair resolution.</p>
            
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Arbitration Team</strong></p>
        </div>
    </div>";

    try {
        Mailer::send($contract['client_email'], $subject, $clientBody);
        Mailer::send($contract['freelancer_email'], $subject, $freelancerBody);
    } catch (Exception $mailEx) {
        error_log("Dispute email notification failed: " . $mailEx->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Dispute raised successfully. Support team has been notified.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
