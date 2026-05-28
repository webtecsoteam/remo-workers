<?php

const MESSAGE_ATTACHMENT_MAX_BYTES = 10 * 1024 * 1024;

const MESSAGE_ATTACHMENT_ALLOWED_EXTS = [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar',
];

function messageAttachmentUploadDir() {
    $dir = BASE_PATH . '/uploads/messages/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function messageAttachmentValidateFile(array $file) {
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file received'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed'];
    }
    if (($file['size'] ?? 0) > MESSAGE_ATTACHMENT_MAX_BYTES) {
        return ['ok' => false, 'error' => 'File is too large (max 10MB)'];
    }

    $originalName = basename($file['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, MESSAGE_ATTACHMENT_ALLOWED_EXTS, true)) {
        return ['ok' => false, 'error' => 'File type not allowed'];
    }

    return ['ok' => true, 'ext' => $ext, 'original_name' => $originalName];
}

function messageAttachmentStore(array $file, int $userId) {
    $check = messageAttachmentValidateFile($file);
    if (!$check['ok']) {
        return $check;
    }

    $uploadDir = messageAttachmentUploadDir();
    $storedName = 'msg_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $check['ext'];
    $targetPath = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['ok' => false, 'error' => 'Failed to save file on server'];
    }

    $mime = $file['type'] ?? '';
    if (!$mime || $mime === 'application/octet-stream') {
        $detected = @mime_content_type($targetPath);
        if ($detected) {
            $mime = $detected;
        }
    }

    return [
        'ok' => true,
        'path' => 'uploads/messages/' . $storedName,
        'name' => $check['original_name'],
        'mime' => $mime ?: 'application/octet-stream',
    ];
}

function messageAttachmentUserCanAccess(array $user, array $message) {
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }
    $uid = (int)($user['id'] ?? 0);
    return $uid > 0 && ($uid === (int)$message['sender_id'] || $uid === (int)$message['receiver_id']);
}

function messageAttachmentDownloadUrl(int $messageId) {
    return baseUrl('actions/download_message_attachment.php?id=' . $messageId);
}
