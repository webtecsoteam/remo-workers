async function handleLogin(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('login-btn');
  const errorDiv = document.getElementById('login-error');
  const btnText = btn.querySelector('.btn-text');
  const btnLoader = btn.querySelector('.btn-loader');
  
  // Reset
  errorDiv.style.display = 'none';
  btnText.style.display = 'none';
  btnLoader.style.display = 'inline-block';
  btn.disabled = true;

  try {
    const formData = new FormData(form);
    const response = await fetch(`${APP_URL}login`, {
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

async function handleRegister(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('register-btn');
  const errorDiv = document.getElementById('register-error');
  const btnText = btn.querySelector('.btn-text');
  const btnLoader = btn.querySelector('.btn-loader');
  
  errorDiv.style.display = 'none';
  btnText.style.display = 'none';
  btnLoader.style.display = 'inline-block';
  btn.disabled = true;

  try {
    const formData = new FormData(form);
    const response = await fetch(`${APP_URL}register`, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const result = await response.json();

    if (result.success) {
      window.location.href = result.redirect;
    } else {
      errorDiv.textContent = result.message || 'Registration failed.';
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

async function handleForgotPassword(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('forgot-btn');
  const errorDiv = document.getElementById('forgot-error');
  const successDiv = document.getElementById('forgot-success');
  const btnText = btn.querySelector('.btn-text');
  const btnLoader = btn.querySelector('.btn-loader');
  
  errorDiv.style.display = 'none';
  successDiv.style.display = 'none';
  btnText.style.display = 'none';
  btnLoader.style.display = 'inline-block';
  btn.disabled = true;

  try {
    const formData = new FormData(form);
    const response = await fetch(`${APP_URL}actions/forgot_password.php`, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const result = await response.json();

    if (result.success) {
      successDiv.textContent = result.message || 'Reset link sent successfully!';
      successDiv.style.display = 'block';
      form.reset();
    } else {
      errorDiv.textContent = result.message || 'An error occurred.';
      errorDiv.style.display = 'block';
    }
  } catch (err) {
    errorDiv.textContent = 'An error occurred. Please try again.';
    errorDiv.style.display = 'block';
  } finally {
    btnText.style.display = 'inline-block';
    btnLoader.style.display = 'none';
    btn.disabled = false;
  }
}
