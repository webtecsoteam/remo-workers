<?php
/**
 * CCPayment withdraw webhook — signature verification and withdrawal handling.
 *
 * Public URL: https://remoworkers.com/ccpayment-webhook-withdraw
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ccpayment_webhook.php';

const CCPAYMENT_WEBHOOK_TYPE_ACTIVATE = 'ActivateWebhookURL';

$logFile = dirname(__DIR__) . '/storage/webhook-logs/ccpayment-withdraw.log';

ccpayment_log_webhook($logFile, [
    'phase' => 'received',
    'headers' => ccpayment_get_headers(),
]);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    ccpayment_json_success([
        'msg' => 'CCPayment withdraw webhook endpoint is reachable. Use POST for verification.',
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

ccpayment_process_withdraw($reqBody);
ccpayment_webhook_ack();
