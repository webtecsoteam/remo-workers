<?php
/**
 * =============================================
 * RemoWorkers - Paystack Initialize Action
 * =============================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/Paystack.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$amount = $_POST['amount'] ?? 0;
if ($amount < 5) { // Minimum 5 units (e.g. $5)
    echo json_encode(['success' => false, 'error' => 'Minimum amount is $5']);
    exit;
}

$paystack = new Paystack();
$callbackUrl = baseUrl('actions/paystack_callback.php');

$metadata = [
    'user_id' => $user['id'],
    'type' => 'deposit'
];

$response = $paystack->initialize($user['email'], $amount, $callbackUrl, $metadata);

if ($response['status'] && isset($response['data']['authorization_url'])) {
    // Record pending transaction
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $response['data']['reference'],
        $user['id'],
        $user['id'],
        $amount,
        'pending',
        'paystack'
    ]);

    echo json_encode([
        'success' => true, 
        'authorization_url' => $response['data']['authorization_url'],
        'reference' => $response['data']['reference']
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => $response['message'] ?? 'Failed to initialize Paystack transaction'
    ]);
}
