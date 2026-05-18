<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    // Mock user fallback
    $db = getDB();
    $stmt = $db->query("SELECT * FROM users WHERE id = 2");
    $user = $stmt->fetch();
}

$input = json_decode(file_get_contents('php://input'), true);
$contract_id = $input['contract_id'] ?? null;
$hours = $input['hours'] ?? null;
$amount = $input['amount'] ?? 0;
$description = $input['description'] ?? null;
$attachments = $input['attachments'] ?? null;

if (!$contract_id || (!$hours && !$description && !$amount)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $db = getDB();

    // Fetch contract to determine rate and calculate amount automatically
    $cStmt = $db->prepare("SELECT * FROM contracts WHERE id = ?");
    $cStmt->execute([$contract_id]);
    $contract = $cStmt->fetch();
    
    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'Contract not found']);
        exit;
    }

    if ($contract['contract_type'] === 'hourly') {
        $amount = (float)$hours * (float)$contract['amount']; // contract['amount'] holds the hourly rate
    }

    $work_date = $input['work_date'] ?? null;
    $created_at = null;
    if ($work_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $work_date)) {
        $created_at = $work_date . ' ' . date('H:i:s');
    }

    $start_time = $input['start_time'] ?? null;
    $end_time = $input['end_time'] ?? null;
    $log_type = $input['log_type'] ?? 'auto';

    if ($created_at) {
        $stmt = $db->prepare("INSERT INTO work_logs (contract_id, freelancer_id, amount, hours, description, attachments, created_at, start_time, end_time, log_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$contract_id, $user['id'], $amount, $hours, $description, $attachments, $created_at, $start_time, $end_time, $log_type]);
    } else {
        $stmt = $db->prepare("INSERT INTO work_logs (contract_id, freelancer_id, amount, hours, description, attachments, start_time, end_time, log_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$contract_id, $user['id'], $amount, $hours, $description, $attachments, $start_time, $end_time, $log_type]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Work logged successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
