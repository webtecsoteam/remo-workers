<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/message_attachments.php';

$user = Auth::user();
if (!$user) {
    http_response_code(401);
    exit('Unauthorized');
}

$messageId = (int)($_GET['id'] ?? 0);
if ($messageId <= 0) {
    http_response_code(400);
    exit('Invalid message');
}

$db = getDB();
$stmt = $db->prepare('SELECT id, sender_id, receiver_id, attachment_path, attachment_name, attachment_mime FROM messages WHERE id = ?');
$stmt->execute([$messageId]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message || empty($message['attachment_path'])) {
    http_response_code(404);
    exit('Attachment not found');
}

if (!messageAttachmentUserCanAccess($user, $message)) {
    http_response_code(403);
    exit('Forbidden');
}

$fullPath = BASE_PATH . '/' . ltrim($message['attachment_path'], '/');
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

$downloadName = $message['attachment_name'] ?: basename($fullPath);
$mime = $message['attachment_mime'] ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, no-cache');
readfile($fullPath);
exit;
