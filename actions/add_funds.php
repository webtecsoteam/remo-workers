<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/classes/Paystack.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);

    if ($amount < 1) {
        json_response(['success' => false, 'error' => 'Minimum $1 required']);
    }

    try {
        $paystack = new Paystack();
        $callbackUrl = baseUrl('actions/paystack_callback.php');

        $metadata = [
            'user_id' => $user['id'],
            'type' => 'deposit'
        ];

        $response = $paystack->initialize($user['email'], $amount, $callbackUrl, $metadata);

        if ($response['status'] && isset($response['data']['authorization_url'])) {
            // Record pending transaction in database
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

            json_response([
                'success' => true,
                'authorization_url' => $response['data']['authorization_url'],
                'reference' => $response['data']['reference']
            ]);
        } else {
            json_response([
                'success' => false,
                'error' => $response['message'] ?? 'Failed to initialize Paystack transaction'
            ]);
        }

    } catch (Exception $e) {
        json_response(['success' => false, 'error' => 'Error initializing payment: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
