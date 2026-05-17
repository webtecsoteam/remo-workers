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

    if ($paymentMethod === 'wallet') {
        // Check client balance
        $balanceStmt = $db->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
        $balanceStmt->execute([$user['id']]);
        $clientBalance = (float)$balanceStmt->fetchColumn();

        if ($clientBalance < $amount) {
            throw new Exception('Insufficient wallet funds. Please fund using a card or top up your account.');
        }

        // Deduct from client
        $deductStmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $deductStmt->execute([$amount, $user['id']]);
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
        INSERT INTO payments (transaction_id, payer_id, payee_id, job_id, amount, status, payment_method) 
        VALUES (?, ?, ?, ?, ?, 'pending', ?)
    ");
    $pStmt->execute([
        $transactionId,
        $user['id'],
        $milestone['freelancer_id'],
        $milestone['job_id'],
        $amount,
        $paymentMethod === 'wallet' ? 'Wallet' : 'Credit Card'
    ]);

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Milestone accepted and funded successfully!',
        'new_balance' => ($paymentMethod === 'wallet') ? ($clientBalance - $amount) : null
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
