<?php
/**
 * Freelancer security holds: pending earnings awaiting admin release to available balance.
 */

function isApprovablePaymentHold(array $payment): bool
{
    if (($payment['status'] ?? '') !== 'pending') {
        return false;
    }

    $desc = (string)($payment['description'] ?? '');
    if ($desc !== '' && (stripos($desc, 'Withdrawal') === 0 || stripos($desc, 'Withdrawal to') !== false)) {
        return false;
    }
    if ($desc !== '' && stripos($desc, 'Connects') !== false && stripos($desc, 'Purchased') !== false) {
        return false;
    }

    $txn = (string)($payment['transaction_id'] ?? '');
    if (str_starts_with($txn, 'ESC-')) {
        return false;
    }

    if (($payment['payee_role'] ?? '') !== 'freelancer') {
        return false;
    }

    return true;
}

function calculatePaymentNetAmount(array $payment): float
{
    $amount = (float)$payment['amount'];
    $method = (string)($payment['payment_method'] ?? '');
    $storedFee = isset($payment['platform_fee']) ? (float)$payment['platform_fee'] : null;

    if ($method === 'Escrow Release' || ($storedFee !== null && $storedFee > 0)) {
        $fee = $storedFee ?? 0.0;
    } else {
        $feePercent = (float)getPlatformSetting('freelancer_fee_fixed', 10);
        $fee = $amount * ($feePercent / 100);
    }

    return round($amount - $fee, 2);
}

/**
 * @return array{payment: array, net_amount: float, new_balance: float}
 */
function approvePendingPaymentHold(PDO $db, int $paymentId, ?int $restrictPayeeId = null): array
{
    $stmt = $db->prepare("
        SELECT p.*, payee.role AS payee_role, payee.name AS payee_name, payee.email AS payee_email
        FROM payments p
        JOIN users payee ON p.payee_id = payee.id
        WHERE p.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Payment not found.');
    }

    if ($restrictPayeeId !== null && (int)$payment['payee_id'] !== $restrictPayeeId) {
        throw new Exception('Payment record not found or unauthorized.');
    }

    if (!isApprovablePaymentHold($payment)) {
        throw new Exception('This payment cannot be released (escrow funding, withdrawal, or already processed).');
    }

    if ($payment['status'] !== 'pending') {
        throw new Exception('Payment is already cleared or completed.');
    }

    $netAmount = calculatePaymentNetAmount($payment);

    $update = $db->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
    $update->execute([$paymentId]);

    $uStmt = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
    $uStmt->execute([$netAmount, $payment['payee_id']]);

    $balStmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
    $balStmt->execute([$payment['payee_id']]);
    $newBalance = (float)$balStmt->fetchColumn();

    return [
        'payment' => $payment,
        'net_amount' => $netAmount,
        'new_balance' => $newBalance,
    ];
}

function paymentHoldListSql(): string
{
    return "
        SELECT p.id, p.transaction_id, p.payer_id, p.payee_id, p.job_id,
               p.amount, p.platform_fee, p.payment_method, p.status, p.description, p.created_at,
               payee.name AS freelancer_name, payee.email AS freelancer_email,
               payer.name AS client_name
        FROM payments p
        JOIN users payee ON p.payee_id = payee.id
        LEFT JOIN users payer ON p.payer_id = payer.id
        WHERE p.status = 'pending'
          AND payee.role = 'freelancer'
          AND (p.description IS NULL OR (p.description NOT LIKE 'Withdrawal%' AND p.description NOT LIKE 'Purchased %Connects%'))
          AND p.transaction_id NOT LIKE 'ESC-%'
    ";
}
