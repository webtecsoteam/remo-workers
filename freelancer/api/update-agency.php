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

$name = trim((string)($_POST['name'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Agency name is required.']);
    exit;
}

try {
    $agency = getActiveAgencyForUser((int)$user['id']);
    if (!$agency) {
        throw new Exception('You do not belong to an agency.');
    }
    if (!in_array((string)$agency['member_role'], ['owner', 'admin'], true)) {
        throw new Exception('Only owner/admin can edit agency details.');
    }

    $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $name));
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'agency-' . (int)$agency['id'];
    }

    $slugStmt = $db->prepare("SELECT id FROM agencies WHERE slug = ? AND id <> ? LIMIT 1");
    $slugStmt->execute([$slug, (int)$agency['id']]);
    if ($slugStmt->fetchColumn()) {
        $slug .= '-' . (int)$agency['id'];
    }

    $stmt = $db->prepare("
        UPDATE agencies
        SET name = ?, slug = ?, description = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$name, $slug, $description !== '' ? $description : null, (int)$agency['id']]);

    echo json_encode(['success' => true, 'message' => 'Agency updated successfully.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
