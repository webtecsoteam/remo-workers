<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/blog_public.php';
require_once __DIR__ . '/../includes/cms_pages.php';
require_once __DIR__ . '/../includes/seo_public.php';

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
<link rel="icon" type="image/png" href="<?php echo baseUrl("favicon.png?v=1.0.0"); ?>">
<?php
$uiAlertsCssPath = __DIR__ . '/../assets/css/ui-alerts.css';
$uiAlertsJsPath = __DIR__ . '/../assets/js/ui-alerts.js';
$uiAlertsAssetVer = max(
    is_file($uiAlertsCssPath) ? filemtime($uiAlertsCssPath) : 0,
    is_file($uiAlertsJsPath) ? filemtime($uiAlertsJsPath) : 0
);
?>
<link rel="stylesheet" href="<?php echo baseUrl('assets/css/ui-alerts.css?v=' . $uiAlertsAssetVer); ?>">
<script src="<?php echo baseUrl('assets/js/pagination.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/js/ui-alerts.js?v=' . $uiAlertsAssetVer); ?>"></script>
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
    width: 240px; min-height: 100vh; height: 100vh; background: var(--sidebar-bg);
    display: flex; flex-direction: column; position: fixed; left: 0; top: 0; bottom: 0; z-index: 100;
    overflow: hidden;
  }
  .logo { padding: 24px 20px 20px; border-bottom: 1px solid #1c2333; flex-shrink: 0; }
  .logo span { color: var(--accent); font-size: 18px; font-weight: 600; letter-spacing: -0.3px; }
  .logo small { display: block; color: var(--sidebar-text); font-size: 11px; margin-top: 2px; }
  #sidebar nav {
    padding: 16px 12px;
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: #2d3a4d transparent;
  }
  #sidebar nav::-webkit-scrollbar { width: 6px; }
  #sidebar nav::-webkit-scrollbar-track { background: transparent; }
  #sidebar nav::-webkit-scrollbar-thumb { background: #2d3a4d; border-radius: 3px; }
  #sidebar nav::-webkit-scrollbar-thumb:hover { background: #3d4f66; }
  .nav-section { margin-bottom: 24px; }
  .nav-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #3d4f66; padding: 0 8px; margin-bottom: 6px; font-weight: 500; }
  .nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; cursor: pointer; transition: all 0.15s; color: var(--sidebar-text); font-size: 13.5px; font-weight: 400; margin-bottom: 2px; }
  .nav-item:hover { background: var(--sidebar-hover); color: #d1d8e4; }
  .nav-item.active { background: #1c2f4a; color: var(--sidebar-active); font-weight: 500; }
  .nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }
  .nav-badge { margin-left: auto; background: var(--red); color: #fff; font-size: 10px; font-weight: 600; border-radius: 10px; padding: 1px 6px; }
  .sidebar-bottom { padding: 16px 12px; border-top: 1px solid #1c2333; flex-shrink: 0; }
  .user-chip { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 8px; cursor: pointer; }
  .user-chip:hover { background: var(--sidebar-hover); }
  .avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 600; flex-shrink: 0; }
  .user-info .name { font-size: 13px; color: #d1d8e4; font-weight: 500; }
  .user-info .role { font-size: 11px; color: var(--sidebar-text); }

  /* Main */
  #main { margin-left: 240px; flex: 1; min-height: 100vh; min-width: 0; display: flex; flex-direction: column; }
  header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 32px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
  .header-left { display: flex; align-items: center; gap: 8px; }
  .page-title { font-size: 16px; font-weight: 600; color: var(--text); }
  .breadcrumb { font-size: 13px; color: var(--text-2); }
  .header-right { display: flex; align-items: center; gap: 12px; }
  .icon-btn { width: 36px; height: 36px; border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: var(--surface); transition: all 0.15s; }
  .icon-btn:hover { background: var(--bg); }
  .icon-btn svg { width: 16px; height: 16px; color: var(--text-2); }

  #content { padding: 28px 32px; flex: 1; min-width: 0; }

  /* Cards */
  .card { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); min-width: 0; }
  .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
  .card-title { font-size: 14px; font-weight: 600; }
  .card-body { padding: 20px; }

  /* Stats grid */
  .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
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
  .table-wrapper {
    overflow-x: auto;
    overflow-y: visible;
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
  }
  .table-wrapper > table {
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
  }
  table { border-collapse: collapse; font-size: 13.5px; }
  th { text-align: left; padding: 11px 16px; font-size: 11.5px; font-weight: 600; color: var(--text-2); text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid var(--border); background: #fafafa; white-space: nowrap; }
  td { padding: 13px 16px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
  #paymentsTable.table-wrapper { display: block; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #fafbfc; }

  /* Badges */
  .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 500; }
  .badge-green { background: var(--accent-light); color: var(--accent-hover); }
  .badge-red { background: var(--red-light); color: #dc2626; }
  .badge-amber { background: var(--amber-light); color: #d97706; }
  .badge-blue { background: var(--blue-light); color: #2563eb; }
  .badge-gray { background: #f3f4f6; color: #6b7280; }

  .online-dot {
    width: 8px; height: 8px; border-radius: 50%; background: var(--accent);
    display: inline-block; flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(20, 168, 0, 0.25);
  }
  .online-user-cell { display: flex; align-items: center; gap: 10px; }
  .online-user-avatar {
    width: 32px; height: 32px; border-radius: 50%; background: var(--accent-light);
    color: var(--accent-hover); font-size: 12px; font-weight: 600;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    overflow: hidden;
  }
  .online-user-avatar img { width: 100%; height: 100%; object-fit: cover; }

  .verify-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
  .verify-tab {
    padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 500;
    border: 1px solid var(--border); background: var(--surface); color: var(--text-2);
    cursor: pointer; font-family: inherit; transition: all 0.15s;
  }
  .verify-tab:hover { border-color: var(--accent); color: var(--text); }
  .verify-tab.active { background: var(--accent-light); border-color: var(--accent); color: var(--accent-hover); font-weight: 600; }

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

  /* User profile (admin) */
  .profile-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-2); cursor: pointer; margin-bottom: 16px; border: none; background: none; font-family: inherit; padding: 0; }
  .profile-back:hover { color: var(--accent); }
  .profile-header { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; }
  .profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--accent-light); color: var(--accent-hover); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 28px; flex-shrink: 0; overflow: hidden; }
  .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .profile-meta-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px 20px; margin-top: 16px; }
  .profile-meta-item label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-3); font-weight: 600; margin-bottom: 4px; }
  .profile-meta-item span { font-size: 13.5px; color: var(--text); }
  .profile-actions-bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
  .profile-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 900px) { .profile-two-col { grid-template-columns: 1fr; } }
  .skill-chip { display: inline-block; padding: 4px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 20px; font-size: 12px; margin: 0 6px 6px 0; }

  /* Loading */
  .loading { text-align: center; padding: 40px; color: var(--text-2); font-size: 14px; }
  .spinner { width: 24px; height: 24px; border: 2px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.7s linear infinite; display: inline-block; margin-right: 8px; vertical-align: middle; }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Toast (theme-aligned) */
  .toast {
    position: fixed; bottom: 24px; right: 24px;
    background: var(--sidebar-bg); color: #fff;
    padding: 13px 18px; border-radius: var(--radius);
    font-size: 13px; font-weight: 500; z-index: 11000;
    transform: translateY(80px); opacity: 0; transition: all .3s;
    max-width: 360px; box-shadow: 0 8px 28px rgba(0,0,0,.25);
  }
  .toast.show { transform: translateY(0); opacity: 1; }
  .toast strong { display: block; margin-bottom: 2px; color: var(--accent); }
  .marketing-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; }
  .marketing-toolbar .blog-search-wrap { flex: 1; min-width: 200px; }
  .marketing-count { font-size: 13px; color: var(--text-2); margin-left: auto; }
  .marketing-actions-bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 12px 0 0; border-top: 1px solid var(--border); margin-top: 12px; }
  input.marketing-check { width: 16px; height: 16px; cursor: pointer; accent-color: var(--accent); }
  .users-filter-panel {
    background: #fafafa; border: 1px solid var(--border); border-radius: var(--radius);
    padding: 16px; margin-bottom: 16px;
  }
  .users-filter-panel .panel-title { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-2); margin-bottom: 12px; }
  .users-filter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; align-items: end; }
  .users-filter-field label { display: block; font-size: 12px; font-weight: 500; color: var(--text-2); margin-bottom: 5px; }
  .users-filter-field input, .users-filter-field select {
    width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px;
    font-size: 13px; font-family: inherit; background: var(--surface); outline: none;
  }
  .users-filter-field input:focus, .users-filter-field select:focus { border-color: var(--accent); }
  .users-filter-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }
  .users-result-count { font-size: 13px; color: var(--text-2); margin-left: auto; }

  /* Migration Output */
  .migration-output { background: #1e1e1e; color: #d4d4d4; font-family: 'DM Mono', monospace; padding: 20px; border-radius: 8px; font-size: 13px; max-height: 400px; overflow-y: auto; line-height: 1.6; }
  .migration-output .success { color: #4ec9b0; }
  .migration-output .error { color: #f44747; }
  .migration-output .info { color: #569cd6; }

  /* Admin chats */
  .messages-layout { display: flex; gap: 16px; min-height: calc(100vh - 140px); }
  .messages-list-panel { flex: 1; min-width: 0; }
  .messages-list-panel.hidden { display: none; }
  .messages-chat-panel { flex: 1.2; min-width: 0; display: none; flex-direction: column; }
  .messages-chat-panel.active { display: flex; }
  .chat-header { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; background: var(--surface); border-radius: var(--radius) var(--radius) 0 0; }
  .chat-back-btn { background: none; border: none; cursor: pointer; padding: 6px; border-radius: 6px; color: var(--text-2); display: none; }
  .chat-back-btn:hover { background: var(--bg); color: var(--text); }
  .chat-participants { flex: 1; }
  .chat-participants .names { font-size: 14px; font-weight: 600; }
  .chat-participants .meta { font-size: 12px; color: var(--text-2); margin-top: 2px; }
  .chat-messages-wrap { flex: 1; overflow-y: auto; padding: 16px 18px; background: #f8f9fa; display: flex; flex-direction: column; gap: 10px; min-height: 400px; max-height: calc(100vh - 220px); }
  .chat-load-more { text-align: center; padding: 8px; font-size: 12px; color: var(--text-2); }
  .chat-load-more button { background: none; border: none; color: var(--blue); cursor: pointer; font-size: 12px; font-weight: 500; font-family: inherit; }
  .chat-load-more button:disabled { color: var(--text-3); cursor: default; }
  .chat-bubble { max-width: 75%; padding: 10px 14px; border-radius: 12px; font-size: 13px; line-height: 1.45; word-break: break-word; }
  .chat-bubble.sent { align-self: flex-end; background: var(--accent-light); border: 1px solid #c8e6c9; }
  .chat-bubble.received { align-self: flex-start; background: var(--surface); border: 1px solid var(--border); }
  .chat-bubble-meta { font-size: 10.5px; color: var(--text-3); margin-top: 4px; }
  .chat-bubble-job { font-size: 11px; color: var(--blue); margin-bottom: 4px; }
  .chat-bubble-attachment { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; padding: 8px 12px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--blue); text-decoration: none; }
  .chat-bubble-attachment:hover { text-decoration: underline; }
  .conv-preview { color: var(--text-2); font-size: 12px; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .conv-row { cursor: pointer; }
  .conv-row.active td { background: var(--accent-light) !important; }
  .messages-pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-top: 1px solid var(--border); font-size: 13px; color: var(--text-2); }
  @media (min-width: 901px) {
    #page-messages.active .messages-chat-panel { display: flex; }
  }
  @media (max-width: 900px) {
    .messages-layout { flex-direction: column; }
    .messages-chat-panel.active .chat-back-btn { display: block; }
    .messages-list-panel.hidden-on-mobile { display: none; }
  }

  /* Blog toolbar & rich text editor */
  .blog-toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
  .country-toggle { position: relative; display: inline-block; width: 44px; height: 24px; cursor: pointer; vertical-align: middle; }
  .country-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
  .country-toggle-slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 24px; transition: background 0.2s; }
  .country-toggle-slider::before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: transform 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.15); }
  .country-toggle input:checked + .country-toggle-slider { background: var(--accent); }
  .country-toggle input:checked + .country-toggle-slider::before { transform: translateX(20px); }
  .country-toggle input:disabled + .country-toggle-slider { opacity: 0.6; cursor: wait; }
  .blog-search-wrap { position: relative; flex: 1; min-width: 200px; }
  .blog-search-wrap input { width: 100%; padding: 8px 12px 8px 32px; border: 1px solid var(--border); border-radius: 8px; font-size: 13.5px; font-family: inherit; outline: none; background: var(--surface); }
  .blog-search-wrap input:focus { border-color: var(--accent); }
  .blog-search-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-3); }
  .blog-filter-select { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; outline: none; background: var(--surface); cursor: pointer; }
  .blog-filter-select:focus { border-color: var(--accent); }
  .rte-wrap { border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: var(--surface); }
  .rte-toolbar { display: flex; flex-wrap: wrap; gap: 4px; padding: 8px 10px; background: #fafafa; border-bottom: 1px solid var(--border); }
  .rte-toolbar button { padding: 5px 10px; font-size: 12px; font-weight: 500; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text); cursor: pointer; font-family: inherit; transition: background 0.15s; }
  .rte-toolbar button:hover { background: var(--bg); }
  .rte-toolbar button.active { background: var(--accent-light); border-color: #b8ddb8; color: var(--accent-hover); }
  .rte-editor { min-height: 220px; max-height: 360px; overflow-y: auto; padding: 12px 14px; font-size: 14px; line-height: 1.6; outline: none; }
  .rte-editor:empty::before { content: attr(data-placeholder); color: var(--text-3); pointer-events: none; }
  .rte-editor h3 { font-size: 16px; font-weight: 600; margin: 12px 0 8px; }
  .rte-editor blockquote { border-left: 3px solid var(--accent); padding: 8px 14px; margin: 10px 0; background: var(--accent-light); border-radius: 0 8px 8px 0; font-style: italic; color: var(--text-2); }
  .rte-editor ul, .rte-editor ol { padding-left: 22px; margin: 8px 0; }
  .rte-editor img { max-width: 100%; height: auto; display: block; margin: 8px 0; border-radius: 6px; }
  .blog-view-body { font-size: 14px; line-height: 1.7; color: var(--text); }
  .blog-view-body h3 { font-size: 18px; font-weight: 600; margin: 16px 0 10px; }
  .blog-view-body blockquote { border-left: 3px solid var(--accent); padding: 10px 16px; margin: 14px 0; background: var(--accent-light); border-radius: 0 8px 8px 0; }
  .blog-view-body ul, .blog-view-body ol { padding-left: 22px; margin: 10px 0; }
  .blog-thumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); background: var(--bg); }
  .blog-image-preview { margin-top: 10px; max-width: 100%; max-height: 140px; border-radius: 8px; border: 1px solid var(--border); display: none; }
  .blog-image-preview.visible { display: block; }

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
      <div class="nav-item" onclick="showPage('agencies', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 10h6"/><path d="M9 14h6"/></svg>
        Agencies
      </div>
      <div class="nav-item" onclick="showPage('jobs', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        Jobs
      </div>
      <div class="nav-item" onclick="showPage('job-categories', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7 12 3 4 7l8 4 8-4z"/><path d="M4 12l8 4 8-4"/><path d="M4 17l8 4 8-4"/></svg>
        Job Categories
      </div>
      <div class="nav-item" onclick="showPage('contracts', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Contracts
      </div>
      <div class="nav-item" onclick="showPage('payments', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Payments
      </div>
      <div class="nav-item" onclick="showPage('disputes', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Dispute
      </div>
      <div class="nav-item" onclick="showPage('job-reports', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
        Job Reports
      </div>
      <div class="nav-item" onclick="showPage('verifications', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
        Verifications
      </div>
      <div class="nav-item" onclick="showPage('marketing', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Marketing
      </div>
      <div class="nav-item" onclick="showPage('payment-holds', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Payment Holds
      </div>
      <div class="nav-item" onclick="showPage('withdrawals', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Withdrawals
      </div>
      <div class="nav-item" onclick="showPage('connects', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
        Connects Packages
      </div>
      <div class="nav-item" onclick="showPage('blogs', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Blog
      </div>
      <div class="nav-item" onclick="showPage('cms-pages', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Pages
      </div>
      <div class="nav-item" onclick="showPage('seo', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        SEO
      </div>
      <div class="nav-item" onclick="showPage('messages', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Chats
      </div>
    </div>
    <div class="nav-section">
      <div class="nav-label">System</div>
      <div class="nav-item" onclick="showPage('security', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Security
      </div>
      <div class="nav-item" onclick="showPage('countries', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        Countries
      </div>
      <div class="nav-item" onclick="showPage('settings', this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Platform Settings
      </div>
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
      <div class="card" style="margin-bottom:24px">
        <div class="card-header" style="flex-wrap:wrap; gap:12px">
          <div>
            <span class="card-title">Online Users</span>
            <span style="display:block;font-size:12px;color:var(--text-2);margin-top:4px;font-weight:400">Active in the last <span id="onlineThresholdLabel">5</span> minutes</span>
          </div>
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <select class="filter-select" id="onlineRoleFilter" onchange="loadOnlineUsers()" style="font-size:13px;padding:7px 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface)">
              <option value="">All roles</option>
              <option value="client">Clients</option>
              <option value="freelancer">Freelancers</option>
            </select>
            <span class="badge badge-green" id="onlineUsersBadge" style="font-size:12px">0 online</span>
          </div>
        </div>
        <div class="table-wrapper" id="onlineUsersTable">
          <div class="loading"><span class="spinner"></span>Loading online users…</div>
        </div>
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
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <span class="card-title">User Management</span>
          <button type="button" class="btn btn-primary btn-sm" onclick="exportUsersExcel()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download Excel
          </button>
        </div>
        <div class="card-body" style="padding-bottom:0">
          <div class="users-filter-panel">
            <div class="panel-title">Filter users</div>
            <div class="verify-tabs" id="userEmailVerifyTabs" style="margin-bottom:14px">
              <button type="button" class="verify-tab active" data-user-email-verified="" onclick="setUserEmailVerifiedFilter('', this)">All emails</button>
              <button type="button" class="verify-tab" data-user-email-verified="1" onclick="setUserEmailVerifiedFilter('1', this)">Email verified</button>
              <button type="button" class="verify-tab" data-user-email-verified="0" onclick="setUserEmailVerifiedFilter('0', this)">Email unverified</button>
            </div>
            <div class="users-filter-grid">
              <div class="users-filter-field">
                <label for="userFilterRole">Role</label>
                <select id="userFilterRole" onchange="loadUsers()">
                  <option value="">All roles</option>
                  <option value="freelancer" selected>Freelancer</option>
                  <option value="client">Client</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="users-filter-field">
                <label for="userFilterStatus">Status</label>
                <select id="userFilterStatus" onchange="loadUsers()">
                  <option value="">All statuses</option>
                  <option value="active">Active</option>
                  <option value="suspended">Suspended</option>
                  <option value="closed">Closed</option>
                  <option value="pending">Pending</option>
                </select>
              </div>
              <div class="users-filter-field">
                <label for="userFilterName">Name</label>
                <input type="text" id="userFilterName" placeholder="Contains…" oninput="debounceUserSearch()" />
              </div>
              <div class="users-filter-field">
                <label for="userFilterEmail">Email</label>
                <input type="text" id="userFilterEmail" placeholder="Contains…" oninput="debounceUserSearch()" />
              </div>
            </div>
            <div class="users-filter-actions">
              <button type="button" class="btn btn-primary btn-sm" onclick="loadUsers()">Apply filters</button>
              <button type="button" class="btn btn-outline btn-sm" onclick="resetUserFilters()">Reset</button>
              <span class="users-result-count" id="usersCountLabel">—</span>
            </div>
          </div>
        </div>
        <div class="table-wrapper" id="usersTable">
          <div class="loading"><span class="spinner"></span>Loading users…</div>
        </div>
      </div>
    </div>

    <!-- AGENCIES -->
    <div class="page" id="page-agencies">
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <span class="card-title">Agency Management</span>
          <input type="text" id="agenciesSearch" placeholder="Search by agency, owner, or email…" oninput="debounceAgenciesSearch()" style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; min-width:260px; outline:none;">
        </div>
        <div class="table-wrapper" id="agenciesTable">
          <div class="loading"><span class="spinner"></span>Loading agencies…</div>
        </div>
      </div>
    </div>

    <!-- USER PROFILE -->
    <div class="page" id="page-user-profile">
      <button type="button" class="profile-back" onclick="closeUserProfile()">← Back to Users</button>
      <div id="userProfileContent">
        <div class="loading"><span class="spinner"></span>Loading profile…</div>
      </div>
    </div>

    <!-- JOB DETAIL -->
    <div class="page" id="page-job-detail">
      <button type="button" class="profile-back" onclick="closeJobDetail()">← Back to Jobs</button>
      <div id="jobDetailContent">
        <div class="loading"><span class="spinner"></span>Loading job details…</div>
      </div>
    </div>

    <!-- CONTRACTS -->
    <div class="page" id="page-contracts">
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <span class="card-title">All Contracts</span>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="text" id="contractsSearch" placeholder="Search job, client, freelancer…" oninput="debounceContractsSearch()" style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; min-width:220px; outline:none;">
            <select id="contractsStatusFilter" onchange="loadContracts()" style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; outline:none;">
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
              <option value="disputed">Disputed</option>
            </select>
          </div>
        </div>
        <div class="table-wrapper" id="contractsTable">
          <div class="loading"><span class="spinner"></span>Loading contracts…</div>
        </div>
      </div>
    </div>

    <!-- PAYMENT HOLDS -->
    <div class="page" id="page-payment-holds">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Pending Payment Holds</span>
          <span style="font-size:12px;color:var(--text-2);max-width:520px;line-height:1.5">After a client pays and funds are in processing, approve here to credit the freelancer&apos;s available balance for withdrawal.</span>
        </div>
        <div class="table-wrapper" id="paymentHoldsTable">
          <div class="loading"><span class="spinner"></span>Loading payment holds…</div>
        </div>
      </div>
    </div>

    <!-- WITHDRAWALS -->
    <div class="page" id="page-withdrawals">
      <div class="card">
        <div class="card-header"><span class="card-title">Pending Withdrawals</span></div>
        <div class="table-wrapper" id="withdrawalsTable">
          <div class="loading"><span class="spinner"></span>Loading withdrawals…</div>
        </div>
      </div>
    </div>

    <!-- CONNECTS PACKAGES -->
    <div class="page" id="page-connects">
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title">Connects Packages</span>
          <button class="btn btn-primary btn-sm" onclick="openConnectsModal()">+ Add Package</button>
        </div>
        <div class="table-wrapper" id="connectsTable">
          <div class="loading"><span class="spinner"></span>Loading packages…</div>
        </div>
      </div>
    </div>

    <!-- BLOG -->
    <div class="page" id="page-blogs">
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title">Blog Posts</span>
          <button class="btn btn-primary btn-sm" onclick="openBlogModal()">+ Add Blog</button>
        </div>
        <div class="card-body" style="padding-bottom:0">
          <div class="blog-toolbar">
            <div class="blog-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="blogSearch" placeholder="Search by title or category…" oninput="debounceBlogSearch()" />
            </div>
            <select class="blog-filter-select" id="blogStatusFilter" onchange="loadBlogs()">
              <option value="">All statuses</option>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="unpublished">Unpublished</option>
            </select>
          </div>
        </div>
        <div class="table-wrapper" id="blogsTable">
          <div class="loading"><span class="spinner"></span>Loading blogs…</div>
        </div>
      </div>
    </div>

    <!-- Blog Add/Edit Modal -->
    <div id="blogModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center; padding:20px;">
      <div class="card" style="width:100%; max-width:720px; margin:20px; max-height:90vh; overflow-y:auto;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title" id="blogModalTitle">Add Blog Post</span>
          <span onclick="closeModal('blogModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
        </div>
        <div class="card-body">
          <form id="blogForm" onsubmit="saveBlog(event)">
            <input type="hidden" id="blogId" name="id">
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Title *</label>
              <input type="text" id="blogName" name="name" required placeholder="Blog post title" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:14px;">
            </div>
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Category *</label>
              <select id="blogCategory" name="category" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; background:var(--surface); cursor:pointer;">
                <option value="">Select category</option>
                <?php foreach (blogCategoryOptions() as $blogCat): ?>
                <option value="<?php echo htmlspecialchars($blogCat, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($blogCat, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Featured image</label>
              <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <input type="text" id="blogImage" name="image" placeholder="Image URL or path (e.g. uploads/blogs/…)" style="flex:1; min-width:200px; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;" oninput="updateBlogImagePreview()">
                <label class="btn btn-outline btn-sm" style="cursor:pointer; margin:0;">
                  Upload
                  <input type="file" id="blogImageFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="uploadBlogImage(event)">
                </label>
              </div>
              <img id="blogImagePreview" class="blog-image-preview" alt="Preview">
            </div>
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Status</label>
              <select id="blogStatus" name="status" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; background:var(--surface); cursor:pointer;">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="unpublished">Unpublished</option>
              </select>
            </div>
            <div style="margin-bottom:20px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Description *</label>
              <div class="rte-wrap">
                <div class="rte-toolbar" id="blogRteToolbar">
                  <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                  <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                  <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                  <button type="button" data-cmd="formatBlock" data-value="h3" title="Heading">H3</button>
                  <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
                  <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
                  <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Quote">Quote</button>
                  <button type="button" data-cmd="createLink" title="Link">Link</button>
                  <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
                </div>
                <div id="blogDescriptionEditor" class="rte-editor" contenteditable="true" data-placeholder="Write your blog content here…"></div>
              </div>
              <input type="hidden" id="blogDescription" name="description">
            </div>
            <div id="blogFormStatus" style="margin-bottom:12px; font-size:13px;"></div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Blog Post</button>
          </form>
        </div>
      </div>
    </div>

    <!-- CMS PAGES -->
    <div class="page" id="page-cms-pages">
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <span class="card-title">Footer &amp; Static Pages</span>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn btn-outline btn-sm" type="button" onclick="syncCmsBuiltinPages()">Import default pages</button>
            <button class="btn btn-primary btn-sm" type="button" onclick="openCmsPageModal()">+ Add Page</button>
          </div>
        </div>
        <div class="card-body" style="padding-top:0; padding-bottom:12px;">
          <p style="font-size:13px; color:var(--text-2); line-height:1.55; margin:0;">Default footer pages (e.g. Success Stories, Pricing) are imported automatically the first time you open this screen. Edit any page below and set status to <strong>Published</strong> to update the live site.</p>
        </div>
        <div class="card-body" style="padding-bottom:0">
          <div class="blog-toolbar">
            <div class="blog-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="cmsPageSearch" placeholder="Search by name or slug…" oninput="debounceCmsPageSearch()" />
            </div>
            <select class="blog-filter-select" id="cmsPageStatusFilter" onchange="loadCmsPages()">
              <option value="">All statuses</option>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
            <select class="blog-filter-select" id="cmsPageSectionFilter" onchange="loadCmsPages()">
              <option value="">All sections</option>
              <?php foreach (cmsFooterSectionOptions() as $sec): ?>
              <option value="<?php echo htmlspecialchars($sec, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($sec), ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="table-wrapper" id="cmsPagesTable">
          <div class="loading"><span class="spinner"></span>Loading pages…</div>
        </div>
      </div>
    </div>

    <div id="cmsPageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center; padding:20px;">
      <div class="card" style="width:100%; max-width:800px; margin:20px; max-height:92vh; overflow-y:auto;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title" id="cmsPageModalTitle">Add Page</span>
          <span onclick="closeModal('cmsPageModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
        </div>
        <div class="card-body">
          <form id="cmsPageForm" onsubmit="saveCmsPage(event)">
            <input type="hidden" id="cmsPageId" name="id">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
              <div>
                <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Name *</label>
                <input type="text" id="cmsPageName" required placeholder="Privacy Policy" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:14px;">
              </div>
              <div>
                <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">URL slug</label>
                <input type="text" id="cmsPageSlug" placeholder="privacy-policy" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:14px;">
              </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px;">
              <div>
                <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Footer section</label>
                <select id="cmsPageSection" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; font-family:inherit; font-size:13px;">
                  <option value="">— Not in footer —</option>
                  <?php foreach (cmsFooterSectionOptions() as $sec): ?>
                  <option value="<?php echo htmlspecialchars($sec, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($sec), ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Link type</label>
                <select id="cmsPageLinkType" onchange="toggleCmsPageLinkFields()" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; font-family:inherit; font-size:13px;">
                  <option value="content">Full page (CMS)</option>
                  <option value="modal">Opens modal</option>
                  <option value="external">External URL</option>
                </select>
              </div>
              <div>
                <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Sort order</label>
                <input type="number" id="cmsPageSortOrder" value="0" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit;">
              </div>
            </div>
            <div id="cmsPageLinkTargetWrap" style="margin-bottom:16px; display:none;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500" id="cmsPageLinkTargetLabel">Link target</label>
              <input type="text" id="cmsPageLinkTarget" placeholder="e.g. help-center or https://…" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
            </div>
            <div style="display:flex; gap:20px; margin-bottom:16px; flex-wrap:wrap;">
              <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                <input type="checkbox" id="cmsPageShowInFooter" value="1" checked style="width:16px; height:16px;">
                Show in footer
              </label>
              <div>
                <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Status</label>
                <select id="cmsPageStatus" style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-family:inherit; font-size:13px;">
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                </select>
              </div>
            </div>
            <h3 style="font-size:13px; font-weight:600; color:var(--accent); text-transform:uppercase; letter-spacing:.04em; margin:20px 0 12px;">SEO</h3>
            <div style="margin-bottom:12px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">SEO title</label>
              <input type="text" id="cmsPageSeoTitle" maxlength="255" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit;">
            </div>
            <div style="margin-bottom:12px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">SEO description</label>
              <textarea id="cmsPageSeoDescription" rows="2" maxlength="500" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; resize:vertical;"></textarea>
            </div>
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">SEO keywords</label>
              <input type="text" id="cmsPageSeoKeywords" maxlength="500" placeholder="comma, separated, keywords" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit;">
            </div>
            <div id="cmsPageContentWrap" style="margin-bottom:20px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Page content *</label>
              <div class="rte-wrap">
                <div class="rte-toolbar" id="cmsPageRteToolbar">
                  <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                  <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                  <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                  <button type="button" data-cmd="formatBlock" data-value="h2" title="Heading">H2</button>
                  <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
                  <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
                  <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Quote">Quote</button>
                  <button type="button" data-cmd="createLink" title="Link">Link</button>
                  <button type="button" data-cmd="removeFormat" title="Clear">Clear</button>
                </div>
                <div id="cmsPageDescriptionEditor" class="rte-editor" contenteditable="true" data-placeholder="Write page content…"></div>
              </div>
              <input type="hidden" id="cmsPageDescription" name="description">
            </div>
            <div id="cmsPageFormStatus" style="margin-bottom:12px; font-size:13px;"></div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Page</button>
          </form>
        </div>
      </div>
    </div>

    <!-- COUNTRIES -->
    <div class="page" id="page-countries">
      <div class="stats-grid" style="margin-bottom:20px; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
        <div class="stat-card" style="padding:16px;">
          <div class="stat-label">Enabled</div>
          <div class="stat-value" id="countriesStatEnabled" style="font-size:22px;color:var(--accent);">—</div>
        </div>
        <div class="stat-card" style="padding:16px;">
          <div class="stat-label">Disabled</div>
          <div class="stat-value" id="countriesStatDisabled" style="font-size:22px;">—</div>
        </div>
        <div class="stat-card" style="padding:16px;">
          <div class="stat-label">Total</div>
          <div class="stat-value" id="countriesStatTotal" style="font-size:22px;">—</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Countries</span>
        </div>
        <div class="card-body" style="padding-top:0; padding-bottom:12px;">
          <p style="font-size:13px; color:var(--text-2); line-height:1.55; margin:0;">Enabled countries appear in signup and profile dropdowns. Disabled countries stay hidden from new selections but existing user locations still display correctly.</p>
        </div>
        <div class="card-body" style="padding-bottom:0">
          <div class="blog-toolbar">
            <div class="blog-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="countrySearch" placeholder="Search name, code, or phone…" oninput="debounceCountrySearch()" />
            </div>
            <select class="blog-filter-select" id="countryStatusFilter" onchange="loadCountries()">
              <option value="">All countries</option>
              <option value="enabled">Enabled only</option>
              <option value="disabled">Disabled only</option>
            </select>
          </div>
        </div>
        <div class="table-wrapper" id="countriesTable">
          <div class="loading"><span class="spinner"></span>Loading countries…</div>
        </div>
      </div>
    </div>

    <!-- SEO SETTINGS -->
    <div class="page" id="page-seo">
      <div class="card" style="max-width:720px;">
        <div class="card-header"><span class="card-title">Website SEO</span></div>
        <div class="card-body">
          <p style="font-size:13px;color:var(--text-2);line-height:1.6;margin-bottom:20px;">Control default meta tags for the homepage and fallback values for CMS pages. Individual pages can override these in the Pages module.</p>
          <form id="seoForm" onsubmit="saveSeoSettings(event)">
            <h3 style="font-size:13px;font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">Homepage</h3>
            <div style="margin-bottom:14px;">
              <label style="display:block;font-size:12px;margin-bottom:6px;color:var(--text-2);">Title</label>
              <input type="text" id="seo_home_title" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;outline:none;font-family:inherit;">
            </div>
            <div style="margin-bottom:14px;">
              <label style="display:block;font-size:12px;margin-bottom:6px;color:var(--text-2);">Meta description</label>
              <textarea id="seo_home_description" rows="3" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;outline:none;font-family:inherit;resize:vertical;"></textarea>
            </div>
            <div style="margin-bottom:20px;">
              <label style="display:block;font-size:12px;margin-bottom:6px;color:var(--text-2);">Keywords</label>
              <input type="text" id="seo_home_keywords" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;outline:none;font-family:inherit;">
            </div>
            <h3 style="font-size:13px;font-weight:600;color:var(--blue);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px;">Site defaults</h3>
            <div style="margin-bottom:14px;">
              <label style="display:block;font-size:12px;margin-bottom:6px;color:var(--text-2);">Default title</label>
              <input type="text" id="seo_site_title" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;outline:none;font-family:inherit;">
            </div>
            <div style="margin-bottom:14px;">
              <label style="display:block;font-size:12px;margin-bottom:6px;color:var(--text-2);">Default meta description</label>
              <textarea id="seo_site_description" rows="3" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;outline:none;font-family:inherit;resize:vertical;"></textarea>
            </div>
            <div style="margin-bottom:14px;">
              <label style="display:block;font-size:12px;margin-bottom:6px;color:var(--text-2);">Default keywords</label>
              <input type="text" id="seo_site_keywords" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;outline:none;font-family:inherit;">
            </div>
            <div style="margin-bottom:20px;">
              <label style="display:block;font-size:12px;margin-bottom:6px;color:var(--text-2);">Open Graph image URL</label>
              <input type="url" id="seo_og_image" placeholder="Leave empty to use assets/ShareLogo.png" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;outline:none;font-family:inherit;">
              <p style="font-size:11px;color:var(--text-2);margin:8px 0 0;line-height:1.45;">Used when links are shared on Facebook, Instagram, X, LinkedIn, WhatsApp, and similar apps. Default: <code>assets/ShareLogo.png</code>.</p>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Save SEO Settings</button>
          </form>
          <div id="seoFormStatus" style="margin-top:15px;font-size:13px;"></div>
        </div>
      </div>
    </div>

    <!-- Blog View Modal -->
    <div id="blogViewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center; padding:20px;">
      <div class="card" style="width:100%; max-width:720px; margin:20px; max-height:90vh; overflow-y:auto;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title" id="blogViewTitle">View Blog</span>
          <span onclick="closeModal('blogViewModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
        </div>
        <div class="card-body">
          <div id="blogViewMeta" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:16px; font-size:12px; color:var(--text-2);"></div>
          <img id="blogViewImage" class="blog-image-preview visible" style="max-height:200px; margin-bottom:16px; display:none;" alt="">
          <div id="blogViewBody" class="blog-view-body"></div>
          <div style="display:flex; gap:10px; margin-top:24px; flex-wrap:wrap;">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('blogViewModal')">Close</button>
            <button type="button" class="btn btn-primary btn-sm" id="blogViewEditBtn">Edit</button>
            <button type="button" class="btn btn-danger btn-sm" id="blogViewDeleteBtn">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Connects Modal -->
    <div id="connectsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
      <div class="card" style="width:100%; max-width:400px; margin:20px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title" id="connectsModalTitle">Add Connects Package</span>
          <span onclick="closeModal('connectsModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
        </div>
        <div class="card-body">
          <form id="connectsForm" onsubmit="saveConnectsPackage(event)">
            <input type="hidden" id="connectsId" name="id">
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2)">Amount (Connects)</label>
              <input type="number" id="connectsAmount" name="amount" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2)">Price ($)</label>
              <input type="number" step="0.01" id="connectsPrice" name="price" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2)">Badge Text (Optional)</label>
              <input type="text" id="connectsBadge" name="badge_text" placeholder="e.g. Most Popular" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom:20px;">
              <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-1)">
                <input type="checkbox" id="connectsActive" name="is_active" value="1" checked>
                Active / Visible to users
              </label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Package</button>
          </form>
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

    <!-- DISPUTES -->
    <div class="page" id="page-disputes">
      <div class="card">
        <div class="card-header"><span class="card-title">Contract Disputes</span></div>
        <div class="table-wrapper" id="disputesTable">
          <div class="loading"><span class="spinner"></span>Loading disputes…</div>
        </div>
      </div>
    </div>

    <!-- JOB REPORTS -->
    <div class="page" id="page-job-reports">
      <div class="card">
        <div class="card-header"><span class="card-title">Reported Jobs</span></div>
        <div class="table-wrapper" id="jobReportsTable">
          <div class="loading"><span class="spinner"></span>Loading job reports…</div>
        </div>
      </div>
    </div>

    <!-- MARKETING -->
    <div class="page" id="page-marketing">
      <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:12px">
          <span class="card-title">Freelancer Marketing Emails</span>
          <div class="verify-tabs" id="marketingVerifyTabs">
            <button type="button" class="verify-tab active" data-marketing-verified="" onclick="switchMarketingTab('', this)">All</button>
            <button type="button" class="verify-tab" data-marketing-verified="1" onclick="switchMarketingTab('1', this)">Verified</button>
            <button type="button" class="verify-tab" data-marketing-verified="0" onclick="switchMarketingTab('0', this)">Unverified</button>
          </div>
        </div>
        <div class="card-body" style="padding-bottom:0">
          <div class="marketing-toolbar">
            <div class="blog-search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="marketingSearch" placeholder="Search freelancers by name or email…" oninput="debounceMarketingSearch()" />
            </div>
            <select class="filter-select" id="marketingStatusFilter" onchange="loadMarketingFreelancers()" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">
              <option value="active">Active accounts</option>
              <option value="">All statuses</option>
              <option value="suspended">Suspended</option>
              <option value="closed">Closed</option>
            </select>
            <span class="marketing-count" id="marketingCountLabel">—</span>
          </div>
          <div class="marketing-actions-bar">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
              <input type="checkbox" class="marketing-check" id="marketingSelectAll" onchange="toggleMarketingSelectAll(this.checked)" />
              Select all on page
            </label>
            <button type="button" class="btn btn-outline btn-sm" onclick="clearMarketingSelection()">Clear selection</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="openPromoEmailModal(false)">Email selected</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="openPromoEmailModal(true)">Email all in filter</button>
          </div>
        </div>
        <div class="table-wrapper" id="marketingTable">
          <div class="loading"><span class="spinner"></span>Loading freelancers…</div>
        </div>
      </div>
    </div>

    <!-- VERIFICATIONS -->
    <div class="page" id="page-verifications">
      <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:12px">
          <span class="card-title">Verification Documents</span>
          <div class="verify-tabs" id="verificationTabs">
            <button type="button" class="verify-tab active" data-verify-tab="pending" onclick="switchVerificationTab('pending', this)">Pending</button>
            <button type="button" class="verify-tab" data-verify-tab="verified" onclick="switchVerificationTab('verified', this)">Verified</button>
            <button type="button" class="verify-tab" data-verify-tab="rejected" onclick="switchVerificationTab('rejected', this)">Rejected</button>
          </div>
        </div>
        <div class="table-wrapper" id="verificationsTable">
          <div class="loading"><span class="spinner"></span>Loading documents…</div>
        </div>
      </div>
    </div>

    <!-- SETTINGS -->
    <div class="page" id="page-settings">
      <div class="card" style="max-width: 600px;">
        <div class="card-header"><span class="card-title">Dynamic Platform Charges &amp; Fees</span></div>
        <div class="card-body">
          <form id="settingsForm" onsubmit="saveSettings(event)">
            <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Freelancer Service Fees (%)</h3>
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Fixed Price Contract Fee (%)</label>
              <input type="number" step="0.01" id="set_freelancer_fee_fixed" name="freelancer_fee_fixed" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Hourly Contract Fee (%)</label>
              <input type="number" step="0.01" id="set_freelancer_fee_hourly" name="freelancer_fee_hourly" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Monthly Contract Fee (%)</label>
              <input type="number" step="0.01" id="set_freelancer_fee_monthly" name="freelancer_fee_monthly" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>

            <h3 style="font-size: 14px; margin-top: 24px; margin-bottom: 12px; color: var(--blue); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Client Service Fees (%)</h3>
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Fixed Price Contract Fee (%)</label>
              <input type="number" step="0.01" id="set_client_fee_fixed" name="client_fee_fixed" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Hourly Contract Fee (%)</label>
              <input type="number" step="0.01" id="set_client_fee_hourly" name="client_fee_hourly" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom: 20px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Monthly Contract Fee (%)</label>
              <input type="number" step="0.01" id="set_client_fee_monthly" name="client_fee_monthly" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Fee Settings</button>
          </form>
          <div id="settingsStatus" style="margin-top: 15px; font-size: 13px;"></div>
        </div>
      </div>

      <div class="card" style="max-width: 600px; margin-top: 24px;">
        <div class="card-header"><span class="card-title">Google Analytics</span></div>
        <div class="card-body">
          <p style="font-size: 13px; color: var(--text-2); margin-bottom: 16px; line-height: 1.5;">
            Add your GA4 Measurement ID to track visitors on the public site (home, client, and freelancer pages). The tracking script is injected in page headers only when enabled.
          </p>
          <form id="analyticsForm" onsubmit="saveAnalyticsSettings(event)">
            <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
              <input type="checkbox" id="set_google_analytics_enabled" name="google_analytics_enabled" value="1" style="width: 18px; height: 18px; cursor: pointer;">
              <label for="set_google_analytics_enabled" style="font-size: 13px; color: var(--text-1); cursor: pointer;">Enable Google Analytics on front site</label>
            </div>
            <div style="margin-bottom: 20px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Measurement ID (GA4)</label>
              <input type="text" id="set_google_analytics_id" name="google_analytics_id" placeholder="G-XXXXXXXXXX" pattern="G-[A-Za-z0-9]+" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
              <span style="display:block; font-size: 11px; color: var(--text-3); margin-top: 6px;">Find this in Google Analytics → Admin → Data Streams → your web stream.</span>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Analytics Settings</button>
          </form>
          <div id="analyticsStatus" style="margin-top: 15px; font-size: 13px;"></div>
        </div>
      </div>

      <div class="card" style="max-width: 600px; margin-top: 24px;">
        <div class="card-header"><span class="card-title">Referral Program</span></div>
        <div class="card-body">
          <p style="font-size: 13px; color: var(--text-2); margin-bottom: 16px; line-height: 1.5;">
            Control the refer-and-share feature for clients and freelancers. When enabled, users earn wallet credits after referred users complete verification steps.
          </p>
          <form id="referralForm" onsubmit="saveReferralSettings(event)">
            <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
              <input type="checkbox" id="set_referral_enabled" name="referral_enabled" value="1" style="width: 18px; height: 18px; cursor: pointer;">
              <label for="set_referral_enabled" style="font-size: 13px; color: var(--text-1); cursor: pointer;">Enable referral program</label>
            </div>
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Qualified referrals per reward</label>
              <input type="number" step="1" min="1" id="set_referral_reward_threshold" name="referral_reward_threshold" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
              <span style="display:block; font-size: 11px; color: var(--text-3); margin-top: 6px;">How many fully qualified referrals unlock each wallet credit.</span>
            </div>
            <div style="margin-bottom: 20px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Reward amount (USD)</label>
              <input type="number" step="0.01" min="0.01" id="set_referral_reward_amount" name="referral_reward_amount" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
              <span style="display:block; font-size: 11px; color: var(--text-3); margin-top: 6px;">Wallet credit paid for each milestone reached.</span>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Referral Settings</button>
          </form>
          <div id="referralSettingsStatus" style="margin-top: 15px; font-size: 13px;"></div>
        </div>
      </div>
    </div>

    <!-- SECURITY -->
    <div class="page" id="page-security">
      <div class="card" style="max-width: 500px;">
        <div class="card-header"><span class="card-title">Change Admin Password</span></div>
        <div class="card-body">
          <form id="passwordForm" onsubmit="changePassword(event)">
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Current Password</label>
              <input type="password" name="current_password" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom: 16px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">New Password</label>
              <input type="password" name="new_password" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <div style="margin-bottom: 20px;">
              <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2);">Confirm New Password</label>
              <input type="password" name="confirm_password" required style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="width:100%; justify-content:center;">Update Password</button>
          </form>
          <div id="passwordStatus" style="margin-top: 15px; font-size: 13px;"></div>
        </div>
      </div>
    </div>

    <!-- CHATS -->
    <div class="page" id="page-messages">
      <div class="messages-layout" id="messagesLayout">
        <div class="messages-list-panel" id="messagesListPanel">
          <div class="card" style="height:100%; display:flex; flex-direction:column;">
            <div class="card-header" style="flex-wrap:wrap; gap:12px;">
              <span class="card-title">Client &amp; Freelancer Chats</span>
              <input type="text" id="conversationsSearch" placeholder="Search by name or email…" oninput="debounceConversationsSearch()" style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; min-width:220px; outline:none;">
            </div>
            <div class="table-wrapper" id="conversationsTable" style="flex:1;">
              <div class="loading"><span class="spinner"></span>Loading conversations…</div>
            </div>
            <div class="messages-pagination" id="conversationsPagination" style="display:none;"></div>
          </div>
        </div>
        <div class="messages-chat-panel card" id="messagesChatPanel">
          <div class="chat-header">
            <button type="button" class="chat-back-btn" id="chatBackBtn" onclick="closeAdminChat()" title="Back to conversations">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <div class="chat-participants" id="chatParticipantsHeader">
              <div class="names">Select a conversation</div>
              <div class="meta">Click a row to view full chat history</div>
            </div>
          </div>
          <div class="chat-messages-wrap" id="adminChatMessages">
            <div style="text-align:center; color:var(--text-3); padding:40px 20px; font-size:13px;">No conversation selected</div>
          </div>
        </div>
      </div>
    </div>

    <div class="page" id="page-jobs">
      <div class="card">
        <div class="card-header"><span class="card-title">Jobs &amp; Listings Management</span></div>
        <div class="table-wrapper" id="jobsTable">
          <div class="loading"><span class="spinner"></span>Loading jobs…</div>
        </div>
      </div>
    </div>
    <div class="page" id="page-job-categories">
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
          <span class="card-title">Job Categories</span>
          <button class="btn btn-primary btn-sm" onclick="openJobCategoryModal()">+ Add Category</button>
        </div>
        <div class="table-wrapper" id="jobCategoriesTable">
          <div class="loading"><span class="spinner"></span>Loading job categories…</div>
        </div>
      </div>
    </div>
    <!-- PAYMENTS -->
    <div class="page" id="page-payments">
      <div class="stats-grid" id="paymentsSummary">
        <div class="stat-card">
          <div class="stat-icon blue"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
          <div class="stat-label">Transactions</div>
          <div class="stat-value" id="paySummaryCount">—</div>
          <div class="stat-change" id="paySummaryCountSub">Matching filters</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <div class="stat-label">Total Amount</div>
          <div class="stat-value" id="paySummaryAmount">—</div>
          <div class="stat-change up" id="paySummaryCompleted">Completed: —</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <div class="stat-label">Platform Fees</div>
          <div class="stat-value" id="paySummaryFees">—</div>
          <div class="stat-change up">From filtered results</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
          <div class="stat-label">Pending / Disputed</div>
          <div class="stat-value" id="paySummaryPending">—</div>
          <div class="stat-change" id="paySummaryDisputed">Disputed: —</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <span class="card-title">Payments Management</span>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="text" id="paymentsSearch" placeholder="Search transaction ID…" oninput="debouncePaymentsSearch()" style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; min-width:200px; outline:none;">
            <select id="paymentsStatusFilter" onchange="loadPayments()" style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; outline:none;">
              <option value="">All statuses</option>
              <option value="pending">Pending</option>
              <option value="completed">Completed</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
              <option value="disputed">Disputed</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
        </div>
        <div class="table-wrapper" id="paymentsTable">
          <div class="loading"><span class="spinner"></span>Loading payments…</div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Balance Modal -->
<div id="balanceModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:400px;">
    <div class="card-header"><span class="card-title">Manage Balance</span> <span onclick="closeModal('balanceModal')" style="cursor:pointer">&times;</span></div>
    <div class="card-body">
      <p id="balanceUserName" style="margin-bottom:12px; font-weight:600;"></p>
      <input type="hidden" id="balanceUserId">
      <div style="margin-bottom:16px;">
        <label style="display:block; font-size:12px; margin-bottom:6px;">Current Balance: <span id="currentBalanceVal">$0</span></label>
        <input type="number" id="balanceAmount" step="0.01" placeholder="Enter amount to add (e.g. 50)" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none;">
      </div>
      <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" onclick="updateBalance('add')" style="flex:1; justify-content:center;">Add</button>
        <button class="btn btn-danger" onclick="updateBalance('set')" style="flex:1; justify-content:center;">Set Exact</button>
      </div>
    </div>
  </div>
</div>

<!-- Client Stats Modal -->
<div id="clientStatsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:480px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title">Edit Client Statistics</span>
      <span onclick="closeModal('clientStatsModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <input type="hidden" id="statsUserId">
      <p id="statsUserName" style="margin-bottom:16px; font-weight:600; font-size:15px;"></p>
      <div id="statsLoading" class="loading" style="display:none;"><span class="spinner"></span>Loading stats...</div>
      <div id="statsFormFields">
        <div style="margin-bottom:16px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Join Date</label>
          <input type="date" id="statsJoinDate" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
          <small style="color:var(--text-3); font-size:11px; margin-top:4px; display:block;">Changing this affects membership duration and all time-based calculations</small>
        </div>
        <div style="margin-bottom:16px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Total Amount Spent ($)</label>
          <input type="number" step="0.01" min="0" id="statsTotalSpent" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
          <small style="color:var(--text-3); font-size:11px; margin-top:4px; display:block;">Actual platform spent: <strong id="statsRealSpent">$0</strong> &mdash; future payments will continue accumulating from this value</small>
        </div>
        <div style="margin-bottom:20px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Total Hires / Jobs Completed</label>
          <input type="number" min="0" id="statsTotalHires" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
          <small style="color:var(--text-3); font-size:11px; margin-top:4px; display:block;">Actual platform hires: <strong id="statsRealHires">0</strong> &mdash; new hires will continue incrementing from this value</small>
        </div>
        <button class="btn btn-primary" onclick="saveClientStats()" style="width:100%; justify-content:center;">Save Changes</button>
      </div>
      <div id="statsStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<!-- Freelancer Join Date Modal -->
<div id="freelancerJoinDateModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:480px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title">Adjust Freelancer Join Date</span>
      <span onclick="closeModal('freelancerJoinDateModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <input type="hidden" id="fjUserId">
      <p id="fjUserName" style="margin-bottom:16px; font-weight:600; font-size:15px;"></p>
      <div style="margin-bottom:16px;">
        <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Join Date</label>
        <input type="date" id="fjJoinDate" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
        <small style="color:var(--text-3); font-size:11px; margin-top:4px; display:block;">This updates the freelancer's joined date (stored as account created date).</small>
      </div>
      <button class="btn btn-primary" onclick="saveFreelancerJoinDate()" style="width:100%; justify-content:center;">Save Join Date</button>
      <div id="fjStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<!-- Reset User Password Modal -->
<div id="resetPasswordModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:420px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title">Reset Password</span>
      <span onclick="closeModal('resetPasswordModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <input type="hidden" id="resetPasswordUserId">
      <p id="resetPasswordUserName" style="margin-bottom:16px; font-weight:600; font-size:15px;"></p>
      <form id="resetPasswordForm" onsubmit="submitResetUserPassword(event)">
        <div style="margin-bottom:16px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">New Password</label>
          <input type="password" id="resetPasswordNew" required minlength="6" autocomplete="new-password" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
        </div>
        <div style="margin-bottom:20px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Confirm New Password</label>
          <input type="password" id="resetPasswordConfirm" required minlength="6" autocomplete="new-password" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Reset Password</button>
      </form>
      <div id="resetPasswordStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<!-- Suspend User Modal -->
<div id="suspendUserModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:480px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title">Suspend Account</span>
      <span onclick="closeSuspendModal()" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <input type="hidden" id="suspendUserId">
      <p id="suspendUserSummary" style="margin-bottom:16px; font-size:14px; color:var(--text-2); line-height:1.55;"></p>
      <div style="margin-bottom:20px;">
        <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Reason for suspension <span style="color:var(--red)">*</span></label>
        <textarea id="suspendReason" rows="4" required placeholder="Explain why this account is being suspended. This will be sent to the user by email." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; resize:vertical;"></textarea>
      </div>
      <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-outline" style="flex:1; justify-content:center;" onclick="closeSuspendModal()">Cancel</button>
        <button type="button" id="suspendConfirmBtn" class="btn btn-danger" style="flex:1; justify-content:center;" onclick="submitSuspendUser()">Suspend &amp; Send Email</button>
      </div>
      <div id="suspendStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<!-- Change User Email Modal -->
<div id="changeEmailModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:420px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title">Change Email</span>
      <span onclick="closeModal('changeEmailModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <input type="hidden" id="changeEmailUserId">
      <p id="changeEmailUserName" style="margin-bottom:16px; font-weight:600; font-size:15px;"></p>
      <form id="changeEmailForm" onsubmit="submitChangeUserEmail(event)">
        <div style="margin-bottom:16px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Current Email</label>
          <input type="email" id="changeEmailCurrent" readonly style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; background:var(--bg); color:var(--text-2);">
        </div>
        <div style="margin-bottom:20px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">New Email</label>
          <input type="email" id="changeEmailNew" required autocomplete="email" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Email</button>
      </form>
      <div id="changeEmailStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<!-- Promotional Email Modal -->
<div id="promoEmailModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:560px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title">Send Promotional Email</span>
      <span onclick="closeModal('promoEmailModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <p id="promoEmailRecipientSummary" style="margin-bottom:16px; font-size:14px; color:var(--text-2); line-height:1.55;"></p>
      <input type="hidden" id="promoEmailSendAll" value="0">
      <div style="margin-bottom:16px;">
        <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Subject <span style="color:var(--red)">*</span></label>
        <input type="text" id="promoEmailSubject" maxlength="200" placeholder="e.g. New features on RemoWorkers" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Message <span style="color:var(--red)">*</span></label>
        <div class="rte-wrap">
          <div class="rte-toolbar" id="promoEmailRteToolbar">
            <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
            <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
            <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
            <button type="button" data-cmd="formatBlock" data-value="h3" title="Heading">H3</button>
            <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
            <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
            <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Quote">Quote</button>
            <button type="button" data-cmd="createLink" title="Link">Link</button>
            <button type="button" data-cmd="insertImage" title="Insert image">Image</button>
            <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
          </div>
          <input type="file" id="promoEmailImageInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
          <div id="promoEmailMessageEditor" class="rte-editor" contenteditable="true" data-placeholder="Write your promotional message..."></div>
        </div>
        <textarea id="promoEmailMessage" maxlength="50000" style="display:none;"></textarea>
      </div>
      <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-outline" style="flex:1; justify-content:center;" onclick="closeModal('promoEmailModal')">Cancel</button>
        <button type="button" id="promoEmailSendBtn" class="btn btn-primary" style="flex:1; justify-content:center;" onclick="submitPromotionalEmail()">Send emails</button>
      </div>
      <div id="promoEmailStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<div class="toast" id="toast"><strong id="t-title"></strong><span id="t-msg"></span></div>

<!-- Dispute Resolve Modal -->
<div id="disputeResolveModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:480px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title" id="disputeResolveTitle">Resolve Dispute</span>
      <span onclick="closeModal('disputeResolveModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <input type="hidden" id="disputeResolveId">
      <input type="hidden" id="disputeResolveAction">
      <p id="disputeResolveSummary" style="margin-bottom:16px; font-size:14px; color:var(--text-2); line-height:1.55;"></p>
      <div style="margin-bottom:16px; padding:12px 14px; background:var(--bg); border-radius:8px; border:1px solid var(--border);">
        <label style="display:block; font-size:11px; font-weight:600; color:var(--text-2); text-transform:uppercase; letter-spacing:0.4px; margin-bottom:8px;">Dispute Reason</label>
        <p id="disputeResolveReason" style="font-size:13px; line-height:1.5; color:var(--text); margin:0; white-space:pre-wrap;"></p>
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500;">Resolution notes (optional)</label>
        <textarea id="disputeResolveNotes" rows="3" placeholder="Add admin notes for this resolution…" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; resize:vertical;"></textarea>
      </div>
      <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-outline" style="flex:1; justify-content:center;" onclick="closeModal('disputeResolveModal')">Cancel</button>
        <button type="button" id="disputeResolveConfirmBtn" class="btn btn-primary" style="flex:1; justify-content:center;" onclick="submitDisputeResolve()">Confirm</button>
      </div>
      <div id="disputeResolveStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<div id="adminDirectMessageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:210; align-items:center; justify-content:center;">
  <div class="card" style="width:min(560px,95vw); max-height:90vh; overflow:auto;">
    <div class="card-header">
      <span class="card-title" id="adminDirectMessageTitle">Contact User</span>
      <span onclick="closeModal('adminDirectMessageModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <input type="hidden" id="adminDirectMessageUserId">
      <input type="hidden" id="adminDirectMessageJobId">
      <input type="hidden" id="adminDirectMessageUserName">
      <input type="hidden" id="adminDirectMessageUserRole">
      <p id="adminDirectMessageMeta" style="margin:0 0 10px; font-size:13px; color:var(--text-2);"></p>
      <textarea id="adminDirectMessageText" rows="6" maxlength="4000" placeholder="Write your message..." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; resize:vertical;"></textarea>
      <div style="display:flex; gap:10px; margin-top:14px;">
        <button type="button" class="btn btn-outline" style="flex:1; justify-content:center;" onclick="closeModal('adminDirectMessageModal')">Cancel</button>
        <button type="button" id="adminDirectMessageSendBtn" class="btn btn-primary" style="flex:1; justify-content:center;" onclick="submitAdminDirectMessage()">Send Message</button>
      </div>
      <div id="adminDirectMessageStatus" style="margin-top:12px; font-size:13px;"></div>
    </div>
  </div>
</div>

<!-- Job Category Modal -->
<div id="jobCategoryModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
  <div class="card" style="width:100%; max-width:520px; margin:20px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <span class="card-title" id="jobCategoryModalTitle">Add Job Category</span>
      <span onclick="closeModal('jobCategoryModal')" style="cursor:pointer; font-size:24px; font-weight:700; color:var(--text-3)">&times;</span>
    </div>
    <div class="card-body">
      <form id="jobCategoryForm" onsubmit="saveJobCategory(event)">
        <input type="hidden" id="jobCategoryId">
        <div style="margin-bottom:14px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Name *</label>
          <input type="text" id="jobCategoryName" required placeholder="Category name" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:14px;">
        </div>
        <div style="margin-bottom:14px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Image URL</label>
          <input type="text" id="jobCategoryImage" placeholder="https://... or uploads/..." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px;">
        </div>
        <div style="margin-bottom:20px;">
          <label style="display:block; font-size:12px; margin-bottom:6px; color:var(--text-2); font-weight:500">Status</label>
          <select id="jobCategoryStatus" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; outline:none; font-family:inherit; font-size:13px; background:var(--surface);">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div id="jobCategoryFormStatus" style="margin-bottom:12px; font-size:13px;"></div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Save Category</button>
      </form>
    </div>
  </div>
</div>

<script>
const API = '<?php echo baseUrl('admin/api.php'); ?>';
const BASE_URL = '<?php echo baseUrl(); ?>';
const CURRENT_ADMIN_ID = <?php echo (int)($user['id'] ?? 0); ?>;
const CURRENT_ADMIN_NAME = <?php echo json_encode((string)($user['name'] ?? 'Admin')); ?>;
let disputesCache = [];

async function apiFetch(action, params = {}) {
  const url = new URL(API);
  url.searchParams.append('action', action);
  Object.entries(params).forEach(([k,v]) => url.searchParams.append(k, v));
  const res = await fetch(url);
  return res.json();
}

async function apiPost(action, body) {
  const url = new URL(API);
  url.searchParams.append('action', action);
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body || {})
  });
  return res.json();
}

let adminToastTimer;
function toast(title, msg, type) {
  const el = document.getElementById('toast');
  if (!el) return;
  const t = document.getElementById('t-title');
  const m = document.getElementById('t-msg');
  if (t) t.textContent = title || 'Notice';
  if (m) m.textContent = msg ? ' — ' + msg : '';
  el.classList.remove('toast-error', 'toast-success');
  if (type === 'error') el.classList.add('toast-error');
  if (type === 'success') el.classList.add('toast-success');
  el.classList.add('show');
  clearTimeout(adminToastTimer);
  adminToastTimer = setTimeout(() => el.classList.remove('show'), 3500);
}

function setAdminFlash(type, title, message) {
  sessionStorage.setItem('adminFlash', JSON.stringify({ type, title, message }));
}

function showAdminFlash() {
  const raw = sessionStorage.getItem('adminFlash');
  if (!raw) return;
  sessionStorage.removeItem('adminFlash');
  try {
    const flash = JSON.parse(raw);
    toast(flash.title || 'Notice', flash.message || '', flash.type || 'success');
  } catch (_) { /* ignore */ }
}

function restoreAdminPage(pageName) {
  if (!pageName) return false;
  const nav = Array.from(document.querySelectorAll('.nav-item')).find(
    (el) => (el.getAttribute('onclick') || '').includes(`showPage('${pageName}'`)
  );
  if (nav) {
    showPage(pageName, nav);
    return true;
  }
  return false;
}

function verificationDocLinks(filePath) {
  if (!filePath) return '—';
  const docUrl = (path) => {
    const clean = String(path).replace(/^\/+/, '');
    return `${BASE_URL}/${clean}`;
  };
  const link = (label, path) =>
    `<a href="${docUrl(path)}" target="_blank" rel="noopener" class="badge badge-blue">${label}</a>`;

  let paths = null;
  const raw = String(filePath).trim();
  if (raw.startsWith('{')) {
    try {
      const parsed = JSON.parse(raw);
      if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) paths = parsed;
    } catch (_) { /* single path stored as string */ }
  }

  if (paths) {
    const parts = [];
    if (paths.front) parts.push(link('Front', paths.front));
    if (paths.back) parts.push(link('Back', paths.back));
    if (parts.length) return parts.join(' ');
    return Object.entries(paths)
      .filter(([, v]) => v)
      .map(([k, v]) => link(k.charAt(0).toUpperCase() + k.slice(1), v))
      .join(' ');
  }

  return link('View Doc', raw);
}

const PAGE_TITLES = { disputes: 'Dispute', 'job-reports': 'Job Reports', payments: 'Payments Management', 'payment-holds': 'Payment Holds', messages: 'Chats', blogs: 'Blog Management', 'cms-pages': 'Pages', seo: 'SEO Settings', countries: 'Countries', marketing: 'Marketing Emails', 'user-profile': 'User Profile', contracts: 'Contracts', 'job-detail': 'Job Detail', 'job-categories': 'Job Categories', agencies: 'Agencies' };
let marketingVerifiedFilter = '';
let marketingFreelancersCache = [];
let marketingSelectedIds = new Set();
let marketingSearchTimer = null;
let viewingUserId = null;
let viewingJobId = null;
let contractsSearchTimer = null;
let blogSearchTimer = null;
let cmsPageSearchTimer = null;
let countrySearchTimer = null;
let userSearchTimer = null;
let agenciesSearchTimer = null;
let blogViewId = null;
let paymentsSearchTimer = null;
let conversationsSearchTimer = null;
let conversationsPage = 1;
let activeConversation = null;
let chatOldestId = null;
let chatLoadingOlder = false;
let chatHasMore = false;
let onlineRefreshTimer = null;
let jobCategoriesCache = [];
const ONLINE_THRESHOLD_MINUTES = 5;

function showPage(name, el) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  document.getElementById('page-' + name).classList.add('active');
  if (el) el.classList.add('active');
  document.getElementById('headerTitle').textContent = PAGE_TITLES[name] || (name.charAt(0).toUpperCase() + name.slice(1));
  refreshPage(name);
}

function refreshPage(name) {
  const active = name || document.querySelector('.page.active')?.id?.replace('page-', '');
  if (active === 'dashboard') {
    loadDashboard();
    startOnlineRefresh();
  } else {
    stopOnlineRefresh();
  }
  if (active === 'users') loadUsers();
  if (active === 'agencies') loadAgencies();
  if (active === 'jobs') loadJobs();
  if (active === 'job-categories') loadJobCategories();
  if (active === 'contracts') loadContracts();
  if (active === 'job-detail' && viewingJobId) loadJobDetail(viewingJobId);
  if (active === 'verifications') loadVerifications();
  if (active === 'marketing') loadMarketingFreelancers();
  if (active === 'settings') loadSettings();
  if (active === 'payment-holds') loadPaymentHolds();
  if (active === 'withdrawals') loadWithdrawals();
  if (active === 'connects') loadConnects();
  if (active === 'blogs') loadBlogs();
  if (active === 'cms-pages') loadCmsPages();
  if (active === 'seo') loadSeoSettings();
  if (active === 'countries') loadCountries();
  if (active === 'disputes') loadDisputes();
  if (active === 'job-reports') loadJobReports();
  if (active === 'payments') loadPayments();
  if (active === 'messages') loadConversations();
  if (active === 'user-profile' && viewingUserId) loadUserProfile(viewingUserId);
}

function openUserProfile(userId) {
  viewingUserId = parseInt(userId, 10);
  if (!viewingUserId) return;
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  document.getElementById('page-user-profile').classList.add('active');
  document.getElementById('headerTitle').textContent = 'User Profile';
  const url = new URL(window.location.href);
  url.searchParams.set('user_id', viewingUserId);
  window.history.replaceState({}, '', url);
  loadUserProfile(viewingUserId);
}

function closeUserProfile() {
  viewingUserId = null;
  const url = new URL(window.location.href);
  url.searchParams.delete('user_id');
  window.history.replaceState({}, '', url);
  const usersNav = document.querySelector('.nav-item[onclick*="users"]');
  showPage('users', usersNav);
}

function openJobDetail(jobId) {
  viewingJobId = parseInt(jobId, 10);
  if (!viewingJobId) return;
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  document.getElementById('page-job-detail').classList.add('active');
  document.getElementById('headerTitle').textContent = 'Job Detail';
  const url = new URL(window.location.href);
  url.searchParams.delete('user_id');
  url.searchParams.set('job_id', viewingJobId);
  window.history.replaceState({}, '', url);
  loadJobDetail(viewingJobId);
}

function closeJobDetail() {
  viewingJobId = null;
  const url = new URL(window.location.href);
  url.searchParams.delete('job_id');
  window.history.replaceState({}, '', url);
  const jobsNav = document.querySelector('.nav-item[onclick*="jobs"]');
  showPage('jobs', jobsNav);
}

async function loadJobDetail(jobId) {
  const el = document.getElementById('jobDetailContent');
  el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading job details…</div>';
  const res = await apiFetch('get_job_detail', { job_id: jobId });
  if (!res.success) {
    el.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(res.message || 'Could not load job')}</div>`;
    return;
  }
  el.innerHTML = renderJobDetailPage(res.data);
  document.getElementById('headerTitle').textContent = res.data.job?.title ? `Job: ${res.data.job.title}` : 'Job Detail';
}

function jobStatusBadge(status) {
  const map = { open: 'badge-green', pending: 'badge-amber', in_progress: 'badge-blue', closed: 'badge-gray', rejected: 'badge-red' };
  return map[status] || 'badge-gray';
}

function contractStatusBadge(status) {
  const map = { active: 'badge-green', completed: 'badge-blue', cancelled: 'badge-gray', disputed: 'badge-red' };
  return map[status] || 'badge-amber';
}

function renderJobDetailPage(d) {
  const j = d.job;
  const client = d.client || {};
  const st = d.stats || {};
  const skills = (j.skills || []).map(s => `<span class="skill-chip">${escapeHtml(typeof s === 'string' ? s : (s.name || s))}</span>`).join('') || '<span style="color:var(--text-3);font-size:13px">—</span>';

  const proposalsHtml = (d.proposals || []).length
    ? `<table><thead><tr><th>ID</th><th>Freelancer</th><th>Bid</th><th>Status</th><th>Milestones</th><th>Submitted</th></tr></thead><tbody>
      ${d.proposals.map(p => `<tr>
        <td>#${p.id}</td>
        <td><button type="button" class="btn btn-outline btn-sm" onclick="openUserProfile(${p.freelancer_id})">${escapeHtml(p.freelancer_name)}</button><br><small style="color:var(--text-3)">${escapeHtml(p.freelancer_email || '')}</small></td>
        <td><strong>$${parseFloat(p.bid_amount || 0).toFixed(2)}</strong></td>
        <td><span class="badge badge-blue">${escapeHtml(p.status)}</span></td>
        <td>${(p.milestones || []).length ? (p.milestones || []).map(m => `<div style="font-size:12px;margin-bottom:4px">${escapeHtml(m.description)} — $${parseFloat(m.amount).toFixed(2)} <span class="badge badge-gray">${escapeHtml(m.status)}</span></div>`).join('') : '—'}</td>
        <td>${p.created_at ? new Date(p.created_at).toLocaleString() : '—'}</td>
      </tr>`).join('')}
    </tbody></table>`
    : '<p style="color:var(--text-3);font-size:13px;padding:8px 0">No proposals yet.</p>';

  const contractsHtml = (d.contracts || []).length
    ? d.contracts.map(c => {
        const milestones = (c.milestones || []).map(m =>
          `<div style="font-size:12px;margin-bottom:4px">${escapeHtml(m.description)} — $${parseFloat(m.amount).toFixed(2)} <span class="badge badge-gray">${escapeHtml(m.status)}</span></div>`
        ).join('') || '<span style="color:var(--text-3);font-size:12px">No milestones</span>';
        const workLogs = (c.work_logs || []).length
          ? `<table style="margin-top:8px;font-size:12px"><thead><tr><th>Hours</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
            ${c.work_logs.map(wl => `<tr><td>${parseFloat(wl.hours || 0).toFixed(2)}</td><td>$${parseFloat(wl.amount || 0).toFixed(2)}</td><td>${escapeHtml(wl.status || '—')}</td><td>${wl.created_at ? new Date(wl.created_at).toLocaleString() : '—'}</td></tr>`).join('')}
          </tbody></table>`
          : '';
        const reviews = (c.reviews || []).map(r =>
          `<div style="font-size:12px;margin-top:6px">★ ${parseFloat(r.rating).toFixed(1)} by ${escapeHtml(r.reviewer_name)} — ${escapeHtml((r.feedback || '').slice(0, 120))}</div>`
        ).join('');
        return `<div style="padding:14px 0;border-bottom:1px solid var(--border)">
          <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:8px">
            <strong>Contract #${c.id}</strong>
            <span class="badge ${contractStatusBadge(c.status)}">${escapeHtml(c.status)}</span>
            <span style="color:var(--text-2);font-size:13px">${escapeHtml(c.contract_type)} · $${parseFloat(c.amount || 0).toFixed(2)}</span>
          </div>
          <div style="font-size:13px;margin-bottom:8px">Hired: <button type="button" class="btn btn-outline btn-sm" onclick="openUserProfile(${c.freelancer_id})">${escapeHtml(c.freelancer_name)}</button> (${escapeHtml(c.freelancer_email || '')})</div>
          <div style="font-size:12px;color:var(--text-2)">Started ${c.start_date ? new Date(c.start_date).toLocaleString() : '—'}${c.end_date ? ' · Ended ' + new Date(c.end_date).toLocaleString() : ''}</div>
          <div style="margin-top:10px"><strong style="font-size:12px">Milestones</strong><div style="margin-top:6px">${milestones}</div></div>
          ${workLogs ? `<div style="margin-top:10px"><strong style="font-size:12px">Work logs</strong>${workLogs}</div>` : ''}
          ${reviews ? `<div style="margin-top:10px"><strong style="font-size:12px">Reviews</strong>${reviews}</div>` : ''}
        </div>`;
      }).join('')
    : '<p style="color:var(--text-3);font-size:13px;padding:8px 0">No contracts for this job.</p>';

  const paymentsHtml = (d.payments || []).length
    ? `<table><thead><tr><th>ID</th><th>Txn</th><th>Payer</th><th>Payee</th><th>Amount</th><th>Fee</th><th>Status</th><th>Date</th></tr></thead><tbody>
      ${d.payments.map(p => `<tr>
        <td>#${p.id}</td>
        <td style="font-family:monospace;font-size:11px">${escapeHtml(p.transaction_id || '—')}</td>
        <td>${escapeHtml(p.payer_name || '—')}</td>
        <td>${escapeHtml(p.payee_name || '—')}</td>
        <td><strong>$${parseFloat(p.amount || 0).toFixed(2)}</strong></td>
        <td>$${parseFloat(p.platform_fee || 0).toFixed(2)}</td>
        <td><span class="badge badge-blue">${escapeHtml(p.status)}</span></td>
        <td>${p.created_at ? new Date(p.created_at).toLocaleString() : '—'}</td>
      </tr>`).join('')}
    </tbody></table>`
    : '<p style="color:var(--text-3);font-size:13px;padding:8px 0">No payments recorded.</p>';

  const disputesHtml = (d.disputes || []).length
    ? `<table><thead><tr><th>ID</th><th>Contract</th><th>Raised by</th><th>Reason</th><th>Status</th><th>Created</th></tr></thead><tbody>
      ${d.disputes.map(dis => `<tr>
        <td>#${dis.id}</td>
        <td>#${dis.contract_id}</td>
        <td>${escapeHtml(dis.raised_by_name || '—')} (${escapeHtml(dis.raised_by_role || '')})</td>
        <td style="max-width:240px">${escapeHtml(dis.reason || '')}</td>
        <td><span class="badge ${dis.status === 'open' ? 'badge-amber' : 'badge-green'}">${escapeHtml(dis.status)}</span></td>
        <td>${dis.created_at ? new Date(dis.created_at).toLocaleString() : '—'}</td>
      </tr>`).join('')}
    </tbody></table>`
    : '<p style="color:var(--text-3);font-size:13px;padding:8px 0">No disputes.</p>';

  const messagesHtml = (d.messages || []).length
    ? `<div style="max-height:400px;overflow-y:auto">
      ${d.messages.map(m => `<div style="padding:10px 0;border-bottom:1px solid var(--border);font-size:13px">
        <div style="font-weight:600">${escapeHtml(m.sender_name)} → ${escapeHtml(m.receiver_name)} <span style="color:var(--text-3);font-weight:400">${m.created_at ? new Date(m.created_at).toLocaleString() : ''}</span></div>
        <p style="margin:6px 0 0;color:var(--text-2);line-height:1.5">${escapeHtml(m.message || '(attachment only)')}</p>
        ${m.attachment_name ? `<a href="${adminMessageAttachmentUrl(m.id)}" target="_blank" rel="noopener" class="badge badge-blue" style="margin-top:6px;display:inline-block">${escapeHtml(m.attachment_name)}</a>` : ''}
      </div>`).join('')}
    </div>`
    : '<p style="color:var(--text-3);font-size:13px;padding:8px 0">No messages linked to this job.</p>';

  const invitationsHtml = (d.invitations || []).length
    ? `<table><thead><tr><th>Freelancer</th><th>Status</th><th>Sent</th></tr></thead><tbody>
      ${d.invitations.map(i => `<tr>
        <td><button type="button" class="btn btn-outline btn-sm" onclick="openUserProfile(${i.freelancer_id})">${escapeHtml(i.freelancer_name)}</button></td>
        <td><span class="badge badge-blue">${escapeHtml(i.status)}</span></td>
        <td>${i.created_at ? new Date(i.created_at).toLocaleString() : '—'}</td>
      </tr>`).join('')}
    </tbody></table>`
    : '';

  return `
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><span class="card-title">Job #${j.id} — ${escapeHtml(j.title)}</span>
        <span class="badge ${jobStatusBadge(j.status)}">${escapeHtml(j.status)}</span>
        ${j.is_flagged == 1 || j.is_flagged === true ? '<span class="badge badge-red">Flagged</span>' : ''}
      </div>
      <div class="card-body">
        <div class="profile-meta-grid">
          <div class="profile-meta-item"><label>Category</label><span>${escapeHtml(j.category || '—')}${j.subcategory ? ' / ' + escapeHtml(j.subcategory) : ''}</span></div>
          <div class="profile-meta-item"><label>Budget</label><span>$${parseFloat(j.budget || 0).toFixed(2)} (${escapeHtml(j.budget_type || 'fixed')})</span></div>
          <div class="profile-meta-item"><label>Posted</label><span>${j.created_at ? new Date(j.created_at).toLocaleString() : '—'}</span></div>
          <div class="profile-meta-item"><label>Approved</label><span>${j.approved_at ? new Date(j.approved_at).toLocaleString() : '—'}</span></div>
          <div class="profile-meta-item"><label>Proposals</label><span>${st.proposals_count}</span></div>
          <div class="profile-meta-item"><label>Contracts</label><span>${st.contracts_count} (${st.active_contracts} active)</span></div>
          <div class="profile-meta-item"><label>Payments completed</label><span>$${parseFloat(st.payments_completed || 0).toFixed(2)}</span></div>
          <div class="profile-meta-item"><label>Escrow pending</label><span>$${parseFloat(st.escrow_pending || 0).toFixed(2)}</span></div>
        </div>
        <div style="margin-top:16px"><label style="display:block;font-size:10px;text-transform:uppercase;color:var(--text-3);font-weight:600;margin-bottom:8px">Skills</label>${skills}</div>
        <div style="margin-top:16px"><label style="display:block;font-size:10px;text-transform:uppercase;color:var(--text-3);font-weight:600;margin-bottom:8px">Description</label>
        <p style="font-size:13px;color:var(--text-2);line-height:1.6;white-space:pre-wrap">${escapeHtml(j.description || '')}</p></div>
        ${j.flag_reason ? `<p style="margin-top:12px;font-size:13px;color:var(--red)"><strong>Flag reason:</strong> ${escapeHtml(j.flag_reason)}</p>` : ''}
      </div>
    </div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><span class="card-title">Client</span></div>
      <div class="card-body">
        <div class="profile-meta-grid">
          <div class="profile-meta-item"><label>Name</label><span>${client.id ? `<button type="button" class="btn btn-outline btn-sm" onclick="openUserProfile(${client.id})">${escapeHtml(client.name)}</button>` : escapeHtml(client.name || '—')}</span></div>
          <div class="profile-meta-item"><label>Email</label><span>${escapeHtml(client.email || '—')}</span></div>
          <div class="profile-meta-item"><label>Status</label><span>${escapeHtml(client.status || '—')}</span></div>
          <div class="profile-meta-item"><label>Member since</label><span>${client.joined ? new Date(client.joined).toLocaleDateString() : '—'}</span></div>
        </div>
      </div>
    </div>
    <div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Proposals (${st.proposals_count})</span></div><div class="card-body table-wrapper">${proposalsHtml}</div></div>
    <div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Contracts &amp; Hired Freelancers (${st.contracts_count})</span></div><div class="card-body">${contractsHtml}</div></div>
    <div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Payments (${st.payments_count})</span></div><div class="card-body table-wrapper">${paymentsHtml}</div></div>
    <div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Disputes (${st.disputes_count})</span></div><div class="card-body table-wrapper">${disputesHtml}</div></div>
    ${invitationsHtml ? `<div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Invitations (${st.invitations_count})</span></div><div class="card-body table-wrapper">${invitationsHtml}</div></div>` : ''}
    <div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Messages (${st.messages_count})</span></div><div class="card-body">${messagesHtml}</div></div>
  `;
}

function debounceContractsSearch() {
  clearTimeout(contractsSearchTimer);
  contractsSearchTimer = setTimeout(loadContracts, 350);
}

async function loadContracts() {
  const table = document.getElementById('contractsTable');
  if (!table) return;
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading contracts…</div>';
  const search = (document.getElementById('contractsSearch')?.value || '').trim();
  const status = document.getElementById('contractsStatusFilter')?.value || '';
  const params = {};
  if (search) params.search = search;
  if (status) params.status = status;
  const data = await apiFetch('get_contracts', params);
  if (!data.success) {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Error')}</div>`;
    return;
  }
  const contracts = data.data || [];
  if (!contracts.length) {
    table.innerHTML = '<div class="loading">No contracts found.</div>';
    return;
  }
  table.innerHTML = `<table>
    <thead><tr><th>ID</th><th>Job</th><th>Client</th><th>Freelancer</th><th>Amount</th><th>Type</th><th>Status</th><th>Started</th><th>Actions</th></tr></thead>
    <tbody>
      ${contracts.map(c => `<tr>
        <td><strong>#${c.id}</strong></td>
        <td>
          <button type="button" class="btn btn-outline btn-sm" onclick="openJobDetail(${c.job_id})">${escapeHtml(c.job_title || 'Job #' + c.job_id)}</button><br>
          <small style="color:var(--text-3)">${escapeHtml(c.job_status || '')}</small>
        </td>
        <td>
          <button type="button" class="btn btn-outline btn-sm" onclick="openUserProfile(${c.client_id})">${escapeHtml(c.client_name)}</button><br>
          <small style="color:var(--text-3)">${escapeHtml(c.client_email || '')}</small>
        </td>
        <td>
          <button type="button" class="btn btn-outline btn-sm" onclick="openUserProfile(${c.freelancer_id})">${escapeHtml(c.freelancer_name)}</button><br>
          <small style="color:var(--text-3)">${escapeHtml(c.freelancer_email || '')}</small>
        </td>
        <td><strong>$${parseFloat(c.amount || 0).toFixed(2)}</strong></td>
        <td><span class="badge badge-gray">${escapeHtml(c.contract_type)}</span></td>
        <td><span class="badge ${contractStatusBadge(c.status)}">${escapeHtml(c.status)}</span></td>
        <td>${c.start_date ? new Date(c.start_date).toLocaleDateString() : '—'}</td>
        <td><button class="btn btn-primary btn-sm" onclick="openJobDetail(${c.job_id})">View Job</button></td>
      </tr>`).join('')}
    </tbody>
  </table>`;
  applyPagination('#contractsTable', 'tbody tr', 15);
}

async function loadUserProfile(userId) {
  const el = document.getElementById('userProfileContent');
  el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading profile…</div>';
  const res = await apiFetch('get_user_profile', { user_id: userId });
  if (!res.success) {
    el.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(res.message || 'Could not load profile')}</div>`;
    return;
  }
  el.innerHTML = renderUserProfilePage(res.data);
}

function refreshUserProfileIfOpen() {
  if (viewingUserId && document.getElementById('page-user-profile')?.classList.contains('active')) {
    loadUserProfile(viewingUserId);
  }
}

function freelancerBadgeLabel(badge) {
  const map = {
    expert_vetted: 'Expert Vetted',
    top_rated_plus: 'Top Rated Plus',
    top_rated: 'Top Rated',
    rising_talent: 'Rising Talent',
  };
  return map[badge] || '';
}

function renderUserProfileActions(u) {
  const isAdmin = u.role === 'admin';
  const name = escapeJsStr(u.name);
  const email = escapeJsStr(u.email);
  const balance = parseFloat(u.balance || 0);
  const statusSelect = isAdmin ? '' : `
    <select class="btn btn-outline btn-sm" id="profileStatusSelect" data-prev-status="${escapeHtml(u.status)}" onchange="onProfileStatusChange(${u.id}, this, '${name}', '${email}')">
      <option value="active" ${u.status === 'active' ? 'selected' : ''}>Active</option>
      <option value="suspended" ${u.status === 'suspended' ? 'selected' : ''}>Suspended</option>
      <option value="closed" ${u.status === 'closed' ? 'selected' : ''}>Closed</option>
    </select>`;
  return `
    <div class="profile-actions-bar">
      ${statusSelect}
      <button class="btn btn-outline btn-sm" onclick="openBalanceModal(${u.id}, '${name}', ${balance})">Balance</button>
      <button class="btn btn-outline btn-sm" onclick="openChangeEmailModal(${u.id}, '${name}', '${email}')" ${isAdmin ? 'disabled' : ''}>Email</button>
      <button class="btn btn-outline btn-sm" onclick="openResetPasswordModal(${u.id}, '${name}', '${email}')" ${isAdmin ? 'disabled' : ''}>Password</button>
      ${u.role === 'client' ? `<button class="btn btn-outline btn-sm" style="border-color:var(--blue);color:var(--blue)" onclick="openClientStatsModal(${u.id})">Stats</button>` : ''}
      <button class="btn btn-danger btn-sm" onclick="deleteUserFromProfile(${u.id})" ${isAdmin ? 'disabled' : ''}>Delete</button>
    </div>`;
}

function onProfileStatusChange(userId, selectEl, userName, userEmail) {
  onUserStatusChange(userId, selectEl, userName, userEmail);
}

async function deleteUserFromProfile(userId) {
  const ok = await remoConfirm('This cannot be undone.', 'Delete this user permanently?', { danger: true, confirmLabel: 'Delete' });
  if (!ok) return;
  const res = await apiFetch('delete_user', { user_id: userId });
  if (res.success) {
    closeUserProfile();
    loadUsers();
    toast('Deleted', 'User removed permanently', 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
}

function renderUserProfilePage(d) {
  const u = d.user;
  const initials = (u.name || '?').trim().split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();
  const avatarHtml = u.avatar_url
    ? `<img src="${escapeHtml(BASE_URL + '/' + String(u.avatar_url).replace(/^\/+/, ''))}" alt="">`
    : escapeHtml(initials);
  const skillsHtml = (d.skills || []).length
    ? d.skills.map(s => `<span class="skill-chip">${escapeHtml(typeof s === 'string' ? s : (s.name || s))}</span>`).join('')
    : '<span style="color:var(--text-3); font-size:13px">No skills listed</span>';

  let roleSection = '';
  if (u.role === 'freelancer' && d.freelancer_stats) {
    const fs = d.freelancer_stats;
    const badge = freelancerBadgeLabel(fs.badge);
    roleSection = `
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span class="card-title">Freelancer Performance</span></div>
        <div class="card-body">
          <div class="profile-meta-grid">
            <div class="profile-meta-item"><label>Job Success Score</label><span>${escapeHtml(fs.jss || 'N/A')}</span></div>
            <div class="profile-meta-item"><label>Rating</label><span>★ ${escapeHtml(fs.rating)} (${fs.reviews_count} reviews)</span></div>
            <div class="profile-meta-item"><label>Profile completeness</label><span>${fs.completeness}%</span></div>
            <div class="profile-meta-item"><label>Connects</label><span>${parseInt(u.connects || 0, 10)}</span></div>
            <div class="profile-meta-item"><label>Total earned</label><span>$${parseFloat(fs.total_earned || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span></div>
            <div class="profile-meta-item"><label>Completed contracts</label><span>${fs.completed_contracts}</span></div>
            <div class="profile-meta-item"><label>Active contracts</label><span>${fs.active_contracts}</span></div>
            <div class="profile-meta-item"><label>Proposals sent</label><span>${d.activity.proposals_sent}</span></div>
            ${badge ? `<div class="profile-meta-item"><label>Badge</label><span>${escapeHtml(badge)}</span></div>` : ''}
            <div class="profile-meta-item"><label>Hourly rate</label><span>$${parseFloat(u.hourly_rate || 0).toFixed(2)}/hr</span></div>
            <div class="profile-meta-item"><label>Availability</label><span>${escapeHtml(u.availability || 'available')}</span></div>
          </div>
        </div>
      </div>`;
    if ((d.services || []).length) {
      roleSection += `<div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Catalog Services</span></div><div class="card-body"><table><thead><tr><th>Title</th><th>Price</th><th>Delivery</th><th>Posted</th></tr></thead><tbody>
        ${d.services.map(s => `<tr><td>${escapeHtml(s.title)}</td><td>$${parseFloat(s.price).toFixed(2)}</td><td>${s.delivery_days} days</td><td>${s.created_at ? new Date(s.created_at).toLocaleDateString() : '—'}</td></tr>`).join('')}
      </tbody></table></div></div>`;
    }
    if ((d.reviews || []).length) {
      roleSection += `<div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Recent Reviews</span></div><div class="card-body">
        ${d.reviews.map(r => `<div style="padding:12px 0; border-bottom:1px solid var(--border)"><div style="font-weight:600">★ ${parseFloat(r.rating).toFixed(1)} — ${escapeHtml(r.client_name)} <span style="color:var(--text-3); font-weight:400">on ${escapeHtml(r.job_title || 'Contract')}</span></div>
        <p style="margin:8px 0 0; font-size:13px; color:var(--text-2); line-height:1.5">${escapeHtml(r.feedback || '')}</p></div>`).join('')}
      </div></div>`;
    }
  }

  if (u.role === 'client' && d.client_stats) {
    const cs = d.client_stats;
    roleSection = `
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span class="card-title">Client Activity</span></div>
        <div class="card-body">
          <div class="profile-meta-grid">
            <div class="profile-meta-item"><label>Member since</label><span>${escapeHtml(cs.join_date)}</span></div>
            <div class="profile-meta-item"><label>Total spent (display)</label><span>$${parseFloat(cs.effective_spent).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span></div>
            <div class="profile-meta-item"><label>Actual platform spent</label><span>$${parseFloat(cs.real_spent).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span></div>
            <div class="profile-meta-item"><label>Total hires (display)</label><span>${cs.effective_hires}</span></div>
            <div class="profile-meta-item"><label>Actual platform hires</label><span>${cs.real_hires}</span></div>
            <div class="profile-meta-item"><label>Jobs posted</label><span>${d.activity.jobs_posted}</span></div>
            <div class="profile-meta-item"><label>Contracts</label><span>${d.activity.contracts_as_client}</span></div>
            <div class="profile-meta-item"><label>Active contracts</label><span>${d.activity.active_contracts}</span></div>
          </div>
        </div>
      </div>`;
    if ((d.recent_jobs || []).length) {
      roleSection += `<div class="card" style="margin-bottom:16px"><div class="card-header"><span class="card-title">Recent Jobs</span></div><div class="card-body"><table><thead><tr><th>Title</th><th>Status</th><th>Budget</th><th>Posted</th></tr></thead><tbody>
        ${d.recent_jobs.map(j => `<tr><td>${escapeHtml(j.title)}</td><td><span class="badge badge-blue">${escapeHtml(j.status)}</span></td><td>$${parseFloat(j.budget || 0).toFixed(2)} ${escapeHtml(j.budget_type || '')}</td><td>${j.created_at ? new Date(j.created_at).toLocaleDateString() : '—'}</td></tr>`).join('')}
      </tbody></table></div></div>`;
    }
  }

  const docsHtml = (d.documents || []).length
    ? `<table><thead><tr><th>Type</th><th>Status</th><th>File</th><th>Submitted</th></tr></thead><tbody>
      ${d.documents.map(doc => `<tr>
        <td>${escapeHtml(doc.doc_type)}</td>
        <td><span class="badge ${doc.status === 'approved' ? 'badge-green' : doc.status === 'rejected' ? 'badge-red' : 'badge-amber'}">${escapeHtml(doc.status)}</span></td>
        <td>${verificationDocLinks(doc.file_path)}</td>
        <td>${doc.created_at ? new Date(doc.created_at).toLocaleString() : '—'}</td>
      </tr>`).join('')}
    </tbody></table>`
    : '<p style="color:var(--text-3); font-size:13px">No verification documents on file.</p>';

  return `
    <div class="card" style="margin-bottom:16px">
      <div class="card-body">
        <div class="profile-header">
          <div class="profile-avatar">${avatarHtml}</div>
          <div style="flex:1; min-width:200px">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px">
              <h2 style="font-size:22px; font-weight:600; margin:0">${escapeHtml(u.name)}</h2>
              <span class="badge ${u.role === 'admin' ? 'badge-red' : 'badge-blue'}">${escapeHtml(u.role)}</span>
              <span class="badge ${u.status === 'active' ? 'badge-green' : 'badge-amber'}">${escapeHtml(u.status)}</span>
            </div>
            ${u.title ? `<p style="color:var(--text-2); margin:0 0 8px">${escapeHtml(u.title)}</p>` : ''}
            <p style="margin:0; font-size:13px"><a href="mailto:${escapeHtml(u.email)}" style="color:var(--blue)">${escapeHtml(u.email)}</a></p>
            ${d.public_profile_url ? `<p style="margin:8px 0 0"><a href="${escapeHtml(d.public_profile_url)}" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="text-decoration:none">View public profile ↗</a></p>` : ''}
          </div>
        </div>
        <div class="profile-meta-grid">
          <div class="profile-meta-item"><label>User ID</label><span>#${u.id}</span></div>
          <div class="profile-meta-item"><label>Balance</label><span><strong>$${parseFloat(u.balance || 0).toFixed(2)}</strong></span></div>
          <div class="profile-meta-item"><label>Country</label><span>${escapeHtml(d.country_name)}</span></div>
          <div class="profile-meta-item"><label>Email verified</label><span>${d.email_verified ? 'Yes' : 'No'}</span></div>
          <div class="profile-meta-item"><label>ID verified</label><span>${d.identity_verified || u.is_verified ? 'Yes' : 'No'}</span></div>
          <div class="profile-meta-item"><label>Joined</label><span>${u.created_at ? new Date(u.created_at).toLocaleString() : '—'}</span></div>
          <div class="profile-meta-item"><label>Last login</label><span>${u.last_login_at ? new Date(u.last_login_at).toLocaleString() : '—'}</span></div>
          <div class="profile-meta-item"><label>Last active</label><span>${u.last_active_at ? new Date(u.last_active_at).toLocaleString() : '—'}</span></div>
        </div>
        ${renderUserProfileActions(u)}
      </div>
    </div>
    <div class="profile-two-col">
      <div class="card">
        <div class="card-header"><span class="card-title">About</span></div>
        <div class="card-body">
          <p style="font-size:14px; line-height:1.6; color:var(--text-2); white-space:pre-wrap">${u.bio ? escapeHtml(u.bio) : '<span style="color:var(--text-3)">No bio provided.</span>'}</p>
          ${u.role === 'freelancer' ? `<div style="margin-top:16px"><label style="display:block; font-size:10px; text-transform:uppercase; color:var(--text-3); font-weight:600; margin-bottom:8px">Skills</label>${skillsHtml}</div>` : ''}
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">Verification Documents</span></div>
        <div class="card-body table-wrapper">${docsHtml}</div>
      </div>
    </div>
    ${roleSection}`;
}

function formatLastActive(iso) {
  if (!iso) return '—';
  const d = new Date(iso.replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return '—';
  const sec = Math.floor((Date.now() - d.getTime()) / 1000);
  if (sec < 60) return 'Just now';
  if (sec < 3600) return Math.floor(sec / 60) + ' min ago';
  if (sec < 86400) return Math.floor(sec / 3600) + ' hr ago';
  return d.toLocaleString();
}

function userInitials(name) {
  return String(name || '?').trim().split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase() || '?';
}

function updateOnlineStatCard(counts, thresholdMinutes) {
  const mins = thresholdMinutes || ONLINE_THRESHOLD_MINUTES;
  const label = document.getElementById('onlineThresholdLabel');
  if (label) label.textContent = String(mins);
  const badge = document.getElementById('onlineUsersBadge');
  if (badge) {
    const total = counts?.total ?? 0;
    badge.textContent = total + ' online';
  }
  const statVal = document.getElementById('statOnlineUsers');
  if (statVal) statVal.textContent = String(counts?.total ?? 0);
  const statSub = document.getElementById('statOnlineBreakdown');
  if (statSub) {
    statSub.textContent = (counts?.clients ?? 0) + ' clients · ' + (counts?.freelancers ?? 0) + ' freelancers';
  }
}

function renderOnlineUsersTable(users) {
  if (!users.length) {
    return '<div class="loading" style="padding:24px">No users online right now.</div>';
  }
  return `<table>
    <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Last active</th><th></th></tr></thead>
    <tbody>
      ${users.map(u => {
        const avatarHtml = u.avatar_url
          ? `<img src="${escapeHtml(BASE_URL + '/' + String(u.avatar_url).replace(/^\/+/, ''))}" alt="">`
          : escapeHtml(userInitials(u.name));
        return `<tr>
          <td>
            <div class="online-user-cell">
              <span class="online-dot" title="Online"></span>
              <div class="online-user-avatar">${avatarHtml}</div>
              <strong>${escapeHtml(u.name)}</strong>
            </div>
          </td>
          <td>${escapeHtml(u.email)}</td>
          <td><span class="badge ${u.role === 'freelancer' ? 'badge-green' : 'badge-blue'}">${escapeHtml(u.role)}</span></td>
          <td style="color:var(--text-2)">${escapeHtml(formatLastActive(u.last_active_at))}</td>
          <td><button class="btn btn-outline btn-sm" onclick="openUserProfile(${u.id})">Profile</button></td>
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;
}

async function loadOnlineUsers() {
  const role = document.getElementById('onlineRoleFilter')?.value || '';
  const data = await apiFetch('get_online_users', { role });
  const table = document.getElementById('onlineUsersTable');
  if (!table) return;
  if (data.success) {
    updateOnlineStatCard(data.counts, data.threshold_minutes);
    table.innerHTML = renderOnlineUsersTable(data.data || []);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red)">${escapeHtml(data.message || 'Failed to load online users')}</div>`;
  }
}

function startOnlineRefresh() {
  stopOnlineRefresh();
  onlineRefreshTimer = setInterval(() => {
    if (document.getElementById('page-dashboard')?.classList.contains('active')) {
      loadOnlineUsers();
    }
  }, 60000);
}

function stopOnlineRefresh() {
  if (onlineRefreshTimer) {
    clearInterval(onlineRefreshTimer);
    onlineRefreshTimer = null;
  }
}

function formatCompactNumber(value) {
  const num = Number(value || 0);
  const abs = Math.abs(num);
  if (abs < 1000) return Math.round(num).toString();
  if (abs >= 1000000000) {
    return (num / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
  }
  if (abs >= 1000000) {
    return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
  }
  return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
}

async function loadDashboard() {
  const data = await apiFetch('get_stats');
  if (data.success) {
    const d = data.data;
    const onlineMins = d.online_threshold_minutes || ONLINE_THRESHOLD_MINUTES;
    document.getElementById('statsGrid').innerHTML = `
      <div class="stat-card">
        <div class="stat-icon blue"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value">${formatCompactNumber(d.total_users)}</div>
        <div class="stat-change up">↑ Active members</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg></div>
        <div class="stat-label">Online Now</div>
        <div class="stat-value" id="statOnlineUsers">${d.online_users ?? 0}</div>
        <div class="stat-change up" id="statOnlineBreakdown">${d.online_clients ?? 0} clients · ${d.online_freelancers ?? 0} freelancers</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></div>
        <div class="stat-label">Total Jobs</div>
        <div class="stat-value">${formatCompactNumber(d.total_jobs)}</div>
        <div class="stat-change up">↑ Posted listings</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
        <div class="stat-label">Total Payments</div>
        <div class="stat-value">${formatCompactNumber(d.total_payments)}</div>
        <div class="stat-change up">↑ Completed tx</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">$${formatCompactNumber(d.total_revenue)}</div>
        <div class="stat-change up">↑ Platform fees</div>
      </div>
    `;
    updateOnlineStatCard({
      total: d.online_users ?? 0,
      clients: d.online_clients ?? 0,
      freelancers: d.online_freelancers ?? 0,
    }, onlineMins);
    loadOnlineUsers();
    loadRecentUsers();
    startOnlineRefresh();
  }
}

async function loadRecentUsers() {
  const data = await apiFetch('get_users', { limit: 5 });
  if (data.success) {
    document.getElementById('recentUsersTable').innerHTML = renderUsersTable(data.data);
  }
}

function openBalanceModal(id, name, balance) {
  document.getElementById('balanceUserId').value = id;
  document.getElementById('balanceUserName').textContent = 'User: ' + name;
  document.getElementById('currentBalanceVal').textContent = '$' + parseFloat(balance || 0).toFixed(2);
  document.getElementById('balanceAmount').value = '';
  document.getElementById('balanceModal').style.display = 'flex';
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}

function escapeJsStr(str) {
  return String(str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function openResetPasswordModal(userId, name, email) {
  document.getElementById('resetPasswordUserId').value = userId;
  document.getElementById('resetPasswordUserName').textContent = 'User: ' + name + (email ? ' (' + email + ')' : '');
  document.getElementById('resetPasswordNew').value = '';
  document.getElementById('resetPasswordConfirm').value = '';
  document.getElementById('resetPasswordStatus').innerHTML = '';
  document.getElementById('resetPasswordModal').style.display = 'flex';
}

async function submitResetUserPassword(e) {
  e.preventDefault();
  const status = document.getElementById('resetPasswordStatus');
  const userId = document.getElementById('resetPasswordUserId').value;
  const newPassword = document.getElementById('resetPasswordNew').value;
  const confirmPassword = document.getElementById('resetPasswordConfirm').value;

  status.innerHTML = '<span style="color:var(--text-2)">Updating…</span>';

  const url = new URL(API);
  url.searchParams.append('action', 'reset_user_password');

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: userId,
      new_password: newPassword,
      confirm_password: confirmPassword
    })
  }).then(r => r.json());

  if (res.success) {
    status.innerHTML = '<span style="color:var(--accent); font-weight:500">' + res.message + '</span>';
    document.getElementById('resetPasswordForm').reset();
    setTimeout(() => { closeModal('resetPasswordModal'); refreshUserProfileIfOpen(); }, 1500);
  } else {
    status.innerHTML = '<span style="color:var(--red)">' + res.message + '</span>';
  }
}

function openChangeEmailModal(userId, name, email) {
  document.getElementById('changeEmailUserId').value = userId;
  document.getElementById('changeEmailUserName').textContent = 'User: ' + name;
  document.getElementById('changeEmailCurrent').value = email || '';
  document.getElementById('changeEmailNew').value = '';
  document.getElementById('changeEmailStatus').innerHTML = '';
  document.getElementById('changeEmailModal').style.display = 'flex';
}

async function submitChangeUserEmail(e) {
  e.preventDefault();
  const status = document.getElementById('changeEmailStatus');
  const userId = document.getElementById('changeEmailUserId').value;
  const newEmail = document.getElementById('changeEmailNew').value.trim();

  status.innerHTML = '<span style="color:var(--text-2)">Updating…</span>';

  const url = new URL(API);
  url.searchParams.append('action', 'change_user_email');

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: userId, new_email: newEmail })
  }).then(r => r.json());

  if (res.success) {
    status.innerHTML = '<span style="color:var(--accent); font-weight:500">' + res.message + '</span>';
    setTimeout(() => {
      closeModal('changeEmailModal');
      loadUsers();
      refreshUserProfileIfOpen();
    }, 1500);
  } else {
    status.innerHTML = '<span style="color:var(--red)">' + res.message + '</span>';
  }
}

async function updateBalance(mode) {
  const id = document.getElementById('balanceUserId').value;
  const amount = document.getElementById('balanceAmount').value;
  if (!amount) {
    remoAlert('Please enter an amount', 'Balance');
    return;
  }

  const res = await apiFetch('update_balance', { user_id: id, amount: amount, mode: mode });
  if (res.success) {
    closeModal('balanceModal');
    refreshPage('users');
    refreshUserProfileIfOpen();
    toast('Balance updated', res.message, 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
}

async function changePassword(e) {
  e.preventDefault();
  const form = e.target;
  const status = document.getElementById('passwordStatus');
  status.innerHTML = '<span class="info">Updating…</span>';
  
  const formData = new FormData(form);
  const params = {};
  formData.forEach((v,k) => params[k] = v);
  
  const res = await apiFetch('change_password', params);
  if (res.success) {
    status.innerHTML = `<span class="success">${res.message}</span>`;
    form.reset();
  } else {
    status.innerHTML = `<span class="error">${res.message}</span>`;
  }
}

let verificationTab = 'pending';

function switchVerificationTab(tab, btn) {
  verificationTab = tab;
  document.querySelectorAll('#verificationTabs .verify-tab').forEach(el => el.classList.remove('active'));
  if (btn) btn.classList.add('active');
  loadVerifications(tab);
}

function verificationStatusBadge(status) {
  if (status === 'approved') return '<span class="badge badge-green">Verified</span>';
  if (status === 'rejected') return '<span class="badge badge-red">Rejected</span>';
  return '<span class="badge badge-amber">Pending</span>';
}

async function loadVerifications(tab) {
  const activeTab = tab || verificationTab || 'pending';
  verificationTab = activeTab;
  const tableEl = document.getElementById('verificationsTable');
  tableEl.innerHTML = '<div class="loading"><span class="spinner"></span>Loading documents…</div>';

  const data = await apiFetch('get_verifications', { status: activeTab });
  if (!data.success) {
    tableEl.innerHTML = `<div class="loading">${escapeHtml(data.message || 'Failed to load documents.')}</div>`;
    return;
  }

  const docs = data.data || [];
  const emptyLabels = {
    pending: 'No pending verification documents.',
    verified: 'No verified documents on file.',
    rejected: 'No rejected documents on file.',
  };

  if (!docs.length) {
    tableEl.innerHTML = `<div class="loading">${emptyLabels[activeTab] || 'No documents found.'}</div>`;
    return;
  }

  const showActions = activeTab === 'pending';
  const actionsHeader = showActions ? '<th>Actions</th>' : '<th>Reviewed</th>';
  const submittedCol = '<th>Submitted</th>';

  tableEl.innerHTML = `<table>
    <thead><tr><th>User</th><th>Type</th><th>View</th><th>Status</th>${submittedCol}${actionsHeader}</tr></thead>
    <tbody>
      ${docs.map(d => {
        const submitted = d.created_at ? new Date(d.created_at).toLocaleString() : '—';
        const actions = showActions
          ? `<td>
              <button class="btn btn-primary btn-sm" onclick="verifyDoc(${d.id}, 'approved')">Approve</button>
              <button class="btn btn-danger btn-sm" onclick="verifyDoc(${d.id}, 'rejected')">Reject</button>
            </td>`
          : `<td>${d.updated_at ? new Date(d.updated_at).toLocaleString() : submitted}${d.rejection_reason ? `<br><small style="color:var(--text-2)">Reason: ${escapeHtml(d.rejection_reason)}</small>` : ''}</td>`;
        return `<tr>
          <td><strong>${escapeHtml(d.user_name)}</strong><br><small>${escapeHtml(d.user_email)}</small></td>
          <td>${escapeHtml(d.doc_type)}</td>
          <td>${verificationDocLinks(d.file_path)}</td>
          <td>${verificationStatusBadge(d.status)}</td>
          <td>${submitted}</td>
          ${actions}
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;
  applyPagination('#verificationsTable', 'tbody tr', 10);
}

async function verifyDoc(id, status) {
  let reason = '';
  if (status === 'rejected') {
    reason = await remoPrompt('Enter rejection reason:', 'Reject document', '', { multiline: true });
    if (reason === null) return;
  }
  
  const res = await apiFetch('update_verification', { id: id, status: status, reason: reason });
  if (res.success) {
    loadVerifications();
    toast('Updated', 'Verification document ' + status, 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
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

async function loadSettings() {
  const status = document.getElementById('settingsStatus');
  status.innerHTML = '<span class="info">Loading settings…</span>';
  
  const res = await apiFetch('get_settings');
  if (res.success) {
    status.innerHTML = '';
    const s = res.data;
    document.getElementById('set_freelancer_fee_fixed').value = s.freelancer_fee_fixed ? s.freelancer_fee_fixed.value : 10;
    document.getElementById('set_freelancer_fee_hourly').value = s.freelancer_fee_hourly ? s.freelancer_fee_hourly.value : 10;
    document.getElementById('set_freelancer_fee_monthly').value = s.freelancer_fee_monthly ? s.freelancer_fee_monthly.value : 10;
    
    document.getElementById('set_client_fee_fixed').value = s.client_fee_fixed ? s.client_fee_fixed.value : 0;
    document.getElementById('set_client_fee_hourly').value = s.client_fee_hourly ? s.client_fee_hourly.value : 0;
    document.getElementById('set_client_fee_monthly').value = s.client_fee_monthly ? s.client_fee_monthly.value : 0;

    const gaEnabled = s.google_analytics_enabled ? String(s.google_analytics_enabled.value) : '0';
    document.getElementById('set_google_analytics_enabled').checked = gaEnabled === '1' || gaEnabled === 'true';
    document.getElementById('set_google_analytics_id').value = s.google_analytics_id ? (s.google_analytics_id.value || '') : '';

    const referralEnabled = s.referral_enabled ? String(s.referral_enabled.value) : '1';
    document.getElementById('set_referral_enabled').checked = referralEnabled === '1' || referralEnabled === 'true';
    document.getElementById('set_referral_reward_threshold').value = s.referral_reward_threshold ? s.referral_reward_threshold.value : 10;
    document.getElementById('set_referral_reward_amount').value = s.referral_reward_amount ? s.referral_reward_amount.value : 1;
  } else {
    status.innerHTML = `<span class="error">Failed to load settings: ${res.message}</span>`;
  }
}

async function saveSettings(e) {
  e.preventDefault();
  const form = e.target;
  const status = document.getElementById('settingsStatus');
  status.innerHTML = '<span class="info">Saving changes…</span>';
  
  const payload = {
    freelancer_fee_fixed: document.getElementById('set_freelancer_fee_fixed').value,
    freelancer_fee_hourly: document.getElementById('set_freelancer_fee_hourly').value,
    freelancer_fee_monthly: document.getElementById('set_freelancer_fee_monthly').value,
    client_fee_fixed: document.getElementById('set_client_fee_fixed').value,
    client_fee_hourly: document.getElementById('set_client_fee_hourly').value,
    client_fee_monthly: document.getElementById('set_client_fee_monthly').value
  };
  
  // Call API using POST method or JSON payload.
  const url = new URL(API);
  url.searchParams.append('action', 'save_settings');
  
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).then(r => r.json());
  
  if (res.success) {
    status.innerHTML = `<span class="success" style="color:var(--accent); font-weight: 500;">${res.message}</span>`;
    setTimeout(() => { status.innerHTML = ''; }, 3000);
  } else {
    status.innerHTML = `<span class="error">${res.message}</span>`;
  }
}

async function saveAnalyticsSettings(e) {
  e.preventDefault();
  const status = document.getElementById('analyticsStatus');
  status.innerHTML = '<span class="info">Saving analytics settings…</span>';

  const payload = {
    google_analytics_enabled: document.getElementById('set_google_analytics_enabled').checked ? '1' : '0',
    google_analytics_id: document.getElementById('set_google_analytics_id').value.trim()
  };

  const url = new URL(API);
  url.searchParams.append('action', 'save_settings');

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).then(r => r.json());

  if (res.success) {
    status.innerHTML = `<span class="success" style="color:var(--accent); font-weight: 500;">${res.message}</span>`;
    setTimeout(() => { status.innerHTML = ''; }, 3000);
  } else {
    status.innerHTML = `<span class="error">${res.message}</span>`;
  }
}

async function saveReferralSettings(e) {
  e.preventDefault();
  const status = document.getElementById('referralSettingsStatus');
  status.innerHTML = '<span class="info">Saving referral settings…</span>';

  const payload = {
    referral_enabled: document.getElementById('set_referral_enabled').checked ? '1' : '0',
    referral_reward_threshold: document.getElementById('set_referral_reward_threshold').value,
    referral_reward_amount: document.getElementById('set_referral_reward_amount').value
  };

  const url = new URL(API);
  url.searchParams.append('action', 'save_settings');

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).then(r => r.json());

  if (res.success) {
    status.innerHTML = `<span class="success" style="color:var(--accent); font-weight: 500;">${res.message}</span>`;
    setTimeout(() => { status.innerHTML = ''; }, 3000);
  } else {
    status.innerHTML = `<span class="error">${res.message}</span>`;
  }
}

async function loadJobs() {
  const table = document.getElementById('jobsTable');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading jobs…</div>';
  
  const data = await apiFetch('get_jobs');
  if (data.success) {
    const jobs = data.data;
    if (!jobs.length) {
      table.innerHTML = '<div class="loading">No jobs found on the platform.</div>';
      return;
    }
    table.innerHTML = `<table>
      <thead><tr><th>Job Details</th><th>Client</th><th>Budget</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        ${jobs.map(j => `<tr>
          <td>
            <div style="font-weight:600; color:var(--accent); font-size:14px;">${j.title}</div>
            <small style="color:var(--text-3);">Posted: ${new Date(j.created_at).toLocaleDateString()}</small>
          </td>
          <td>
            <strong>${j.client_name || 'System / Client'}</strong><br>
            <small style="color:var(--text-2);">${j.client_email || ''}</small>
          </td>
          <td><strong>$${parseFloat(j.budget || 0).toLocaleString()}</strong></td>
          <td><span class="badge badge-gray">${j.job_type || 'fixed'}</span></td>
          <td><span class="badge ${j.status === 'open' ? 'badge-green' : 'badge-amber'}">${j.status}</span></td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <button class="btn btn-primary btn-sm" onclick="openJobDetail(${j.id})">View</button>
            <button class="btn btn-danger btn-sm" onclick="deleteJob(${j.id})">Delete</button>
          </td>
        </tr>`).join('')}
      </tbody>
    </table>`;
    applyPagination('#jobsTable', 'tbody tr', 10);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${data.message}</div>`;
  }
}

async function loadJobCategories() {
  const table = document.getElementById('jobCategoriesTable');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading job categories…</div>';

  const data = await apiFetch('get_job_categories');
  if (!data.success) {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Failed to load categories')}</div>`;
    return;
  }

  const rows = data.data || [];
  jobCategoriesCache = rows;
  if (!rows.length) {
    table.innerHTML = '<div class="loading">No job categories found. Add your first category.</div>';
    return;
  }

  table.innerHTML = `<table>
    <thead><tr><th>ID</th><th>Name</th><th>Image</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      ${rows.map(c => `<tr>
        <td>#${c.id}</td>
        <td><strong>${escapeHtml(c.name || '')}</strong></td>
        <td>${c.image ? `<img src="${escapeHtml(c.image)}" alt="" style="width:34px;height:34px;border-radius:6px;object-fit:cover;border:1px solid var(--border)">` : '<span style="color:var(--text-3)">—</span>'}</td>
        <td><span class="badge ${c.status === 'active' ? 'badge-green' : 'badge-gray'}">${escapeHtml(c.status || 'inactive')}</span></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" onclick="openJobCategoryModalById(${c.id})">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteJobCategory(${c.id})">Delete</button>
        </td>
      </tr>`).join('')}
    </tbody>
  </table>`;
  applyPagination('#jobCategoriesTable', 'tbody tr', 10);
}

function openJobCategoryModalById(id) {
  const category = jobCategoriesCache.find(item => parseInt(item.id, 10) === parseInt(id, 10)) || null;
  openJobCategoryModal(category);
}

function openJobCategoryModal(category = null) {
  const modal = document.getElementById('jobCategoryModal');
  const title = document.getElementById('jobCategoryModalTitle');
  const statusEl = document.getElementById('jobCategoryFormStatus');
  document.getElementById('jobCategoryForm').reset();
  document.getElementById('jobCategoryId').value = category?.id || '';
  document.getElementById('jobCategoryName').value = category?.name || '';
  document.getElementById('jobCategoryImage').value = category?.image || '';
  document.getElementById('jobCategoryStatus').value = category?.status || 'active';
  statusEl.innerHTML = '';
  title.textContent = category ? 'Edit Job Category' : 'Add Job Category';
  modal.style.display = 'flex';
}

async function saveJobCategory(e) {
  e.preventDefault();
  const statusEl = document.getElementById('jobCategoryFormStatus');
  const payload = {
    id: parseInt(document.getElementById('jobCategoryId').value || '0', 10),
    name: document.getElementById('jobCategoryName').value.trim(),
    image: document.getElementById('jobCategoryImage').value.trim(),
    status: document.getElementById('jobCategoryStatus').value
  };

  if (!payload.name) {
    statusEl.innerHTML = '<span style="color:var(--red)">Category name is required</span>';
    return;
  }

  statusEl.innerHTML = '<span style="color:var(--text-2)">Saving…</span>';
  const res = await apiPost('save_job_category', payload);
  if (!res.success) {
    statusEl.innerHTML = `<span style="color:var(--red)">${escapeHtml(res.message || 'Failed to save')}</span>`;
    return;
  }

  closeModal('jobCategoryModal');
  toast('Saved', res.message || 'Category saved', 'success');
  loadJobCategories();
}

async function deleteJobCategory(id) {
  const ok = await remoConfirm('Jobs that already use this category keep their current category text.', 'Delete this job category?', { danger: true, confirmLabel: 'Delete' });
  if (!ok) return;
  const res = await apiFetch('delete_job_category', { id });
  if (!res.success) {
    remoAlert(res.message || 'Failed to delete category', 'Error');
    return;
  }
  toast('Deleted', 'Job category removed', 'success');
  loadJobCategories();
}

async function deleteJob(id) {
  const ok = await remoConfirm(
    'This will also remove related proposals and contracts.',
    'Delete this job permanently?',
    { danger: true, confirmLabel: 'Delete' }
  );
  if (!ok) return;
  const res = await apiFetch('delete_job', { job_id: id });
  if (res.success) {
    refreshPage('jobs');
    toast('Deleted', 'Job post removed', 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
}

let userEmailVerifiedFilter = '';

function getUserFilterParams() {
  const params = {};
  const role = (document.getElementById('userFilterRole')?.value || '').trim();
  const status = (document.getElementById('userFilterStatus')?.value || '').trim();
  const name = (document.getElementById('userFilterName')?.value || '').trim();
  const email = (document.getElementById('userFilterEmail')?.value || '').trim();
  if (role) params.role = role;
  if (status) params.status = status;
  if (name) params.name = name;
  if (email) params.email = email;
  if (userEmailVerifiedFilter !== '') params.email_verified = userEmailVerifiedFilter;
  return params;
}

function setUserEmailVerifiedFilter(value, btn) {
  userEmailVerifiedFilter = value;
  document.querySelectorAll('#userEmailVerifyTabs .verify-tab').forEach(el => el.classList.remove('active'));
  if (btn) btn.classList.add('active');
  loadUsers();
}

function resetUserFilters() {
  userEmailVerifiedFilter = '';
  document.querySelectorAll('#userEmailVerifyTabs .verify-tab').forEach(el => {
    el.classList.toggle('active', el.dataset.userEmailVerified === '');
  });
  const roleEl = document.getElementById('userFilterRole');
  const statusEl = document.getElementById('userFilterStatus');
  const nameEl = document.getElementById('userFilterName');
  const emailEl = document.getElementById('userFilterEmail');
  if (roleEl) roleEl.value = 'freelancer';
  if (statusEl) statusEl.value = '';
  if (nameEl) nameEl.value = '';
  if (emailEl) emailEl.value = '';
  loadUsers();
}

function debounceUserSearch() {
  clearTimeout(userSearchTimer);
  userSearchTimer = setTimeout(loadUsers, 350);
}

function exportUsersExcel() {
  const url = new URL(API);
  url.searchParams.set('action', 'export_users');
  Object.entries(getUserFilterParams()).forEach(([k, v]) => url.searchParams.set(k, v));
  window.location.href = url.toString();
}

async function loadUsers() {
  const table = document.getElementById('usersTable');
  const countLabel = document.getElementById('usersCountLabel');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading users…</div>';
  if (countLabel) countLabel.textContent = 'Loading…';
  const data = await apiFetch('get_users', getUserFilterParams());
  if (data.success) {
    const count = data.count ?? data.data.length;
    if (countLabel) countLabel.textContent = `${count} user${count === 1 ? '' : 's'} matching filters`;
    table.innerHTML = renderUsersTable(data.data);
    applyPagination('#usersTable', 'tbody tr', 10);
  } else {
    if (countLabel) countLabel.textContent = '—';
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Could not load users')}</div>`;
  }
}

function renderUsersTable(users) {
  if (!users.length) return '<div class="loading">No users match the current filters.</div>';
  return `<table>
    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Email verified</th><th>Status</th><th>Balance</th><th>Actions</th></tr></thead>
    <tbody>
      ${users.map(u => `<tr>
        <td style="color:var(--muted)">#${u.id}</td>
        <td><strong>${escapeHtml(u.name)}</strong></td>
        <td>${escapeHtml(u.email)}</td>
        <td><span class="badge ${u.role === 'admin' ? 'badge-red' : 'badge-blue'}">${escapeHtml(u.role)}</span></td>
        <td><span class="badge ${u.email_verified ? 'badge-green' : 'badge-gray'}">${u.email_verified ? 'Verified' : 'Unverified'}</span></td>
        <td>
          <select class="btn btn-outline btn-sm" data-prev-status="${escapeHtml(u.status)}" onchange="onUserStatusChange(${u.id}, this, '${escapeJsStr(u.name)}', '${escapeJsStr(u.email)}')" ${u.role === 'admin' ? 'disabled' : ''}>
            <option value="active" ${u.status === 'active' ? 'selected' : ''}>Active</option>
            <option value="suspended" ${u.status === 'suspended' ? 'selected' : ''}>Suspended</option>
            <option value="closed" ${u.status === 'closed' ? 'selected' : ''}>Closed</option>
          </select>
        </td>
        <td><strong>$${parseFloat(u.balance || 0).toFixed(2)}</strong></td>
        <td style="display:flex; gap:6px; flex-wrap:wrap;">
          <button class="btn btn-primary btn-sm" onclick="openUserProfile(${u.id})">Profile</button>
          <button class="btn btn-outline btn-sm" onclick="openBalanceModal(${u.id}, '${escapeJsStr(u.name)}', ${u.balance})">Balance</button>
          <button class="btn btn-outline btn-sm" onclick="openChangeEmailModal(${u.id}, '${escapeJsStr(u.name)}', '${escapeJsStr(u.email)}')" ${u.role === 'admin' ? 'disabled' : ''}>Email</button>
          <button class="btn btn-outline btn-sm" onclick="openResetPasswordModal(${u.id}, '${escapeJsStr(u.name)}', '${escapeJsStr(u.email)}')" ${u.role === 'admin' ? 'disabled' : ''}>Password</button>
          ${u.role === 'client' ? `<button class="btn btn-outline btn-sm" style="border-color:var(--blue);color:var(--blue)" onclick="openClientStatsModal(${u.id})">Stats</button>` : ''}
          ${u.role === 'freelancer' ? `<button class="btn btn-outline btn-sm" style="border-color:var(--accent);color:var(--accent)" onclick="openFreelancerJoinDateModal(${u.id}, '${escapeJsStr(u.name)}', '${escapeJsStr(u.created_at || '')}')">Join date</button>` : ''}
          <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})" ${u.role === 'admin' ? 'disabled' : ''}>Delete</button>
        </td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

function debounceAgenciesSearch() {
  clearTimeout(agenciesSearchTimer);
  agenciesSearchTimer = setTimeout(loadAgencies, 300);
}

async function loadAgencies() {
  const table = document.getElementById('agenciesTable');
  if (!table) return;
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading agencies…</div>';
  const search = (document.getElementById('agenciesSearch')?.value || '').trim();
  const res = await apiFetch('get_agencies_admin', { search });
  if (!res.success) {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(res.message || 'Could not load agencies')}</div>`;
    return;
  }
  table.innerHTML = renderAgenciesTable(res.data || []);
  applyPagination('#agenciesTable', 'tbody tr[data-kind="agency"]', 8);
}

function renderAgenciesTable(agencies) {
  if (!agencies.length) return '<div class="loading">No agencies found.</div>';

  return `<table>
    <thead>
      <tr>
        <th>Agency</th>
        <th>Owner</th>
        <th>Members</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      ${agencies.map(a => {
        const members = Array.isArray(a.members) ? a.members : [];
        const membersHtml = members.length
          ? members.map(m => {
              const isOwner = m.role === 'owner';
              const roleControl = isOwner
                ? `<span class="badge badge-amber">Owner</span>`
                : `<select class="btn btn-outline btn-sm" onchange="changeAgencyMemberRole(${a.id}, ${m.user_id}, this.value)">
                    <option value="member" ${m.role === 'member' ? 'selected' : ''}>Member</option>
                    <option value="admin" ${m.role === 'admin' ? 'selected' : ''}>Admin</option>
                  </select>`;
              const removeBtn = isOwner
                ? ''
                : `<button class="btn btn-danger btn-sm" onclick="removeAgencyMember(${a.id}, ${m.user_id}, '${escapeJsStr(m.name)}')">Remove</button>`;
              return `<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid var(--border)">
                <div style="min-width:0">
                  <div style="font-size:12.5px;font-weight:600">${escapeHtml(m.name || 'Member')}</div>
                  <div style="font-size:11px;color:var(--text-3)">${escapeHtml(m.email || '')}</div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                  ${roleControl}
                  ${removeBtn}
                </div>
              </div>`;
            }).join('')
          : '<span style="color:var(--text-3);font-size:12px">No members</span>';

        return `<tr data-kind="agency">
          <td>
            <div style="display:flex;flex-direction:column;gap:6px;min-width:220px">
              <input id="agency-name-${a.id}" type="text" value="${escapeHtml(a.name || '')}" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:12px;outline:none">
              <textarea id="agency-desc-${a.id}" rows="2" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:12px;resize:vertical;outline:none">${escapeHtml(a.description || '')}</textarea>
              <div style="display:flex;flex-direction:column;gap:8px;padding:12px;min-height:88px;border:1px solid #d1fae5;border-radius:10px;background:linear-gradient(180deg,#f0fdf4 0%,#ecfdf3 100%)">
                <div style="font-size:11px;color:#065f46;font-weight:700;letter-spacing:.02em;text-transform:uppercase">Complete agency earnings</div>
                <div style="font-size:20px;line-height:1.1;font-weight:800;color:#065f46">$${Number(a.agency_earned_total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                <div style="font-size:11px;color:#047857">(live: $${Number(a.agency_earned_live || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} · offset: $${Number(a.agency_earnings_offset || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })})</div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                  <input id="agency-earnings-amount-${a.id}" type="number" min="0.01" step="0.01" placeholder="Amount" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:12px;outline:none;max-width:120px">
                  <button class="btn btn-outline btn-sm" onclick="adjustAgencyEarnings(${a.id}, 'add')">+ Add</button>
                  <button class="btn btn-outline btn-sm" onclick="adjustAgencyEarnings(${a.id}, 'subtract')">- Subtract</button>
                </div>
              </div>
              <div style="font-size:11px;color:var(--text-3)">Slug: ${escapeHtml(a.slug || '—')} · ID #${a.id}</div>
            </div>
          </td>
          <td>
            <div style="font-size:12.5px;font-weight:600">${escapeHtml(a.owner_name || 'Unknown')}</div>
            <div style="font-size:11px;color:var(--text-3)">${escapeHtml(a.owner_email || '')}</div>
          </td>
          <td style="min-width:300px">
            <div style="font-size:11px;color:var(--text-3);margin-bottom:6px">${members.length} active member${members.length === 1 ? '' : 's'}</div>
            <div style="max-height:190px;overflow:auto;padding-right:4px">${membersHtml}</div>
          </td>
          <td style="font-size:12px;color:var(--text-2)">${a.created_at ? new Date(a.created_at).toLocaleString() : '—'}</td>
          <td style="display:flex;flex-direction:column;gap:8px;min-width:120px">
            <button class="btn btn-primary btn-sm" onclick="saveAgencyFromAdmin(${a.id})">Save</button>
            <button class="btn btn-danger btn-sm" onclick="deleteAgencyFromAdmin(${a.id}, '${escapeJsStr(a.name || '')}')">Delete</button>
          </td>
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;
}

async function saveAgencyFromAdmin(agencyId) {
  const name = (document.getElementById(`agency-name-${agencyId}`)?.value || '').trim();
  const description = (document.getElementById(`agency-desc-${agencyId}`)?.value || '').trim();
  if (!name) {
    remoAlert('Agency name is required', 'Validation');
    return;
  }
  const res = await apiPost('update_agency_admin', { agency_id: agencyId, name, description });
  if (res.success) {
    toast('Saved', res.message || 'Agency updated', 'success');
  } else {
    remoAlert(res.message || 'Could not update agency', 'Error');
  }
  await loadAgencies();
}

async function adjustAgencyEarnings(agencyId, mode) {
  const input = document.getElementById(`agency-earnings-amount-${agencyId}`);
  try {
    const amount = Number(input?.value || 0);
    if (!Number.isFinite(amount) || amount <= 0) {
      toast('Validation', 'Enter a valid amount greater than 0', 'error');
      return;
    }

    const res = await apiPost('adjust_agency_earnings_admin', { agency_id: agencyId, mode, amount });
    if (res && res.success) {
      toast('Updated', res.message || 'Agency earnings updated', 'success');
      if (input) input.value = '';
    } else {
      const msg = (res && res.message) ? res.message : 'Could not update agency earnings';
      toast('Error', msg, 'error');
    }
  } catch (e) {
    toast('Error', (e && e.message) ? e.message : 'Could not update agency earnings', 'error');
  } finally {
    try {
      await loadAgencies();
    } catch (e) {
      window.location.reload();
    }
  }
}

async function deleteAgencyFromAdmin(agencyId, agencyName) {
  const ok = await remoConfirm(
    `Delete agency "${agencyName}"? All linked users will be moved to individual mode.`,
    'Delete agency?',
    { danger: true, confirmLabel: 'Delete' }
  );
  if (!ok) return;
  const res = await apiPost('delete_agency_admin', { agency_id: agencyId });
  if (res.success) {
    toast('Deleted', res.message || 'Agency deleted', 'success');
  } else {
    remoAlert(res.message || 'Could not delete agency', 'Error');
  }
  await loadAgencies();
}

async function changeAgencyMemberRole(agencyId, userId, role) {
  const res = await apiPost('update_agency_member_admin', { agency_id: agencyId, user_id: userId, role });
  if (res.success) {
    toast('Updated', res.message || 'Member role updated', 'success');
  } else {
    remoAlert(res.message || 'Could not update member role', 'Error');
  }
  await loadAgencies();
}

async function removeAgencyMember(agencyId, userId, memberName) {
  const ok = await remoConfirm(
    `Remove "${memberName}" from this agency?`,
    'Remove member?',
    { danger: true, confirmLabel: 'Remove' }
  );
  if (!ok) return;
  try {
    const res = await apiPost('remove_agency_member_admin', { agency_id: agencyId, user_id: userId });
    if (res && res.success) {
      toast('Removed', res.message || 'Member removed', 'success');
    } else {
      const msg = (res && res.message) ? res.message : 'Could not remove member';
      toast('Error', msg, 'error');
    }
  } catch (e) {
    toast('Error', (e && e.message) ? e.message : 'Could not remove member', 'error');
  } finally {
    try {
      await loadAgencies();
    } catch (e) {
      window.location.reload();
    }
  }
}

function onUserStatusChange(userId, selectEl, userName, userEmail) {
  const newStatus = selectEl.value;
  const previousStatus = selectEl.dataset.prevStatus || 'active';
  if (newStatus === previousStatus) return;

  if (newStatus === 'suspended') {
    selectEl.value = previousStatus;
    openSuspendModal(userId, userName, userEmail);
    return;
  }

  updateUserStatus(userId, newStatus, selectEl, previousStatus);
}

function openSuspendModal(userId, userName, userEmail) {
  document.getElementById('suspendUserId').value = userId;
  document.getElementById('suspendReason').value = '';
  document.getElementById('suspendStatus').innerHTML = '';
  document.getElementById('suspendConfirmBtn').disabled = false;
  document.getElementById('suspendUserSummary').innerHTML =
    `Suspend <strong>${escapeHtml(userName)}</strong> (${escapeHtml(userEmail)})? The user will receive an email with the reason you provide.`;
  document.getElementById('suspendUserModal').style.display = 'flex';
}

function closeSuspendModal() {
  document.getElementById('suspendUserModal').style.display = 'none';
}

async function submitSuspendUser() {
  const userId = document.getElementById('suspendUserId').value;
  const reason = document.getElementById('suspendReason').value.trim();
  const statusEl = document.getElementById('suspendStatus');
  const btn = document.getElementById('suspendConfirmBtn');

  if (!reason) {
    statusEl.innerHTML = '<span style="color:var(--red)">Please enter a suspension reason.</span>';
    return;
  }

  statusEl.innerHTML = '<span style="color:var(--text-2)">Suspending account and sending email…</span>';
  btn.disabled = true;

  const res = await apiFetch('update_user_status', { user_id: userId, status: 'suspended', reason: reason });

  if (res.success) {
    statusEl.innerHTML = '<span style="color:var(--accent); font-weight:500">' + escapeHtml(res.message) + '</span>';
    setTimeout(() => {
      closeSuspendModal();
      loadUsers();
      refreshUserProfileIfOpen();
    }, 1200);
  } else {
    statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Could not suspend user') + '</span>';
    btn.disabled = false;
  }
}

async function updateUserStatus(userId, status, selectEl, previousStatus) {
  const ok = await remoConfirm(`Change this user's status to "${status}"?`, 'Update account status');
  if (!ok) {
    if (selectEl && previousStatus) selectEl.value = previousStatus;
    else loadUsers();
    return;
  }
  const res = await apiFetch('update_user_status', { user_id: userId, status: status });
  if (res.success) {
    if (selectEl) selectEl.dataset.prevStatus = status;
    loadUsers();
    refreshUserProfileIfOpen();
    toast('Status updated', res.message, 'success');
  } else {
    remoAlert(res.message, 'Error');
    if (selectEl && previousStatus) selectEl.value = previousStatus;
    else loadUsers();
  }
}

async function deleteUser(userId) {
  const ok = await remoConfirm('This cannot be undone.', 'Delete this user permanently?', { danger: true, confirmLabel: 'Delete' });
  if (!ok) return;
  const res = await apiFetch('delete_user', { user_id: userId });
  if (res.success) {
    loadUsers();
    if (viewingUserId === userId) closeUserProfile();
    toast('Deleted', 'User removed permanently', 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
}

async function openClientStatsModal(userId) {
  document.getElementById('statsUserId').value = userId;
  document.getElementById('statsLoading').style.display = 'block';
  document.getElementById('statsFormFields').style.display = 'none';
  document.getElementById('statsStatus').innerHTML = '';
  document.getElementById('clientStatsModal').style.display = 'flex';

  const res = await apiFetch('get_client_stats', { user_id: userId });
  document.getElementById('statsLoading').style.display = 'none';
  document.getElementById('statsFormFields').style.display = 'block';

  if (res.success) {
    const d = res.data;
    document.getElementById('statsUserName').textContent = 'Client: ' + d.name;
    document.getElementById('statsJoinDate').value = d.join_date;
    document.getElementById('statsTotalSpent').value = parseFloat(d.effective_spent).toFixed(2);
    document.getElementById('statsTotalHires').value = d.effective_hires;
    document.getElementById('statsRealSpent').textContent = '$' + parseFloat(d.real_spent).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('statsRealHires').textContent = d.real_hires;
  } else {
    document.getElementById('statsFormFields').style.display = 'none';
    document.getElementById('statsStatus').innerHTML = '<span style="color:var(--red)">' + res.message + '</span>';
  }
}

async function saveClientStats() {
  const userId = document.getElementById('statsUserId').value;
  const status = document.getElementById('statsStatus');
  status.innerHTML = '<span style="color:var(--text-2)">Saving...</span>';

  const payload = {
    user_id: userId,
    join_date: document.getElementById('statsJoinDate').value,
    total_spent: document.getElementById('statsTotalSpent').value,
    total_hires: document.getElementById('statsTotalHires').value
  };

  const url = new URL(API);
  url.searchParams.append('action', 'update_client_stats');

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).then(r => r.json());

  if (res.success) {
    status.innerHTML = '<span style="color:var(--accent); font-weight:500">' + res.message + '</span>';
    setTimeout(() => { closeModal('clientStatsModal'); loadUsers(); refreshUserProfileIfOpen(); }, 1500);
  } else {
    status.innerHTML = '<span style="color:var(--red)">' + res.message + '</span>';
  }
}

function openFreelancerJoinDateModal(userId, userName, createdAt) {
  document.getElementById('fjUserId').value = userId;
  document.getElementById('fjUserName').textContent = 'Freelancer: ' + (userName || '');
  document.getElementById('fjStatus').innerHTML = '';

  // Pre-fill from created_at if present
  let dateVal = '';
  if (createdAt) {
    const d = new Date(createdAt);
    if (!isNaN(d.getTime())) {
      dateVal = d.toISOString().slice(0, 10);
    }
  }
  document.getElementById('fjJoinDate').value = dateVal;
  document.getElementById('freelancerJoinDateModal').style.display = 'flex';
}

async function saveFreelancerJoinDate() {
  const userId = document.getElementById('fjUserId').value;
  const joinDate = document.getElementById('fjJoinDate').value;
  const status = document.getElementById('fjStatus');
  status.innerHTML = '<span style="color:var(--text-2)">Saving...</span>';

  if (!joinDate) {
    status.innerHTML = '<span style="color:var(--red)">Please select a join date.</span>';
    return;
  }

  const res = await apiPost('update_freelancer_join_date', { user_id: userId, join_date: joinDate });
  if (res.success) {
    status.innerHTML = '<span style="color:var(--accent); font-weight:500">' + (res.message || 'Updated') + '</span>';
    setTimeout(() => { closeModal('freelancerJoinDateModal'); loadUsers(); refreshUserProfileIfOpen(); }, 1200);
  } else {
    status.innerHTML = '<span style="color:var(--red)">' + (res.message || 'Could not update join date') + '</span>';
  }
}

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function disputeStatusBadge(status) {
  const s = (status || '').toLowerCase();
  if (s === 'open') return 'badge-amber';
  if (s === 'resolved' || s === 'closed') return 'badge-green';
  return 'badge-gray';
}

async function loadDisputes() {
  const table = document.getElementById('disputesTable');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading disputes…</div>';
  const data = await apiFetch('get_disputes');
  if (data.success) {
    disputesCache = data.data;
    table.innerHTML = renderDisputesTable(data.data);
    applyPagination('#disputesTable', 'tbody tr', 10);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message)}</div>`;
  }
}

const JOB_REPORT_TYPE_LABELS = {
  suspicious: 'Suspicious activity',
  fraud: 'Fraud / scam attempt',
  spam: 'Spam or duplicate posting',
  inappropriate: 'Inappropriate content',
  misleading: 'Misleading job details',
  scam: 'Payment scam',
  other: 'Other',
};

function jobReportTypeLabel(type) {
  return JOB_REPORT_TYPE_LABELS[(type || '').toLowerCase()] || type || '—';
}

async function loadJobReports() {
  const table = document.getElementById('jobReportsTable');
  if (!table) return;
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading job reports…</div>';
  const data = await apiFetch('get_job_reports');
  if (!data.success) {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Failed to load job reports.')}</div>`;
    return;
  }
  table.innerHTML = renderJobReportsTable(data.data || []);
  applyPagination('#jobReportsTable', 'tbody tr', 10);
}

function renderJobReportsTable(reports) {
  if (!reports.length) return '<div class="loading">No job reports found.</div>';
  return `<table>
    <thead><tr>
      <th>ID</th><th>Job</th><th>Reported User</th><th>Reporter</th><th>Report Type</th><th>Date</th><th>Actions</th>
    </tr></thead>
    <tbody>
      ${reports.map(r => `<tr>
        <td>#${r.id}</td>
        <td>
          <strong>${escapeHtml(r.job_title || 'Job #' + r.job_id)}</strong>
          ${r.job_status ? `<br><span class="badge badge-gray" style="margin-top:4px">${escapeHtml(r.job_status)}</span>` : ''}
        </td>
        <td>
          <strong>${escapeHtml(r.reported_user_name || 'User #' + r.reported_user_id)}</strong>
          ${r.reported_user_email ? `<br><small style="color:var(--text-2)">${escapeHtml(r.reported_user_email)}</small>` : ''}
          ${r.reported_user_role ? `<br><span class="badge badge-blue" style="margin-top:4px">${escapeHtml(r.reported_user_role)}</span>` : ''}
        </td>
        <td>
          <strong>${escapeHtml(r.reporter_name || 'User #' + r.reporter_id)}</strong>
          ${r.reporter_email ? `<br><small style="color:var(--text-2)">${escapeHtml(r.reporter_email)}</small>` : ''}
          ${r.reporter_role ? `<br><span class="badge badge-gray" style="margin-top:4px">${escapeHtml(r.reporter_role)}</span>` : ''}
        </td>
        <td><span class="badge badge-amber">${escapeHtml(jobReportTypeLabel(r.report_type))}</span></td>
        <td style="white-space:nowrap; font-size:12px">${r.created_at ? new Date(r.created_at).toLocaleString() : '—'}</td>
        <td style="white-space:nowrap">
          <div style="display:flex; flex-direction:column; gap:6px; min-width:120px">
            ${r.job_id ? `<button class="btn btn-outline btn-sm" onclick="openJobDetail(${r.job_id})">View Job</button>` : ''}
            ${r.job_id ? `<button class="btn btn-danger btn-sm" onclick="deleteJobFromReport(${r.job_id})">Delete Job</button>` : ''}
            ${r.reported_user_id ? `<button class="btn btn-outline btn-sm" onclick="openUserProfile(${r.reported_user_id})">View Client</button>` : ''}
          </div>
        </td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

async function deleteJobFromReport(jobId) {
  const ok = await remoConfirm(
    'This will permanently delete the job and related proposals/contracts.',
    'Delete this reported job?',
    { danger: true, confirmLabel: 'Delete Job' }
  );
  if (!ok) return;
  const res = await apiFetch('delete_job', { job_id: jobId });
  if (res.success) {
    toast('Deleted', 'Job post removed', 'success');
    loadJobReports();
  } else {
    remoAlert(res.message || 'Could not delete job.', 'Error');
  }
}

function renderDisputesTable(disputes) {
  if (!disputes.length) return '<div class="loading">No disputes found.</div>';
  return `<table>
    <thead><tr>
      <th>ID</th><th>Contract</th><th>Job</th><th>Client</th><th>Freelancer</th><th>Escrow</th><th>Raised By</th><th>Reason</th><th>Status</th><th>Resolution</th><th>Created</th><th>Actions</th>
    </tr></thead>
    <tbody>
      ${disputes.map(d => {
        const isOpen = (d.status || '').toLowerCase() === 'open';
        return `<tr>
        <td>#${d.id}</td>
        <td><strong>#${d.contract_id}</strong></td>
        <td>${escapeHtml(d.job_title || '—')}</td>
        <td>
          <strong>${escapeHtml(d.client_name || '—')}</strong>
          ${d.client_email ? `<br><small style="color:var(--text-2)">${escapeHtml(d.client_email)}</small>` : ''}
        </td>
        <td>
          <strong>${escapeHtml(d.freelancer_name || '—')}</strong>
          ${d.freelancer_email ? `<br><small style="color:var(--text-2)">${escapeHtml(d.freelancer_email)}</small>` : ''}
        </td>
        <td><strong>$${parseFloat(d.escrow_held || 0).toFixed(2)}</strong></td>
        <td>
          <strong>${escapeHtml(d.raised_by_name || 'User #' + d.raised_by)}</strong>
          ${d.raised_by_email ? `<br><small style="color:var(--text-2)">${escapeHtml(d.raised_by_email)}</small>` : ''}
          ${d.raised_by_role ? `<br><span class="badge badge-blue" style="margin-top:4px">${escapeHtml(d.raised_by_role)}</span>` : ''}
        </td>
        <td style="max-width:220px; white-space:normal; line-height:1.45">${escapeHtml(d.reason)}</td>
        <td><span class="badge ${disputeStatusBadge(d.status)}">${escapeHtml(d.status || '—')}</span></td>
        <td style="max-width:180px; white-space:normal; color:var(--text-2); font-size:12px">${d.resolution_notes ? escapeHtml(d.resolution_notes) : '<span style="color:var(--text-3)">—</span>'}</td>
        <td style="white-space:nowrap; font-size:12px">${d.created_at ? new Date(d.created_at).toLocaleString() : '—'}</td>
        <td style="white-space:nowrap">
          ${isOpen ? `
            <div style="display:flex; flex-direction:column; gap:6px; min-width:130px">
              <button class="btn btn-primary btn-sm" onclick="openDisputeResolveModal(${d.id}, 'pay_freelancer')">Pay Freelancer</button>
              <button class="btn btn-outline btn-sm" onclick="openDisputeResolveModal(${d.id}, 'refund_client')" style="color:var(--red); border-color:#fecaca">Refund Client</button>
              ${d.client_id ? `<button class="btn btn-outline btn-sm" onclick="openAdminDirectMessageModal(${d.client_id}, '${escapeJsStr(d.client_name || 'Client')}', '${escapeJsStr(d.client_role || 'client')}', ${d.job_id || 0}, 'Dispute #${d.id}')">Message Client</button>` : ''}
              ${d.freelancer_id ? `<button class="btn btn-outline btn-sm" onclick="openAdminDirectMessageModal(${d.freelancer_id}, '${escapeJsStr(d.freelancer_name || 'Freelancer')}', '${escapeJsStr(d.freelancer_role || 'freelancer')}', ${d.job_id || 0}, 'Dispute #${d.id}')">Message Freelancer</button>` : ''}
            </div>
          ` : '<span style="color:var(--text-3); font-size:12px">—</span>'}
        </td>
      </tr>`;
      }).join('')}
    </tbody>
  </table>`;
}

function openDisputeResolveModal(disputeId, action) {
  const d = disputesCache.find(x => Number(x.id) === Number(disputeId));
  if (!d) return;

  const isPay = action === 'pay_freelancer';
  document.getElementById('disputeResolveId').value = disputeId;
  document.getElementById('disputeResolveAction').value = action;
  document.getElementById('disputeResolveNotes').value = '';
  document.getElementById('disputeResolveStatus').innerHTML = '';
  document.getElementById('disputeResolveTitle').textContent = isPay ? 'Pay Freelancer' : 'Refund Client';
  document.getElementById('disputeResolveReason').textContent = d.reason || '—';

  const escrow = parseFloat(d.escrow_held || 0).toFixed(2);
  document.getElementById('disputeResolveSummary').innerHTML = isPay
    ? `Release <strong>$${escrow}</strong> in escrow to <strong>${escapeHtml(d.freelancer_name || 'freelancer')}</strong> for <strong>${escapeHtml(d.job_title || 'this contract')}</strong> and close this dispute?`
    : `Refund <strong>$${escrow}</strong> to client <strong>${escapeHtml(d.client_name || 'client')}</strong> for <strong>${escapeHtml(d.job_title || 'this contract')}</strong>? The freelancer will be notified by email.`;

  const btn = document.getElementById('disputeResolveConfirmBtn');
  btn.className = isPay ? 'btn btn-primary' : 'btn btn-danger';
  btn.style.flex = '1';
  btn.style.justifyContent = 'center';
  btn.textContent = isPay ? 'Pay Freelancer' : 'Refund Client';
  btn.disabled = false;

  document.getElementById('disputeResolveModal').style.display = 'flex';
}

function openAdminDirectMessageModal(userId, userName, userRole, jobId, contextLabel) {
  const safeUserId = parseInt(userId, 10);
  if (!safeUserId) return;
  const safeJobId = parseInt(jobId || 0, 10) || 0;
  const role = (userRole || 'user').toString().toLowerCase();
  const userLabel = (userName || 'User').toString();
  const context = contextLabel ? ` · ${contextLabel}` : '';

  document.getElementById('adminDirectMessageUserId').value = String(safeUserId);
  document.getElementById('adminDirectMessageJobId').value = String(safeJobId);
  document.getElementById('adminDirectMessageUserName').value = userLabel;
  document.getElementById('adminDirectMessageUserRole').value = role;
  document.getElementById('adminDirectMessageTitle').textContent = `Contact ${role === 'client' ? 'Client' : role === 'freelancer' ? 'Freelancer' : 'User'}`;
  document.getElementById('adminDirectMessageMeta').innerHTML = `<strong>${escapeHtml(userLabel)}</strong> (${escapeHtml(role)}) · User #${safeUserId}${context}`;
  document.getElementById('adminDirectMessageText').value = '';
  document.getElementById('adminDirectMessageStatus').innerHTML = '';
  document.getElementById('adminDirectMessageSendBtn').disabled = false;
  document.getElementById('adminDirectMessageModal').style.display = 'flex';
  setTimeout(() => {
    document.getElementById('adminDirectMessageText')?.focus();
  }, 0);
}

async function goToMessagesAndOpenDirectChat(targetUserId, targetUserName, targetUserRole) {
  if (!targetUserId || !CURRENT_ADMIN_ID) return;

  const messagesNav = document.querySelector('.nav-item[onclick*="messages"]');
  showPage('messages', messagesNav || null);

  await loadConversations(1);

  const adminId = Number(CURRENT_ADMIN_ID);
  const otherId = Number(targetUserId);
  const userAId = Math.min(adminId, otherId);
  const userBId = Math.max(adminId, otherId);
  const otherName = targetUserName || ('User #' + otherId);
  const otherRole = targetUserRole || 'user';

  const userAName = userAId === adminId ? CURRENT_ADMIN_NAME : otherName;
  const userBName = userBId === adminId ? CURRENT_ADMIN_NAME : otherName;
  const userARole = userAId === adminId ? 'admin' : otherRole;
  const userBRole = userBId === adminId ? 'admin' : otherRole;

  const row = document.querySelector(`.conv-row[data-user-a-id="${userAId}"][data-user-b-id="${userBId}"]`);
  await openAdminChat(userAId, userBId, userAName, userBName, userARole, userBRole, row || null);
}

async function submitAdminDirectMessage() {
  const userId = parseInt(document.getElementById('adminDirectMessageUserId')?.value || '0', 10);
  const jobId = parseInt(document.getElementById('adminDirectMessageJobId')?.value || '0', 10);
  const userName = (document.getElementById('adminDirectMessageUserName')?.value || '').trim();
  const userRole = (document.getElementById('adminDirectMessageUserRole')?.value || 'user').trim();
  const message = (document.getElementById('adminDirectMessageText')?.value || '').trim();
  const statusEl = document.getElementById('adminDirectMessageStatus');
  const sendBtn = document.getElementById('adminDirectMessageSendBtn');

  if (!userId) return;
  if (!message) {
    statusEl.innerHTML = '<span style="color:var(--red)">Please write a message before sending.</span>';
    return;
  }

  statusEl.innerHTML = '<span style="color:var(--text-2)">Sending…</span>';
  sendBtn.disabled = true;

  let res = null;
  try {
    res = await apiPost('send_admin_direct_message', {
      receiver_id: userId,
      message: message,
      job_id: jobId > 0 ? jobId : null
    });
  } catch (err) {
    statusEl.innerHTML = '<span style="color:var(--red)">Request failed. Please try again.</span>';
    sendBtn.disabled = false;
    return;
  }

  if (res && res.success) {
    statusEl.innerHTML = '<span style="color:var(--accent); font-weight:500">Message sent.</span>';
    try {
      toast('Message sent', 'Your direct message was delivered.', 'success');
    } catch (_) {
      // Do not fail the message flow if toast helper is unavailable.
    }
    setTimeout(async () => {
      if (typeof closeModal === 'function') {
        closeModal('adminDirectMessageModal');
      }
      try {
        await goToMessagesAndOpenDirectChat(userId, userName, userRole);
      } catch (_) {
        // Keep success state even if chat navigation fails.
      }
    }, 900);
    sendBtn.disabled = false;
    return;
  }

  statusEl.innerHTML = `<span style="color:var(--red)">${escapeHtml((res && res.message) || 'Could not send message')}</span>`;
  sendBtn.disabled = false;
}

async function submitDisputeResolve() {
  const statusEl = document.getElementById('disputeResolveStatus');
  const btn = document.getElementById('disputeResolveConfirmBtn');
  const disputeId = document.getElementById('disputeResolveId').value;
  const action = document.getElementById('disputeResolveAction').value;
  const notes = document.getElementById('disputeResolveNotes').value.trim();

  statusEl.innerHTML = '<span style="color:var(--text-2)">Processing…</span>';
  btn.disabled = true;

  const url = new URL(API);
  url.searchParams.append('action', 'resolve_dispute');

  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ dispute_id: disputeId, resolution: action, notes })
    }).then(r => r.json());

    if (res.success) {
      statusEl.innerHTML = '<span style="color:var(--accent); font-weight:500">' + escapeHtml(res.message) + '</span>';
      setTimeout(() => {
        closeModal('disputeResolveModal');
        loadDisputes();
      }, 1500);
    } else {
      statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Could not resolve dispute') + '</span>';
      btn.disabled = false;
    }
  } catch (err) {
    statusEl.innerHTML = '<span style="color:var(--red)">Request failed. Please try again.</span>';
    btn.disabled = false;
  }
}

function debouncePaymentsSearch() {
  clearTimeout(paymentsSearchTimer);
  paymentsSearchTimer = setTimeout(loadPayments, 400);
}

function paymentStatusBadge(status) {
  const s = (status || '').toLowerCase();
  if (s === 'completed') return 'badge-green';
  if (s === 'pending') return 'badge-amber';
  if (s === 'failed' || s === 'refunded') return 'badge-red';
  if (s === 'disputed') return 'badge-red';
  return 'badge-gray';
}

function formatPaymentMoney(n) {
  return '$' + parseFloat(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderPaymentsSummary(summary) {
  const s = summary || {};
  const count = s.count ?? 0;
  const statusFilter = document.getElementById('paymentsStatusFilter')?.value || '';
  const search = document.getElementById('paymentsSearch')?.value?.trim() || '';
  const parts = [];
  if (statusFilter) parts.push(statusFilter);
  if (search) parts.push('search applied');
  const filterNote = parts.length ? parts.join(' · ') : 'All payments';

  const countEl = document.getElementById('paySummaryCount');
  const countSub = document.getElementById('paySummaryCountSub');
  const amountEl = document.getElementById('paySummaryAmount');
  const completedEl = document.getElementById('paySummaryCompleted');
  const feesEl = document.getElementById('paySummaryFees');
  const pendingEl = document.getElementById('paySummaryPending');
  const disputedEl = document.getElementById('paySummaryDisputed');

  if (countEl) countEl.textContent = count.toLocaleString();
  if (countSub) countSub.textContent = filterNote;
  if (amountEl) amountEl.textContent = formatPaymentMoney(s.total_amount);
  if (completedEl) completedEl.textContent = 'Completed: ' + formatPaymentMoney(s.completed_amount);
  if (feesEl) feesEl.textContent = formatPaymentMoney(s.total_fees);
  if (pendingEl) pendingEl.textContent = formatPaymentMoney(s.pending_amount);
  if (disputedEl) {
    const disputed = s.disputed_count ?? 0;
    disputedEl.textContent = 'Disputed: ' + disputed.toLocaleString();
    disputedEl.className = 'stat-change' + (disputed > 0 ? ' down' : '');
  }
}

async function loadPayments() {
  const table = document.getElementById('paymentsTable');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading payments…</div>';
  const search = document.getElementById('paymentsSearch')?.value?.trim() || '';
  const status = document.getElementById('paymentsStatusFilter')?.value || '';
  const params = { limit: 1000 };
  if (search) params.search = search;
  if (status) params.status = status;
  const data = await apiFetch('get_payments', params);
  if (data.success) {
    renderPaymentsSummary(data.summary);
    table.innerHTML = renderPaymentsTable(data.data);
    applyPagination('#paymentsTable', 'tbody tr', 15);
  } else {
    renderPaymentsSummary({ count: 0, total_amount: 0, total_fees: 0, completed_amount: 0, pending_amount: 0, disputed_count: 0 });
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Failed to load payments')}</div>`;
  }
}

function renderPaymentsTable(payments) {
  if (!payments.length) return '<div class="loading">No payments found.</div>';
  return `<table>
    <thead><tr>
      <th>ID</th>
      <th>Transaction ID</th>
      <th>Payer ID</th>
      <th>Payee ID</th>
      <th>Job ID</th>
      <th>Amount</th>
      <th>Platform Fee</th>
      <th>Currency</th>
      <th>Method</th>
      <th>Status</th>
      <th>Created At</th>
      <th>Description</th>
    </tr></thead>
    <tbody>
      ${payments.map(p => `<tr>
        <td>${p.id}</td>
        <td style="font-family:monospace;font-size:12px;white-space:nowrap">${escapeHtml(p.transaction_id || '—')}</td>
        <td>${p.payer_id ?? '—'}</td>
        <td>${p.payee_id ?? '—'}</td>
        <td>${p.job_id ?? '—'}</td>
        <td style="font-weight:600;white-space:nowrap">$${parseFloat(p.amount || 0).toFixed(2)}</td>
        <td style="white-space:nowrap">$${parseFloat(p.platform_fee || 0).toFixed(2)}</td>
        <td>${escapeHtml(p.currency || 'USD')}</td>
        <td><span class="badge badge-gray">${escapeHtml(p.payment_method || '—')}</span></td>
        <td><span class="badge ${paymentStatusBadge(p.status)}">${escapeHtml(p.status || '—')}</span></td>
        <td style="white-space:nowrap;color:var(--text-2)">${p.created_at ? new Date(p.created_at).toLocaleString() : '—'}</td>
        <td style="font-size:12px;white-space:nowrap;max-width:none">${escapeHtml(p.description || '—')}</td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

async function loadPaymentHolds() {
  const table = document.getElementById('paymentHoldsTable');
  if (!table) return;
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading payment holds…</div>';
  const data = await apiFetch('get_payment_holds');
  if (data.success) {
    table.innerHTML = renderPaymentHoldsTable(data.data);
    applyPagination('#paymentHoldsTable', 'tbody tr', 10);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Failed to load payment holds')}</div>`;
  }
}

function renderPaymentHoldsTable(holds) {
  if (!holds.length) return '<div class="loading">No pending payment holds. Funds appear here after a client pays and earnings are in processing status.</div>';
  return `<table>
    <thead><tr>
      <th>Date</th>
      <th>Freelancer</th>
      <th>Client</th>
      <th>Job</th>
      <th>Gross</th>
      <th>Net (to credit)</th>
      <th>Method</th>
      <th>Transaction</th>
      <th>Description</th>
      <th>Actions</th>
    </tr></thead>
    <tbody>
      ${holds.map(h => `<tr>
        <td style="white-space:nowrap">${h.created_at ? new Date(h.created_at).toLocaleString() : '—'}</td>
        <td><strong>${escapeHtml(h.freelancer_name || '—')}</strong><br><span style="font-size:11px;color:var(--muted)">${escapeHtml(h.freelancer_email || '')}</span></td>
        <td>${escapeHtml(h.client_name || '—')}</td>
        <td>${h.job_id ?? '—'}</td>
        <td style="font-weight:600">$${parseFloat(h.amount || 0).toFixed(2)}</td>
        <td style="font-weight:700;color:var(--accent-hover)">$${parseFloat(h.net_amount || 0).toFixed(2)}</td>
        <td><span class="badge badge-gray">${escapeHtml(h.payment_method || '—')}</span></td>
        <td style="font-family:monospace;font-size:11px">${escapeHtml(h.transaction_id || '—')}</td>
        <td style="font-size:12px;max-width:220px">${escapeHtml(h.description || '—')}</td>
        <td>
          <button class="btn btn-primary btn-sm" onclick="approvePaymentHold(${h.id})">Approve</button>
        </td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

async function approvePaymentHold(id) {
  if (!(await remoConfirm(
    'Credit the net amount to the freelancer available balance? They can withdraw after approval.',
    'Approve payment hold?'
  ))) return;
  const res = await apiFetch('approve_payment_hold', { id });
  if (res.success) {
    setAdminFlash('success', 'Payment approved', res.message || 'Payment hold approved. Funds are now in the freelancer available balance.');
    sessionStorage.setItem('adminRestorePage', 'payment-holds');
    location.reload();
  } else {
    toast('Approval failed', res.message || 'Could not approve this payment hold.', 'error');
  }
}

async function loadWithdrawals() {
  const table = document.getElementById('withdrawalsTable');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading withdrawals…</div>';
  const data = await apiFetch('get_withdrawals');
  if (data.success) {
    table.innerHTML = renderWithdrawalsTable(data.data);
    applyPagination('#withdrawalsTable', 'tbody tr', 10);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${data.message}</div>`;
  }
}

function renderWithdrawalsTable(withdrawals) {
  if (!withdrawals.length) return '<div class="loading">No pending withdrawals.</div>';
  return `<table>
    <thead><tr><th>Date</th><th>Freelancer</th><th>Amount</th><th>Method</th><th>Details</th><th>Actions</th></tr></thead>
    <tbody>
      ${withdrawals.map(w => `<tr>
        <td>${new Date(w.created_at).toLocaleDateString()}</td>
        <td><strong>${w.user_name}</strong><br><span style="font-size:11px;color:var(--muted)">${w.user_email}</span></td>
        <td><strong>$${parseFloat(w.amount).toFixed(2)}</strong></td>
        <td><span class="badge badge-gray">${w.payment_method}</span></td>
        <td><div style="font-size:12px;color:var(--muted);max-width:300px;line-height:1.4">${w.description.replace('Withdrawal to ', '')}</div></td>
        <td style="display:flex; gap:6px;">
          <button class="btn btn-primary btn-sm" onclick="approveWithdrawal(${w.id}, '${w.transaction_id}')">Approve</button>
          <button class="btn btn-danger btn-sm" onclick="rejectWithdrawal(${w.id}, '${w.transaction_id}')">Reject</button>
        </td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

async function approveWithdrawal(id, txnId) {
  const ok = await remoConfirm(
    'Ensure you have actually sent the funds manually before approving.',
    'Approve this withdrawal?'
  );
  if (!ok) return;
  const res = await apiFetch('approve_withdrawal', { id: id, transaction_id: txnId });
  if (res.success) {
    loadWithdrawals();
    toast('Approved', res.message || 'Withdrawal approved', 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
}

async function rejectWithdrawal(id, txnId) {
  const reason = await remoPrompt(
    'The amount will be refunded to the freelancer.',
    'Rejection reason',
    '',
    { multiline: true }
  );
  if (reason === null) return;
  const res = await apiFetch('reject_withdrawal', { id: id, transaction_id: txnId, reason: reason });
  if (res.success) {
    loadWithdrawals();
    toast('Rejected', 'Withdrawal rejected', 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
}

async function loadConnects() {
  const table = document.getElementById('connectsTable');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading packages…</div>';
  const data = await apiFetch('get_connects_packages');
  if (data.success) {
    table.innerHTML = renderConnectsTable(data.data);
    applyPagination('#connectsTable', 'tbody tr', 10);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${data.message}</div>`;
  }
}

function renderConnectsTable(packages) {
  if (!packages.length) return '<div class="loading">No connects packages defined.</div>';
  return `<table>
    <thead><tr><th>ID</th><th>Amount</th><th>Price</th><th>Badge</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      ${packages.map(p => {
        // Escaping package data for attributes safely
        const safePkg = JSON.stringify(p).replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        return `<tr>
          <td style="color:var(--muted)">#${p.id}</td>
          <td><strong>${p.amount} Connects</strong></td>
          <td><strong>$${parseFloat(p.price).toFixed(2)}</strong></td>
          <td>${p.badge_text ? `<span class="badge badge-blue">${p.badge_text}</span>` : '<span style="color:var(--muted); font-size:12px;">None</span>'}</td>
          <td><span class="badge ${parseInt(p.is_active) ? 'badge-green' : 'badge-gray'}">${parseInt(p.is_active) ? 'Active' : 'Inactive'}</span></td>
          <td style="display:flex; gap:6px;">
            <button class="btn btn-outline btn-sm" onclick="openConnectsModal(${safePkg})">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteConnectsPackage(${p.id})">Delete</button>
          </td>
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;
}

function openConnectsModal(pkg = null) {
  const modal = document.getElementById('connectsModal');
  const title = document.getElementById('connectsModalTitle');
  const form = document.getElementById('connectsForm');
  
  form.reset();
  
  if (pkg) {
    title.textContent = 'Edit Connects Package';
    document.getElementById('connectsId').value = pkg.id;
    document.getElementById('connectsAmount').value = pkg.amount;
    document.getElementById('connectsPrice').value = pkg.price;
    document.getElementById('connectsBadge').value = pkg.badge_text || '';
    document.getElementById('connectsActive').checked = !!parseInt(pkg.is_active);
  } else {
    title.textContent = 'Add Connects Package';
    document.getElementById('connectsId').value = '';
    document.getElementById('connectsActive').checked = true;
  }
  
  modal.style.display = 'flex';
}

async function saveConnectsPackage(e) {
  e.preventDefault();
  const form = document.getElementById('connectsForm');
  const formData = new FormData(form);
  const params = {
    id: formData.get('id'),
    amount: formData.get('amount'),
    price: formData.get('price'),
    badge_text: formData.get('badge_text'),
    is_active: formData.get('is_active') ? 1 : 0
  };
  
  const res = await apiFetch('save_connects_package', params);
  if (res.success) {
    closeModal('connectsModal');
    loadConnects();
  } else {
    remoAlert(res.message, 'Error');
  }
}

async function deleteConnectsPackage(id) {
  const ok = await remoConfirm('This package will be removed permanently.', 'Delete connects package?', { danger: true, confirmLabel: 'Delete' });
  if (!ok) return;
  const res = await apiFetch('delete_connects_package', { id: id });
  if (res.success) {
    loadConnects();
    toast('Deleted', 'Package removed', 'success');
  } else {
    remoAlert(res.message, 'Error');
  }
}

function debounceBlogSearch() {
  clearTimeout(blogSearchTimer);
  blogSearchTimer = setTimeout(loadBlogs, 350);
}

function blogStatusBadge(status) {
  const map = { published: 'badge-green', draft: 'badge-amber', unpublished: 'badge-gray' };
  const cls = map[status] || 'badge-gray';
  const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
  return `<span class="badge ${cls}">${escapeHtml(label)}</span>`;
}

function blogImageUrl(path) {
  if (!path) return '';
  const p = String(path).trim();
  if (p.startsWith('http://') || p.startsWith('https://')) return p;
  return BASE_URL + p.replace(/^\/+/, '');
}

function updateBlogImagePreview() {
  const path = document.getElementById('blogImage').value.trim();
  const img = document.getElementById('blogImagePreview');
  if (path) {
    img.src = blogImageUrl(path);
    img.classList.add('visible');
  } else {
    img.src = '';
    img.classList.remove('visible');
  }
}

function initBlogRichEditor() {
  const toolbar = document.getElementById('blogRteToolbar');
  const editor = document.getElementById('blogDescriptionEditor');
  if (!toolbar || !editor || toolbar._rteBound) return;
  toolbar._rteBound = true;

  toolbar.querySelectorAll('button[data-cmd]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const cmd = btn.getAttribute('data-cmd');
      const val = btn.getAttribute('data-value') || null;
      editor.focus();
      if (cmd === 'createLink') {
        (async () => {
          const url = await remoPrompt('Enter link URL:', 'Insert link', 'https://');
          if (url) document.execCommand('createLink', false, url);
        })();
        return;
      }
      if (cmd === 'formatBlock' && val) {
        document.execCommand('formatBlock', false, val);
        return;
      }
      document.execCommand(cmd, false, val);
    });
  });
}

function setBlogEditorHtml(html) {
  const editor = document.getElementById('blogDescriptionEditor');
  editor.innerHTML = html || '';
}

function getBlogEditorHtml() {
  return document.getElementById('blogDescriptionEditor').innerHTML.trim();
}

async function loadBlogs() {
  const table = document.getElementById('blogsTable');
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading blogs…</div>';
  const search = (document.getElementById('blogSearch')?.value || '').trim();
  const status = document.getElementById('blogStatusFilter')?.value || '';
  const data = await apiFetch('get_blogs', { search, status });
  if (data.success) {
    table.innerHTML = renderBlogsTable(data.data || []);
    applyPagination('#blogsTable', 'tbody tr', 10);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Failed to load')}</div>`;
  }
}

function renderBlogsTable(blogs) {
  if (!blogs.length) return '<div class="loading">No blog posts yet. Click "+ Add Blog" to create one.</div>';
  return `<table>
    <thead><tr><th>Post</th><th>Category</th><th>Status</th><th>Created</th><th>Updated</th><th>Actions</th></tr></thead>
    <tbody>
      ${blogs.map(b => {
        const thumb = b.image
          ? `<img src="${attrEsc(blogImageUrl(b.image))}" class="blog-thumb" alt="">`
          : `<div class="blog-thumb" style="display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--text-3)">📄</div>`;
        return `<tr>
          <td>
            <div style="display:flex;align-items:center;gap:12px;">
              ${thumb}
              <div>
                <div style="font-weight:500">${escapeHtml(b.name)}</div>
                <div style="font-size:12px;color:var(--text-2)">#${b.id}</div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-gray">${escapeHtml(b.category || '—')}</span></td>
          <td>${blogStatusBadge(b.status)}</td>
          <td style="font-size:13px;color:var(--text-2)">${formatBlogDate(b.created_at)}</td>
          <td style="font-size:13px;color:var(--text-2)">${formatBlogDate(b.updated_at)}</td>
          <td style="display:flex;gap:6px;flex-wrap:wrap;">
            <button class="btn btn-outline btn-sm" onclick="viewBlog(${b.id})">View</button>
            <button class="btn btn-outline btn-sm" onclick="editBlog(${b.id})">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteBlog(${b.id})">Delete</button>
          </td>
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;
}

function formatBlogDate(d) {
  if (!d) return '—';
  const dt = new Date(String(d).replace(' ', 'T'));
  if (isNaN(dt.getTime())) return d;
  return dt.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

async function openBlogModal(blog = null) {
  initBlogRichEditor();
  const modal = document.getElementById('blogModal');
  const title = document.getElementById('blogModalTitle');
  const form = document.getElementById('blogForm');
  document.getElementById('blogFormStatus').innerHTML = '';
  form.reset();
  setBlogEditorHtml('');

  if (blog) {
    title.textContent = 'Edit Blog Post';
    document.getElementById('blogId').value = blog.id;
    document.getElementById('blogName').value = blog.name || '';
    const catSel = document.getElementById('blogCategory');
    if (blog.category && ![...catSel.options].some(o => o.value === blog.category)) {
      const opt = document.createElement('option');
      opt.value = blog.category;
      opt.textContent = blog.category;
      catSel.appendChild(opt);
    }
    catSel.value = blog.category || '';
    document.getElementById('blogImage').value = blog.image || '';
    document.getElementById('blogStatus').value = blog.status || 'draft';
    setBlogEditorHtml(blog.description || '');
  } else {
    title.textContent = 'Add Blog Post';
    document.getElementById('blogId').value = '';
    document.getElementById('blogStatus').value = 'draft';
  }

  updateBlogImagePreview();
  document.getElementById('blogImageFile').value = '';
  modal.style.display = 'flex';
}

async function editBlog(id) {
  const res = await apiFetch('get_blog', { id });
  if (res.success) {
    closeModal('blogViewModal');
    openBlogModal(res.data);
  } else {
    remoAlert(res.message || 'Could not load blog', 'Error');
  }
}

async function viewBlog(id) {
  blogViewId = id;
  const res = await apiFetch('get_blog', { id });
  if (!res.success) {
    remoAlert(res.message || 'Could not load blog', 'Error');
    return;
  }
  const b = res.data;
  document.getElementById('blogViewTitle').textContent = b.name || 'Blog Post';
  document.getElementById('blogViewMeta').innerHTML = `
    <span>${blogStatusBadge(b.status)}</span>
    ${b.category ? `<span class="badge badge-gray">${escapeHtml(b.category)}</span>` : ''}
    <span>Created: ${formatBlogDate(b.created_at)}</span>
    <span>Updated: ${formatBlogDate(b.updated_at)}</span>
  `;
  const viewImg = document.getElementById('blogViewImage');
  if (b.image) {
    viewImg.src = blogImageUrl(b.image);
    viewImg.style.display = 'block';
  } else {
    viewImg.style.display = 'none';
    viewImg.src = '';
  }
  document.getElementById('blogViewBody').innerHTML = b.description || '<p style="color:var(--text-2)">No content.</p>';
  document.getElementById('blogViewEditBtn').onclick = () => editBlog(id);
  document.getElementById('blogViewDeleteBtn').onclick = () => {
    closeModal('blogViewModal');
    deleteBlog(id);
  };
  document.getElementById('blogViewModal').style.display = 'flex';
}

async function saveBlog(e) {
  e.preventDefault();
  const statusEl = document.getElementById('blogFormStatus');
  const name = document.getElementById('blogName').value.trim();
  const category = document.getElementById('blogCategory').value.trim();
  const description = getBlogEditorHtml();

  if (!name) {
    statusEl.innerHTML = '<span style="color:var(--red)">Title is required.</span>';
    return;
  }
  if (!category) {
    statusEl.innerHTML = '<span style="color:var(--red)">Category is required.</span>';
    return;
  }
  if (!description || description === '<br>') {
    statusEl.innerHTML = '<span style="color:var(--red)">Description is required.</span>';
    return;
  }

  statusEl.innerHTML = '<span style="color:var(--text-2)">Saving…</span>';

  const payload = {
    id: document.getElementById('blogId').value || null,
    name,
    category,
    image: document.getElementById('blogImage').value.trim() || null,
    status: document.getElementById('blogStatus').value,
    description,
  };

  const url = new URL(API);
  url.searchParams.append('action', 'save_blog');

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  }).then(r => r.json());

  if (res.success) {
    statusEl.innerHTML = '<span style="color:var(--accent); font-weight:500">' + escapeHtml(res.message) + '</span>';
    setTimeout(() => {
      closeModal('blogModal');
      loadBlogs();
    }, 800);
  } else {
    statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Save failed') + '</span>';
  }
}

async function deleteBlog(id) {
  const ok = await remoConfirm('This blog post will be deleted permanently.', 'Delete blog post?', { danger: true, confirmLabel: 'Delete' });
  if (!ok) return;
  const res = await apiFetch('delete_blog', { id });
  if (res.success) {
    loadBlogs();
    toast('Deleted', 'Blog post removed', 'success');
  } else {
    remoAlert(res.message || 'Delete failed', 'Error');
  }
}

function debounceCmsPageSearch() {
  clearTimeout(cmsPageSearchTimer);
  cmsPageSearchTimer = setTimeout(loadCmsPages, 350);
}

function cmsPageStatusBadge(status) {
  const cls = status === 'published' ? 'badge-green' : 'badge-amber';
  const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Draft';
  return `<span class="badge ${cls}">${escapeHtml(label)}</span>`;
}

async function syncCmsBuiltinPages() {
  const data = await apiFetch('sync_cms_builtin_pages');
  if (data.success) {
    toast('Pages', data.message || 'Import complete', 'success');
    loadCmsPages();
  } else {
    remoAlert(data.message || 'Import failed', 'Error');
  }
}

async function loadCmsPages() {
  const table = document.getElementById('cmsPagesTable');
  if (!table) return;
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading pages…</div>';
  const search = (document.getElementById('cmsPageSearch')?.value || '').trim();
  const status = document.getElementById('cmsPageStatusFilter')?.value || '';
  const footer_section = document.getElementById('cmsPageSectionFilter')?.value || '';
  const data = await apiFetch('get_cms_pages', { search, status, footer_section });
  if (data.success) {
    if (data.sync?.inserted > 0) {
      toast('Pages', `Imported ${data.sync.inserted} default page(s) — you can edit them below.`, 'success');
    }
    table.innerHTML = renderCmsPagesTable(data.data || []);
    applyPagination('#cmsPagesTable', 'tbody tr', 12);
  } else {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Failed to load')}</div>`;
  }
}

function renderCmsPagesTable(pages) {
  if (!pages.length) return '<div class="loading">No pages yet. Click "+ Add Page" to create footer links and static content.</div>';
  return `<table>
    <thead><tr><th>Page</th><th>Section</th><th>Type</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
    <tbody>
      ${pages.map(p => `<tr>
        <td>
          <div style="font-weight:500">${escapeHtml(p.name)}</div>
          <div style="font-size:12px;color:var(--text-2)">/${escapeHtml(p.slug || '')}</div>
        </td>
        <td><span class="badge badge-gray">${escapeHtml(p.footer_section || '—')}</span></td>
        <td style="font-size:12px">${escapeHtml(p.link_type || 'content')}</td>
        <td>${cmsPageStatusBadge(p.status)}</td>
        <td style="font-size:13px;color:var(--text-2)">${escapeHtml(String(p.sort_order ?? 0))}</td>
        <td style="display:flex;gap:6px;flex-wrap:wrap;">
          <a class="btn btn-outline btn-sm" href="${attrEsc((window.BASE_URL || '') + 'page/' + (p.slug || ''))}" target="_blank" rel="noopener">View</a>
          <button class="btn btn-outline btn-sm" onclick="editCmsPage(${p.id})">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteCmsPage(${p.id})">Delete</button>
        </td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

function toggleCmsPageLinkFields() {
  const type = document.getElementById('cmsPageLinkType')?.value || 'content';
  const wrap = document.getElementById('cmsPageLinkTargetWrap');
  const contentWrap = document.getElementById('cmsPageContentWrap');
  const label = document.getElementById('cmsPageLinkTargetLabel');
  if (wrap) {
    wrap.style.display = type === 'modal' || type === 'external' ? 'block' : 'none';
    if (label) label.textContent = type === 'modal' ? 'Modal key (e.g. help-center)' : 'External URL';
  }
  if (contentWrap) contentWrap.style.display = type === 'content' ? 'block' : 'none';
}

function initCmsPageRichEditor() {
  const toolbar = document.getElementById('cmsPageRteToolbar');
  const editor = document.getElementById('cmsPageDescriptionEditor');
  if (!toolbar || !editor || toolbar._rteBound) return;
  toolbar._rteBound = true;
  toolbar.querySelectorAll('button[data-cmd]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const cmd = btn.getAttribute('data-cmd');
      const val = btn.getAttribute('data-value') || null;
      editor.focus();
      if (cmd === 'createLink') {
        (async () => {
          const url = await remoPrompt('Enter link URL:', 'Insert link', 'https://');
          if (url) document.execCommand('createLink', false, url);
        })();
        return;
      }
      if (cmd === 'formatBlock' && val) {
        document.execCommand('formatBlock', false, val);
        return;
      }
      document.execCommand(cmd, false, val);
    });
  });
}

function setCmsPageEditorHtml(html) {
  const editor = document.getElementById('cmsPageDescriptionEditor');
  if (editor) editor.innerHTML = html || '';
}

function getCmsPageEditorHtml() {
  return document.getElementById('cmsPageDescriptionEditor')?.innerHTML.trim() || '';
}

async function openCmsPageModal(page = null) {
  initCmsPageRichEditor();
  const modal = document.getElementById('cmsPageModal');
  document.getElementById('cmsPageFormStatus').innerHTML = '';
  document.getElementById('cmsPageForm').reset();
  setCmsPageEditorHtml('');
  document.getElementById('cmsPageId').value = '';
  document.getElementById('cmsPageShowInFooter').checked = true;
  document.getElementById('cmsPageSortOrder').value = '0';

  if (page) {
    document.getElementById('cmsPageModalTitle').textContent = 'Edit Page';
    document.getElementById('cmsPageId').value = page.id;
    document.getElementById('cmsPageName').value = page.name || '';
    document.getElementById('cmsPageSlug').value = page.slug || '';
    document.getElementById('cmsPageSection').value = page.footer_section || '';
    document.getElementById('cmsPageLinkType').value = page.link_type || 'content';
    document.getElementById('cmsPageLinkTarget').value = page.link_target || '';
    document.getElementById('cmsPageSortOrder').value = page.sort_order ?? 0;
    document.getElementById('cmsPageShowInFooter').checked = !!Number(page.show_in_footer);
    document.getElementById('cmsPageStatus').value = page.status || 'draft';
    document.getElementById('cmsPageSeoTitle').value = page.seo_title || '';
    document.getElementById('cmsPageSeoDescription').value = page.seo_description || '';
    document.getElementById('cmsPageSeoKeywords').value = page.seo_keywords || '';
    setCmsPageEditorHtml(page.description || '');
  } else {
    document.getElementById('cmsPageModalTitle').textContent = 'Add Page';
    document.getElementById('cmsPageStatus').value = 'draft';
  }
  toggleCmsPageLinkFields();
  modal.style.display = 'flex';
}

async function editCmsPage(id) {
  const res = await apiFetch('get_cms_page', { id });
  if (res.success) openCmsPageModal(res.data);
  else remoAlert(res.message || 'Could not load page', 'Error');
}

async function saveCmsPage(e) {
  e.preventDefault();
  const statusEl = document.getElementById('cmsPageFormStatus');
  const linkType = document.getElementById('cmsPageLinkType').value;
  const description = getCmsPageEditorHtml();
  const name = document.getElementById('cmsPageName').value.trim();
  if (!name) {
    statusEl.innerHTML = '<span style="color:var(--red)">Name is required.</span>';
    return;
  }
  if (linkType === 'content' && (!description || description === '<br>')) {
    statusEl.innerHTML = '<span style="color:var(--red)">Page content is required.</span>';
    return;
  }
  statusEl.innerHTML = '<span style="color:var(--text-2)">Saving…</span>';
  const payload = {
    id: document.getElementById('cmsPageId').value || null,
    name,
    slug: document.getElementById('cmsPageSlug').value.trim(),
    description,
    footer_section: document.getElementById('cmsPageSection').value,
    link_type: linkType,
    link_target: document.getElementById('cmsPageLinkTarget').value.trim(),
    sort_order: document.getElementById('cmsPageSortOrder').value,
    show_in_footer: document.getElementById('cmsPageShowInFooter').checked ? 1 : 0,
    status: document.getElementById('cmsPageStatus').value,
    seo_title: document.getElementById('cmsPageSeoTitle').value.trim(),
    seo_description: document.getElementById('cmsPageSeoDescription').value.trim(),
    seo_keywords: document.getElementById('cmsPageSeoKeywords').value.trim(),
  };
  const url = new URL(API);
  url.searchParams.append('action', 'save_cms_page');
  const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }).then(r => r.json());
  if (res.success) {
    statusEl.innerHTML = '<span style="color:var(--accent);font-weight:500">' + escapeHtml(res.message) + (res.public_url ? ' · <a href="' + attrEsc(res.public_url) + '" target="_blank" rel="noopener">View page</a>' : '') + '</span>';
    setTimeout(() => { closeModal('cmsPageModal'); loadCmsPages(); }, 800);
  } else {
    statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Save failed') + '</span>';
  }
}

async function deleteCmsPage(id) {
  const ok = await remoConfirm('This page will be deleted permanently.', 'Delete page?', { danger: true, confirmLabel: 'Delete' });
  if (!ok) return;
  const res = await apiFetch('delete_cms_page', { id });
  if (res.success) {
    loadCmsPages();
    toast('Deleted', 'Page removed', 'success');
  } else {
    remoAlert(res.message || 'Delete failed', 'Error');
  }
}

function debounceCountrySearch() {
  clearTimeout(countrySearchTimer);
  countrySearchTimer = setTimeout(loadCountries, 300);
}

async function loadCountries() {
  const table = document.getElementById('countriesTable');
  if (!table) return;
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading countries…</div>';

  const search = (document.getElementById('countrySearch')?.value || '').trim();
  const status = document.getElementById('countryStatusFilter')?.value || '';
  const data = await apiFetch('get_countries', { search, status });

  if (!data.success) {
    table.innerHTML = `<div class="loading" style="color:var(--red);">${escapeHtml(data.message || 'Failed to load')}</div>`;
    return;
  }

  const stats = data.stats || {};
  const elEnabled = document.getElementById('countriesStatEnabled');
  const elDisabled = document.getElementById('countriesStatDisabled');
  const elTotal = document.getElementById('countriesStatTotal');
  if (elEnabled) elEnabled.textContent = stats.enabled ?? '0';
  if (elDisabled) elDisabled.textContent = stats.disabled ?? '0';
  if (elTotal) elTotal.textContent = stats.total ?? '0';

  table.innerHTML = renderCountriesTable(data.data || []);
  applyPagination('#countriesTable', 'tbody tr', 25);
}

function renderCountriesTable(countries) {
  if (!countries.length) {
    return '<div class="loading">No countries match your search.</div>';
  }
  return `<table>
    <thead><tr><th>Country</th><th>Code</th><th>Phone</th><th>Status</th><th style="text-align:right;">Visible in dropdowns</th></tr></thead>
    <tbody>
      ${countries.map(c => {
        const enabled = !!c.is_enabled;
        return `<tr data-country-id="${c.id}">
          <td style="font-weight:500">${escapeHtml(c.name)}</td>
          <td><span class="badge badge-gray">${escapeHtml(c.country_code)}</span></td>
          <td style="font-family:'DM Mono',monospace;font-size:13px;color:var(--text-2)">${escapeHtml(c.phone_code || '—')}</td>
          <td>${enabled ? '<span class="badge badge-green">Enabled</span>' : '<span class="badge badge-red">Disabled</span>'}</td>
          <td style="text-align:right;">
            <label class="country-toggle" title="${enabled ? 'Disable country' : 'Enable country'}">
              <input type="checkbox" ${enabled ? 'checked' : ''} onchange="toggleCountryStatus(${c.id}, this.checked, this)" />
              <span class="country-toggle-slider"></span>
            </label>
          </td>
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;
}

async function toggleCountryStatus(id, isEnabled, inputEl) {
  if (inputEl) inputEl.disabled = true;
  const res = await apiPost('update_country_status', { id, is_enabled: isEnabled ? 1 : 0 });
  if (inputEl) inputEl.disabled = false;

  if (!res.success) {
    if (inputEl) inputEl.checked = !isEnabled;
    toast('Error', res.message || 'Could not update country', 'error');
    return;
  }

  toast('Saved', res.message || 'Country updated', 'success');
  await loadCountries();
}

async function loadSeoSettings() {
  const res = await apiFetch('get_seo_settings');
  if (!res.success) return;
  const d = res.data || {};
  seoSettingKeys().forEach(key => {
    const el = document.getElementById(key);
    if (el) el.value = d[key] ?? '';
  });
}

function seoSettingKeys() {
  return ['seo_site_title', 'seo_site_description', 'seo_site_keywords', 'seo_home_title', 'seo_home_description', 'seo_home_keywords', 'seo_og_image'];
}

async function saveSeoSettings(e) {
  e.preventDefault();
  const statusEl = document.getElementById('seoFormStatus');
  statusEl.innerHTML = '<span style="color:var(--text-2)">Saving…</span>';
  const payload = {};
  seoSettingKeys().forEach(key => {
    const el = document.getElementById(key);
    if (el) payload[key] = el.value.trim();
  });
  const url = new URL(API);
  url.searchParams.append('action', 'save_seo_settings');
  const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }).then(r => r.json());
  if (res.success) {
    statusEl.innerHTML = '<span style="color:var(--accent);font-weight:500">' + escapeHtml(res.message) + '</span>';
  } else {
    statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Save failed') + '</span>';
  }
}

async function uploadBlogImage(e) {
  const file = e.target.files && e.target.files[0];
  if (!file) return;

  const statusEl = document.getElementById('blogFormStatus');
  statusEl.innerHTML = '<span style="color:var(--text-2)">Uploading image…</span>';

  const formData = new FormData();
  formData.append('image', file);

  const url = new URL(API);
  url.searchParams.append('action', 'upload_blog_image');

  try {
    const res = await fetch(url, { method: 'POST', body: formData }).then(r => r.json());
    if (res.success) {
      document.getElementById('blogImage').value = res.path || '';
      updateBlogImagePreview();
      statusEl.innerHTML = '<span style="color:var(--accent)">Image uploaded.</span>';
      setTimeout(() => { statusEl.innerHTML = ''; }, 2000);
    } else {
      statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Upload failed') + '</span>';
    }
  } catch (err) {
    statusEl.innerHTML = '<span style="color:var(--red)">Upload failed.</span>';
  }
  e.target.value = '';
}

function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str == null ? '' : String(str);
  return d.innerHTML;
}

function attrEsc(str) {
  return String(str == null ? '' : str)
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;');
}

function formatChatTime(iso) {
  if (!iso) return '';
  const d = new Date(iso.replace(' ', 'T'));
  if (isNaN(d.getTime())) return iso;
  return d.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function roleBadge(role) {
  const cls = role === 'freelancer' ? 'badge-blue' : role === 'client' ? 'badge-green' : 'badge-gray';
  return `<span class="badge ${cls}">${escapeHtml(role || 'user')}</span>`;
}

function debounceConversationsSearch() {
  clearTimeout(conversationsSearchTimer);
  conversationsSearchTimer = setTimeout(() => {
    conversationsPage = 1;
    loadConversations();
  }, 350);
}

async function loadConversations(page) {
  if (page) conversationsPage = page;
  const search = (document.getElementById('conversationsSearch')?.value || '').trim();
  const data = await apiFetch('get_message_conversations', {
    page: conversationsPage,
    limit: 20,
    search: search
  });

  const el = document.getElementById('conversationsTable');
  const pagEl = document.getElementById('conversationsPagination');

  if (!data.success) {
    el.innerHTML = `<div class="loading" style="color:var(--red)">${escapeHtml(data.message || 'Failed to load')}</div>`;
    return;
  }

  const convs = data.data || [];
  const pag = data.pagination || {};

  if (!convs.length) {
    el.innerHTML = '<div class="loading">No conversations found.</div>';
    pagEl.style.display = 'none';
    return;
  }

  el.innerHTML = `<table>
    <thead><tr>
      <th>Participants</th>
      <th>Last message</th>
      <th>Messages</th>
      <th>Last activity</th>
    </tr></thead>
    <tbody>
      ${convs.map(c => {
        const isActive = activeConversation &&
          activeConversation.user_a_id == c.user_a_id &&
          activeConversation.user_b_id == c.user_b_id;
        const preview = (c.last_message || '').substring(0, 80);
        return `<tr class="conv-row${isActive ? ' active' : ''}"
          data-user-a-id="${c.user_a_id}" data-user-b-id="${c.user_b_id}"
          data-user-a-name="${attrEsc(c.user_a_name)}" data-user-b-name="${attrEsc(c.user_b_name)}"
          data-user-a-role="${attrEsc(c.user_a_role)}" data-user-b-role="${attrEsc(c.user_b_role)}"
          onclick="openAdminChatFromRow(this)">
          <td>
            <div><strong>${escapeHtml(c.user_a_name)}</strong> ${roleBadge(c.user_a_role)}</div>
            <div style="margin-top:4px"><strong>${escapeHtml(c.user_b_name)}</strong> ${roleBadge(c.user_b_role)}</div>
            <div style="font-size:11px;color:var(--text-3);margin-top:2px">${escapeHtml(c.user_a_email)} · ${escapeHtml(c.user_b_email)}</div>
          </td>
          <td><div class="conv-preview" title="${escapeHtml(c.last_message)}">${escapeHtml(preview)}${preview.length < (c.last_message || '').length ? '…' : ''}</div></td>
          <td><span class="badge badge-gray">${c.msg_count}</span></td>
          <td style="white-space:nowrap;font-size:12px;color:var(--text-2)">${formatChatTime(c.last_time)}</td>
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;

  if (pag.total_pages > 1) {
    pagEl.style.display = 'flex';
    pagEl.innerHTML = `
      <span>Page ${pag.page} of ${pag.total_pages} (${pag.total} conversations)</span>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-outline btn-sm" ${pag.page <= 1 ? 'disabled' : ''} onclick="loadConversations(${pag.page - 1})">Previous</button>
        <button class="btn btn-outline btn-sm" ${pag.page >= pag.total_pages ? 'disabled' : ''} onclick="loadConversations(${pag.page + 1})">Next</button>
      </div>`;
  } else {
    pagEl.style.display = 'none';
  }
}

function closeAdminChat() {
  activeConversation = null;
  chatOldestId = null;
  chatHasMore = false;
  if (window.innerWidth <= 900) {
    document.getElementById('messagesChatPanel').classList.remove('active');
    document.getElementById('messagesListPanel').classList.remove('hidden-on-mobile');
  }
  document.querySelectorAll('.conv-row').forEach(r => r.classList.remove('active'));
  document.getElementById('adminChatMessages').innerHTML =
    '<div style="text-align:center;color:var(--text-3);padding:40px 20px;font-size:13px;">No conversation selected</div>';
  document.getElementById('chatParticipantsHeader').innerHTML =
    '<div class="names">Select a conversation</div><div class="meta">Click a row to view full chat history</div>';
}

function openAdminChatFromRow(row) {
  openAdminChat(
    parseInt(row.dataset.userAId, 10),
    parseInt(row.dataset.userBId, 10),
    row.dataset.userAName,
    row.dataset.userBName,
    row.dataset.userARole,
    row.dataset.userBRole,
    row
  );
}

async function openAdminChat(userAId, userBId, userAName, userBName, userARole, userBRole, rowEl) {
  activeConversation = {
    user_a_id: userAId,
    user_b_id: userBId,
    user_a_name: userAName,
    user_b_name: userBName,
    user_a_role: userARole,
    user_b_role: userBRole
  };
  chatOldestId = null;
  chatHasMore = false;

  if (window.innerWidth <= 900) {
    document.getElementById('messagesChatPanel').classList.add('active');
    document.getElementById('messagesListPanel').classList.add('hidden-on-mobile');
  }
  document.querySelectorAll('.conv-row').forEach(r => r.classList.remove('active'));
  if (rowEl) rowEl.classList.add('active');

  document.getElementById('chatParticipantsHeader').innerHTML = `
    <div class="names">${escapeHtml(userAName)} ${roleBadge(userARole)} ↔ ${escapeHtml(userBName)} ${roleBadge(userBRole)}</div>
    <div class="meta">User #${userAId} ↔ User #${userBId}</div>`;

  const wrap = document.getElementById('adminChatMessages');
  wrap.innerHTML = '<div class="loading" style="padding:24px"><span class="spinner"></span>Loading messages…</div>';

  await loadConversationMessages(true);
  setupChatScrollLoadMore();
}

async function loadConversationMessages(initial) {
  if (!activeConversation) return;
  const wrap = document.getElementById('adminChatMessages');

  const params = {
    user_a_id: activeConversation.user_a_id,
    user_b_id: activeConversation.user_b_id,
    limit: 30
  };
  if (!initial && chatOldestId) {
    params.before_id = chatOldestId;
  }

  const data = await apiFetch('get_conversation_messages', params);
  if (!data.success) {
    wrap.innerHTML = `<div style="padding:20px;color:var(--red);text-align:center">${escapeHtml(data.message)}</div>`;
    return;
  }

  const messages = data.data || [];
  chatHasMore = !!data.has_more;

  if (messages.length) {
    chatOldestId = messages[0].id;
  }

  if (initial) {
    wrap.innerHTML = renderChatLoadMoreBar() + messages.map(m => renderAdminChatBubble(m)).join('');
    requestAnimationFrame(() => {
      wrap.scrollTop = wrap.scrollHeight;
    });
  } else if (messages.length) {
    const prevHeight = wrap.scrollHeight;
    const firstBubble = wrap.querySelector('.chat-bubble');
    const bubblesHtml = messages.map(m => renderAdminChatBubble(m)).join('');
    if (firstBubble) {
      firstBubble.insertAdjacentHTML('beforebegin', bubblesHtml);
    } else {
      const bar = wrap.querySelector('[data-load-bar]');
      if (bar) bar.insertAdjacentHTML('afterend', bubblesHtml);
    }
    wrap.scrollTop = wrap.scrollHeight - prevHeight;
  }

  updateChatLoadMoreBar();
  chatLoadingOlder = false;
}

function renderChatLoadMoreBar() {
  if (!chatHasMore) {
    return '<div class="chat-load-more" data-load-bar><span style="color:var(--text-3)">Beginning of conversation</span></div>';
  }
  return '<div class="chat-load-more" data-load-bar><button type="button" onclick="loadOlderChatMessages()">Load older messages</button></div>';
}

function updateChatLoadMoreBar() {
  const bar = document.querySelector('#adminChatMessages [data-load-bar]');
  if (!bar) return;
  if (!chatHasMore) {
    bar.innerHTML = '<span style="color:var(--text-3)">Beginning of conversation</span>';
  } else if (!bar.querySelector('button')) {
    bar.innerHTML = '<button type="button" onclick="loadOlderChatMessages()">Load older messages</button>';
  }
}

function adminMessageAttachmentUrl(messageId) {
  const base = (typeof BASE_URL === 'string' ? BASE_URL : '/').replace(/\/?$/, '/');
  return base + 'actions/download_message_attachment.php?id=' + messageId;
}

function renderAdminMessageAttachment(m) {
  if (!m.attachment_path && !m.attachment_name) return '';
  const name = escapeHtml(m.attachment_name || 'Attachment');
  const url = adminMessageAttachmentUrl(m.id);
  return `<a class="chat-bubble-attachment" href="${url}" target="_blank" rel="noopener noreferrer">📎 ${name}</a>`;
}

function renderAdminChatBubble(m) {
  const side = Number(m.sender_id) === Number(activeConversation.user_a_id) ? 'sent' : 'received';
  const jobLine = m.job_id && m.job_title
    ? `<div class="chat-bubble-job">Job: ${escapeHtml(m.job_title)}</div>`
    : (m.job_id ? `<div class="chat-bubble-job">Job #${m.job_id}</div>` : '');
  const bodyText = m.message ? escapeHtml(m.message) : '';
  const attachmentLine = renderAdminMessageAttachment(m);
  return `<div class="chat-bubble ${side}" data-msg-id="${m.id}">
    ${jobLine}
    ${bodyText ? `<div>${bodyText}</div>` : ''}
    ${attachmentLine}
    <div class="chat-bubble-meta">${escapeHtml(m.sender_name)} (${escapeHtml(m.sender_role)}) · ${formatChatTime(m.created_at)}${m.is_read == 0 ? ' · unread' : ''}</div>
  </div>`;
}

async function loadOlderChatMessages() {
  if (!activeConversation || chatLoadingOlder || !chatHasMore || !chatOldestId) return;
  chatLoadingOlder = true;
  const btn = document.querySelector('#adminChatMessages [data-load-bar] button');
  if (btn) btn.disabled = true;
  await loadConversationMessages(false);
}

function setupChatScrollLoadMore() {
  const wrap = document.getElementById('adminChatMessages');
  if (wrap._chatScrollBound) return;
  wrap._chatScrollBound = true;
  wrap.addEventListener('scroll', () => {
    if (wrap.scrollTop < 80 && chatHasMore && !chatLoadingOlder) {
      loadOlderChatMessages();
    }
  });
}

function switchMarketingTab(verified, btn) {
  marketingVerifiedFilter = verified;
  document.querySelectorAll('#marketingVerifyTabs .verify-tab').forEach(el => el.classList.remove('active'));
  if (btn) btn.classList.add('active');
  marketingSelectedIds.clear();
  loadMarketingFreelancers();
}

function debounceMarketingSearch() {
  clearTimeout(marketingSearchTimer);
  marketingSearchTimer = setTimeout(loadMarketingFreelancers, 350);
}

function getMarketingFilterParams() {
  const search = (document.getElementById('marketingSearch')?.value || '').trim();
  const status = document.getElementById('marketingStatusFilter')?.value ?? 'active';
  const params = { status };
  if (marketingVerifiedFilter !== '') params.verified = marketingVerifiedFilter;
  if (search) params.search = search;
  return params;
}

async function loadMarketingFreelancers() {
  const table = document.getElementById('marketingTable');
  const countLabel = document.getElementById('marketingCountLabel');
  if (!table) return;
  table.innerHTML = '<div class="loading"><span class="spinner"></span>Loading freelancers…</div>';

  const data = await apiFetch('get_marketing_freelancers', getMarketingFilterParams());
  if (!data.success) {
    table.innerHTML = `<div class="loading" style="color:var(--red)">${escapeHtml(data.message || 'Failed to load')}</div>`;
    if (countLabel) countLabel.textContent = '—';
    return;
  }

  marketingFreelancersCache = data.data || [];
  if (countLabel) {
    countLabel.textContent = `${marketingFreelancersCache.length} freelancer(s) in filter · ${marketingSelectedIds.size} selected`;
  }
  table.innerHTML = renderMarketingTable(marketingFreelancersCache);
  applyPagination('#marketingTable', 'tbody tr', 15);
  syncMarketingSelectAllCheckbox();
}

function renderMarketingTable(users) {
  if (!users.length) {
    return '<div class="loading">No freelancers match this filter.</div>';
  }
  const verifiedLabel = (u) => parseInt(u.is_verified, 10)
    ? '<span class="badge badge-green">Verified</span>'
    : '<span class="badge badge-gray">Unverified</span>';
  const statusBadge = (s) => {
    if (s === 'active') return '<span class="badge badge-green">Active</span>';
    if (s === 'suspended') return '<span class="badge badge-red">Suspended</span>';
    return '<span class="badge badge-gray">' + escapeHtml(s || '—') + '</span>';
  };
  return `<table>
    <thead><tr>
      <th style="width:40px"></th>
      <th>Name</th>
      <th>Email</th>
      <th>Identity</th>
      <th>Status</th>
      <th>Joined</th>
      <th></th>
    </tr></thead>
    <tbody>
      ${users.map(u => {
        const checked = marketingSelectedIds.has(Number(u.id)) ? 'checked' : '';
        const joined = u.created_at ? new Date(u.created_at).toLocaleDateString() : '—';
        return `<tr>
          <td><input type="checkbox" class="marketing-check marketing-row-check" data-user-id="${u.id}" ${checked} onchange="toggleMarketingRow(${u.id}, this.checked)" /></td>
          <td><strong>${escapeHtml(u.name)}</strong></td>
          <td>${escapeHtml(u.email)}</td>
          <td>${verifiedLabel(u)}</td>
          <td>${statusBadge(u.status)}</td>
          <td style="font-size:12px;color:var(--text-2)">${joined}</td>
          <td><button type="button" class="btn btn-outline btn-sm" onclick="openUserProfile(${u.id})">Profile</button></td>
        </tr>`;
      }).join('')}
    </tbody>
  </table>`;
}

function toggleMarketingRow(userId, checked) {
  const id = Number(userId);
  if (checked) marketingSelectedIds.add(id);
  else marketingSelectedIds.delete(id);
  const countLabel = document.getElementById('marketingCountLabel');
  if (countLabel) {
    countLabel.textContent = `${marketingFreelancersCache.length} freelancer(s) in filter · ${marketingSelectedIds.size} selected`;
  }
  syncMarketingSelectAllCheckbox();
}

function toggleMarketingSelectAll(checked) {
  const rows = document.querySelectorAll('#marketingTable .marketing-row-check');
  rows.forEach(cb => {
    const id = Number(cb.dataset.userId);
    cb.checked = checked;
    if (checked) marketingSelectedIds.add(id);
    else marketingSelectedIds.delete(id);
  });
  const countLabel = document.getElementById('marketingCountLabel');
  if (countLabel) {
    countLabel.textContent = `${marketingFreelancersCache.length} freelancer(s) in filter · ${marketingSelectedIds.size} selected`;
  }
}

function syncMarketingSelectAllCheckbox() {
  const master = document.getElementById('marketingSelectAll');
  if (!master) return;
  const rows = document.querySelectorAll('#marketingTable .marketing-row-check');
  if (!rows.length) {
    master.checked = false;
    master.indeterminate = false;
    return;
  }
  const checkedCount = [...rows].filter(cb => cb.checked).length;
  master.checked = checkedCount === rows.length;
  master.indeterminate = checkedCount > 0 && checkedCount < rows.length;
}

function clearMarketingSelection() {
  marketingSelectedIds.clear();
  document.querySelectorAll('#marketingTable .marketing-row-check').forEach(cb => { cb.checked = false; });
  const master = document.getElementById('marketingSelectAll');
  if (master) { master.checked = false; master.indeterminate = false; }
  const countLabel = document.getElementById('marketingCountLabel');
  if (countLabel && marketingFreelancersCache.length) {
    countLabel.textContent = `${marketingFreelancersCache.length} freelancer(s) in filter · 0 selected`;
  }
}

function openPromoEmailModal(sendAllInFilter) {
  document.getElementById('promoEmailSendAll').value = sendAllInFilter ? '1' : '0';
  document.getElementById('promoEmailSubject').value = '';
  setPromoEmailEditorHtml('');
  document.getElementById('promoEmailMessage').value = '';
  document.getElementById('promoEmailStatus').innerHTML = '';
  document.getElementById('promoEmailSendBtn').disabled = false;

  const summary = document.getElementById('promoEmailRecipientSummary');
  if (sendAllInFilter) {
    const tabLabel = marketingVerifiedFilter === '1' ? 'verified' : (marketingVerifiedFilter === '0' ? 'unverified' : 'all');
    summary.textContent = `Send to all freelancers in the current filter (${tabLabel}, ${marketingFreelancersCache.length} on screen, max 500 per send).`;
  } else {
    const n = marketingSelectedIds.size;
    if (!n) {
      remoAlert('Select at least one freelancer, or use "Email all in filter".', 'No recipients');
      return;
    }
    summary.textContent = `Send to ${n} selected freelancer(s).`;
  }
  document.getElementById('promoEmailModal').style.display = 'flex';
}

async function rteInsertLink(editor) {
  if (!editor) return;
  let defaultText = '';
  const sel = window.getSelection();
  if (sel && !sel.isCollapsed && sel.rangeCount && editor.contains(sel.anchorNode)) {
    defaultText = sel.toString().trim();
  }
  let link = null;
  if (typeof remoLinkPrompt === 'function') {
    link = await remoLinkPrompt('Insert link', { text: defaultText, url: 'https://' });
  } else if (typeof remoPrompt === 'function') {
    const url = await remoPrompt('Enter link URL:', 'Insert link', 'https://');
    if (!url) return;
    link = { text: defaultText || url, url };
  } else {
    remoAlert('Link dialog failed to load. Hard-refresh the admin page and try again.', 'Insert link');
    return;
  }
  if (!link) return;
  insertRteLink(editor, link.text, link.url);
}

function insertPromoEmailImage(editor, imageUrl) {
  if (!editor || !imageUrl) return;
  editor.focus();
  const html = '<img src="' + attrEsc(imageUrl) + '" alt="" style="max-width:100%;height:auto;">';
  document.execCommand('insertHTML', false, html);
}

async function uploadPromoEmailImage(file, editor) {
  if (!file || !editor) return;
  const statusEl = document.getElementById('promoEmailStatus');
  statusEl.innerHTML = '<span style="color:var(--text-2)">Uploading image…</span>';

  const formData = new FormData();
  formData.append('image', file);

  const url = new URL(API);
  url.searchParams.append('action', 'upload_promo_email_image');

  try {
    const res = await fetch(url, { method: 'POST', body: formData }).then(r => r.json());
    if (res.success && res.url) {
      insertPromoEmailImage(editor, res.url);
      statusEl.innerHTML = '<span style="color:var(--accent)">Image inserted.</span>';
      setTimeout(() => { statusEl.innerHTML = ''; }, 2000);
    } else {
      statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Upload failed') + '</span>';
    }
  } catch (err) {
    statusEl.innerHTML = '<span style="color:var(--red)">Upload failed.</span>';
  }
}

function promoEmailHasContent(html, text) {
  if ((text || '').trim()) return true;
  return /<img\b/i.test(html || '');
}

function initPromoEmailRichEditor() {
  const toolbar = document.getElementById('promoEmailRteToolbar');
  const editor = document.getElementById('promoEmailMessageEditor');
  const fileInput = document.getElementById('promoEmailImageInput');
  if (!toolbar || !editor || toolbar._rteBound) return;
  toolbar._rteBound = true;

  if (fileInput && !fileInput._rteBound) {
    fileInput._rteBound = true;
    fileInput.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      e.target.value = '';
      if (file) void uploadPromoEmailImage(file, editor);
    });
  }

  toolbar.querySelectorAll('button[data-cmd]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const cmd = btn.getAttribute('data-cmd');
      const val = btn.getAttribute('data-value') || null;
      editor.focus();
      if (cmd === 'createLink') {
        void rteInsertLink(editor);
        return;
      }
      if (cmd === 'insertImage') {
        if (fileInput) fileInput.click();
        return;
      }
      if (cmd === 'formatBlock' && val) {
        document.execCommand('formatBlock', false, val);
        return;
      }
      document.execCommand(cmd, false, val);
    });
  });
}

function setPromoEmailEditorHtml(html) {
  const editor = document.getElementById('promoEmailMessageEditor');
  if (editor) editor.innerHTML = html || '';
}

function getPromoEmailEditorHtml() {
  return document.getElementById('promoEmailMessageEditor')?.innerHTML.trim() || '';
}

function getPromoEmailEditorText() {
  const editor = document.getElementById('promoEmailMessageEditor');
  return editor ? editor.textContent.trim() : '';
}

async function submitPromotionalEmail() {
  const sendAll = document.getElementById('promoEmailSendAll').value === '1';
  const subject = document.getElementById('promoEmailSubject').value.trim();
  const messageHtml = getPromoEmailEditorHtml();
  const message = getPromoEmailEditorText();
  document.getElementById('promoEmailMessage').value = message;
  const statusEl = document.getElementById('promoEmailStatus');
  const btn = document.getElementById('promoEmailSendBtn');

  if (!subject) {
    statusEl.innerHTML = '<span style="color:var(--red)">Subject is required.</span>';
    return;
  }
  if (!promoEmailHasContent(messageHtml, message)) {
    statusEl.innerHTML = '<span style="color:var(--red)">Message is required.</span>';
    return;
  }

  let recipientCount = sendAll ? marketingFreelancersCache.length : marketingSelectedIds.size;
  if (!recipientCount) {
    remoAlert('No recipients to email.', 'Cannot send');
    return;
  }

  const ok = await remoConfirm(
    `You are about to send a promotional email to ${sendAll ? 'all freelancers in the current filter (up to 500)' : recipientCount + ' selected freelancer(s)'}. Continue?`,
    'Send marketing email?'
  );
  if (!ok) return;

  const payload = { subject, message, message_html: messageHtml };
  if (sendAll) {
    Object.assign(payload, getMarketingFilterParams());
  } else {
    payload.user_ids = [...marketingSelectedIds];
  }

  statusEl.innerHTML = '<span style="color:var(--text-2)">Sending emails…</span>';
  btn.disabled = true;

  const res = await apiPost('send_promotional_email', payload);

  if (res.success) {
    statusEl.innerHTML = '<span style="color:var(--accent); font-weight:500">' + escapeHtml(res.message) + '</span>';
    toast('Emails sent', res.message, 'success');
    setTimeout(() => closeModal('promoEmailModal'), 2000);
  } else {
    statusEl.innerHTML = '<span style="color:var(--red)">' + escapeHtml(res.message || 'Send failed') + '</span>';
    remoAlert(res.message || 'Send failed', 'Error');
  }
  btn.disabled = false;
}

// Init
initBlogRichEditor();
initPromoEmailRichEditor();
const initUserId = new URLSearchParams(window.location.search).get('user_id');
const initJobId = new URLSearchParams(window.location.search).get('job_id');
const restorePage = sessionStorage.getItem('adminRestorePage');
if (restorePage) {
  sessionStorage.removeItem('adminRestorePage');
  if (!restoreAdminPage(restorePage)) {
    loadDashboard();
  }
} else if (initJobId) {
  openJobDetail(initJobId);
} else if (initUserId) {
  openUserProfile(initUserId);
} else {
  loadDashboard();
}
setTimeout(showAdminFlash, 150);
</script>
</body>
</html>
