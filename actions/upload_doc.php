<?php
ob_start();
header('Content-Type: application/json');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $db = getDB();
    $docType = $_POST['doc_type'] ?? 'ID';
    $file = $_FILES['document'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'image/jpg'];
    // We can also check extension if mime type is tricky
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($ext, $allowedExts)) {
        json_response(['success' => false, 'error' => 'Invalid file extension. Only JPG, PNG, and PDF are allowed.']);
    }
    
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        json_response(['success' => false, 'error' => 'File is too large (max 10MB)']);
    }
    
    // Ensure directory exists
    $uploadDir = BASE_PATH . '/uploads/verifications/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Save file
    $filename = 'verify_' . $user['id'] . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Save to DB
        $stmt = $db->prepare("INSERT INTO user_documents (user_id, doc_type, file_path, status) VALUES (?, ?, ?, ?)");
        $relativePath = 'uploads/verifications/' . $filename;
        $stmt->execute([$user['id'], $docType, $relativePath, 'pending']);
        
        json_response(['success' => true]);
    } else {
        json_response(['success' => false, 'error' => 'Failed to save file on server']);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request. No file received.']);
}
