<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    handleCorsPreflight();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    applyCorsHeaders();
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $country = $_POST['country'] ?? '';
    
    $isAjax = isAjaxRequest();
    
    if ($name && $email && $password && $role) {
        if ($role === 'admin') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized role.']);
                exit;
            }
            header('Location: ' . baseUrl('?error=unauthorized_role'));
            exit;
        }
        
        try {
            if (Auth::register($name, $email, $password, $role, $country)) {
                Auth::login($email, $password);
                $targetUrl = ($role === 'client') ? baseUrl('client') : baseUrl('remoworkers-dashboard');
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => $targetUrl]);
                    exit;
                }
                redirect($targetUrl);
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate entry') !== false) $msg = 'Email already exists';
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            header('Location: ' . baseUrl('?error=' . urlencode($msg)));
            exit;
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
            exit;
        }
        header('Location: ' . baseUrl('?error=missing_fields'));
        exit;
    }
} else {
    redirect(baseUrl());
}
