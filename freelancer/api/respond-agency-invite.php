<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

ensureAgencySchema();
$db = getDB();
$user = Auth::user();

$token = trim((string)($_GET['token'] ?? ''));
$action = strtolower(trim((string)($_GET['action'] ?? '')));
$isHtmlRequest = strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') === false;

if ($token === '' || !in_array($action, ['accept', 'decline'], true)) {
    http_response_code(400);
    $message = 'Invalid invitation request.';
    if ($isHtmlRequest) {
        echo "<h3>{$message}</h3>";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    }
    exit;
}

if (!$user || ($user['role'] ?? '') !== 'freelancer') {
    if ($isHtmlRequest) {
        // Keep this invite URL as post-login destination.
        $currentRequest = $_SERVER['REQUEST_URI'] ?? '';
        if ($currentRequest !== '') {
            $currentPath = parse_url($currentRequest, PHP_URL_PATH) ?: '';
            $currentQuery = parse_url($currentRequest, PHP_URL_QUERY) ?: '';
            $relativePath = ltrim((string)$currentPath, '/');

            // Store redirect path relative to APP_URL base path to avoid
            // duplicate prefixes like /remowork/remowork/... after login.
            $appPrefix = trim((string)appBasePathPrefix(), '/');
            if ($appPrefix !== '' && strpos($relativePath, $appPrefix . '/') === 0) {
                $relativePath = substr($relativePath, strlen($appPrefix) + 1);
            } elseif ($relativePath === $appPrefix) {
                $relativePath = '';
            }
            if ($relativePath !== '') {
                $_SESSION['redirect_to'] = $relativePath . ($currentQuery !== '' ? '?' . $currentQuery : '');
            }
        }
        redirect(baseUrl('?login=1'));
    }

    $message = 'Please log in to your freelancer account to respond to this invitation.';
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT ami.*, a.name AS agency_name
        FROM agency_member_invitations ami
        JOIN agencies a ON a.id = ami.agency_id
        WHERE ami.invitation_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invite) {
        throw new Exception('Invitation not found.');
    }
    if ((int)$invite['user_id'] !== (int)$user['id']) {
        throw new Exception('This invitation does not belong to your account.');
    }
    if (($invite['status'] ?? '') !== 'pending') {
        throw new Exception('This invitation has already been responded to.');
    }

    if ($action === 'accept') {
        $checkOther = $db->prepare("
            SELECT 1 FROM agency_members
            WHERE user_id = ? AND agency_id <> ? AND status = 'active'
            LIMIT 1
        ");
        $checkOther->execute([(int)$user['id'], (int)$invite['agency_id']]);
        if ($checkOther->fetchColumn()) {
            throw new Exception('You are already active in another agency.');
        }

        $db->beginTransaction();

        $upsertMember = $db->prepare("
            INSERT INTO agency_members (agency_id, user_id, role, status, invited_by)
            VALUES (?, ?, ?, 'active', ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role), status = 'active', invited_by = VALUES(invited_by)
        ");
        $upsertMember->execute([
            (int)$invite['agency_id'],
            (int)$user['id'],
            (string)$invite['role'],
            (int)$invite['invited_by']
        ]);

        $linkUser = $db->prepare("UPDATE users SET account_mode = 'agency', agency_id = ? WHERE id = ?");
        $linkUser->execute([(int)$invite['agency_id'], (int)$user['id']]);

        $updInvite = $db->prepare("
            UPDATE agency_member_invitations
            SET status = 'accepted', responded_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");
        $updInvite->execute([(int)$invite['id']]);

        $db->commit();
        $message = 'You have accepted the invitation and joined ' . ($invite['agency_name'] ?? 'the agency') . '.';
    } else {
        $updInvite = $db->prepare("
            UPDATE agency_member_invitations
            SET status = 'declined', responded_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");
        $updInvite->execute([(int)$invite['id']]);
        $message = 'You have declined this agency invitation.';
    }

    if ($isHtmlRequest) {
        $safeMessage = htmlspecialchars($message);
        echo "<div style='font-family:Arial,sans-serif;max-width:640px;margin:40px auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px'>
                <h2 style='margin-top:0'>Remoworkers Agency Invitation</h2>
                <p style='font-size:15px;line-height:1.6;color:#374151'>{$safeMessage}</p>
                <a href='" . baseUrl('freelancer/index.php?page=profile') . "' style='display:inline-block;margin-top:12px;background:#16a34a;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px'>Go to Dashboard</a>
              </div>";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message]);
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($isHtmlRequest) {
        $safeMessage = htmlspecialchars($e->getMessage());
        echo "<h3 style='font-family:Arial,sans-serif;margin:40px auto;max-width:640px;color:#b91c1c'>{$safeMessage}</h3>";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
