<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$db = getDB();

// Fetch real user from DB
$user = Auth::user();
if (!$user) {
    // Default fallback for testing
    $userId = 2; 
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch() ?: ['id'=>2, 'name'=>'Freelancer', 'connects'=>0, 'balance'=>0, 'role'=>'freelancer'];
}

// Initial jobs fetch
$allJobs = [];
try {
    $allJobsStmt = $db->query("SELECT j.*, u.name as client_name FROM jobs j JOIN users u ON j.client_id = u.id WHERE j.status = 'open' ORDER BY j.created_at DESC");
    if ($allJobsStmt) {
        $allJobs = $allJobsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Exception $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("Jobs Fetch Error: " . $e->getMessage());
    }
}

$userSkills = !empty($user['skills']) ? json_decode($user['skills'], true) : [];

// Initialize variables with defaults
$allContracts = [];
$fStats = ['total_earned'=>0, 'active_contracts'=>0, 'pending_proposals'=>0, 'monthly_earnings'=>0];
$transactions = [];
$recentMessages = [];
$submittedProposals = [];
$myServices = [];
$workHistory = [];
$savedJobs = [];

// Fetch data with safety fallbacks
try {
    // All Contracts with earnings (completed and pending)
    $allContractsStmt = $db->prepare("
        SELECT c.*, j.title as job_title, u.name as client_name,
        (SELECT SUM(amount) FROM payments WHERE job_id = c.job_id AND payee_id = c.freelancer_id AND status = 'completed') as total_earned,
        (SELECT SUM(amount) FROM payments WHERE job_id = c.job_id AND payee_id = c.freelancer_id AND status = 'pending') as pending_earned
        FROM contracts c 
        JOIN jobs j ON c.job_id = j.id 
        JOIN users u ON j.client_id = u.id 
        WHERE c.freelancer_id = ?
    ");
    $allContractsStmt->execute([$user['id']]);
    $allContracts = $allContractsStmt->fetchAll() ?: [];
    $activeContracts = array_filter($allContracts, fn($c) => $c['status'] === 'active');

    // Stats
    $totalEarnedStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payee_id = ? AND status = 'completed'");
    $totalEarnedStmt->execute([$user['id']]);
    $fStats['total_earned'] = (float)$totalEarnedStmt->fetchColumn() ?: 0;
    
    $pendingEarnedStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payee_id = ? AND status = 'pending'");
    $pendingEarnedStmt->execute([$user['id']]);
    $fStats['pending_earnings'] = (float)$pendingEarnedStmt->fetchColumn() ?: 0;

    $fStats['active_contracts'] = count($activeContracts);
    
    $pendingStmt = $db->prepare("SELECT COUNT(*) FROM proposals WHERE freelancer_id = ? AND status = 'pending'");
    $pendingStmt->execute([$user['id']]);
    $fStats['pending_proposals'] = (int)$pendingStmt->fetchColumn() ?: 0;
    
    $monthlyStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payee_id = ? AND status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $monthlyStmt->execute([$user['id']]);
    $fStats['monthly_earnings'] = (float)$monthlyStmt->fetchColumn() ?: 0;

    // Transactions (Payments) with virtual type and description
    $transactionsStmt = $db->prepare("
        SELECT p.*, j.title as job_title,
        CASE WHEN p.payee_id = ? THEN 'credit' ELSE 'debit' END as type,
        CONCAT('Payment for ', IFNULL(j.title, 'Service')) as description
        FROM payments p
        LEFT JOIN jobs j ON p.job_id = j.id
        WHERE (p.payer_id = ? OR p.payee_id = ?) 
        ORDER BY p.created_at DESC LIMIT 10
    ");
    $transactionsStmt->execute([$user['id'], $user['id'], $user['id']]);
    $transactions = $transactionsStmt->fetchAll() ?: [];

    // My Project Catalog (Services)
    $servicesStmt = $db->prepare("SELECT * FROM services WHERE freelancer_id = ? ORDER BY created_at DESC");
    $servicesStmt->execute([$user['id']]);
    $myServices = $servicesStmt->fetchAll() ?: [];

    // Recent Messages
    $messagesStmt = $db->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.receiver_id = ? 
        ORDER BY m.created_at DESC LIMIT 10
    ");
    $messagesStmt->execute([$user['id']]);
    $recentMessages = $messagesStmt->fetchAll() ?: [];

    // Submitted Proposals
    $proposalsStmt = $db->prepare("
        SELECT p.*, j.title as job_title 
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        WHERE p.freelancer_id = ? 
        ORDER BY p.created_at DESC
    ");
    $proposalsStmt->execute([$user['id']]);
    $submittedProposals = $proposalsStmt->fetchAll() ?: [];

    // Project Catalog (Services)
    $servicesStmt = $db->prepare("SELECT * FROM services WHERE user_id = ?");
    $servicesStmt->execute([$user['id']]);
    $myServices = $servicesStmt->fetchAll() ?: [];

    // Saved Jobs
    $savedJobsStmt = $db->prepare("
        SELECT j.*, u.name as client_name 
        FROM saved_jobs s 
        JOIN jobs j ON s.job_id = j.id 
        JOIN users u ON j.client_id = u.id 
        WHERE s.user_id = ?
    ");
    $savedJobsStmt->execute([$user['id']]);
    $savedJobs = $savedJobsStmt->fetchAll() ?: [];

} catch (Exception $e) {
    // Fail silently in production, or log error
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("Freelancer Dashboard Error: " . $e->getMessage());
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal" id="modal">
    <div class="mh">
      <h2 id="mh-title">Detail</h2>
      <button class="mclose" onclick="closeModal()">×</button>
    </div>
    <div class="mc" id="mc-body" style="padding:0"></div>
  </div>
</div>

<div class="toast" id="toast">
  <strong id="t-title">Success</strong> <span id="t-msg"></span>
</div>

<div class="mob-sidebar-overlay" id="mob-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="main-sidebar">
  <div class="sb-brand">RemoWorkers</div>
  
  <div class="sb-user" onclick="showPage('profile')">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <div class="sb-av"><?php echo strtoupper(substr($user['name'] ?? 'CH', 0, 2)); ?></div>
      <div style="min-width:0">
        <div class="sb-name"><?php echo htmlspecialchars($user['name'] ?? 'Chirag'); ?></div>
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
    <div id="nav-home" class="sb-item active" onclick="showPage('home')"><span class="sb-ico">🏠</span>Home</div>
    <div id="nav-find-work" class="sb-item" onclick="showPage('find-work')"><span class="sb-ico">🔍</span>Find Work</div>
    <div id="nav-proposals" class="sb-item" onclick="showPage('proposals')"><span class="sb-ico">📝</span>My Proposals</div>
    <div id="nav-contracts" class="sb-item" onclick="showPage('contracts')"><span class="sb-ico">🤝</span>My Contracts</div>
    <div id="nav-messages" class="sb-item" onclick="showPage('messages')"><span class="sb-ico">💬</span>Messages</div>
    
    <div class="sb-section">Earnings</div>
    <div id="nav-earnings" class="sb-item" onclick="showPage('earnings')"><span class="sb-ico">💰</span>Earnings</div>
    <div id="nav-catalog" class="sb-item" onclick="showPage('catalog')"><span class="sb-ico">📦</span>My Services</div>
    
    <div class="sb-section">Settings</div>
    <div id="nav-verification" class="sb-item" onclick="showPage('verification')">
      <span class="sb-ico">🛡️</span>ID Verification
      <?php if (!($user['is_verified'] ?? false)): ?>
        <span class="sb-badge" style="background:#f59e0b">!</span>
      <?php endif; ?>
    </div>
  </nav>

  <div class="sb-footer">
    <a href="<?php echo baseUrl("logout.php"); ?>" class="sb-item" style="color:#ef4444"><span class="sb-ico">🚪</span>Log Out</a>
  </div>
</aside>

<main class="main">
  <header class="top-bar" style="background:white;padding:0 20px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
    <button class="mob-toggle" onclick="toggleSidebar()" style="background:none;border:none;font-size:20px;cursor:pointer">☰</button>
    <div class="tb-title" id="page-title" style="font-weight:700">Dashboard</div>
    <div class="tb-av" style="width:34px;height:34px;border-radius:50%;background:var(--lime);color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;cursor:pointer" onclick="showPage('profile')"><?php echo strtoupper(substr($user['name'] ?? 'CH', 0, 2)); ?></div>
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