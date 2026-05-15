<?php
require_once __DIR__ . '/../includes/classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    
    try {
        if (Auth::login($email, $password)) {
            $user = Auth::user();
            
            $targetUrl = baseUrl();
            if ($user['role'] === 'admin') {
                $targetUrl = baseUrl('admin');
            } elseif ($user['role'] === 'client') {
                $targetUrl = baseUrl('client');
            } else {
                $targetUrl = baseUrl('remoworkers-dashboard');
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => $targetUrl]);
                exit;
            }
            redirect($targetUrl);
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
                exit;
            }
            $errorPath = ($redirect === 'admin') ? 'admin/login?error=login_failed' : '?error=login_failed';
            header('Location: ' . baseUrl($errorPath));
            exit;
        }
    } catch (Exception $e) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        $errorPath = ($redirect === 'admin') ? 'admin/login?error=' : '?error=';
        header('Location: ' . baseUrl($errorPath . urlencode($e->getMessage())));
        exit;
    }
} else {
    redirect(baseUrl());
}
