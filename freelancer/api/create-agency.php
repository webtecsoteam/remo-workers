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

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Agency name is required.']);
    exit;
}

try {
    $db->beginTransaction();

    $existing = getActiveAgencyForUser((int)$user['id']);
    if ($existing) {
        throw new Exception('You already belong to an agency.');
    }

    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $slug = trim((string)$slug, '-');
    if ($slug === '') {
        $slug = 'agency-' . (int)$user['id'];
    }

    $stmt = $db->prepare("INSERT INTO agencies (owner_user_id, name, slug, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([(int)$user['id'], $name, $slug, $description !== '' ? $description : null]);
    $agencyId = (int)$db->lastInsertId();

    $mStmt = $db->prepare("INSERT INTO agency_members (agency_id, user_id, role, status, invited_by) VALUES (?, ?, 'owner', 'active', ?)");
    $mStmt->execute([$agencyId, (int)$user['id'], (int)$user['id']]);

    $uStmt = $db->prepare("UPDATE users SET account_mode = 'agency', agency_id = ? WHERE id = ?");
    $uStmt->execute([$agencyId, (int)$user['id']]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Agency created successfully.', 'agency_id' => $agencyId]);
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Could not create agency: ' . $e->getMessage()]);
}
