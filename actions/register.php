<?php
require_once __DIR__ . '/../includes/classes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if ($name && $email && $password && $role) {
        try {
            if (Auth::register($name, $email, $password, $role)) {
                Auth::login($email, $password);
                if ($role === 'client') {
                    redirect(baseUrl('client'));
                } else {
                    redirect(baseUrl('remoworkers-dashboard'));
                }
            }
        } catch (Exception $e) {
            header('Location: ' . baseUrl('?error=registration_failed'));
            exit;
        }
    } else {
        header('Location: ' . baseUrl('?error=missing_fields'));
        exit;
    }
} else {
    redirect(baseUrl());
}
