<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$user = Auth::user();
if (!$user || $user['role'] !== 'admin') {
    header('Location: ' . baseUrl('admin/login?error=unauthorized'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Panel - RemoWorkers</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #f4f2ee;
    --surface: #ffffff;
    --sidebar-bg: #0d1117;
    --sidebar-text: #8b9db5;
    --sidebar-active: #ffffff;
    --sidebar-hover: #1c2333;
    --accent: #14a800;
    --accent-hover: #118a00;
    --accent-light: #e6f5e6;
    --text: #1a1a2e;
    --text-2: #6b7280;
    --text-3: #9ca3af;
    --border: #e5e7eb;
    --red: #ef4444;
    --red-light: #fef2f2;
    --amber: #f59e0b;
    --amber-light: #fffbeb;
    --blue: #3b82f6;
    --blue-light: #eff6ff;
    --radius: 10px;
    --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
  }
  body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

  /* Sidebar */
  #sidebar {
    width: 240px; min-height: 100vh; background: var(--sidebar-bg);
    display: flex; flex-direction: column; position: fixed; left: 0; top: 0; bottom: 0; z-index: 100;
  }
  .logo { padding: 24px 20px 20px; border-bottom: 1px solid #1c2333; }
  .logo span { color: var(--accent); font-size: 18px; font-weight: 600; letter-spacing: -0.3px; }
  .logo small { display: block; color: var(--sidebar-text); font-size: 11px; margin-top: 2px; }
  nav { padding: 16px 12px; flex: 1; }
  .nav-section { margin-bottom: 24px; }
  .nav-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #3d4f66; padding: 0 8px; margin-bottom: 6px; font-weight: 500; }
  .nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; cursor: pointer; transition: all 0.15s; color: var(--sidebar-text); font-size: 13.5px; font-weight: 400; margin-bottom: 2px; }
  .nav-item:hover { background: var(--sidebar-hover); color: #d1d8e4; }
  .nav-item.active { background: #1c2f4a; color: var(--sidebar-active); font-weight: 500; }
  .nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }
  .nav-badge { margin-left: auto; background: var(--red); color: #fff; font-size: 10px; font-weight: 600; border-radius: 10px; padding: 1px 6px; }
  .sidebar-bottom { padding: 16px 12px; border-top: 1px solid #1c2333; }
  .user-chip { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 8px; cursor: pointer; }
  .user-chip:hover { background: var(--sidebar-hover); }
  .avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 600; flex-shrink: 0; }
  .user-info .name { font-size: 13px; color: #d1d8e4; font-weight: 500; }
  .user-info .role { font-size: 11px; color: var(--sidebar-text); }

  /* Main */
  #main { margin-left: 240px; flex: 1; min-height: 100vh; display: flex; flex-direction: column; }
  header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 32px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
  .header-left { display: flex; align-items: center; gap: 8px; }
  .page-title { font-size: 16px; font-weight: 600; color: var(--text); }
  .breadcrumb { font-size: 13px; color: var(--text-2); }
  .header-right { display: flex; align-items: center; gap: 12px; }
  .icon-btn { width: 36px; height: 36px; border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: var(--surface); transition: all 0.15s; }
  .icon-btn:hover { background: var(--bg); }
  .icon-btn svg { width: 16px; height: 16px; color: var(--text-2); }

  #content { padding: 28px 32px; flex: 1; }

  /* Cards */
  .card { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); }
  .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
  .card-title { font-size: 14px; font-weight: 600; }
  .card-body { padding: 20px; }

  /* Stats grid */
  .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
  .stat-card { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); padding: 20px; box-shadow: var(--shadow); }
  .stat-label { font-size: 12px; color: var(--text-2); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
  .stat-value { font-size: 26px; font-weight: 600; color: var(--text); letter-spacing: -0.5px; }
  .stat-change { font-size: 12px; margin-top: 6px; }
  .stat-change.up { color: var(--accent); }
  .stat-change.down { color: var(--red); }
  .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
  .stat-icon.green { background: var(--accent-light); }
  .stat-icon.blue { background: var(--blue-light); }
  .stat-icon.amber { background: var(--amber-light); }
  .stat-icon.red { background: var(--red-light); }

  /* Table */
  .table-wrapper { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  th { text-align: left; padding: 11px 16px; font-size: 11.5px; font-weight: 600; color: var(--text-2); text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid var(--border); background: #fafafa; }
  td { padding: 13px 16px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #fafbfc; }

  /* Badges */
  .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 500; }
  .badge-green { background: var(--accent-light); color: var(--accent-hover); }
  .badge-red { background: var(--red-light); color: #dc2626; }
  .badge-amber { background: var(--amber-light); color: #d97706; }
  .badge-blue { background: var(--blue-light); color: #2563eb; }
  .badge-gray { background: #f3f4f6; color: #6b7280; }

  /* Buttons */
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; font-family: inherit; }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-primary:hover { background: var(--accent-hover); }
  .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
  .btn-outline:hover { background: var(--bg); }
  .btn-danger { background: var(--red-light); color: var(--red); border: 1px solid #fecaca; }
  .btn-sm { padding: 5px 11px; font-size: 12px; border-radius: 6px; }

  /* Page sections */
  .page { display: none; }
  .page.active { display: block; }

  /* Loading */
  .loading { text-align: center; padding: 40px; color: var(--text-2); font-size: 14px; }
  .spinner { width: 24px; height: 24px; border: 2px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.7s linear infinite; display: inline-block; margin-right: 8px; vertical-align: middle; }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Migration Output */
  .migration-output { background: #1e1e1e; color: #d4d4d4; font-family: 'DM Mono', monospace; padding: 20px; border-radius: 8px; font-size: 13px; max-height: 400px; overflow-y: auto; line-height: 1.6; }
  .migration-output .success { color: #4ec9b0; }
  .migration-output .error { color: #f44747; }
  .migration-output .info { color: #569cd6; }

  /* Login page */
  .login-page { position: fixed; inset: 0; background: var(--sidebar-bg); display: flex; align-items: center; justify-content: center; z-index: 999; }
  .login-card { background: var(--surface); border-radius: 16px; padding: 40px; width: 380px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
</style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
  <div class="logo">
    <span>⬡ RemoAdmin</span>
    <small>Platform Administration</small>
  </div>
  <nav>
    <div class="nav-section">
      <div class="nav-label">Overview</div>
      <div class="nav-item active" onclick="showPage('dashboard', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </div>
    </div>
    <div class="nav-section">
      <div class="nav-label">Management</div>
      <div class="nav-item" onclick="showPage('users', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Users
      </div>
      <div class="nav-item" onclick="showPage('jobs', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        Jobs
      </div>
      <div class="nav-item" onclick="showPage('payments', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Payments
      </div>
    </div>
    <div class="nav-section">
      <div class="nav-label">System</div>
      <div class="nav-item" onclick="showPage('migrations', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        Migrations
      </div>
      <a href="<?php echo baseUrl('logout'); ?>" class="nav-item" style="text-decoration: none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    </div>
  </nav>
  <div class="sidebar-bottom">
    <div class="user-chip">
      <div class="avatar"><?php echo substr($user['name'], 0, 1); ?></div>
      <div class="user-info">
        <div class="name"><?php echo $user['name']; ?></div>
        <div class="role">Super Admin</div>
      </div>
    </div>
  </div>
</div>

<!-- Main -->
<div id="main">
  <header>
    <div class="header-left">
      <div class="page-title" id="headerTitle">Dashboard</div>
    </div>
    <div class="header-right">
      <div class="icon-btn" title="Refresh" onclick="refreshPage()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
      </div>
    </div>
  </header>

  <div id="content">

    <!-- DASHBOARD -->
    <div class="page active" id="page-dashboard">
      <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
          <div class="stat-icon green"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
          <div class="stat-label">Total Users</div>
          <div class="stat-value">0</div>
          <div class="stat-change up">↑ 0 today</div>
        </div>
        <!-- More cards will be loaded via JS -->
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">Recent Users</span></div>
        <div class="table-wrapper" id="recentUsersTable">
          <div class="loading"><span class="spinner"></span>Loading users…</div>
        </div>
      </div>
    </div>

    <!-- USERS -->
    <div class="page" id="page-users">
      <div class="card">
        <div class="card-header"><span class="card-title">User Management</span></div>
        <div class="table-wrapper" id="usersTable">
          <div class="loading"><span class="spinner"></span>Loading users…</div>
        </div>
      </div>
    </div>

    <!-- MIGRATIONS -->
    <div class="page" id="page-migrations">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Database Migrations</span>
          <button class="btn btn-primary btn-sm" onclick="runMigrations()">Run Migrations Now</button>
        </div>
        <div class="card-body">
          <p style="margin-bottom: 15px; font-size: 14px; color: var(--text-2);">This will execute all pending SQL scripts in the <code>database/migrations</code> folder.</p>
          <div id="migrationOutput" class="migration-output">
            Click "Run Migrations" to start...
          </div>
        </div>
      </div>
    </div>

    <!-- Placeholder for other pages -->
    <div class="page" id="page-jobs"><div class="card"><div class="card-body">Jobs Management (Coming Soon)</div></div></div>
    <div class="page" id="page-payments"><div class="card"><div class="card-body">Payments Management (Coming Soon)</div></div></div>

  </div>
</div>

<script>
const API = '<?php echo baseUrl('admin/api.php'); ?>';

async function apiFetch(action, params = {}) {
  const url = new URL(API, window.location.href);
  url.searchParams.append('action', action);
  Object.entries(params).forEach(([k,v]) => url.searchParams.append(k, v));
  const res = await fetch(url);
  return res.json();
}

function showPage(name, el) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  document.getElementById('page-' + name).classList.add('active');
  if (el) el.classList.add('active');
  document.getElementById('headerTitle').textContent = name.charAt(0).toUpperCase() + name.slice(1);
  refreshPage(name);
}

function refreshPage(name) {
  const active = name || document.querySelector('.page.active')?.id?.replace('page-', '');
  if (active === 'dashboard') loadDashboard();
  if (active === 'users') loadUsers();
}

async function loadDashboard() {
  const data = await apiFetch('get_stats');
  if (data.success) {
    const d = data.data;
    document.getElementById('statsGrid').innerHTML = `
      <div class="stat-card">
        <div class="stat-icon blue"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value">${d.total_users}</div>
        <div class="stat-change up">↑ Active members</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></div>
        <div class="stat-label">Total Jobs</div>
        <div class="stat-value">${d.total_jobs}</div>
        <div class="stat-change up">↑ Posted listings</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
        <div class="stat-label">Total Payments</div>
        <div class="stat-value">${d.total_payments}</div>
        <div class="stat-change up">↑ Completed tx</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">$${parseFloat(d.total_revenue).toLocaleString()}</div>
        <div class="stat-change up">↑ Platform fees</div>
      </div>
    `;
    loadRecentUsers();
  }
}

async function loadRecentUsers() {
  const data = await apiFetch('get_users', { limit: 5 });
  if (data.success) {
    document.getElementById('recentUsersTable').innerHTML = renderUsersTable(data.data);
  }
}

async function loadUsers() {
  const data = await apiFetch('get_users');
  if (data.success) {
    document.getElementById('usersTable').innerHTML = renderUsersTable(data.data);
  }
}

function renderUsersTable(users) {
  if (!users.length) return '<div class="loading">No users found.</div>';
  return `<table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
    <tbody>
      ${users.map(u => `<tr>
        <td><strong>${u.name}</strong></td>
        <td>${u.email}</td>
        <td><span class="badge ${u.role === 'admin' ? 'badge-red' : 'badge-blue'}">${u.role}</span></td>
        <td><span class="badge ${u.status === 'active' ? 'badge-green' : 'badge-amber'}">${u.status}</span></td>
        <td>${new Date(u.created_at).toLocaleDateString()}</td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

async function runMigrations() {
  const output = document.getElementById('migrationOutput');
  output.innerHTML = '<span class="info">Starting migrations...</span><br>';
  
  const data = await apiFetch('run_migrations');
  if (data.success) {
    output.innerHTML += `<span class="success">${data.message.replace(/\n/g, '<br>')}</span>`;
  } else {
    output.innerHTML += `<span class="error">Error: ${data.message}</span>`;
  }
}

// Init
loadDashboard();
</script>
</body>
</html>
