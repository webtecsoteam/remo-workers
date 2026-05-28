<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

header('Content-Type: application/json');

$suspendedError = Auth::suspendedClientError();
if ($suspendedError) {
    echo json_encode(['success' => false, 'error' => $suspendedError]);
    exit;
}

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

$min_hourly_rate = isset($_POST['min_hourly_rate']) && $_POST['min_hourly_rate'] !== '' ? (float)$_POST['min_hourly_rate'] : null;
$max_hourly_rate = isset($_POST['max_hourly_rate']) && $_POST['max_hourly_rate'] !== '' ? (float)$_POST['max_hourly_rate'] : null;

if (empty($title) || empty($description) || empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Title, description, and category are required']);
    exit;
}

$activeCategoryNames = array_column(getJobCategories(true), 'name');
if (!in_array($category, $activeCategoryNames, true)) {
    echo json_encode(['success' => false, 'error' => 'Please select a valid active category']);
    exit;
}

if (empty($subcategory)) {
    $subcategory = 'General';
}

try {
    $stmt = $db->prepare("INSERT INTO jobs (client_id, title, description, category, subcategory, specialty, skills_required, budget, budget_type, status, min_hourly_rate, max_hourly_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, ?)");
    $stmt->execute([
        $user['id'],
        $title,
        $description,
        $category,
        $subcategory,
        $specialty,
        json_encode(explode(',', $skills)),
        $budget,
        $budget_type,
        $min_hourly_rate,
        $max_hourly_rate
    ]);

    echo json_encode(['success' => true, 'message' => 'Job posted successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
