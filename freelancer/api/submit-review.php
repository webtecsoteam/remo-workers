<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$db = getDB();
$contractId = $_POST['contract_id'] ?? 0;
$rating = (float)($_POST['rating'] ?? 5.0);
$feedback = trim($_POST['feedback'] ?? 'Great client to work with!');

if (!$contractId) {
    echo json_encode(['success' => false, 'error' => 'Contract ID is required']);
    exit;
}

try {
    // 1. Verify contract and freelancer ownership
    $stmt = $db->prepare("SELECT * FROM contracts WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$contractId, $user['id']]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        echo json_encode(['success' => false, 'error' => 'Contract not found or unauthorized']);
        exit;
    }

    if ($contract['status'] !== 'completed') {
        echo json_encode(['success' => false, 'error' => 'Contract is not completed yet']);
        exit;
    }

    // 2. Check if freelancer already reviewed client for this contract
    $check = $db->prepare("SELECT id FROM reviews WHERE contract_id = ? AND reviewer_id = ?");
    $check->execute([$contractId, $user['id']]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'You have already submitted review feedback for this contract']);
        exit;
    }

    // 3. Insert review
    $stmt = $db->prepare("
        INSERT INTO reviews (contract_id, reviewer_id, reviewee_id, rating, feedback)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $contractId,
        $user['id'], // freelancer
        $contract['client_id'], // client
        $rating,
        $feedback
    ]);

    echo json_encode(['success' => true, 'message' => 'Your review feedback has been submitted successfully!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
