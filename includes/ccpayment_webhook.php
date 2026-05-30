<?php
/**
 * Shared helpers for CCPayment webhook endpoints.
 */

if (!function_exists('ccpayment_get_headers')) {
    function ccpayment_get_headers(): array
    {
        if (function_exists('getallheaders')) {
            $h = getallheaders();
            return is_array($h) ? $h : [];
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}

function ccpayment_header_value(array $headers, string $name): string
{
    foreach ($headers as $key => $value) {
        if (strcasecmp((string) $key, $name) === 0) {
            return (string) $value;
        }
    }
    return '';
}

function ccpayment_app_id(): string
{
    return (string) env('CCPAYMENT_API_KEY', '');
}

function ccpayment_app_secret(): string
{
    return (string) env('CCPAYMENT_SECRET', '');
}

/**
 * Verify incoming webhook signature (HMAC per CCPayment API samples, with doc fallback).
 */
function ccpayment_verify_signature(string $appId, string $appSecret, string $timestamp, string $rawBody, string $providedSign): bool
{
    if ($providedSign === '' || $appId === '' || $appSecret === '') {
        return false;
    }

    $signText = $appId . $timestamp . $rawBody;
    $expected = hash_hmac('sha256', $signText, $appSecret);
    if (hash_equals($expected, $providedSign)) {
        return true;
    }

    $signTextWithSecret = $appId . $appSecret . $timestamp . $rawBody;
    $expectedPlain = hash('sha256', $signTextWithSecret);
    return hash_equals($expectedPlain, $providedSign);
}

function ccpayment_log_webhook(string $logFile, array $extra = []): void
{
    $logDir = dirname(__DIR__) . '/storage/webhook-logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $entry = array_merge([
        'timestamp' => gmdate('c'),
        'path' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ], $extra);

    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function ccpayment_fail(int $code, string $message, ?string $logFile = null, array $extra = []): void
{
    if ($logFile !== null) {
        ccpayment_log_webhook($logFile, array_merge([
            'phase' => 'error',
            'http_code' => $code,
            'error' => $message,
        ], $extra));
    }

    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function ccpayment_json_success(array $payload = ['msg' => 'Success']): void
{
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * ActivateWebhookURL verification — JSON body plus signed response headers (CCPayment docs).
 */
function ccpayment_activation_success(?string $logFile = null): void
{
    $body = json_encode(['msg' => 'Success'], JSON_UNESCAPED_SLASHES);
    $appId = ccpayment_app_id();
    $appSecret = ccpayment_app_secret();
    $timestamp = (string) time();
    $sign = hash('sha256', $appId . $appSecret . $timestamp . $body);

    if ($logFile !== null) {
        ccpayment_log_webhook($logFile, [
            'phase' => 'activation_ok',
            'response_body' => $body,
        ]);
    }

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Appid: ' . $appId);
    header('Timestamp: ' . $timestamp);
    header('Sign: ' . $sign);
    echo $body;
    exit;
}

/** CCPayment webhooks expect HTTP 200 and body "success". */
function ccpayment_webhook_ack(): void
{
    $body = 'success';
    $appId = ccpayment_app_id();
    $appSecret = ccpayment_app_secret();
    $timestamp = (string) time();
    $sign = hash('sha256', $appId . $appSecret . $timestamp . $body);

    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('Appid: ' . $appId);
    header('Timestamp: ' . $timestamp);
    header('Sign: ' . $sign);
    echo $body;
    exit;
}

/**
 * Credit client balance when a deposit webhook confirms payment.
 */
function ccpayment_process_deposit(array $payload): bool
{
    $payStatus = strtolower((string) ($payload['pay_status'] ?? ''));
    $type = (string) ($payload['type'] ?? '');
    $msg = $payload['msg'] ?? null;

    if ($type === 'ApiDeposit' && is_array($msg)) {
        $status = strtolower((string) ($msg['status'] ?? ''));
        if ($status !== 'success') {
            return false;
        }
        $recordId = (string) ($msg['recordId'] ?? '');
        $orderId = (string) ($msg['orderId'] ?? '');
        $amount = (float) ($payload['order_amount'] ?? $payload['product_price'] ?? 0);
        if ($amount <= 0 && isset($msg['paidAmount'])) {
            $amount = (float) $msg['paidAmount'];
        }
    } elseif ($payStatus === 'success') {
        $recordId = (string) ($payload['record_id'] ?? '');
        $orderId = (string) ($payload['order_id'] ?? '');
        $paid = (float) ($payload['paid_amount'] ?? 0);
        $tokenRate = (float) ($payload['token_rate'] ?? 1);
        $amount = $paid > 0 ? $paid * $tokenRate : (float) ($payload['order_amount'] ?? $payload['product_price'] ?? 0);
    } else {
        return false;
    }

    if (($recordId ?? '') === '' && ($orderId ?? '') === '') {
        return false;
    }

    $transactionId = $recordId !== '' ? $recordId : $orderId;
    $merchantOrderId = '';
    if (isset($payload['extend']) && is_array($payload['extend'])) {
        $merchantOrderId = (string) ($payload['extend']['merchant_order_id'] ?? '');
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare("SELECT id, payer_id, payee_id, amount, status FROM payments WHERE transaction_id IN (?, ?, ?) LIMIT 1 FOR UPDATE");
        $stmt->execute([$transactionId, $orderId, $merchantOrderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            $db->rollBack();
            return false;
        }

        if (($payment['status'] ?? '') === 'completed') {
            $db->commit();
            return true;
        }

        $userId = (int) ($payment['payee_id'] ?? $payment['payer_id'] ?? 0);
        if ($userId <= 0) {
            $db->rollBack();
            return false;
        }

        if ($amount <= 0) {
            $amount = (float) ($payment['amount'] ?? 0);
        }
        if ($amount <= 0) {
            $db->rollBack();
            return false;
        }

        $upd = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $upd->execute([$amount, $userId]);

        $updPay = $db->prepare("UPDATE payments SET status = 'completed', amount = ?, payment_method = 'ccpayment' WHERE id = ?");
        $updPay->execute([$amount, $payment['id']]);

        $db->commit();
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('ccpayment_process_deposit: ' . $e->getMessage());
        }
        return false;
    }
}

/** @alias */
function ccpayment_deposit_ack(): void
{
    ccpayment_webhook_ack();
}

/**
 * Mark withdrawal complete when CCPayment confirms payout (ApiWithdrawal / legacy payload).
 */
function ccpayment_process_withdraw(array $payload): bool
{
    $type = (string) ($payload['type'] ?? '');
    $msg = $payload['msg'] ?? null;

    if ($type === 'ApiWithdrawal' && is_array($msg)) {
        $status = strtolower((string) ($msg['status'] ?? ''));
        $recordId = (string) ($msg['recordId'] ?? '');
        $orderId = (string) ($msg['orderId'] ?? '');
    } else {
        $status = strtolower((string) ($payload['pay_status'] ?? $payload['status'] ?? ''));
        $recordId = (string) ($payload['record_id'] ?? '');
        $orderId = (string) ($payload['order_id'] ?? '');
    }

    if ($recordId === '' && $orderId === '') {
        return false;
    }

    if (in_array($status, ['failed', 'rejected'], true)) {
        return ccpayment_reverse_withdrawal($recordId, $orderId, $payload);
    }

    if ($status !== 'success') {
        return false;
    }

    return ccpayment_complete_withdrawal($recordId, $orderId, $payload);
}

/**
 * @param array<string, mixed> $payload
 */
function ccpayment_complete_withdrawal(string $recordId, string $orderId, array $payload): bool
{
    $merchantOrderId = '';
    if (isset($payload['extend']) && is_array($payload['extend'])) {
        $merchantOrderId = (string) ($payload['extend']['merchant_order_id'] ?? '');
    }
    if ($merchantOrderId === '' && isset($payload['merchant_order_id'])) {
        $merchantOrderId = (string) $payload['merchant_order_id'];
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare("SELECT id, status FROM payments WHERE transaction_id IN (?, ?, ?) LIMIT 1 FOR UPDATE");
        $stmt->execute([$recordId, $orderId, $merchantOrderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            $db->rollBack();
            return false;
        }

        if (($payment['status'] ?? '') === 'completed') {
            $db->commit();
            return true;
        }

        $updPay = $db->prepare("UPDATE payments SET status = 'completed', payment_method = 'ccpayment_crypto' WHERE id = ?");
        $updPay->execute([$payment['id']]);

        if (file_exists(__DIR__ . '/ccpayment_transactions.php')) {
            require_once __DIR__ . '/ccpayment_transactions.php';
            ccpayment_ensure_transactions_table();
            $refs = array_values(array_filter([$orderId, $merchantOrderId]));
            if ($refs !== []) {
                $placeholders = implode(',', array_fill(0, count($refs), '?'));
                $ccUpd = $db->prepare("
                    UPDATE ccpayment_transactions
                    SET status = 'completed', completed_at = NOW(), ccpayment_record_id = COALESCE(?, ccpayment_record_id)
                    WHERE reference_id IN ($placeholders) AND purpose = 'withdraw'
                ");
                $ccUpd->execute(array_merge([$recordId !== '' ? $recordId : null], $refs));
            }
        }

        $db->commit();
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('ccpayment_complete_withdrawal: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Refund freelancer balance when an on-chain withdrawal fails or is rejected.
 *
 * @param array<string, mixed> $payload
 */
function ccpayment_reverse_withdrawal(string $recordId, string $orderId, array $payload): bool
{
    $merchantOrderId = '';
    if (isset($payload['extend']) && is_array($payload['extend'])) {
        $merchantOrderId = (string) ($payload['extend']['merchant_order_id'] ?? '');
    }
    if ($merchantOrderId === '' && isset($payload['merchant_order_id'])) {
        $merchantOrderId = (string) $payload['merchant_order_id'];
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare("
            SELECT id, status, payee_id, amount
            FROM payments
            WHERE transaction_id IN (?, ?, ?)
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$recordId, $orderId, $merchantOrderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            $db->rollBack();
            return false;
        }

        $currentStatus = (string) ($payment['status'] ?? '');
        if ($currentStatus === 'failed' || $currentStatus === 'cancelled') {
            $db->commit();
            return true;
        }

        if ($currentStatus === 'completed') {
            $db->rollBack();
            return false;
        }

        $payeeId = (int) ($payment['payee_id'] ?? 0);
        $amount = (float) ($payment['amount'] ?? 0);

        if ($payeeId > 0 && $amount > 0) {
            $refund = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
            $refund->execute([$amount, $payeeId]);
        }

        $updPay = $db->prepare("UPDATE payments SET status = 'failed', payment_method = 'ccpayment_crypto' WHERE id = ?");
        $updPay->execute([$payment['id']]);

        if (file_exists(__DIR__ . '/ccpayment_transactions.php')) {
            require_once __DIR__ . '/ccpayment_transactions.php';
            ccpayment_ensure_transactions_table();
            $refs = array_values(array_filter([$orderId, $merchantOrderId]));
            if ($refs !== []) {
                $placeholders = implode(',', array_fill(0, count($refs), '?'));
                $ccUpd = $db->prepare("
                    UPDATE ccpayment_transactions
                    SET status = 'failed', ccpayment_record_id = COALESCE(?, ccpayment_record_id)
                    WHERE reference_id IN ($placeholders) AND purpose = 'withdraw'
                ");
                $ccUpd->execute(array_merge([$recordId !== '' ? $recordId : null], $refs));
            }
        }

        $db->commit();
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('ccpayment_reverse_withdrawal: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Validate an incoming CCPayment POST webhook. Exits on failure; returns decoded JSON body.
 *
 * @return array<string, mixed>
 */
function ccpayment_validate_webhook_post(string $logFile): array
{
    $appId = ccpayment_app_id();
    $appSecret = ccpayment_app_secret();

    if ($appId === '' || $appSecret === '') {
        ccpayment_fail(500, 'CCPayment is not configured', $logFile, [
            'missing' => array_values(array_filter([
                $appId === '' ? 'CCPAYMENT_API_KEY' : null,
                $appSecret === '' ? 'CCPAYMENT_SECRET' : null,
            ])),
        ]);
    }

    $headers = ccpayment_get_headers();
    $requestAppId = ccpayment_header_value($headers, 'Appid');
    $requestSign = ccpayment_header_value($headers, 'Sign');
    $requestTimestamp = ccpayment_header_value($headers, 'Timestamp');

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false) {
        $rawBody = '';
    }

    ccpayment_log_webhook($logFile, [
        'phase' => 'post_body',
        'body_raw' => $rawBody,
    ]);

    if ($requestAppId !== $appId) {
        ccpayment_fail(401, 'Invalid AppId', $logFile, [
            'request_app_id' => $requestAppId,
            'expected_app_id' => $appId,
        ]);
    }

    $timestamp = filter_var($requestTimestamp, FILTER_VALIDATE_INT);
    if ($timestamp === false) {
        ccpayment_fail(400, 'Invalid timestamp format', $logFile, ['timestamp' => $requestTimestamp]);
    }

    $timeSkew = abs(time() - $timestamp);
    if ($timeSkew > 300) {
        ccpayment_fail(401, 'The timestamp is invalid or has expired', $logFile, [
            'timestamp' => $timestamp,
            'server_time' => time(),
            'skew_seconds' => $timeSkew,
        ]);
    }

    if (!ccpayment_verify_signature($appId, $appSecret, (string) $timestamp, $rawBody, $requestSign)) {
        ccpayment_fail(402, 'Invalid signature', $logFile, [
            'sign_received' => $requestSign,
        ]);
    }

    $reqBody = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($reqBody)) {
        ccpayment_fail(400, 'Invalid JSON format', $logFile);
    }

    ccpayment_log_webhook($logFile, [
        'phase' => 'verified',
        'body' => $reqBody,
    ]);

    return $reqBody;
}
