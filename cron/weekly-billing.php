<?php
/**
 * Upwork Clone - Automated Weekly Hourly Billing Cron Job
 * Recommended Schedule: Run every Sunday at midnight (00:00 UTC)
 * 
 * Flow:
 * 1. Finds all active hourly contracts.
 * 2. Fetches all unbilled ('pending') work logs for each contract.
 * 3. Charges the client's wallet balance.
 * 4. Deducts 10% platform fee.
 * 5. Transfers net funds to freelancer's 'pending' processing hold.
 * 6. Marks work logs as 'approved' (billed).
 * 7. Automatically pauses the contract if the client has insufficient balance (billing issue).
 */

require_once __DIR__ . '/../includes/config.php';

if (php_sapi_name() !== 'cli') {
    // Optional: Protect cron endpoint if triggered via browser with a security token
    $token = $_GET['token'] ?? null;
    if ($token !== 'billing_secret_token_123') {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
        exit;
    }
}

header('Content-Type: application/json');

try {
    $db = getDB();
    $db->beginTransaction();

    // 1. Fetch all active hourly contracts
    $contractsStmt = $db->query("
        SELECT c.*, u.balance AS client_balance, u.email AS client_email, f.name AS freelancer_name 
        FROM contracts c
        JOIN users u ON c.client_id = u.id
        JOIN users f ON c.freelancer_id = f.id
        WHERE c.contract_type = 'hourly' AND c.status = 'active'
    ");
    $contracts = $contractsStmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];

    foreach ($contracts as $contract) {
        $contractId = $contract['id'];
        $clientId = $contract['client_id'];
        $freelancerId = $contract['freelancer_id'];
        $hourlyRate = (float)$contract['amount']; // Hourly rate

        // 2. Fetch all unbilled (pending) work logs for this contract
        $logsStmt = $db->prepare("SELECT * FROM work_logs WHERE contract_id = ? AND status = 'pending'");
        $logsStmt->execute([$contractId]);
        $workLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($workLogs)) {
            continue; // No hours logged this week for this contract
        }

        // Calculate totals
        $totalHours = 0;
        foreach ($workLogs as $log) {
            $totalHours += (float)$log['hours'];
        }

        $grossAmount = $totalHours * $hourlyRate;
        $freelancerFeePercent = getPlatformSetting('freelancer_fee_hourly', 10);
        $clientFeePercent = getPlatformSetting('client_fee_hourly', 0);
        
        $clientFee = $grossAmount * ($clientFeePercent / 100);
        $totalClientCharge = $grossAmount + $clientFee;
        $platformFee = $grossAmount * ($freelancerFeePercent / 100);
        $netAmount = $grossAmount - $platformFee;

        if ($grossAmount <= 0) {
            continue;
        }

        // Check if client has sufficient balance
        if ($contract['client_balance'] < $totalClientCharge) {
            // Insufficient Balance - Pause contract and notify! (Just like Upwork)
            $pauseStmt = $db->prepare("UPDATE contracts SET status = 'paused' WHERE id = ?");
            $pauseStmt->execute([$contractId]);

            $results[] = [
                'contract_id' => $contractId,
                'status' => 'failed_insufficient_funds',
                'client_id' => $clientId,
                'logged_hours' => $totalHours,
                'gross_amount' => $grossAmount,
                'total_client_charge' => $totalClientCharge,
                'message' => 'Contract paused due to client billing issues.'
            ];
            continue;
        }

        // 3. Charge the client's wallet balance
        $chargeStmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $chargeStmt->execute([$totalClientCharge, $clientId]);

        // 4. Create transaction log with status = 'pending' (moves to freelancer's Processing hold card)
        $transactionId = 'TXN-' . strtoupper(uniqid());
        $pStmt = $db->prepare("
            INSERT INTO payments (transaction_id, payer_id, payee_id, job_id, amount, platform_fee, status, payment_method, description) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', 'Hourly Billing', ?)
        ");
        $description = sprintf("Hourly payment for %s (%.2f hrs tracked) + service fee", $contract['freelancer_name'], $totalHours);
        $pStmt->execute([
            $transactionId,
            $clientId,
            $freelancerId,
            $contract['job_id'],
            $grossAmount,
            $platformFee,
            $description
        ]);

        // 5. Mark all work logs from this week as 'approved' (billed)
        $approveLogsStmt = $db->prepare("UPDATE work_logs SET status = 'approved' WHERE contract_id = ? AND status = 'pending'");
        $approveLogsStmt->execute([$contractId]);

        $results[] = [
            'contract_id' => $contractId,
            'status' => 'success',
            'client_id' => $clientId,
            'freelancer_id' => $freelancerId,
            'logged_hours' => $totalHours,
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
            'transaction_id' => $transactionId
        ];
    }

    // 6. Automatically release pending holds older than 5 days (or release all if test parameter is set)
    $releaseAll = isset($_GET['release_all']) && $_GET['release_all'] === 'true';
    $holdDays = 5;

    if ($releaseAll) {
        $expiredHoldsStmt = $db->query("SELECT * FROM payments WHERE status = 'pending'");
    } else {
        $expiredHoldsStmt = $db->prepare("SELECT * FROM payments WHERE status = 'pending' AND created_at <= NOW() - INTERVAL ? DAY");
        $expiredHoldsStmt->execute([$holdDays]);
    }
    $expiredHolds = $expiredHoldsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $releasedHolds = [];
    foreach ($expiredHolds as $hold) {
        $holdId = $hold['id'];
        $payeeId = $hold['payee_id'];
        $amount = (float)$hold['amount'];
        $fee = (float)$hold['platform_fee'];
        $netAmount = $amount - $fee;

        // Update status to completed
        $updateStmt = $db->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
        $updateStmt->execute([$holdId]);

        // Credit freelancer balance
        $creditStmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $creditStmt->execute([$netAmount, $payeeId]);

        $releasedHolds[] = [
            'payment_id' => $holdId,
            'freelancer_id' => $payeeId,
            'gross_amount' => $amount,
            'net_amount' => $netAmount,
            'description' => $hold['description']
        ];
    }

    $db->commit();
    echo json_encode([
        'success' => true,
        'processed_at' => date('Y-m-d H:i:s'),
        'billing_results' => $results,
        'released_holds' => $releasedHolds
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
