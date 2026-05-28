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

try {
    $agency = getActiveAgencyForUser((int)$user['id']);
    if (!$agency) {
        throw new Exception('You do not belong to an agency.');
    }
    if (!in_array((string)$agency['member_role'], ['owner', 'admin'], true)) {
        throw new Exception('Only owner/admin can delete the agency.');
    }

    $agencyId = (int)$agency['id'];

    $db->beginTransaction();

    $resetUsers = $db->prepare("
        UPDATE users
        SET account_mode = 'individual', agency_id = NULL
        WHERE agency_id = ?
    ");
    $resetUsers->execute([$agencyId]);

    // Hard cleanup in case FK cascade is not present on older installs.
    $deleteInvites = $db->prepare("DELETE FROM agency_member_invitations WHERE agency_id = ?");
    $deleteInvites->execute([$agencyId]);

    $deleteMembers = $db->prepare("DELETE FROM agency_members WHERE agency_id = ?");
    $deleteMembers->execute([$agencyId]);

    $deleteAgency = $db->prepare("DELETE FROM agencies WHERE id = ? LIMIT 1");
    $deleteAgency->execute([$agencyId]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Agency deleted successfully.']);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
