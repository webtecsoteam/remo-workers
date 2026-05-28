<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = getDB();
$contractId = $_GET['contract_id'] ?? 0;

if (!$contractId) {
    echo json_encode(['success' => false, 'error' => 'Contract ID is required']);
    exit;
}

try {
    // 1. Fetch contract
    $stmt = $db->prepare("SELECT c.*, u.name as client_name FROM contracts c JOIN users u ON c.client_id = u.id WHERE c.id = ?");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract || ($contract['freelancer_id'] != $user['id'] && $contract['client_id'] != $user['id'])) {
        echo json_encode(['success' => false, 'error' => 'Contract not found or unauthorized']);
        exit;
    }

    // 2. Check for review from Client to Freelancer
    $stmt = $db->prepare("SELECT * FROM reviews WHERE contract_id = ? AND reviewer_id = ? AND reviewee_id = ?");
    $stmt->execute([$contractId, $contract['client_id'], $contract['freelancer_id']]);
    $clientReview = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // 3. Check for review from Freelancer to Client
    $stmt = $db->prepare("SELECT * FROM reviews WHERE contract_id = ? AND reviewer_id = ? AND reviewee_id = ?");
    $stmt->execute([$contractId, $contract['freelancer_id'], $contract['client_id']]);
    $freelancerReview = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    echo json_encode([
        'success' => true,
        'client_review' => $clientReview,
        'freelancer_review' => $freelancerReview,
        'client_name' => $contract['client_name'] ?? 'Client'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
