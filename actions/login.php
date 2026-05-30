<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    handleCorsPreflight();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    applyCorsHeaders();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';
    $isAjax = isAjaxRequest();
    
    try {
        if (Auth::login($email, $password)) {
            $user = Auth::user();
            
            $targetUrl = baseUrl();
            if (!empty($_SESSION['post_job_after_login'])) {
                unset($_SESSION['post_job_after_login']);
                if (($user['role'] ?? '') === 'client') {
                    $targetUrl = baseUrl('client#post-job');
                } else {
                    $targetUrl = baseUrl('remoworkers-dashboard');
                }
            } elseif (!empty($_SESSION['redirect_to'])) {
                $targetUrl = baseUrl($_SESSION['redirect_to']);
                unset($_SESSION['redirect_to']);
            } elseif ($user['role'] === 'admin') {
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
