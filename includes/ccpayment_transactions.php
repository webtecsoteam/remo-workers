<?php
/**
 * Database helpers for CCPayment reference / transaction logs.
 */

function ccpayment_ensure_transactions_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS ccpayment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reference_id VARCHAR(64) NOT NULL,
            user_id INT NOT NULL,
            payment_id INT NULL,
            purpose VARCHAR(32) NOT NULL DEFAULT 'connects',
            connects_amount INT NULL,
            amount_usd DECIMAL(12, 2) NOT NULL,
            chain VARCHAR(32) NOT NULL DEFAULT 'TRX',
            coin_symbol VARCHAR(16) NOT NULL DEFAULT 'USDT',
            deposit_address VARCHAR(255) NULL,
            memo VARCHAR(255) NULL,
            status ENUM('pending', 'awaiting_confirm', 'completed', 'failed') NOT NULL DEFAULT 'pending',
            ccpayment_record_id VARCHAR(255) NULL,
            webhook_payload JSON NULL,
            api_request JSON NULL,
            api_response JSON NULL,
            user_confirmed_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_ccpayment_reference (reference_id),
            KEY idx_ccpayment_user (user_id),
            KEY idx_ccpayment_status (status),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    ccpayment_ensure_transactions_columns($db);
    $done = true;
}

function ccpayment_ensure_transactions_columns(PDO $db): void
{
    static $columnsDone = false;
    if ($columnsDone) {
        return;
    }

    $columns = [
        'deposited_amount' => 'DECIMAL(18, 8) NULL',
        'deposited_coin' => 'VARCHAR(16) NULL',
        'deposited_usd' => 'DECIMAL(12, 2) NULL',
        'tx_id' => 'VARCHAR(255) NULL',
    ];

    foreach ($columns as $name => $definition) {
        $check = $db->query("SHOW COLUMNS FROM ccpayment_transactions LIKE " . $db->quote($name));
        if (!$check || !$check->fetch()) {
            $db->exec("ALTER TABLE ccpayment_transactions ADD COLUMN {$name} {$definition}");
        }
    }

    $columnsDone = true;
}

function ccpayment_allow_test_coins(): bool
{
    if (env('CCPAYMENT_ALLOW_TEST_COINS', false)) {
        return true;
    }
    return defined('APP_ENV') && APP_ENV === 'development';
}

/**
 * Chain symbol for CCPayment API (e.g. Tron = TRX, not TRON).
 */
function ccpayment_normalize_chain(string $chain): string
{
    $chain = strtoupper(trim($chain));
    return match ($chain) {
        'TRON', 'TRC20' => 'TRX',
        'MATIC' => 'POLYGON',
        default => $chain !== '' ? $chain : 'TRX',
    };
}

/**
 * Human-readable network label for UI (CCPAYMENT_CHAIN values).
 */
function ccpayment_chain_label(string $chain): string
{
    $chain = ccpayment_normalize_chain($chain);
    return match ($chain) {
        'TRX' => 'Tron (TRC20)',
        'POLYGON' => 'Polygon',
        'ETH' => 'Ethereum (ERC20)',
        'BSC' => 'BNB Smart Chain (BEP20)',
        default => $chain,
    };
}

function ccpayment_is_allowed_deposit_coin(string $coinSymbol): bool
{
    $symbol = strtoupper(trim($coinSymbol));
    if ($symbol === 'USDT') {
        return true;
    }
    if (ccpayment_allow_test_coins() && in_array($symbol, ['TETH', 'USDC'], true)) {
        return true;
    }
    return false;
}

/**
 * Estimate USD value of a deposit record returned by getAppDepositRecord.
 */
function ccpayment_deposit_usd_value(array $record): float
{
    $amount = (float) ($record['amount'] ?? 0);
    if ($amount <= 0) {
        return 0.0;
    }

    $symbol = strtoupper((string) ($record['coinSymbol'] ?? ''));
    if ($symbol === 'USDT' || $symbol === 'USDC') {
        return $amount;
    }

    $usdPrice = (float) ($record['coinUSDPrice'] ?? 0);
    if ($usdPrice > 0) {
        return $amount * $usdPrice;
    }

    if (ccpayment_allow_test_coins()) {
        return $amount;
    }

    return 0.0;
}

/**
 * @return array<string, mixed>|null
 */
function ccpayment_find_transaction(string $referenceId): ?array
{
    ccpayment_ensure_transactions_table();
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM ccpayment_transactions WHERE reference_id = ? LIMIT 1');
    $stmt->execute([$referenceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ccpayment_append_webhook_log(int $transactionRowId, array $payload): void
{
    ccpayment_ensure_transactions_table();
    $db = getDB();
    $stmt = $db->prepare('SELECT webhook_payload FROM ccpayment_transactions WHERE id = ?');
    $stmt->execute([$transactionRowId]);
    $existing = $stmt->fetchColumn();
    $log = [];
    if ($existing) {
        $decoded = json_decode((string) $existing, true);
        if (is_array($decoded)) {
            $log = isset($decoded[0]) ? $decoded : [$decoded];
        }
    }
    $log[] = array_merge(['at' => gmdate('c')], $payload);
    $upd = $db->prepare('UPDATE ccpayment_transactions SET webhook_payload = ? WHERE id = ?');
    $upd->execute([json_encode($log, JSON_UNESCAPED_SLASHES), $transactionRowId]);
}

/**
 * Fulfill a connects purchase after CCPayment confirms deposit.
 */
function ccpayment_fulfill_connects_purchase(
    array $ccRow,
    ?string $recordId = null,
    ?array $webhookPayload = null,
    ?array $depositRecord = null
): bool {
    if (($ccRow['status'] ?? '') === 'completed') {
        return true;
    }

    $userId = (int) ($ccRow['user_id'] ?? 0);
    $connectsAmount = (int) ($ccRow['connects_amount'] ?? 0);
    $price = (float) ($ccRow['amount_usd'] ?? 0);
    $referenceId = (string) ($ccRow['reference_id'] ?? '');
    $depositedUsd = isset($ccRow['deposited_usd']) ? (float) $ccRow['deposited_usd'] : 0.0;

    if ($userId <= 0 || $connectsAmount <= 0 || $referenceId === '') {
        return false;
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        $lock = $db->prepare('SELECT id, status FROM ccpayment_transactions WHERE reference_id = ? FOR UPDATE');
        $lock->execute([$referenceId]);
        $locked = $lock->fetch(PDO::FETCH_ASSOC);
        if (!$locked || ($locked['status'] ?? '') === 'completed') {
            $db->commit();
            return true;
        }

        $payStmt = $db->prepare('SELECT id, status FROM payments WHERE transaction_id = ? FOR UPDATE');
        $payStmt->execute([$referenceId]);
        $payment = $payStmt->fetch(PDO::FETCH_ASSOC);

        if ($payment && ($payment['status'] ?? '') === 'completed') {
            $updCc = $db->prepare("
                UPDATE ccpayment_transactions
                SET status = 'completed', ccpayment_record_id = COALESCE(?, ccpayment_record_id),
                    completed_at = NOW(), webhook_payload = COALESCE(?, webhook_payload)
                WHERE reference_id = ?
            ");
            $updCc->execute([
                $recordId,
                $webhookPayload ? json_encode($webhookPayload, JSON_UNESCAPED_SLASHES) : null,
                $referenceId,
            ]);
            $db->commit();
            return true;
        }

        $addConnects = $db->prepare('UPDATE users SET connects = connects + ? WHERE id = ?');
        $addConnects->execute([$connectsAmount, $userId]);

        $logHistory = $db->prepare("
            INSERT INTO connects_history (user_id, action, description, amount)
            VALUES (?, 'purchase', ?, ?)
        ");
        $historyNote = 'Purchased ' . $connectsAmount . ' Connects Pack (USDT';
        if ($depositedUsd > 0) {
            $historyNote .= ', $' . number_format($depositedUsd, 2, '.', '') . ' received';
        }
        $historyNote .= ')';
        $logHistory->execute([
            $userId,
            $historyNote,
            $connectsAmount,
        ]);

        $paidLabel = $depositedUsd > 0
            ? sprintf(' (received $%.2f USDT/crypto)', $depositedUsd)
            : '';
        $payDescription = 'Purchased ' . $connectsAmount . ' Connects (USDT crypto completed)' . $paidLabel;

        if ($payment) {
            $updPay = $db->prepare("
                UPDATE payments
                SET status = 'completed', amount = ?, payment_method = 'ccpayment_crypto',
                    description = ?
                WHERE id = ?
            ");
            $updPay->execute([
                $depositedUsd > 0 ? $depositedUsd : $price,
                $payDescription,
                $payment['id'],
            ]);
        } else {
            $insPay = $db->prepare("
                INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method, description, platform_fee)
                VALUES (?, ?, 1, ?, 'completed', 'ccpayment_crypto', ?, 0.0)
            ");
            $insPay->execute([
                $referenceId,
                $userId,
                $depositedUsd > 0 ? $depositedUsd : $price,
                $payDescription,
            ]);
        }

        $webhookJson = $webhookPayload ? json_encode($webhookPayload, JSON_UNESCAPED_SLASHES) : null;
        $depositJson = $depositRecord ? json_encode($depositRecord, JSON_UNESCAPED_SLASHES) : null;
        $updCc = $db->prepare("
            UPDATE ccpayment_transactions
            SET status = 'completed', ccpayment_record_id = ?, webhook_payload = ?,
                deposited_amount = COALESCE(?, deposited_amount),
                deposited_coin = COALESCE(?, deposited_coin),
                deposited_usd = COALESCE(?, deposited_usd),
                tx_id = COALESCE(?, tx_id),
                api_response = COALESCE(?, api_response),
                completed_at = NOW()
            WHERE reference_id = ?
        ");
        $updCc->execute([
            $recordId,
            $webhookJson,
            isset($ccRow['deposited_amount']) ? $ccRow['deposited_amount'] : null,
            isset($ccRow['deposited_coin']) ? $ccRow['deposited_coin'] : null,
            $depositedUsd > 0 ? $depositedUsd : null,
            isset($ccRow['tx_id']) ? $ccRow['tx_id'] : null,
            $depositJson,
            $referenceId,
        ]);

        $db->commit();
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('ccpayment_fulfill_connects_purchase: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Credit client wallet after CCPayment confirms a balance deposit.
 */
function ccpayment_fulfill_wallet_deposit(
    array $ccRow,
    ?string $recordId = null,
    ?array $webhookPayload = null,
    ?array $depositRecord = null
): bool {
    if (($ccRow['status'] ?? '') === 'completed') {
        return true;
    }

    $userId = (int) ($ccRow['user_id'] ?? 0);
    $expectedUsd = (float) ($ccRow['amount_usd'] ?? 0);
    $referenceId = (string) ($ccRow['reference_id'] ?? '');
    $depositedUsd = isset($ccRow['deposited_usd']) ? (float) $ccRow['deposited_usd'] : 0.0;

    if ($userId <= 0 || $expectedUsd <= 0 || $referenceId === '') {
        return false;
    }

    $creditUsd = $depositedUsd > 0 ? $depositedUsd : $expectedUsd;

    $db = getDB();
    $db->beginTransaction();

    try {
        $lock = $db->prepare('SELECT id, status FROM ccpayment_transactions WHERE reference_id = ? FOR UPDATE');
        $lock->execute([$referenceId]);
        $locked = $lock->fetch(PDO::FETCH_ASSOC);
        if (!$locked || ($locked['status'] ?? '') === 'completed') {
            $db->commit();
            return true;
        }

        $payStmt = $db->prepare('SELECT id, status FROM payments WHERE transaction_id = ? FOR UPDATE');
        $payStmt->execute([$referenceId]);
        $payment = $payStmt->fetch(PDO::FETCH_ASSOC);

        if ($payment && ($payment['status'] ?? '') === 'completed') {
            $updCc = $db->prepare("
                UPDATE ccpayment_transactions
                SET status = 'completed', ccpayment_record_id = COALESCE(?, ccpayment_record_id),
                    completed_at = NOW(), webhook_payload = COALESCE(?, webhook_payload)
                WHERE reference_id = ?
            ");
            $updCc->execute([
                $recordId,
                $webhookPayload ? json_encode($webhookPayload, JSON_UNESCAPED_SLASHES) : null,
                $referenceId,
            ]);
            $db->commit();
            return true;
        }

        $addBalance = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
        $addBalance->execute([$creditUsd, $userId]);

        $paidLabel = $depositedUsd > 0
            ? sprintf(' (received $%.2f USDT/crypto)', $depositedUsd)
            : '';
        $payDescription = 'Add Funds (USDT crypto completed)' . $paidLabel;

        if ($payment) {
            $updPay = $db->prepare("
                UPDATE payments
                SET status = 'completed', amount = ?, payment_method = 'ccpayment_crypto',
                    description = ?
                WHERE id = ?
            ");
            $updPay->execute([$creditUsd, $payDescription, $payment['id']]);
        } else {
            $insPay = $db->prepare("
                INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method, description, platform_fee)
                VALUES (?, ?, ?, ?, 'completed', 'ccpayment_crypto', ?, 0.0)
            ");
            $insPay->execute([
                $referenceId,
                $userId,
                $userId,
                $creditUsd,
                $payDescription,
            ]);
        }

        $webhookJson = $webhookPayload ? json_encode($webhookPayload, JSON_UNESCAPED_SLASHES) : null;
        $depositJson = $depositRecord ? json_encode($depositRecord, JSON_UNESCAPED_SLASHES) : null;
        $updCc = $db->prepare("
            UPDATE ccpayment_transactions
            SET status = 'completed', ccpayment_record_id = ?, webhook_payload = ?,
                deposited_amount = COALESCE(?, deposited_amount),
                deposited_coin = COALESCE(?, deposited_coin),
                deposited_usd = COALESCE(?, deposited_usd),
                tx_id = COALESCE(?, tx_id),
                api_response = COALESCE(?, api_response),
                completed_at = NOW()
            WHERE reference_id = ?
        ");
        $updCc->execute([
            $recordId,
            $webhookJson,
            isset($ccRow['deposited_amount']) ? $ccRow['deposited_amount'] : null,
            isset($ccRow['deposited_coin']) ? $ccRow['deposited_coin'] : null,
            $depositedUsd > 0 ? $depositedUsd : null,
            isset($ccRow['tx_id']) ? $ccRow['tx_id'] : null,
            $depositJson,
            $referenceId,
        ]);

        $db->commit();
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('ccpayment_fulfill_wallet_deposit: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Load deposit amount/details from CCPayment (webhook DirectDeposit does not include amount).
 *
 * @return array{ok: bool, record?: array<string, mixed>, cc_row?: array<string, mixed>, error?: string}
 */
function ccpayment_resolve_direct_deposit(array $msg, array $ccRow): array
{
    $recordId = (string) ($msg['recordId'] ?? '');
    if ($recordId === '') {
        return ['ok' => false, 'error' => 'missing_record_id'];
    }

    require_once __DIR__ . '/classes/CCPayment.php';
    $client = new CCPayment();
    $lookup = $client->getDepositRecord($recordId);
    if (!$lookup['success'] || empty($lookup['record'])) {
        return ['ok' => false, 'error' => $lookup['message'] ?? 'deposit_lookup_failed'];
    }

    $record = $lookup['record'];
    $recordStatus = strtolower((string) ($record['status'] ?? ''));
    if ($recordStatus !== 'success') {
        return ['ok' => false, 'error' => 'deposit_not_success', 'record' => $record];
    }

    if (!empty($record['isFlaggedAsRisky'])) {
        return ['ok' => false, 'error' => 'deposit_flagged_risky', 'record' => $record];
    }

    $coinSymbol = (string) ($record['coinSymbol'] ?? $msg['coinSymbol'] ?? '');
    if (!ccpayment_is_allowed_deposit_coin($coinSymbol)) {
        return ['ok' => false, 'error' => 'coin_not_allowed', 'record' => $record];
    }

    $recordRef = (string) ($record['referenceId'] ?? '');
    $expectedRef = (string) ($ccRow['reference_id'] ?? '');
    if ($recordRef !== '' && $expectedRef !== '' && $recordRef !== $expectedRef) {
        return ['ok' => false, 'error' => 'reference_mismatch', 'record' => $record];
    }

    $depositedUsd = ccpayment_deposit_usd_value($record);
    $requiredUsd = (float) ($ccRow['amount_usd'] ?? 0);
    $tolerance = (float) env('CCPAYMENT_AMOUNT_TOLERANCE', 0.01);
    $minimumUsd = max(0, $requiredUsd - $tolerance);

    if ($depositedUsd < $minimumUsd) {
        return [
            'ok' => false,
            'error' => 'underpaid',
            'record' => $record,
            'deposited_usd' => $depositedUsd,
            'required_usd' => $requiredUsd,
        ];
    }

    $ccRow['deposited_amount'] = (float) ($record['amount'] ?? 0);
    $ccRow['deposited_coin'] = strtoupper($coinSymbol);
    $ccRow['deposited_usd'] = round($depositedUsd, 2);
    $ccRow['tx_id'] = (string) ($record['txId'] ?? '');

    ccpayment_ensure_transactions_table();
    $db = getDB();
    $upd = $db->prepare('
        UPDATE ccpayment_transactions
        SET deposited_amount = ?, deposited_coin = ?, deposited_usd = ?, tx_id = ?,
            ccpayment_record_id = ?, api_response = ?
        WHERE reference_id = ?
    ');
    $upd->execute([
        $ccRow['deposited_amount'],
        $ccRow['deposited_coin'],
        $ccRow['deposited_usd'],
        $ccRow['tx_id'] !== '' ? $ccRow['tx_id'] : null,
        $recordId,
        json_encode($lookup['raw'] ?? $record, JSON_UNESCAPED_SLASHES),
        $expectedRef,
    ]);

    return ['ok' => true, 'record' => $record, 'cc_row' => $ccRow];
}

/**
 * Handle CCPayment DirectDeposit webhook (permanent address / referenceId).
 */
function ccpayment_process_direct_deposit(array $payload): bool
{
    $type = (string) ($payload['type'] ?? '');
    if ($type !== 'DirectDeposit') {
        return false;
    }

    $msg = $payload['msg'] ?? null;
    if (!is_array($msg)) {
        return false;
    }

    $status = strtolower((string) ($msg['status'] ?? ''));
    if ($status !== 'success') {
        return false;
    }

    $referenceId = (string) ($msg['referenceId'] ?? '');
    if ($referenceId === '') {
        return false;
    }

    ccpayment_ensure_transactions_table();
    $ccRow = ccpayment_find_transaction($referenceId);
    if (!$ccRow) {
        return false;
    }

    if (!empty($msg['isFlaggedAsRisky'])) {
        ccpayment_append_webhook_log((int) $ccRow['id'], ['rejected' => 'risky_webhook_flag']);
        return false;
    }

    $recordId = (string) ($msg['recordId'] ?? '');
    $resolved = ccpayment_resolve_direct_deposit($msg, $ccRow);
    if (!$resolved['ok']) {
        ccpayment_append_webhook_log((int) $ccRow['id'], [
            'deposit_rejected' => $resolved['error'] ?? 'unknown',
            'deposited_usd' => $resolved['deposited_usd'] ?? null,
            'required_usd' => $resolved['required_usd'] ?? null,
            'webhook' => $payload,
        ]);
        return false;
    }

    $ccRow = $resolved['cc_row'] ?? $ccRow;
    $depositRecord = $resolved['record'] ?? null;

    $fulfillArgs = [
        $ccRow,
        $recordId !== '' ? $recordId : null,
        $payload,
        is_array($depositRecord) ? $depositRecord : null,
    ];

    return match ($ccRow['purpose'] ?? '') {
        'connects' => ccpayment_fulfill_connects_purchase(...$fulfillArgs),
        'deposit' => ccpayment_fulfill_wallet_deposit(...$fulfillArgs),
        default => false,
    };
}
