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

try {
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
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(['success' => true, 'conversations' => $conversations]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
