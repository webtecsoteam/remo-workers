<?php
ob_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

$db = getDB();
$userId = (int) ($user['id'] ?? 0);
ensureAgencySchema();

try {
    $activeAgency = getActiveAgencyForUser($userId);
    if (($user['role'] ?? '') === 'freelancer' && !empty($user['account_mode']) && $user['account_mode'] === 'agency' && $activeAgency) {
        $agencyStartedAt = (string)($activeAgency['created_at'] ?? '1970-01-01 00:00:00');
        $memberStmt = $db->prepare("SELECT user_id FROM agency_members WHERE agency_id = ? AND status = 'active'");
        $memberStmt->execute([(int)$activeAgency['id']]);
        $memberIds = array_map('intval', $memberStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (empty($memberIds)) {
            json_response(['success' => true, 'unread_count' => 0]);
        }
        $memberPh = implode(',', array_fill(0, count($memberIds), '?'));
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM messages m
            WHERE m.receiver_id IN ($memberPh)
              AND m.is_read = 0
              AND m.sender_id NOT IN ($memberPh)
              AND m.created_at >= ?
        ");
        $stmt->execute(array_merge($memberIds, $memberIds, [$agencyStartedAt]));
    } else {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS cnt
            FROM messages
            WHERE receiver_id = ?
              AND is_read = 0
        ");
        $stmt->execute([$userId]);
    }
    $unreadCount = (int) $stmt->fetchColumn();

    json_response(['success' => true, 'unread_count' => $unreadCount]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

