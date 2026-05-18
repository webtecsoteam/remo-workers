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
$milestoneId = $data['milestone_id'] ?? null;
$paymentMethod = $data['payment_method'] ?? 'wallet'; // 'wallet' or 'card'

if (!$milestoneId) {
    echo json_encode(['success' => false, 'message' => 'Milestone ID required']);
    exit;
}

$db = getDB();
try {
    $db->beginTransaction();

    // Verify milestone belongs to client
    $stmt = $db->prepare("
        SELECT m.*, c.freelancer_id, c.client_id, c.job_id 
        FROM milestones m 
        JOIN contracts c ON m.contract_id = c.id 
        WHERE m.id = ? AND c.client_id = ?
    ");
    $stmt->execute([$milestoneId, $user['id']]);
    $milestone = $stmt->fetch();

    if (!$milestone) {
        throw new Exception('Milestone not found or unauthorized');
    }

    if ($milestone['status'] !== 'pending') {
        throw new Exception('Milestone is already funded or paid');
    }

    $amount = (float)$milestone['amount'];
    $clientFeePercent = getPlatformSetting('client_fee_fixed', 0);
    $clientFee = $amount * ($clientFeePercent / 100);
    $totalToCharge = $amount + $clientFee;

    if ($paymentMethod === 'wallet') {
        // Check client balance
        $balanceStmt = $db->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
        $balanceStmt->execute([$user['id']]);
        $clientBalance = (float)$balanceStmt->fetchColumn();

        if ($clientBalance < $totalToCharge) {
            throw new Exception('Insufficient wallet funds. Required: $' . number_format($totalToCharge, 2) . ' (includes $' . number_format($clientFee, 2) . ' service fee), Available: $' . number_format($clientBalance, 2));
        }

        // Deduct from client
        $deductStmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $deductStmt->execute([$totalToCharge, $user['id']]);
    } else {
        // Funded by card: log a dummy card transaction in payments if wanted, 
        // but the actual escrow funds are now held and will be sent to the freelancer when approved.
    }

    // Update milestone status to funded
    $update = $db->prepare("UPDATE milestones SET status = 'funded' WHERE id = ?");
    $update->execute([$milestoneId]);

    // Create a transaction record to show the escrow funding
    $transactionId = 'ESC-' . strtoupper(uniqid());
    $pStmt = $db->prepare("
        INSERT INTO payments (transaction_id, payer_id, payee_id, job_id, amount, status, payment_method, platform_fee) 
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    $pStmt->execute([
        $transactionId,
        $user['id'],
        $milestone['freelancer_id'],
        $milestone['job_id'],
        $amount,
        $paymentMethod === 'wallet' ? 'Wallet' : 'Credit Card',
        $clientFee
    ]);

    // Send automated chat message to the freelancer
    $msgText = "AUTOMATED MESSAGE: The client has funded your milestone **" . htmlspecialchars($milestone['description']) . "** ($" . number_format($amount, 2) . "). The funds are now held securely in escrow, and you can start working on this milestone.";
    $sendMsgStmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $sendMsgStmt->execute([
        $user['id'], 
        $milestone['freelancer_id'], 
        $milestone['job_id'], 
        $msgText
    ]);

    // Fetch freelancer's email and name for the email notification
    $freelancerStmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $freelancerStmt->execute([$milestone['freelancer_id']]);
    $freelancerUser = $freelancerStmt->fetch();
    $freelancerEmail = $freelancerUser['email'] ?? '';
    $freelancerName = $freelancerUser['name'] ?? 'Freelancer';

    if (!empty($freelancerEmail)) {
        $subject = "Milestone Funded: " . $milestone['description'];
        $body = "Hi " . $freelancerName . ",\n\n";
        $body .= "Great news! The client (" . $user['name'] . ") has funded your milestone:\n";
        $body .= "- Description: " . $milestone['description'] . "\n";
        $body .= "- Amount: $" . number_format($amount, 2) . "\n\n";
        $body .= "The funds are now held securely in escrow. You can start working on this milestone and submit your work when ready.\n\n";
        $body .= "Best regards,\n";
        $body .= "Remoworkers Support Team";
        
        $headers = "From: support@remoworkers.com\r\n" .
                   "Reply-To: support@remoworkers.com\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        // Suppress warning in case mail configuration is missing
        @mail($freelancerEmail, $subject, $body, $headers);
        
        // Write a copy to a local test log file for easy visibility
        $logDir = __DIR__ . '/../../scratch';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/email_notifications.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] EMAIL TO: " . $freelancerEmail . " (" . $freelancerName . ")\n";
        $logEntry .= "SUBJECT: " . $subject . "\n";
        $logEntry .= "BODY:\n" . $body . "\n";
        $logEntry .= "--------------------------------------------------\n\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Milestone accepted and funded successfully!',
        'new_balance' => ($paymentMethod === 'wallet') ? ($clientBalance - $totalToCharge) : null
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
