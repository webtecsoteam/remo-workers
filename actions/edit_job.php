<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$job_id = intval($_POST['job_id'] ?? 0);
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? '';
$subcategory = $_POST['subcategory'] ?? '';
$specialty = $_POST['specialty'] ?? '';
$budget = (float)($_POST['budget'] ?? 0);
$budget_type = $_POST['budget_type'] ?? 'fixed';
$skills = $_POST['skills'] ?? '';

if (!$job_id || empty($title) || empty($description) || empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$db = getDB();
try {
    // Verify ownership
    $check = $db->prepare("SELECT id FROM jobs WHERE id = ? AND client_id = ?");
    $check->execute([$job_id, $user['id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Job not found or unauthorized']);
        exit;
    }

    $stmt = $db->prepare("UPDATE jobs SET title = ?, description = ?, category = ?, subcategory = ?, specialty = ?, skills_required = ?, budget = ?, budget_type = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([
        $title,
        $description,
        $category,
        $subcategory,
        $specialty,
        json_encode(explode(',', $skills)),
        $budget,
        $budget_type,
        $job_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Job updated successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
