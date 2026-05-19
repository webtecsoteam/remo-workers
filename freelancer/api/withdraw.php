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
$amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
$method_id = isset($data['method_id']) ? (int)$data['method_id'] : 0;

if ($amount <= 0 || empty($method_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid withdrawal request.']);
    exit;
}

$db = getDB();

try {
    // Fetch the saved method
    $methStmt = $db->prepare("SELECT * FROM user_withdrawal_methods WHERE id = ? AND user_id = ?");
    $methStmt->execute([$method_id, $user['id']]);
    $savedMethod = $methStmt->fetch(PDO::FETCH_ASSOC);
    if (!$savedMethod) {
        throw new Exception("Selected withdrawal method not found.");
    }
    
    $method = $savedMethod['method_type'];
    $details = json_decode($savedMethod['details'], true);

    $db->beginTransaction();

    // Lock user row for balance check
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user['id']]);
    $currentBalance = (float)$stmt->fetchColumn();

    if ($currentBalance < $amount) {
        throw new Exception('Insufficient balance for withdrawal.');
    }

    // Deduct balance
    $upd = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $upd->execute([$amount, $user['id']]);

    // Create a transaction record
    // Store withdrawal details as a human-readable string
    if (isset($details['email'])) {
        $detailsStr = "Withdrawal to " . $method . " (" . $details['email'] . ")";
    } else {
        $detailsStr = "Withdrawal to " . $method . " (" . ($details['bankName'] ?? 'Bank') . " - *" . substr($details['accNum'] ?? '', -4) . ")";
    }
    
    $tx = $db->prepare("
        INSERT INTO payments (transaction_id, payer_id, payee_id, amount, payment_method, description, status) 
        VALUES (?, NULL, ?, ?, ?, ?, 'pending')
    ");
    $txId = 'WTH-' . strtoupper(uniqid());
    $tx->execute([$txId, $user['id'], $amount, $method, $detailsStr]);

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
