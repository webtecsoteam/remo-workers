<?php
require_once __DIR__ . '/../includes/config.php';
ensureFreelancerSchema();

$token = $_GET['token'] ?? '';
$isValid = false;
$errorMessage = 'This password reset link is invalid or has expired.';

if (!empty($token)) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, name, email, password_reset_expires_at FROM users 
            WHERE password_reset_token = ?
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if ($user) {
            $expiry = strtotime($user['password_reset_expires_at']);
            if ($expiry > time()) {
                $isValid = true;
            } else {
                $errorMessage = 'This password reset link has expired.';
            }
        }
    } catch (Exception $e) {
        $errorMessage = 'Database connection error. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - RemoWorkers</title>
  <?php include __DIR__ . '/../includes/google-analytics.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --g: #14a800;
      --dark: #001e00;
      --border: #dce8d8;
      --muted: #617a5a;
      --bg: #f9fafb;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
    body {
      background: var(--bg);
      color: var(--dark);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .card {
      background: #ffffff;
      border: 1.5px solid var(--border);
      border-radius: 16px;
      padding: 35px;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 10px 25px -5px rgba(20,168,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
    }
    .logo {
      color: var(--g);
      font-size: 24px;
      font-weight: 800;
      text-align: center;
      margin-bottom: 25px;
      text-decoration: none;
      display: block;
    }
    h2 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 10px;
      text-align: center;
    }
    .subtitle {
      color: var(--muted);
      font-size: 13.5px;
      text-align: center;
      margin-bottom: 25px;
      line-height: 1.5;
    }
    .form-group {
      margin-bottom: 20px;
    }
    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    input {
      width: 100%;
      padding: 12px 16px;
      font-size: 14px;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      outline: none;
      transition: all 0.2s ease;
    }
    input:focus {
      border-color: var(--g);
      box-shadow: 0 0 0 3px rgba(20,168,0,0.1);
    }
    .btn {
      width: 100%;
      background: var(--g);
      color: #ffffff;
      border: none;
      padding: 14px;
      font-size: 15px;
      font-weight: 700;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.2s ease;
    }
    .btn:hover {
      background: #118f00;
      transform: translateY(-1px);
    }
    .btn:disabled {
      background: #a3e299;
      cursor: not-allowed;
      transform: none;
    }
    .error-div {
      background: rgba(239, 68, 68, 0.08);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.15);
      border-radius: 8px;
      padding: 12px;
      font-size: 13px;
      margin-bottom: 20px;
      text-align: center;
      display: none;
    }
    .success-div {
      background: rgba(20, 168, 0, 0.08);
      color: var(--g);
      border: 1px solid rgba(20, 168, 0, 0.15);
      border-radius: 8px;
      padding: 15px;
      font-size: 14px;
      margin-bottom: 25px;
      text-align: center;
      display: none;
      line-height: 1.5;
    }
    .error-card {
      text-align: center;
      padding: 40px 30px;
    }
    .error-icon {
      font-size: 40px;
      margin-bottom: 20px;
    }
    .back-link {
      color: var(--g);
      font-weight: 600;
      text-decoration: none;
      font-size: 13.5px;
      display: inline-block;
      margin-top: 15px;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="card">
    <a href="<?php echo baseUrl(); ?>" class="logo">RemoWorkers</a>
    
    <?php if (!$isValid): ?>
      <div class="error-card">
        <div class="error-icon">⚠️</div>
        <h2>Invalid Link</h2>
        <p class="subtitle"><?php echo htmlspecialchars($errorMessage); ?></p>
        <a href="<?php echo baseUrl(); ?>" class="back-link">Return to Home</a>
      </div>
    <?php else: ?>
      <h2>Reset Password</h2>
      <p class="subtitle">Set a secure password for your account.</p>
      
      <div id="error-box" class="error-div"></div>
      <div id="success-box" class="success-div"></div>
      
      <form id="reset-form" onsubmit="handleReset(event)">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="password" required placeholder="Min. 8 characters" minlength="8">
        </div>
        
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" required placeholder="Re-enter password" minlength="8">
        </div>
        
        <button type="submit" class="btn" id="submit-btn">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>

  <script>
    async function handleReset(e) {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById('submit-btn');
      const errorBox = document.getElementById('error-box');
      const successBox = document.getElementById('success-box');
      
      errorBox.style.display = 'none';
      btn.disabled = true;
      btn.textContent = 'Resetting Password...';
      
      try {
        const formData = new FormData(form);
        const response = await fetch('<?php echo baseUrl("actions/reset_password.php"); ?>', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          form.style.display = 'none';
          successBox.textContent = result.message;
          successBox.style.display = 'block';
          
          // Redirect to home with login modal after 3 seconds
          setTimeout(() => {
            window.location.href = '<?php echo baseUrl("?show_login=1&reset=success"); ?>';
          }, 3000);
        } else {
          errorBox.textContent = result.message || 'Failed to reset password.';
          errorBox.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Reset Password';
        }
      } catch (err) {
        errorBox.textContent = 'Connection error. Please try again.';
        errorBox.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Reset Password';
      }
    }
  </script>
</body>
</html>
