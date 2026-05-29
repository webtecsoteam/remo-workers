<?php
/**
 * CCPayment deposit webhook — signature verification and deposit handling.
 *
 * Public URL: https://remoworkers.com/ccpayment-webhook-deposit
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ccpayment_webhook.php';
require_once __DIR__ . '/../includes/ccpayment_transactions.php';

const CCPAYMENT_WEBHOOK_TYPE_ACTIVATE = 'ActivateWebhookURL';

$logFile = dirname(__DIR__) . '/storage/webhook-logs/ccpayment-deposit.log';

ccpayment_log_webhook($logFile, [
    'phase' => 'received',
    'headers' => ccpayment_get_headers(),
]);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    ccpayment_json_success([
        'msg' => 'CCPayment deposit webhook endpoint is reachable. Use POST for verification.',
    ]);
}

if ($method !== 'POST') {
    ccpayment_fail(405, 'Method Not Allowed', $logFile);
}

$reqBody = ccpayment_validate_webhook_post($logFile);

$webhookType = (string) ($reqBody['type'] ?? '');

if ($webhookType === CCPAYMENT_WEBHOOK_TYPE_ACTIVATE) {
    ccpayment_activation_success($logFile);
}

if ($webhookType === 'DirectDeposit') {
    ccpayment_process_direct_deposit($reqBody);
    ccpayment_webhook_ack();
}

ccpayment_process_deposit($reqBody);
ccpayment_webhook_ack();
