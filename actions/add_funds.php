<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/Paystack.php';
require_once __DIR__ . '/../includes/classes/CCPayment.php';
require_once __DIR__ . '/../includes/ccpayment_transactions.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'client') {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$jsonBody = null;
if (stripos($contentType, 'application/json') !== false) {
    $jsonBody = json_decode(file_get_contents('php://input'), true);
    if (!is_array($jsonBody)) {
        $jsonBody = [];
    }
}

$amount = (float) ($jsonBody['amount'] ?? $_POST['amount'] ?? 0);
$paymentMethod = trim((string) ($jsonBody['payment_method'] ?? $_POST['payment_method'] ?? 'card'));
if ($paymentMethod === 'paystack') {
    $paymentMethod = 'card';
}

if ($amount < 1) {
    json_response(['success' => false, 'error' => 'Minimum $1 required']);
}

if ($paymentMethod === 'crypto') {
    ccpayment_ensure_transactions_table();
    $db = getDB();

    try {
        $db->beginTransaction();

        $ccpayment = new CCPayment();
        if (!$ccpayment->isConfigured()) {
            throw new Exception('Cryptocurrency payments are not configured. Please contact support.');
        }

        $referenceId = 'DEP' . $user['id'] . 'T' . time() . 'R' . bin2hex(random_bytes(4));
        $referenceId = substr($referenceId, 0, 64);

        $chain = ccpayment_normalize_chain((string) env('CCPAYMENT_CHAIN', 'TRX'));
        $apiRequest = ['referenceId' => $referenceId, 'chain' => $chain];
        $addressResult = $ccpayment->getOrCreateDepositAddress($referenceId, $chain);

        if (!$addressResult['success']) {
            throw new Exception($addressResult['message'] ?? 'Failed to get crypto deposit address.');
        }

        $logPayment = $db->prepare("
            INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method, description, platform_fee)
            VALUES (?, ?, ?, ?, 'pending', 'ccpayment_crypto', ?, 0.0)
        ");
        $logPayment->execute([
            $referenceId,
            $user['id'],
            $user['id'],
            $amount,
            'Add Funds (USDT crypto pending)',
        ]);
        $paymentId = (int) $db->lastInsertId();

        $logCc = $db->prepare("
            INSERT INTO ccpayment_transactions (
                reference_id, user_id, payment_id, purpose, connects_amount, amount_usd,
                chain, coin_symbol, deposit_address, memo, status, api_request, api_response
            ) VALUES (?, ?, ?, 'deposit', NULL, ?, ?, 'USDT', ?, ?, 'pending', ?, ?)
        ");
        $logCc->execute([
            $referenceId,
            $user['id'],
            $paymentId,
            $amount,
            $chain,
            $addressResult['address'],
            $addressResult['memo'] ?? '',
            json_encode($apiRequest, JSON_UNESCAPED_SLASHES),
            json_encode($addressResult['raw'] ?? [], JSON_UNESCAPED_SLASHES),
        ]);

        $db->commit();

        json_response([
            'success' => true,
            'crypto' => true,
            'reference_id' => $referenceId,
            'address' => $addressResult['address'],
            'memo' => $addressResult['memo'] ?? '',
            'chain' => $chain,
            'coin' => 'USDT',
            'usdt_amount' => round($amount, 2),
            'rate_label' => '1 USDT = 1 USD',
            'chain_label' => ccpayment_chain_label($chain),
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

try {
    $paystack = new Paystack();
    $callbackUrl = baseUrl('actions/paystack_callback.php');

    $metadata = [
        'user_id' => $user['id'],
        'type' => 'deposit',
    ];

    $response = $paystack->initialize($user['email'], $amount, $callbackUrl, $metadata);

    if ($response['status'] && isset($response['data']['authorization_url'])) {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method, description)
            VALUES (?, ?, ?, ?, 'pending', 'paystack', 'Add Funds (Paystack pending)')
        ");
        $stmt->execute([
            $response['data']['reference'],
            $user['id'],
            $user['id'],
            $amount,
        ]);

        json_response([
            'success' => true,
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $response['data']['reference'],
        ]);
    }

    json_response([
        'success' => false,
        'error' => $response['message'] ?? 'Failed to initialize Paystack transaction',
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Error initializing payment: ' . $e->getMessage()]);
}
