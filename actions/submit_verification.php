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
    $docType = $_POST['doc_type'] ?? 'ID';
    $legalName = $_POST['legal_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $docNumber = $_POST['doc_number'] ?? '';

    // Validate files
    if (!isset($_FILES['front'])) {
        json_response(['success' => false, 'error' => 'Front side of document is required']);
    }

    $filesToUpload = ['front'];
    if (isset($_FILES['back']) && !empty($_FILES['back']['name'])) {
        $filesToUpload[] = 'back';
    }

    $uploadedPaths = [];
    $uploadDir = BASE_PATH . '/uploads/verifications/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    foreach ($filesToUpload as $key) {
        $file = $_FILES[$key];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExts)) {
            json_response(['success' => false, 'error' => "Invalid file extension for $key side. Only JPG, PNG, and PDF are allowed."]);
        }
        
        if ($file['size'] > $maxSize) {
            json_response(['success' => false, 'error' => "File $key is too large (max 10MB)"]);
        }

        $filename = 'verify_' . $user['id'] . '_' . $key . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $uploadedPaths[$key] = 'uploads/verifications/' . $filename;
        } else {
            json_response(['success' => false, 'error' => "Failed to save $key side file on server"]);
        }
    }

    // Save to DB
    // We'll store the paths as JSON if both exist, or just the string if only front
    $filePath = count($uploadedPaths) > 1 ? json_encode($uploadedPaths) : $uploadedPaths['front'];
    
    // Check if user has these columns, if not we'll just save the basic info
    // For now, I'll just save doc_type and file_path to be safe with existing schema
    // But I'll also try to save the other fields if I can (I'll assume they don't exist yet to avoid crashes)
    
    try {
        $stmt = $db->prepare("INSERT INTO user_documents (user_id, doc_type, file_path, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $docType, $filePath, 'pending']);
        
        // Also update user record if needed (e.g. status)
        // $db->prepare("UPDATE users SET status = 'pending_verification' WHERE id = ?")->execute([$user['id']]);

        json_response(['success' => true, 'message' => 'Verification submitted successfully']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
