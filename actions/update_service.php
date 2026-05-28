<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $id = $_POST['id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $deliveryDays = $_POST['delivery_days'] ?? 1;

    // Verify ownership and get current image
    $check = $db->prepare("SELECT image_url FROM services WHERE id = ? AND freelancer_id = ?");
    $check->execute([$id, $user['id']]);
    $service = $check->fetch();
    if (!$service) {
        json_response(['success' => false, 'error' => 'Unauthorized or project not found']);
    }

    $imageUrl = $service['image_url'];

    // Handle New Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = BASE_PATH . '/uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imageUrl = baseUrl('uploads/services/' . $fileName);
        }
    }

    if (empty($id) || empty($title) || empty($description) || $price <= 0) {
        json_response(['success' => false, 'error' => 'Please fill all required fields']);
    }

    try {
        $stmt = $db->prepare("UPDATE services SET title = ?, description = ?, price = ?, delivery_days = ?, image_url = ? WHERE id = ? AND freelancer_id = ?");
        $stmt->execute([$title, $description, $price, $deliveryDays, $imageUrl, $id, $user['id']]);
        
        json_response(['success' => true, 'message' => 'Project updated successfully']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
 else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
