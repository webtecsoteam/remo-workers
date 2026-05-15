<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/classes/Auth.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // Simple auth using Auth class if available
    if (class_exists('Auth')) {
        $user = Auth::attempt($email, $password);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :e');
        $stmt->execute(['e' => $email]);
        $row = $stmt->fetch();
        $user = ($row && password_verify($password, $row['password'])) ? $row : null;
    }
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: '.baseUrl('freelancer/index.php'));
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Freelancer Login</title>
    <link rel="stylesheet" href="<?=baseUrl('css/style.css')?>">
    <style>
        .login-card{max-width:380px;margin:80px auto;padding:30px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
    </style>
</head>
<body class="bg">
    <div class="login-card">
        <h2 style="margin-bottom:20px;">Freelancer Login</h2>
        <?php if($error): ?>
            <div class="alert" style="color:#c00;margin-bottom:15px;">⚠️ <?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="you@example.com" required style="width:100%;padding:9px 12px;margin-bottom:10px;">
            <input type="password" name="password" placeholder="Password" required style="width:100%;padding:9px 12px;margin-bottom:15px;">
            <button type="submit" class="btn btn-g btn-block">Login →</button>
        </form>
    </div>
</body>
</html>
