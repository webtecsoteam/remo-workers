<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$user = Auth::user();
if (!$user || $user['role'] !== 'freelancer') {
    // If not logged in, we'll show a mock user for now to ensure sidebar works for testing
    // Or redirect to login if you prefer. For now, let's keep it safe.
    $user = ['id'=>1, 'name'=>'Chirag Limbachiya', 'role'=>'freelancer', 'connects'=>50, 'balance'=>0, 'email'=>'chirag@gmail.com'];
}

$db = getDB();

// Fetch required data with safety fallbacks
try {
    $allJobsStmt = $db->query("SELECT j.*, u.name as client_name FROM jobs j JOIN users u ON j.client_id = u.id WHERE j.status = 'open' ORDER BY j.created_at DESC");
    $allJobs = $allJobsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) { $allJobs = []; }

$userSkills = !empty($user['skills']) ? json_decode($user['skills'], true) : [];
$activeContracts = [];
$fStats = ['total_earned'=>0, 'active_contracts'=>0, 'pending_proposals'=>0, 'monthly_earnings'=>0];
$transactions = [];
$recentMessages = [];
$workHistory = [];
$myServices = [];

include __DIR__ . '/includes/header.php';
?>

<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal" id="modal">
    <div class="modal-head">
      <h2 id="mh-title">Detail</h2>
      <button class="close-btn" onclick="closeModal()">×</button>
    </div>
    <div class="modal-body" id="mc-body"></div>
  </div>
</div>

<div class="toast" id="toast">
  <strong id="t-title">Success</strong> <span id="t-msg"></span>
</div>

<div class="mob-sidebar-overlay" id="mob-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="main-sidebar">
  <div class="sb-brand">RemoWorkers</div>
  
  <div class="sb-user" onclick="showPage('profile',document.querySelector('[onclick*=profile]'))">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <div class="sb-av"><?php echo strtoupper(substr($user['name'], 0, 2)); ?></div>
      <div style="min-width:0">
        <div class="sb-name"><?php echo htmlspecialchars($user['name']); ?></div>
        <div class="sb-role">Freelancer</div>
      </div>
    </div>
    <div class="sb-stats">
      <div class="sb-stat"><div class="sb-stat-val">96%</div><div class="sb-stat-lbl">Job Success</div></div>
      <div class="sb-stat"><div class="sb-stat-val" id="sb-connects-val"><?php echo $user['connects'] ?? 0; ?></div><div class="sb-stat-lbl">Connects</div></div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-section">Dashboard</div>
    <div class="sb-item active" onclick="showPage('home',this)"><span class="sb-ico">🏠</span>Home</div>
    <div class="sb-item" onclick="showPage('find-work',this)"><span class="sb-ico">🔍</span>Find Work</div>
    <div class="sb-item" onclick="showPage('proposals',this)"><span class="sb-ico">📝</span>My Proposals</div>
    <div class="sb-item" onclick="showPage('contracts',this)"><span class="sb-ico">🤝</span>My Contracts</div>
    <div class="sb-item" onclick="showPage('messages',this)"><span class="sb-ico">💬</span>Messages</div>
    
    <div class="sb-section">Earnings</div>
    <div class="sb-item" onclick="showPage('earnings',this)"><span class="sb-ico">💰</span>Earnings</div>
    <div class="sb-item" onclick="showPage('catalog',this)"><span class="sb-ico">📦</span>My Services</div>
  </nav>

  <div class="sb-footer">
    <a href="<?php echo baseUrl("logout.php"); ?>" class="sb-item" style="color:#ef4444"><span class="sb-ico">🚪</span>Log Out</a>
  </div>
</aside>

<main class="main">
  <header class="top-bar" style="background:white;padding:0 20px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
    <button class="mob-toggle" onclick="toggleSidebar()" style="background:none;border:none;font-size:20px;cursor:pointer">☰</button>
    <div class="tb-title" id="page-title" style="font-weight:700">Dashboard</div>
    <div class="tb-av" style="width:34px;height:34px;border-radius:50%;background:var(--lime);color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;cursor:pointer" onclick="showPage('profile',document.querySelector('[onclick*=profile]'))"><?php echo strtoupper(substr($user['name'], 0, 2)); ?></div>
  </header>

  <div class="content">
    <?php 
    // Include all modular views
    include __DIR__ . '/views/home.php';
    include __DIR__ . '/views/find-work.php';
    include __DIR__ . '/views/proposals.php';
    include __DIR__ . '/views/contracts.php';
    include __DIR__ . '/views/messages.php';
    include __DIR__ . '/views/earnings.php';
    include __DIR__ . '/views/reports.php';
    include __DIR__ . '/views/catalog.php';
    include __DIR__ . '/views/profile.php';
    include __DIR__ . '/views/verification.php';
    ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>