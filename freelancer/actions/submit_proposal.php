<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $job_id = (int)$_POST['job_id'];
    $bid_amount = (float)$_POST['bid_amount'];
    $cover_letter = $_POST['cover_letter'];
    $connects_cost = 4;

    try {
        $db->beginTransaction();

        // 1. Check if already applied
        $check = $db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
        $check->execute([$job_id, $user['id']]);
        if ($check->fetch()) {
            throw new Exception("You have already submitted a proposal for this job.");
        }

        // 2. Check connects balance
        $uStmt = $db->prepare("SELECT connects FROM users WHERE id = ?");
        $uStmt->execute([$user['id']]);
        $currentConnects = $uStmt->fetchColumn();

        if ($currentConnects < $connects_cost) {
            throw new Exception("Not enough connects. You need $connects_cost connects to apply.");
        }

        // 3. Insert proposal
        $ins = $db->prepare("INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status) VALUES (?, ?, ?, ?, 'pending')");
        $ins->execute([$job_id, $user['id'], $bid_amount, $cover_letter]);

        // 4. Deduct connects
        $upd = $db->prepare("UPDATE users SET connects = connects - ? WHERE id = ?");
        $upd->execute([$connects_cost, $user['id']]);

        $db->commit();
        echo json_encode(['success' => true, 'new_connects' => $currentConnects - $connects_cost]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
