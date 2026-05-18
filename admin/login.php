<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

// If already logged in as admin, go to dashboard
$user = Auth::user();
if ($user && $user['role'] === 'admin') {
    header('Location: ' . baseUrl('admin'));
    exit;
}

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - RemoWorkers</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="<?php echo baseUrl("favicon.png?v=1.0.0"); ?>">
<style>
    :root {
        --bg: #0d1117;
        --surface: #161b22;
        --accent: #14a800;
        --accent-hover: #118a00;
        --text: #f0f6fc;
        --text-muted: #8b9db5;
        --border: #30363d;
        --error: #f85149;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--bg);
        color: var(--text);
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-card {
        background: var(--surface);
        padding: 40px;
        border-radius: 16px;
        width: 100%;
        max-width: 400px;
        border: 1px solid var(--border);
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .logo {
        text-align: center;
        margin-bottom: 30px;
    }
    .logo span {
        color: var(--accent);
        font-size: 24px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    h2 {
        font-size: 20px;
        font-weight: 500;
        margin-bottom: 24px;
        text-align: center;
    }
    .form-group {
        margin-bottom: 20px;
    }
    label {
        display: block;
        font-size: 14px;
        margin-bottom: 8px;
        color: var(--text-muted);
    }
    input {
        width: 100%;
        padding: 12px 16px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--text);
        font-family: inherit;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
    }
    input:focus {
        border-color: var(--accent);
    }
    .btn {
        width: 100%;
        padding: 12px;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn:hover {
        background: var(--accent-hover);
    }
    .error-msg {
        background: rgba(248, 81, 73, 0.1);
        border: 1px solid var(--error);
        color: var(--error);
        padding: 10px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 20px;
        text-align: center;
    }
    .footer {
        margin-top: 24px;
        text-align: center;
        font-size: 12px;
        color: var(--text-muted);
    }
    .footer a {
        color: var(--accent);
        text-decoration: none;
    }
</style>
</head>
<body>

<div class="login-card">
    <div class="logo">
        <span>⬡ RemoAdmin</span>
    </div>
    
    <h2>Welcome Back</h2>
    
    <div id="login-error" class="error-msg" style="display:<?php echo $error ? 'block' : 'none'; ?>">
        <?php 
            if ($error === 'login_failed') echo 'Invalid email or password.';
            else if ($error === 'unauthorized') echo 'Please login to access the admin panel.';
            else if ($error) echo 'An error occurred. Please try again.';
        ?>
    </div>
    
    <form id="admin-login-form" onsubmit="handleAdminLogin(event)">
        <input type="hidden" name="redirect" value="admin">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="admin@remoworkers.com">
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        
        <button type="submit" class="btn" id="login-btn">
            <span class="btn-text">Sign In</span>
            <span class="btn-loader" style="display:none;"><svg class="spinner" viewBox="0 0 50 50" style="width:20px;height:20px;"><circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5" style="stroke:white;"></circle></svg></span>
        </button>
    </form>
    
    <div class="footer">
        <p>Back to <a href="<?php echo baseUrl(); ?>">RemoWorkers Home</a></p>
    </div>
</div>

<style>
@keyframes spin{to{transform:rotate(360deg)}}
.spinner{animation:spin 0.8s linear infinite;transform-origin:center;}
</style>

<script>
async function handleAdminLogin(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('login-btn');
  const errorDiv = document.getElementById('login-error');
  const btnText = btn.querySelector('.btn-text');
  const btnLoader = btn.querySelector('.btn-loader');
  
  errorDiv.style.display = 'none';
  btnText.style.display = 'none';
  btnLoader.style.display = 'inline-block';
  btn.disabled = true;

  try {
    const formData = new FormData(form);
    const response = await fetch('<?php echo baseUrl("login"); ?>', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const result = await response.json();

    if (result.success) {
      window.location.href = result.redirect;
    } else {
      errorDiv.textContent = result.message || 'Invalid email or password.';
      errorDiv.style.display = 'block';
      btnText.style.display = 'inline-block';
      btnLoader.style.display = 'none';
      btn.disabled = false;
    }
  } catch (err) {
    errorDiv.textContent = 'An error occurred. Please try again.';
    errorDiv.style.display = 'block';
    btnText.style.display = 'inline-block';
    btnLoader.style.display = 'none';
    btn.disabled = false;
  }
}
</script>
</body>
</html>
