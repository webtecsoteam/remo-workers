<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function logLoginError(string $type, string $message, array $context = []): void
{
    $logDir = __DIR__ . '/../scratch';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/login_errors.log';
    $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $entry = sprintf(
        "[%s] %s | %s | %s\n",
        date('Y-m-d H:i:s'),
        $type,
        $message,
        json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

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
            logLoginError('invalid_credentials', 'Invalid email or password.', [
                'email' => $email,
                'redirect' => $redirect,
                'ajax' => $isAjax,
            ]);
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
        logLoginError('exception', $e->getMessage(), [
            'email' => $email,
            'redirect' => $redirect,
            'ajax' => $isAjax,
        ]);
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
