<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

ensureFreelancerSchema();
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
    $allJobsStmt = $db->query("
        SELECT j.*, u.name as client_name, u.country as client_country, u.is_verified as client_verified,
        COALESCE((SELECT SUM(amount) FROM payments WHERE payer_id = j.client_id AND status = 'completed'), 0) as client_total_spent,
        COALESCE((SELECT COUNT(*) FROM contracts WHERE client_id = j.client_id), 0) as client_hires,
        COALESCE((SELECT COUNT(*) FROM proposals WHERE job_id = j.id), 0) as proposal_count,
        COALESCE((SELECT AVG(rating) FROM reviews WHERE reviewee_id = j.client_id), 0.0) as client_rating,
        COALESCE((SELECT COUNT(*) FROM contracts WHERE job_id = j.id), 0) as project_hires
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        WHERE j.status IN ('open', 'in_progress')
        ORDER BY j.created_at DESC
    ");
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
$jobInvitations = [];
$myServices = [];
$workHistory = [];
$savedJobs = [];
$totalProposals = 0;
$totalContracts = 0;
$unreadMessages = 0;
$fullLedger = [];
$bonusPayments = [];
$earningsByClient = [];
$weeklyEarnings = [];
$conversations = [];

// Fetch data with safety fallbacks
try {
    // All Contracts with earnings (completed and pending)
    $allContractsStmt = $db->prepare("
        SELECT c.*, j.title as job_title, u.name as client_name,
        (SELECT SUM(amount) FROM payments WHERE job_id = c.job_id AND payee_id = c.freelancer_id AND status = 'completed' AND transaction_id NOT LIKE 'ESC-%') as total_earned,
        (SELECT SUM(amount) FROM payments WHERE job_id = c.job_id AND payee_id = c.freelancer_id AND status = 'pending' AND transaction_id NOT LIKE 'ESC-%') as pending_earned
        FROM contracts c 
        LEFT JOIN jobs j ON c.job_id = j.id 
        LEFT JOIN users u ON j.client_id = u.id 
        WHERE c.freelancer_id = ?
        ORDER BY c.id DESC
    ");
    $allContractsStmt->execute([$user['id']]);
    $allContracts = $allContractsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Fetch milestones for contracts
    if (!empty($allContracts)) {
        $contractIds = array_column($allContracts, 'id');
        $placeholders = implode(',', array_fill(0, count($contractIds), '?'));
        $mStmt = $db->prepare("SELECT contract_id, milestones.* FROM milestones WHERE contract_id IN ($placeholders) ORDER BY id ASC");
        $mStmt->execute($contractIds);
        $allMilestones = $mStmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
        
        foreach ($allContracts as &$c) {
            $c['milestones'] = $allMilestones[$c['id']] ?? [];
        }
        unset($c);
    }

    $activeContracts = array_filter($allContracts, fn($c) => $c['status'] === 'active');


    // Stats
    $totalEarnedStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payee_id = ? AND status = 'completed' AND transaction_id NOT LIKE 'ESC-%'");
    $totalEarnedStmt->execute([$user['id']]);
    $fStats['total_earned'] = (float)$totalEarnedStmt->fetchColumn() ?: 0;
    
    $pendingEarnedStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payee_id = ? AND status = 'pending' AND transaction_id NOT LIKE 'ESC-%'");
    $pendingEarnedStmt->execute([$user['id']]);
    $fStats['pending_earnings'] = (float)$pendingEarnedStmt->fetchColumn() ?: 0;

    $wipStmt = $db->prepare("SELECT SUM(amount) FROM work_logs WHERE freelancer_id = ? AND status = 'pending'");
    $wipStmt->execute([$user['id']]);
    
    // Add funded milestones to WIP earnings
    $fundedMilestonesStmt = $db->prepare("
        SELECT SUM(m.amount) 
        FROM milestones m 
        JOIN contracts c ON m.contract_id = c.id 
        WHERE c.freelancer_id = ? AND m.status = 'funded'
    ");
    $fundedMilestonesStmt->execute([$user['id']]);
    $fundedMilestonesAmount = (float)$fundedMilestonesStmt->fetchColumn() ?: 0;

    $fStats['wip_earnings'] = ((float)$wipStmt->fetchColumn() ?: 0) + $fundedMilestonesAmount;

    $fStats['active_contracts'] = count($activeContracts);
    
    $pendingStmt = $db->prepare("SELECT COUNT(*) FROM proposals WHERE freelancer_id = ? AND status = 'pending'");
    $pendingStmt->execute([$user['id']]);
    $fStats['pending_proposals'] = (int)$pendingStmt->fetchColumn() ?: 0;
    
    $monthlyStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payee_id = ? AND status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND transaction_id NOT LIKE 'ESC-%'");
    $monthlyStmt->execute([$user['id']]);
    $fStats['monthly_earnings'] = (float)$monthlyStmt->fetchColumn() ?: 0;

    $completedStmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'completed'");
    $completedStmt->execute([$user['id']]);
    $fStats['completed_contracts'] = (int)$completedStmt->fetchColumn() ?: 0;

    // Calculate JSS and badge dynamically using global helper
    $dynStats = getFreelancerStats($user['id']);
    $fStats['jss'] = $dynStats['jss'];
    $fStats['badge'] = $dynStats['badge'];
    $fStats['is_top_rated'] = ($dynStats['badge'] === 'top_rated' || $dynStats['badge'] === 'top_rated_plus' || $dynStats['badge'] === 'expert_vetted');

    // Transactions (Payments) with virtual type and description
    $transactionsStmt = $db->prepare("
        SELECT p.*, j.title as job_title,
        CASE WHEN p.payee_id = ? THEN 'credit' ELSE 'debit' END as type,
        COALESCE(NULLIF(p.description, ''), CONCAT('Payment for ', IFNULL(j.title, 'Service'))) as description
        FROM payments p
        LEFT JOIN jobs j ON p.job_id = j.id
        WHERE (p.payer_id = ? OR p.payee_id = ?) AND p.transaction_id NOT LIKE 'ESC-%'
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

    // Saved Withdrawal Methods
    $wmStmt = $db->prepare("SELECT * FROM user_withdrawal_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $wmStmt->execute([$user['id']]);
    $withdrawalMethods = $wmStmt->fetchAll() ?: [];

    // --- REPORT DATA ---
    // Full Transaction Ledger
    $ledgerStmt = $db->prepare("
        SELECT p.*, j.title as job_title, u.name as client_name,
        'payment' as ledger_type,
        COALESCE(NULLIF(p.description, ''), CONCAT('Payment for ', IFNULL(j.title, 'Service'))) as description,
        CASE WHEN p.job_id IS NULL THEN 'bonus' ELSE 'hourly' END as p_type
        FROM payments p
        LEFT JOIN jobs j ON p.job_id = j.id
        LEFT JOIN users u ON p.payer_id = u.id
        WHERE (p.payee_id = ? OR p.payer_id = ?) AND p.transaction_id NOT LIKE 'ESC-%'
        ORDER BY p.created_at DESC
    ");
    $ledgerStmt->execute([$user['id'], $user['id']]);
    $fullLedger = $ledgerStmt->fetchAll() ?: [];

    // Earnings by Client
    $clientEarnStmt = $db->prepare("
        SELECT u.name as client_name, SUM(p.amount) as total
        FROM payments p
        JOIN users u ON p.payer_id = u.id
        WHERE p.payee_id = ? AND p.status = 'completed' AND p.transaction_id NOT LIKE 'ESC-%'
        GROUP BY p.payer_id
        ORDER BY total DESC
    ");
    $clientEarnStmt->execute([$user['id']]);
    $earningsByClient = $clientEarnStmt->fetchAll() ?: [];

    // Bonus Payments
    $bonusPayments = array_filter($fullLedger, fn($l) => $l['p_type'] === 'bonus' && $l['status'] === 'completed');
    
    // Weekly Earnings Simulation (last 7 days)
    $weeklyEarnings = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dailyTotal = 0;
        foreach($fullLedger as $l) {
            if (date('Y-m-d', strtotime($l['created_at'])) === $date && $l['status'] === 'completed') {
                $dailyTotal += $l['amount'];
            }
        }
        $weeklyEarnings[] = ['date' => $date, 'label' => date('D', strtotime($date)), 'total' => $dailyTotal];
    }

    // --- COUNTS FOR SIDEBAR BADGES ---
    $countProposals = $db->prepare("SELECT COUNT(*) FROM proposals WHERE freelancer_id = ?");
    $countProposals->execute([$user['id']]);
    $totalProposals = $countProposals->fetchColumn();

    $countContracts = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'active'");
    $countContracts->execute([$user['id']]);
    $totalContracts = $countContracts->fetchColumn();

    $countMessages = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $countMessages->execute([$user['id']]);
    $unreadMessages = $countMessages->fetchColumn();

    // Conversations for Messages Page
    $conversationsStmt = $db->prepare("
        SELECT 
            u.id as other_id, u.name as other_name, u.avatar_url as other_avatar,
            m1.message as last_message, m1.created_at as last_time, m1.is_read, m1.sender_id
        FROM users u
        JOIN (
            SELECT 
                MAX(id) as max_id, 
                CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id
            FROM messages 
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY other_user_id
        ) m2 ON u.id = m2.other_user_id
        JOIN messages m1 ON m1.id = m2.max_id
        ORDER BY m1.created_at DESC
    ");
    $conversationsStmt->execute([$user['id'], $user['id'], $user['id']]);
    $conversations = $conversationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Submitted Proposals
    $proposalsStmt = $db->prepare("
        SELECT p.*, j.title as job_title, j.status as job_status 
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        WHERE p.freelancer_id = ? 
        ORDER BY p.created_at DESC
    ");
    $proposalsStmt->execute([$user['id']]);
    $submittedProposals = $proposalsStmt->fetchAll() ?: [];

    // Job Invitations
    $invitationsStmt = $db->prepare("
        SELECT i.*, j.title as job_title, j.budget, j.budget_type, u.name as client_name 
        FROM job_invitations i
        JOIN jobs j ON i.job_id = j.id
        JOIN users u ON i.client_id = u.id
        WHERE i.freelancer_id = ? AND i.status = 'pending'
        ORDER BY i.created_at DESC
    ");
    $invitationsStmt->execute([$user['id']]);
    $jobInvitations = $invitationsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Project Catalog (Services)
    $servicesStmt = $db->prepare("SELECT * FROM services WHERE freelancer_id = ?");
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
  <a class="sb-logo" href="<?php echo baseUrl(); ?>" style="display:flex;align-items:center;gap:8px;padding:16px 20px 14px;border-bottom:1px solid rgba(255,255,255,.08);text-decoration:none"><img src="<?php echo baseUrl('favicon.png'); ?>" style="width:24px;height:24px;object-fit:contain;border-radius:50%"><div class="sb-logo-wordmark" style="display:flex;flex-direction:column;gap:0px;line-height:1"><span class="sb-logo-text" style="font-size:17px;font-weight:800;color:#fff;letter-spacing:-.4px;line-height:1">Remo<em style="color:#c8f135;font-style:normal">Workers</em></span><span class="sb-logo-tagline" style="font-size:9px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-top:2px">Freelancer Portal</span></div></a>
  
  <div class="sb-user" onclick="showPage('profile')">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <div class="sb-av">
        <?php if (!empty($user['avatar_url'])): ?>
          <img src="<?php echo baseUrl($user['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
        <?php else: ?>
          <?php echo strtoupper(substr($user['name'] ?? 'CH', 0, 2)); ?>
        <?php endif; ?>
      </div>
      <div style="min-width:0">
        <div class="sb-name"><?php echo htmlspecialchars($user['name'] ?? 'Chirag'); ?></div>
        <div class="sb-role">
          <?php if (!empty($fStats['badge'])): ?>
            <span style="color:#c8f135;font-weight:700;font-size:10px">✦ <?php echo htmlspecialchars($dynStats['badge_label']); ?></span>
          <?php else: ?>
            Freelancer
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="sb-stats">
      <div class="sb-stat"><div class="sb-stat-val"><?php echo $fStats['jss']; ?></div><div class="sb-stat-lbl">Job Success</div></div>
      <div class="sb-stat" onclick="showPage('connects')" style="cursor:pointer">
        <div class="sb-stat-val" id="sb-connects-val"><?php echo $user['connects'] ?? 0; ?></div>
        <div class="sb-stat-lbl">Connects</div>
      </div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-section">Dashboard</div>
    <div id="nav-home" class="sb-item active" onclick="showPage('home')"><span class="sb-ico">🏠</span>Home</div>
    <div id="nav-find-work" class="sb-item" onclick="showPage('find-work')"><span class="sb-ico">🔍</span>Find Work</div>
    <div id="nav-proposals" class="sb-item" onclick="showPage('proposals')">
      <span class="sb-ico">📝</span>My Proposals
      <?php if ($totalProposals > 0): ?><span class="sb-badge green"><?php echo $totalProposals; ?></span><?php endif; ?>
    </div>
    <div id="nav-contracts" class="sb-item" onclick="showPage('contracts')">
      <span class="sb-ico">🤝</span>My Contracts
      <?php if ($totalContracts > 0): ?><span class="sb-badge"><?php echo $totalContracts; ?></span><?php endif; ?>
    </div>
    <div id="nav-messages" class="sb-item" onclick="showPage('messages')">
      <span class="sb-ico">💬</span>Messages
      <?php if ($unreadMessages > 0): ?><span class="sb-badge"><?php echo $unreadMessages; ?></span><?php endif; ?>
    </div>
    
    <div class="sb-section">Earnings</div>
    <div id="nav-earnings" class="sb-item" onclick="showPage('earnings')"><span class="sb-ico">💰</span>Earnings & Payments</div>
    <div id="nav-reports" class="sb-item" onclick="showPage('reports')"><span class="sb-ico">📊</span>Payment Reports</div>
    <div id="nav-connects" class="sb-item" onclick="showPage('connects')"><span class="sb-ico">🔗</span>Connects (<?php echo $user['connects'] ?? 0; ?>)</div>
    <div id="nav-catalog" class="sb-item" onclick="showPage('catalog')"><span class="sb-ico">📦</span>My Services</div>
    
    <div class="sb-section">Settings</div>
    <div id="nav-profile" class="sb-item" onclick="showPage('profile')"><span class="sb-ico">👤</span>My Profile</div>
    <div id="nav-password" class="sb-item" onclick="openModal('change-password')"><span class="sb-ico">🔑</span>Change Password</div>
    <div id="nav-verification" class="sb-item" onclick="showPage('verification')">
      <span class="sb-ico">🛡️</span>ID Verification
      <?php if (!($user['is_verified'] ?? false)): ?>
        <span class="sb-badge" style="background:#f59e0b">!</span>
      <?php endif; ?>
    </div>
  </nav>

  <div class="sb-footer">
    <?php
      $avail = $user['availability'] ?? 'available';
      $availLabel = 'Available for Work';
      $availDot = '🟢';
      if ($avail === 'limited') { $availLabel = 'Limited Availability'; $availDot = '🟡'; }
      if ($avail === 'unavailable') { $availLabel = 'Not Available'; $availDot = '🔴'; }
    ?>
    <a onclick="toggleAvailability()" id="avail-status" style="cursor:pointer;display:flex;align-items:center;gap:8px;font-size:12.5px;padding:6px 0;color:rgba(255,255,255,.7);text-decoration:none">
      <span id="avail-dot" style="font-size:14px"><?php echo $availDot; ?></span> <span id="avail-text"><?php echo $availLabel; ?></span>
    </a>
    <a onclick="openModal('change-password')" style="cursor:pointer;display:flex;align-items:center;gap:8px;font-size:12.5px;padding:6px 0;color:rgba(255,255,255,.6);text-decoration:none">
      <span style="font-size:14px">🔑</span> Change Password
    </a>
    <a href="<?php echo baseUrl("logout.php"); ?>" style="display:flex;align-items:center;gap:8px;font-size:12.5px;padding:6px 0;color:#f87171;text-decoration:none">
      <span style="font-size:14px">🚪</span> Log Out
    </a>
  </div>
</aside>

<main class="main">
  <header class="top-bar" style="background:white;padding:0 20px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
    <button class="mob-toggle" onclick="toggleSidebar()" style="background:none;border:none;font-size:20px;cursor:pointer">☰</button>
    <div class="tb-title" id="page-title" style="font-weight:700">Dashboard</div>
    <div class="tb-av" style="width:34px;height:34px;border-radius:50%;background:var(--lime);color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;cursor:pointer;overflow:hidden" onclick="showPage('profile')">
      <?php if (!empty($user['avatar_url'])): ?>
        <img src="<?php echo baseUrl($user['avatar_url']); ?>" style="width:100%;height:100%;object-fit:cover">
      <?php else: ?>
        <?php echo strtoupper(substr($user['name'] ?? 'CH', 0, 2)); ?>
      <?php endif; ?>
    </div>
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
    include __DIR__ . '/views/connects.php';
    ?>
  </div>
</main>

<nav class="mob-nav">
  <div class="mob-nav-inner">
    <button class="mob-nav-item active" onclick="showPage('home')">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      <span>Home</span>
    </button>
    <button class="mob-nav-item" onclick="showPage('find-work')">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      <span>Find Work</span>
    </button>
    <button class="mob-nav-item" onclick="showPage('proposals')">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
      <span>Proposals</span>
    </button>
    <button class="mob-nav-item" onclick="showPage('messages')">
      <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
      <span>Messages</span>
      <?php if($unreadMessages > 0): ?><div class="mob-nav-badge"><?php echo $unreadMessages; ?></div><?php endif; ?>
    </button>
    <button class="mob-nav-item" onclick="showPage('profile')">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      <span>Profile</span>
    </button>
  </div>
</nav>

<?php include __DIR__ . '/includes/footer.php'; ?>