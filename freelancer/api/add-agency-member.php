<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/classes/Mailer.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

ensureAgencySchema();
$db = getDB();

$email = strtolower(trim($_POST['email'] ?? ''));
$role = strtolower(trim($_POST['role'] ?? 'member'));
if (!in_array($role, ['admin', 'member'], true)) {
    $role = 'member';
}
if ($email === '') {
    echo json_encode(['success' => false, 'message' => 'Freelancer email is required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $agency = getActiveAgencyForUser((int)$user['id']);
    if (!$agency) {
        throw new Exception('You do not belong to an agency.');
    }
    if (!in_array($agency['member_role'], ['owner', 'admin'], true)) {
        throw new Exception('Only owner/admin can add members.');
    }

    $uStmt = $db->prepare("SELECT id, role, name, is_verified FROM users WHERE email = ? LIMIT 1");
    $uStmt->execute([$email]);
    $targetUser = $uStmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        throw new Exception('The freelancer is not existing on Remoworkers.');
    }

    if (($targetUser['role'] ?? '') !== 'freelancer') {
        throw new Exception('A non-freelancer account already exists with this email.');
    }

    if (!(bool)($targetUser['is_verified'] ?? false)) {
        throw new Exception('Only freelancers with verified Remoworkers profiles can be added to an agency.');
    }

    $targetUserId = (int)$targetUser['id'];
    $checkOther = $db->prepare("
        SELECT 1 FROM agency_members
        WHERE user_id = ? AND agency_id <> ? AND status = 'active'
        LIMIT 1
    ");
    $checkOther->execute([$targetUserId, (int)$agency['id']]);
    if ($checkOther->fetchColumn()) {
        throw new Exception('This freelancer is already active in another agency.');
    }

    $checkActiveInAgency = $db->prepare("
        SELECT 1 FROM agency_members
        WHERE agency_id = ? AND user_id = ? AND status = 'active'
        LIMIT 1
    ");
    $checkActiveInAgency->execute([(int)$agency['id'], $targetUserId]);
    if ($checkActiveInAgency->fetchColumn()) {
        throw new Exception('This freelancer is already an active member of your agency.');
    }

    $pendingCheck = $db->prepare("
        SELECT id FROM agency_member_invitations
        WHERE agency_id = ? AND user_id = ? AND status = 'pending'
        LIMIT 1
    ");
    $pendingCheck->execute([(int)$agency['id'], $targetUserId]);
    if ($pendingCheck->fetchColumn()) {
        throw new Exception('An invitation is already pending for this freelancer.');
    }

    $inviteToken = bin2hex(random_bytes(32));
    $inviteStmt = $db->prepare("
        INSERT INTO agency_member_invitations (agency_id, user_id, email, role, status, invitation_token, invited_by)
        VALUES (?, ?, ?, ?, 'pending', ?, ?)
    ");
    $inviteStmt->execute([(int)$agency['id'], $targetUserId, $email, $role, $inviteToken, (int)$user['id']]);

    $acceptUrl = baseUrl('freelancer/api/respond-agency-invite.php?token=' . urlencode($inviteToken) . '&action=accept');
    $declineUrl = baseUrl('freelancer/api/respond-agency-invite.php?token=' . urlencode($inviteToken) . '&action=decline');
    $inviterName = htmlspecialchars((string)($user['name'] ?? 'Agency Owner'));
    $agencyName = htmlspecialchars((string)($agency['name'] ?? 'Agency'));
    $memberName = htmlspecialchars((string)($targetUser['name'] ?? 'Freelancer'));

    $subject = 'Agency Invitation from ' . ($agency['name'] ?? 'Remoworkers Agency');
    $emailHtml = "
    <div style='font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px;background:#ffffff;'>
      <h2 style='margin:0 0 14px;color:#111827;'>Agency Invitation</h2>
      <p style='color:#374151;font-size:14px;line-height:1.6;'>Hello {$memberName},</p>
      <p style='color:#374151;font-size:14px;line-height:1.6;'>
        <strong>{$inviterName}</strong> invited you to join <strong>{$agencyName}</strong> as an <strong>" . htmlspecialchars(ucfirst($role)) . "</strong> on Remoworkers.
      </p>
      <p style='color:#6b7280;font-size:13px;line-height:1.5;'>
        Please choose one of the options below:
      </p>
      <div style='margin:24px 0;display:flex;gap:10px;flex-wrap:wrap;'>
        <a href='{$acceptUrl}' style='display:inline-block;background:#16a34a;color:#fff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:700;'>Accept</a>
        <a href='{$declineUrl}' style='display:inline-block;background:#ef4444;color:#fff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:700;'>Decline</a>
      </div>
      <p style='color:#9ca3af;font-size:12px;line-height:1.5;margin:0;'>
        You must be logged into your Remoworkers freelancer account to respond.
      </p>
    </div>";

    $emailText = "Hello {$targetUser['name']},\n\n"
        . "{$user['name']} invited you to join {$agency['name']} as " . ucfirst($role) . " on Remoworkers.\n\n"
        . "Accept: {$acceptUrl}\n"
        . "Decline: {$declineUrl}\n\n"
        . "You must be logged into your Remoworkers freelancer account to respond.";

    Mailer::send($email, $subject, $emailHtml, $emailText);

    echo json_encode(['success' => true, 'message' => 'Invitation sent successfully.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
