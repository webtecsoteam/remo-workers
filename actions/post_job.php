<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$db = getDB();

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? '';
$subcategory = $_POST['subcategory'] ?? '';
$specialty = $_POST['specialty'] ?? '';
$budget = (float)($_POST['budget'] ?? 0);
$budget_type = $_POST['budget_type'] ?? 'fixed';
$skills = $_POST['skills'] ?? '';

if (empty($title) || empty($description) || empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Title, description, and category are required']);
    exit;
}

if (empty($subcategory)) {
    $subcategory = 'General';
}

try {
    $stmt = $db->prepare("INSERT INTO jobs (client_id, title, description, category, subcategory, specialty, skills_required, budget, budget_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')");
    $stmt->execute([
        $user['id'],
        $title,
        $description,
        $category,
        $subcategory,
        $specialty,
        json_encode(explode(',', $skills)),
        $budget,
        $budget_type
    ]);

    echo json_encode(['success' => true, 'message' => 'Job posted successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
