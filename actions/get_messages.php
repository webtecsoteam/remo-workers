<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/client_blocks.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

$other_id = intval($_GET['with'] ?? 0);
if (!$other_id) {
    json_response(['success' => false, 'error' => 'Missing recipient ID']);
}

$db = getDB();
ensureAgencySchema();
try {
    $activeAgency = getActiveAgencyForUser((int)$user['id']);
    if (($user['role'] ?? '') === 'freelancer' && !empty($user['account_mode']) && $user['account_mode'] === 'agency' && $activeAgency) {
        $agencyId = (int)$activeAgency['id'];
        $agencyStartedAt = (string)($activeAgency['created_at'] ?? '1970-01-01 00:00:00');
        $memberStmt = $db->prepare("SELECT user_id FROM agency_members WHERE agency_id = ? AND status = 'active'");
        $memberStmt->execute([$agencyId]);
        $memberIds = array_map('intval', $memberStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (empty($memberIds)) {
            json_response(['success' => true, 'messages' => [], 'blocked' => false, 'blocked_by_me' => false]);
        }

        $memberPh = implode(',', array_fill(0, count($memberIds), '?'));
        $markSql = "UPDATE messages SET is_read = 1 WHERE receiver_id IN ($memberPh) AND sender_id = ? AND created_at >= ?";
        $markStmt = $db->prepare($markSql);
        $markStmt->execute(array_merge($memberIds, [$other_id, $agencyStartedAt]));

        $msgSql = "
            SELECT m.*, u.name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (
                (m.sender_id IN ($memberPh) AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id IN ($memberPh))
            )
              AND m.created_at >= ?
            ORDER BY m.created_at ASC
        ";
        $stmt = $db->prepare($msgSql);
        $stmt->execute(array_merge($memberIds, [$other_id, $other_id], $memberIds, [$agencyStartedAt]));
    } else {
        // Mark as read
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
        $stmt->execute([$user['id'], $other_id]);

        // Fetch messages
        $stmt = $db->prepare("
            SELECT m.*, u.name as sender_name 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?) 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user['id'], $other_id, $other_id, $user['id']]);
    }
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $blockStatus = clientMessagingBlockStatus($db, $user, $other_id);

    json_response([
        'success' => true,
        'messages' => $messages,
        'blocked' => $blockStatus['blocked'],
        'blocked_by_me' => $blockStatus['blocked_by_me'],
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
