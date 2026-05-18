<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
try {
    // Get current connects and balance
    $uStmt = $db->prepare("SELECT balance, connects FROM users WHERE id = ?");
    $uStmt->execute([$user['id']]);
    $uData = $uStmt->fetch(PDO::FETCH_ASSOC);

    // Get recent activity with payment method joined
    $stmt = $db->prepare("
        SELECT c.*, p.payment_method 
        FROM connects_history c 
        LEFT JOIN payments p ON p.payer_id = c.user_id 
            AND p.status = 'completed'
            AND ABS(TIMESTAMPDIFF(SECOND, p.created_at, c.created_at)) < 300
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'connects' => (int)$uData['connects'],
        'balance' => (float)$uData['balance'],
        'history' => $history
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
