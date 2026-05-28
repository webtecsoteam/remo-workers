<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'freelancer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

if (!Auth::isEmailVerified($user)) {
    echo json_encode(['success' => false, 'error' => 'Please verify your email before applying to jobs.']);
    exit;
}

if (!Auth::isIdentityVerified($user)) {
    echo json_encode(['success' => false, 'error' => 'Please complete identity verification before applying to jobs.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $job_id = (int)$_POST['job_id'];
    $bid_amount = (float)$_POST['bid_amount'];
    $cover_letter = trim($_POST['cover_letter'] ?? '');
    $connects_cost = Auth::CONNECTS_PER_APPLICATION;

    try {
        $db->beginTransaction();

        $check = $db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
        $check->execute([$job_id, $user['id']]);
        if ($check->fetch()) {
            throw new Exception("You have already submitted a proposal for this job.");
        }

        $uStmt = $db->prepare("SELECT connects FROM users WHERE id = ? FOR UPDATE");
        $uStmt->execute([$user['id']]);
        $currentConnects = (int)$uStmt->fetchColumn();

        if ($currentConnects < $connects_cost) {
            throw new Exception("Not enough connects. You need $connects_cost connects to apply.");
        }

        $ins = $db->prepare("INSERT INTO proposals (job_id, freelancer_id, bid_amount, cover_letter, status) VALUES (?, ?, ?, ?, 'pending')");
        $ins->execute([$job_id, $user['id'], $bid_amount, $cover_letter]);

        $upd = $db->prepare("UPDATE users SET connects = connects - ? WHERE id = ?");
        $upd->execute([$connects_cost, $user['id']]);

        $db->commit();
        echo json_encode(['success' => true, 'new_connects' => $currentConnects - $connects_cost]);

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
