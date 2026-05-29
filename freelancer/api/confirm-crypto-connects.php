<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/ccpayment_transactions.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$referenceId = isset($data['reference_id']) ? trim((string) $data['reference_id']) : '';

if ($referenceId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing payment reference.']);
    exit;
}

ccpayment_ensure_transactions_table();
$db = getDB();

$stmt = $db->prepare('
    SELECT * FROM ccpayment_transactions
    WHERE reference_id = ? AND user_id = ?
    LIMIT 1
');
$stmt->execute([$referenceId, $user['id']]);
$ccRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ccRow) {
    echo json_encode(['success' => false, 'message' => 'Payment reference not found.']);
    exit;
}

if (($ccRow['status'] ?? '') !== 'completed') {
    $upd = $db->prepare("
        UPDATE ccpayment_transactions
        SET status = 'awaiting_confirm', user_confirmed_at = NOW()
        WHERE reference_id = ? AND user_id = ?
    ");
    $upd->execute([$referenceId, $user['id']]);

    $recordId = (string) ($ccRow['ccpayment_record_id'] ?? '');
    if ($recordId !== '') {
        $resolved = ccpayment_resolve_direct_deposit(
            ['recordId' => $recordId, 'status' => 'Success'],
            $ccRow
        );
        if ($resolved['ok'] && ($ccRow['purpose'] ?? '') === 'connects') {
            $ccRow = $resolved['cc_row'] ?? $ccRow;
            ccpayment_fulfill_connects_purchase(
                $ccRow,
                $recordId,
                null,
                is_array($resolved['record'] ?? null) ? $resolved['record'] : null
            );
            $stmt->execute([$referenceId, $user['id']]);
            $ccRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: $ccRow;
        }
    }
}

$payStmt = $db->prepare('SELECT status FROM payments WHERE transaction_id = ? LIMIT 1');
$payStmt->execute([$referenceId]);
$paymentStatus = $payStmt->fetchColumn();

if ($paymentStatus === 'completed' || ($ccRow['status'] ?? '') === 'completed') {
    $uStmt = $db->prepare('SELECT connects, balance FROM users WHERE id = ?');
    $uStmt->execute([$user['id']]);
    $uData = $uStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'completed' => true,
        'message' => 'Payment confirmed! Your connects have been added.',
        'new_connects' => (int) ($uData['connects'] ?? 0),
        'new_balance' => (float) ($uData['balance'] ?? 0),
        'connects_added' => (int) ($ccRow['connects_amount'] ?? 0),
    ]);
    exit;
}

$detail = '';
if (!empty($ccRow['deposited_usd']) && (float) $ccRow['amount_usd'] > (float) $ccRow['deposited_usd']) {
    $detail = sprintf(
        ' We received $%.2f but this pack requires $%.2f USDT.',
        (float) $ccRow['deposited_usd'],
        (float) $ccRow['amount_usd']
    );
}

echo json_encode([
    'success' => true,
    'completed' => false,
    'pending' => true,
    'message' => 'We have not confirmed your USDT deposit yet. Blockchain confirmations can take a few minutes — please wait and try again shortly.' . $detail,
    'deposited_usd' => isset($ccRow['deposited_usd']) ? (float) $ccRow['deposited_usd'] : null,
    'required_usd' => (float) ($ccRow['amount_usd'] ?? 0),
]);
