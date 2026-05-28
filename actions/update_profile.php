<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $name = $_POST['name'] ?? '';
    $title = $_POST['title'] ?? '';
    $rate = $_POST['hourly_rate'] ?? 0;
    $country = $_POST['country'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $avatarUrl = $user['avatar_url'] ?? '';

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                $avatarUrl = 'uploads/avatars/' . $newFileName;
            }
        }
    }

    if (empty($name)) {
        json_response(['success' => false, 'error' => 'Name is required']);
    }

    try {
        $stmt = $db->prepare("UPDATE users SET name = ?, title = ?, hourly_rate = ?, country = ?, bio = ?, avatar_url = ? WHERE id = ?");
        $stmt->execute([$name, $title, $rate, $country, $bio, $avatarUrl, $user['id']]);
        
        json_response(['success' => true, 'avatar_url' => $avatarUrl]);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
