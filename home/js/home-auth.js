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

let referralLookupTimer = null;
let referralLookupRequestId = 0;

function normalizeReferralCode(value) {
  return String(value || '').trim().replace(/\s+/g, '').toUpperCase();
}

function setReferralStatus(state, message) {
  const statusEl = document.getElementById('register-referral-status');
  if (!statusEl) return;

  if (!message) {
    statusEl.style.display = 'none';
    statusEl.textContent = '';
    statusEl.className = 'auth-referral-status';
    return;
  }

  statusEl.style.display = 'block';
  statusEl.textContent = message;
  statusEl.className = 'auth-referral-status' + (state ? ' is-' + state : '');
}

async function verifyReferralCode(code) {
  const normalized = normalizeReferralCode(code);
  if (!normalized) {
    setReferralStatus('', '');
    return;
  }

  const requestId = ++referralLookupRequestId;
  setReferralStatus('loading', 'Checking referral code…');

  try {
    const response = await fetch(`${APP_URL}verify-referral?code=${encodeURIComponent(normalized)}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const result = await response.json();

    if (requestId !== referralLookupRequestId) return;

    if (!result.success) {
      setReferralStatus('warning', result.message || 'Unable to verify referral code.');
      return;
    }

    if (result.found) {
      setReferralStatus('valid', `Referred by: ${result.name}`);
      return;
    }

    setReferralStatus('warning', result.message || 'Referral code not found. You can still register without one.');
  } catch (err) {
    if (requestId !== referralLookupRequestId) return;
    setReferralStatus('warning', 'Unable to verify referral code right now.');
  }
}

function scheduleReferralLookup(code) {
  clearTimeout(referralLookupTimer);
  referralLookupTimer = setTimeout(() => verifyReferralCode(code), 400);
}

function initSignupReferralField() {
  if (window.REFERRAL_PROGRAM_ENABLED === false) return;

  const input = document.getElementById('register-referral-code');
  if (!input || input.dataset.referralBound === '1') return;

  input.dataset.referralBound = '1';

  const pendingCode = window.__pendingReferralCode || '';
  if (pendingCode) {
    input.value = normalizeReferralCode(pendingCode);
    window.__pendingReferralCode = '';
    verifyReferralCode(input.value);
  }

  input.addEventListener('input', () => {
    input.value = normalizeReferralCode(input.value);
    scheduleReferralLookup(input.value);
  });

  input.addEventListener('blur', () => {
    clearTimeout(referralLookupTimer);
    verifyReferralCode(input.value);
  });
}

const nativeOpenModal = window.openModal;
if (typeof nativeOpenModal === 'function') {
  window.openModal = function (id) {
    nativeOpenModal(id);
    if (id === 'signup') {
      initSignupReferralField();
    }
  };
}

function initSignupReferralIfPresent() {
  if (document.getElementById('register-referral-code')) {
    initSignupReferralField();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSignupReferralIfPresent);
} else {
  initSignupReferralIfPresent();
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
