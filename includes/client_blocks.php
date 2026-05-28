<?php

function clientHasBlockedFreelancer(PDO $db, int $clientId, int $freelancerId): bool
{
    if ($clientId <= 0 || $freelancerId <= 0) {
        return false;
    }
    $stmt = $db->prepare(
        'SELECT 1 FROM client_blocked_freelancers WHERE client_id = ? AND freelancer_id = ? LIMIT 1'
    );
    $stmt->execute([$clientId, $freelancerId]);
    return (bool) $stmt->fetchColumn();
}

function clientBlockedFreelancerIds(PDO $db, int $clientId): array
{
    if ($clientId <= 0) {
        return [];
    }
    $stmt = $db->prepare('SELECT freelancer_id FROM client_blocked_freelancers WHERE client_id = ?');
    $stmt->execute([$clientId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function clientBlockMessagingError(PDO $db, array $sender, int $receiverId): ?string
{
    $senderId = (int) ($sender['id'] ?? 0);
    $senderRole = $sender['role'] ?? '';

    if ($senderId <= 0 || $receiverId <= 0) {
        return null;
    }

    $roleStmt = $db->prepare('SELECT id, role FROM users WHERE id = ?');
    $roleStmt->execute([$receiverId]);
    $receiver = $roleStmt->fetch(PDO::FETCH_ASSOC);
    if (!$receiver) {
        return 'Receiver not found';
    }

    if ($senderRole === 'client' && $receiver['role'] === 'freelancer') {
        if (clientHasBlockedFreelancer($db, $senderId, $receiverId)) {
            return 'You have blocked this freelancer. Unblock them to send messages.';
        }
    } elseif ($senderRole === 'freelancer' && $receiver['role'] === 'client') {
        if (clientHasBlockedFreelancer($db, (int) $receiver['id'], $senderId)) {
            return 'This client is not accepting messages from you.';
        }
    }

    return null;
}

function clientMessagingBlockStatus(PDO $db, array $user, int $otherId): array
{
    $blocked = false;
    $blockedByMe = false;

    if ($otherId <= 0) {
        return ['blocked' => false, 'blocked_by_me' => false];
    }

    $roleStmt = $db->prepare('SELECT role FROM users WHERE id = ?');
    $roleStmt->execute([$otherId]);
    $otherRole = $roleStmt->fetchColumn();

    if (($user['role'] ?? '') === 'client' && $otherRole === 'freelancer') {
        $blockedByMe = clientHasBlockedFreelancer($db, (int) $user['id'], $otherId);
        $blocked = $blockedByMe;
    } elseif (($user['role'] ?? '') === 'freelancer' && $otherRole === 'client') {
        $blocked = clientHasBlockedFreelancer($db, $otherId, (int) $user['id']);
    }

    return ['blocked' => $blocked, 'blocked_by_me' => $blockedByMe];
}
