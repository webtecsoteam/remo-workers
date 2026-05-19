<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? (int)$data['amount'] : 0;
$price = isset($data['price']) ? (float)$data['price'] : 0.0;

if ($amount <= 0 || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid connects package selection.']);
    exit;
}

$db = getDB();

// Validate package from DB
$pkgStmt = $db->prepare("SELECT price FROM connects_packages WHERE amount = ? AND is_active = 1");
$pkgStmt->execute([$amount]);
$validPrice = $pkgStmt->fetchColumn();

// Allow predefined packages OR custom amounts at standard $0.15 rate
if ($validPrice !== false) {
    if (abs((float)$validPrice - $price) > 0.001) {
        echo json_encode(['success' => false, 'message' => 'Invalid package price mismatch. Please refresh the page.']);
        exit;
    }
} else {
    $expectedPrice = round($amount * 0.15, 2);
    if (abs($expectedPrice - $price) > 0.001) {
        echo json_encode(['success' => false, 'message' => 'Invalid custom amount or price mismatch.']);
        exit;
    }
}

$paymentMethod = isset($data['payment_method']) ? trim($data['payment_method']) : 'wallet';

try {
    $db->beginTransaction();

    if ($paymentMethod === 'wallet') {
        // Check freelancer balance
        $stmt = $db->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user['id']]);
        $currentBalance = (float)$stmt->fetchColumn();

        if ($currentBalance < $price) {
            throw new Exception('Insufficient available balance to buy connects.');
        }

        // Deduct balance from freelancer
        $deduct = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $deduct->execute([$price, $user['id']]);
        $paymentType = 'Upwork Balance';
    } else {
        // Paystack checkout for connects!
        require_once __DIR__ . '/../../includes/classes/Paystack.php';
        $paystack = new Paystack();
        
        $callbackUrl = baseUrl('actions/paystack_callback.php');
        $metadata = [
            'user_id' => $user['id'],
            'type' => 'connects',
            'connects_amount' => $amount,
            'price' => $price
        ];

        // Initialize Paystack with price in USD (lowest denomination: cents)
        $response = $paystack->initialize($user['email'], $price, $callbackUrl, $metadata);

        if ($response['status'] && isset($response['data']['authorization_url'])) {
            $paymentType = 'paystack';
            $transactionId = $response['data']['reference'];

            // Record pending transaction so we can trace it when Paystack callbacks or webhooks are fired
            $logPayment = $db->prepare("
                INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method, description, platform_fee) 
                VALUES (?, ?, ?, ?, 'pending', ?, ?, 0.0)
            ");
            // Payer is freelancer, payee is 1 (Admin/System)
            $logPayment->execute([
                $transactionId,
                $user['id'],
                1, // System
                $price,
                $paymentType,
                'Purchased ' . $amount . ' Connects (' . $paymentType . ' pending)'
            ]);

            $db->commit();
            echo json_encode([
                'success' => true,
                'redirect' => true,
                'authorization_url' => $response['data']['authorization_url']
            ]);
            exit;
        } else {
            throw new Exception($response['message'] ?? 'Failed to initialize Paystack transaction');
        }
    }

    // 2. Add connects to freelancer (Only reached for instant wallet payments)
    $addConnects = $db->prepare("UPDATE users SET connects = connects + ? WHERE id = ?");
    $addConnects->execute([$amount, $user['id']]);

    // 3. Log inside connects_history
    $logHistory = $db->prepare("
        INSERT INTO connects_history (user_id, action, description, amount) 
        VALUES (?, 'purchase', ?, ?)
    ");
    $logHistory->execute([
        $user['id'],
        'Purchased ' . $amount . ' Connects Pack',
        $amount
    ]);

    // 4. Log in payments/transaction history so it displays in payments history too
    $transactionId = 'CON-' . strtoupper(uniqid());
    $logPayment = $db->prepare("
        INSERT INTO payments (transaction_id, payer_id, payee_id, amount, status, payment_method, description, platform_fee) 
        VALUES (?, ?, ?, ?, 'completed', ?, ?, 0.0)
    ");
    // Payer is freelancer, payee is 1 (Admin/System)
    $logPayment->execute([
        $transactionId,
        $user['id'],
        1, // Admin User ID (System)
        $price,
        $paymentType,
        'Purchased ' . $amount . ' Connects (' . $paymentType . ')',
    ]);

    // Get updated details
    $getUpdated = $db->prepare("SELECT balance, connects FROM users WHERE id = ?");
    $getUpdated->execute([$user['id']]);
    $updated = $getUpdated->fetch(PDO::FETCH_ASSOC);

    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Successfully purchased ' . $amount . ' connects!',
        'new_balance' => (float)$updated['balance'],
        'new_connects' => (int)$updated['connects']
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
