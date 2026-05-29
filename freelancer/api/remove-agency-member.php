<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'freelancer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

ensureAgencySchema();
$db = getDB();

$memberId = (int)($_POST['member_id'] ?? 0);
if ($memberId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid member selected.']);
    exit;
}

try {
    $agency = getActiveAgencyForUser((int)$user['id']);
    if (!$agency) {
        throw new Exception('You do not belong to an agency.');
    }
    if ((string)$agency['member_role'] !== 'owner') {
        throw new Exception('Only the agency owner can remove admins or members.');
    }

    $agencyId = (int)$agency['id'];
    if ($memberId === (int)$user['id']) {
        throw new Exception('You cannot remove your own membership.');
    }

    $db->beginTransaction();

    $memberStmt = $db->prepare("
        SELECT am.user_id, am.role
        FROM agency_members am
        WHERE am.agency_id = ? AND am.user_id = ? AND am.status = 'active'
        LIMIT 1
    ");
    $memberStmt->execute([$agencyId, $memberId]);
    $targetMember = $memberStmt->fetch(PDO::FETCH_ASSOC);

    $inviteStmt = $db->prepare("
        SELECT id
        FROM agency_member_invitations
        WHERE agency_id = ? AND user_id = ? AND status = 'pending'
        LIMIT 1
    ");
    $inviteStmt->execute([$agencyId, $memberId]);
    $pendingInviteId = (int)($inviteStmt->fetchColumn() ?: 0);

    if (!$targetMember && $pendingInviteId <= 0) {
        throw new Exception('Member not found in your agency.');
    }

    if ($targetMember && ((string)$targetMember['role'] === 'owner')) {
        throw new Exception('Agency owner cannot be removed.');
    }

    if ($targetMember) {
        $removeMemberStmt = $db->prepare("
            UPDATE agency_members
            SET status = 'inactive'
            WHERE agency_id = ? AND user_id = ?
            LIMIT 1
        ");
        $removeMemberStmt->execute([$agencyId, $memberId]);

        $unlinkUserStmt = $db->prepare("
            UPDATE users
            SET account_mode = 'individual', agency_id = NULL
            WHERE id = ? AND agency_id = ?
            LIMIT 1
        ");
        $unlinkUserStmt->execute([$memberId, $agencyId]);
    }

    if ($pendingInviteId > 0) {
        $cancelInviteStmt = $db->prepare("
            UPDATE agency_member_invitations
            SET status = 'declined', responded_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");
        $cancelInviteStmt->execute([$pendingInviteId]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Agency member removed successfully.']);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
