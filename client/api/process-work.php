<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$log_id = $input['log_id'] ?? null;
$action = $input['action'] ?? null; // 'approved' or 'rejected'

if (!$log_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Fetch the work log and ensure it belongs to this client's contract
    $stmt = $db->prepare("
        SELECT wl.*, c.id as contract_id, c.client_id, c.freelancer_id, c.job_id
        FROM work_logs wl
        JOIN contracts c ON wl.contract_id = c.id
        WHERE wl.id = ? AND c.client_id = ? AND wl.status = 'pending'
    ");
    $stmt->execute([$log_id, $user['id']]);
    $log = $stmt->fetch();

    if (!$log) {
        throw new Exception('Work log not found or already processed.');
    }

    // Update work log status
    $updateStmt = $db->prepare("UPDATE work_logs SET status = ? WHERE id = ?");
    $updateStmt->execute([$action, $log_id]);

    if ($action === 'approved') {
        // Create payment
        $payStmt = $db->prepare("
            INSERT INTO payments (transaction_id, job_id, payer_id, payee_id, amount, status, payment_method)
            VALUES (?, ?, ?, ?, ?, 'completed', ?)
        ");
        $transaction_id = 'TRX-' . strtoupper(uniqid());
        $payStmt->execute([
            $transaction_id,
            $log['job_id'],
            $log['client_id'],
            $log['freelancer_id'],
            $log['amount'],
            'Upwork Balance'
        ]);

        // Update balances
        // Deduct from client
        $dStmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $dStmt->execute([$log['amount'], $log['client_id']]);

        // Add to freelancer
        $fStmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $fStmt->execute([$log['amount'], $log['freelancer_id']]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Work log ' . $action . ' successfully.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
