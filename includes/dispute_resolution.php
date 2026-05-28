<?php
/**
 * Admin dispute resolution: pay freelancer or refund client.
 */
require_once __DIR__ . '/classes/Mailer.php';

function disputeResolutionEmailWrap(string $innerHtml): string
{
    $logoUrl = baseUrl('favicon.png');
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='{$logoUrl}' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>{$innerHtml}
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Arbitration Team</strong></p>
        </div>
    </div>";
}

function fetchOpenDisputeForUpdate(PDO $db, int $disputeId): ?array
{
    $stmt = $db->prepare("
        SELECT d.*,
               c.id AS contract_id, c.job_id, c.client_id, c.freelancer_id,
               c.amount AS contract_amount, c.status AS contract_status,
               j.title AS job_title,
               cl.name AS client_name, cl.email AS client_email,
               fr.name AS freelancer_name, fr.email AS freelancer_email
        FROM disputes d
        JOIN contracts c ON d.contract_id = c.id
        JOIN jobs j ON c.job_id = j.id
        JOIN users cl ON c.client_id = cl.id
        JOIN users fr ON c.freelancer_id = fr.id
        WHERE d.id = ? AND d.status = 'open'
        FOR UPDATE
    ");
    $stmt->execute([$disputeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getPendingContractPayments(PDO $db, int $jobId, int $clientId, int $freelancerId): array
{
    $stmt = $db->prepare("
        SELECT * FROM payments
        WHERE job_id = ? AND payer_id = ? AND payee_id = ? AND status = 'pending'
        FOR UPDATE
    ");
    $stmt->execute([$jobId, $clientId, $freelancerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function postDisputeResolutionMessage(PDO $db, array $ctx, string $text, int $adminId): void
{
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$adminId, $ctx['client_id'], $ctx['job_id'], $text]);
    $stmt->execute([$adminId, $ctx['freelancer_id'], $ctx['job_id'], $text]);
}

function resolveDisputePayFreelancer(PDO $db, int $disputeId, string $notes, array $adminUser): array
{
    $ctx = fetchOpenDisputeForUpdate($db, $disputeId);
    if (!$ctx) {
        throw new Exception('Dispute not found or already resolved.');
    }

    $payments = getPendingContractPayments(
        $db,
        (int)$ctx['job_id'],
        (int)$ctx['client_id'],
        (int)$ctx['freelancer_id']
    );

    $totalNet = 0.0;
    $feePercent = (float)getPlatformSetting('freelancer_fee_fixed', 10);

    foreach ($payments as $payment) {
        $amount = (float)$payment['amount'];
        $storedFee = (float)($payment['platform_fee'] ?? 0);
        $method = (string)($payment['payment_method'] ?? '');

        if ($method === 'Escrow Release') {
            $net = $amount - $storedFee;
        } else {
            $net = $amount - ($amount * ($feePercent / 100));
        }

        $credit = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $credit->execute([$net, $ctx['freelancer_id']]);

        $complete = $db->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
        $complete->execute([$payment['id']]);

        $totalNet += $net;
    }

    try {
        $mStmt = $db->prepare("
            UPDATE milestones SET status = 'paid'
            WHERE contract_id = ? AND status IN ('funded', 'completed', 'requested')
        ");
        $mStmt->execute([$ctx['contract_id']]);
    } catch (PDOException $e) {
        // milestone status enum may differ on older DBs
    }

    $resolutionLabel = '[Pay Freelancer]';
    $fullNotes = trim($notes) !== ''
        ? $resolutionLabel . ' ' . $notes
        : $resolutionLabel . ' Funds released to freelancer. Net: $' . number_format($totalNet, 2);

    $db->prepare("UPDATE disputes SET status = 'resolved', resolution_notes = ? WHERE id = ?")
        ->execute([$fullNotes, $disputeId]);

    $db->prepare("UPDATE contracts SET status = 'completed', updated_at = NOW() WHERE id = ?")
        ->execute([$ctx['contract_id']]);

    $msgText = '✅ **Dispute Resolved (Admin)** — Payment of **$' . number_format($totalNet, 2)
        . '** has been released to the freelancer for contract **' . htmlspecialchars($ctx['job_title']) . '**.';
    postDisputeResolutionMessage($db, $ctx, $msgText, (int)$adminUser['id']);

    $subject = 'Dispute Resolved — Payment to Freelancer: ' . $ctx['job_title'];
    $amountLine = '<p><strong>Amount released to freelancer:</strong> $' . number_format($totalNet, 2) . '</p>';

    $clientBody = disputeResolutionEmailWrap(
        '<p>Hello ' . htmlspecialchars($ctx['client_name']) . ',</p>'
        . '<p style="font-weight:bold;color:#14a800;">Dispute closed — freelancer paid</p>'
        . '<p>Our team reviewed the dispute on <strong>' . htmlspecialchars($ctx['job_title']) . '</strong> and released escrowed funds to the freelancer.</p>'
        . $amountLine
        . ($notes ? '<p><strong>Admin notes:</strong><br>' . nl2br(htmlspecialchars($notes)) . '</p>' : '')
    );

    $freelancerBody = disputeResolutionEmailWrap(
        '<p>Hello ' . htmlspecialchars($ctx['freelancer_name']) . ',</p>'
        . '<p style="font-weight:bold;color:#14a800;">Dispute resolved in your favor</p>'
        . '<p>The dispute on <strong>' . htmlspecialchars($ctx['job_title']) . '</strong> has been closed. Escrowed funds have been released to your account balance.</p>'
        . $amountLine
        . ($notes ? '<p><strong>Admin notes:</strong><br>' . nl2br(htmlspecialchars($notes)) . '</p>' : '')
    );

    try {
        if (!empty($ctx['client_email'])) {
            Mailer::send($ctx['client_email'], $subject, $clientBody);
        }
        if (!empty($ctx['freelancer_email'])) {
            Mailer::send($ctx['freelancer_email'], $subject, $freelancerBody);
        }
    } catch (Exception $e) {
        error_log('Dispute pay-freelancer email failed: ' . $e->getMessage());
    }

    return [
        'action' => 'pay_freelancer',
        'amount_released' => $totalNet,
        'payments_processed' => count($payments),
    ];
}

function resolveDisputeRefundClient(PDO $db, int $disputeId, string $notes, array $adminUser): array
{
    $ctx = fetchOpenDisputeForUpdate($db, $disputeId);
    if (!$ctx) {
        throw new Exception('Dispute not found or already resolved.');
    }

    $payments = getPendingContractPayments(
        $db,
        (int)$ctx['job_id'],
        (int)$ctx['client_id'],
        (int)$ctx['freelancer_id']
    );

    $totalRefund = 0.0;
    $refundReason = 'Dispute resolved — refund to client';

    foreach ($payments as $payment) {
        $amount = (float)$payment['amount'];
        $fee = (float)($payment['platform_fee'] ?? 0);
        $refundToClient = $amount + $fee;

        $credit = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $credit->execute([$refundToClient, $ctx['client_id']]);

        $refund = $db->prepare("
            UPDATE payments
            SET status = 'refunded', refunded_at = NOW(), refund_reason = ?
            WHERE id = ?
        ");
        $refund->execute([$refundReason, $payment['id']]);

        $totalRefund += $refundToClient;
    }

    try {
        $mStmt = $db->prepare("
            UPDATE milestones SET status = 'pending'
            WHERE contract_id = ? AND status IN ('funded', 'requested', 'completed')
        ");
        $mStmt->execute([$ctx['contract_id']]);
    } catch (PDOException $e) {
        // ignore enum mismatch
    }

    $resolutionLabel = '[Refund Client]';
    $fullNotes = trim($notes) !== ''
        ? $resolutionLabel . ' ' . $notes
        : $resolutionLabel . ' Refunded to client. Total: $' . number_format($totalRefund, 2);

    $db->prepare("UPDATE disputes SET status = 'resolved', resolution_notes = ? WHERE id = ?")
        ->execute([$fullNotes, $disputeId]);

    $db->prepare("UPDATE contracts SET status = 'cancelled', updated_at = NOW() WHERE id = ?")
        ->execute([$ctx['contract_id']]);

    $msgText = '✅ **Dispute Resolved (Admin)** — A refund of **$' . number_format($totalRefund, 2)
        . '** has been issued to the client for contract **' . htmlspecialchars($ctx['job_title']) . '**.';
    postDisputeResolutionMessage($db, $ctx, $msgText, (int)$adminUser['id']);

    $subject = 'Dispute Resolved — Refund Issued: ' . $ctx['job_title'];
    $amountLine = '<p><strong>Refund amount issued to client:</strong> $' . number_format($totalRefund, 2) . '</p>';

    $clientBody = disputeResolutionEmailWrap(
        '<p>Hello ' . htmlspecialchars($ctx['client_name']) . ',</p>'
        . '<p style="font-weight:bold;color:#2563eb;">Refund issued to your account</p>'
        . '<p>Our team reviewed the dispute on <strong>' . htmlspecialchars($ctx['job_title']) . '</strong> and refunded escrowed funds to your RemoWorkers balance.</p>'
        . $amountLine
        . ($notes ? '<p><strong>Admin notes:</strong><br>' . nl2br(htmlspecialchars($notes)) . '</p>' : '')
    );

    $freelancerBody = disputeResolutionEmailWrap(
        '<p>Hello ' . htmlspecialchars($ctx['freelancer_name']) . ',</p>'
        . '<p style="font-weight:bold;color:#dc2626;">Dispute resolved — refund issued to client</p>'
        . '<p>The dispute on <strong>' . htmlspecialchars($ctx['job_title']) . '</strong> has been closed. '
        . 'After review, <strong>a refund has been issued to the client</strong> for the escrowed contract funds ($'
        . number_format($totalRefund, 2) . ').</p>'
        . '<p>If you have questions about this decision, please contact RemoWorkers support.</p>'
        . ($notes ? '<p><strong>Admin notes:</strong><br>' . nl2br(htmlspecialchars($notes)) . '</p>' : '')
    );

    try {
        if (!empty($ctx['client_email'])) {
            Mailer::send($ctx['client_email'], $subject, $clientBody);
        }
        if (!empty($ctx['freelancer_email'])) {
            Mailer::send($ctx['freelancer_email'], 'Refund Issued to Client — ' . $ctx['job_title'], $freelancerBody);
        }
    } catch (Exception $e) {
        error_log('Dispute refund-client email failed: ' . $e->getMessage());
    }

    return [
        'action' => 'refund_client',
        'amount_refunded' => $totalRefund,
        'payments_processed' => count($payments),
    ];
}
