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
$userId = (int) $user['id'];
ensureAgencySchema();

try {
    $activeAgency = getActiveAgencyForUser($userId);
    if (($user['role'] ?? '') === 'freelancer' && !empty($user['account_mode']) && $user['account_mode'] === 'agency' && $activeAgency) {
        $agencyId = (int) $activeAgency['id'];
        $agencyStartedAt = (string)($activeAgency['created_at'] ?? '1970-01-01 00:00:00');

        $memberStmt = $db->prepare("SELECT user_id FROM agency_members WHERE agency_id = ? AND status = 'active'");
        $memberStmt->execute([$agencyId]);
        $memberIds = array_map('intval', $memberStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (empty($memberIds)) {
            json_response(['success' => true, 'conversations' => []]);
        }

        $memberPh = implode(',', array_fill(0, count($memberIds), '?'));
        $sql = "
            SELECT u.id as other_id, u.name as other_name, u.avatar_url as other_avatar,
                   m1.message as last_message, m1.created_at as last_time, m1.is_read, m1.sender_id
            FROM users u
            JOIN (
                SELECT MAX(id) as max_id,
                       CASE
                           WHEN sender_id IN ($memberPh) THEN receiver_id
                           ELSE sender_id
                       END as other_user_id
                FROM messages
                WHERE (
                    sender_id IN ($memberPh)
                    OR receiver_id IN ($memberPh)
                )
                AND created_at >= ?
                GROUP BY other_user_id
                HAVING other_user_id NOT IN ($memberPh)
            ) m2 ON u.id = m2.other_user_id
            JOIN messages m1 ON m1.id = m2.max_id
            ORDER BY m1.created_at DESC
        ";
        $params = array_merge($memberIds, $memberIds, [$agencyStartedAt], $memberIds);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $db->prepare("
            SELECT
                u.id as other_id, u.name as other_name, u.avatar_url as other_avatar,
                m1.message as last_message, m1.created_at as last_time, m1.is_read, m1.sender_id
            FROM users u
            JOIN (
                SELECT
                    MAX(id) as max_id,
                    CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY other_user_id
            ) m2 ON u.id = m2.other_user_id
            JOIN messages m1 ON m1.id = m2.max_id
            WHERE u.id NOT IN (
                SELECT freelancer_id
                FROM client_blocked_freelancers
                WHERE client_id = ?
            )
            ORDER BY m1.created_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
    }
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(['success' => true, 'conversations' => $conversations]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
