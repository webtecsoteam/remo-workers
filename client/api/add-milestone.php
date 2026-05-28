<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$contractId = isset($data['contract_id']) ? intval($data['contract_id']) : null;
$description = isset($data['description']) ? trim($data['description']) : '';
$amount = isset($data['amount']) ? floatval($data['amount']) : 0.0;

if (!$contractId) {
    echo json_encode(['success' => false, 'message' => 'Contract ID is required.']);
    exit;
}

if (empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Milestone description is required.']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Milestone amount must be greater than $0.']);
    exit;
}

$db = getDB();
try {
    $db->beginTransaction();

    // 1. Verify contract exists and belongs to this client
    $cStmt = $db->prepare("SELECT * FROM contracts WHERE id = ? AND client_id = ?");
    $cStmt->execute([$contractId, $user['id']]);
    $contract = $cStmt->fetch();

    if (!$contract) {
        throw new Exception('Contract not found or unauthorized.');
    }

    if ($contract['status'] === 'completed' || $contract['status'] === 'paused') {
        // Automatically reactivate contract when adding a new milestone
        $reactivateStmt = $db->prepare("UPDATE contracts SET status = 'active' WHERE id = ?");
        $reactivateStmt->execute([$contractId]);
        $contract['status'] = 'active'; // Update local value
    } elseif ($contract['status'] !== 'active') {
        throw new Exception('Milestones can only be added to active, paused, or completed contracts.');
    }

    // 2. Insert new milestone
    $stmt = $db->prepare("
        INSERT INTO milestones (proposal_id, contract_id, description, amount, status) 
        VALUES (NULL, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$contractId, $description, $amount]);
    $newMilestoneId = $db->lastInsertId();

    // 3. Send automated chat message to the freelancer
    $msgText = "CREATED MILESTONE: I have created a new milestone of $" . number_format($amount, 2) . " for: \"" . htmlspecialchars($description) . "\". You can start working on it now, or wait for me to fund it.";
    $sendMsgStmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $sendMsgStmt->execute([
        $user['id'], 
        $contract['freelancer_id'], 
        $contract['job_id'], 
        $msgText
    ]);

    // 4. Fetch freelancer's email and name for the email notification
    $freelancerStmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $freelancerStmt->execute([$contract['freelancer_id']]);
    $freelancerUser = $freelancerStmt->fetch();
    $freelancerEmail = $freelancerUser['email'] ?? '';
    $freelancerName = $freelancerUser['name'] ?? 'Freelancer';

    if (!empty($freelancerEmail)) {
        require_once __DIR__ . '/../../includes/classes/Mailer.php';
        
        $subject = "New Milestone Created: " . $description;
        $dashboardUrl = baseUrl('remoworkers-dashboard');
        $logoUrl = baseUrl('favicon.png');
        
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
            <div style='text-align: center; margin-bottom: 25px;'>
                <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
                <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
            </div>
            <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
                <p>Hello " . htmlspecialchars($freelancerName) . ",</p>
                <p>The client (<strong>" . htmlspecialchars($user['name']) . "</strong>) has created a new milestone on your contract:</p>
                <div style='background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 20px 0;'>
                    <table style='width: 100%; border-collapse: collapse; font-size: 14.5px;'>
                        <tr>
                            <td style='padding: 6px 0; color: #6b7280; width: 100px;'>Description:</td>
                            <td style='padding: 6px 0; font-weight: bold; color: #111827;'>" . htmlspecialchars($description) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #6b7280;'>Amount:</td>
                            <td style='padding: 6px 0; font-weight: bold; color: #111827;'>$" . number_format($amount, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #6b7280;'>Status:</td>
                            <td style='padding: 6px 0;'><span style='background-color: #f3f4f6; color: #4b5563; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;'>Awaiting Funding</span></td>
                        </tr>
                    </table>
                </div>
                <p>This milestone is currently pending funding. You will receive an automated notification as soon as the client funds it.</p>
                <div style='text-align: center; margin: 35px 0;'>
                    <a href='" . $dashboardUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>View Contract</a>
                </div>
                <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
                <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Team</strong></p>
            </div>
        </div>";

        Mailer::send($freelancerEmail, $subject, $body);

        // Write a copy to a local test log file for easy visibility
        $logDir = __DIR__ . '/../../scratch';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/email_notifications.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] EMAIL TO: " . $freelancerEmail . " (" . $freelancerName . ")\n";
        $logEntry .= "SUBJECT: " . $subject . "\n";
        $logEntry .= "BODY (HTML):\n" . $body . "\n";
        $logEntry .= "--------------------------------------------------\n\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Milestone added successfully!',
        'milestone_id' => $newMilestoneId,
        'milestone' => [
            'id' => $newMilestoneId,
            'contract_id' => $contractId,
            'description' => $description,
            'amount' => $amount,
            'status' => 'pending'
        ]
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
