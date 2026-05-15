<?php
require_once __DIR__ . '/../includes/classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (Auth::login($email, $password)) {
        $user = Auth::user();
        if ($user['role'] === 'client') {
            redirect(baseUrl('client'));
        } else {
            redirect(baseUrl('remoworkers-dashboard'));
        }
    } else {
        // Redirect back with error
        header('Location: ' . baseUrl('?error=login_failed'));
        exit;
    }
} else {
    redirect(baseUrl());
}
