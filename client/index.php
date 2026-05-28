<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
$user = Auth::user();
if(!$user) { redirect(baseUrl()); }

$db = getDB();
ensureAgencySchema();
$vDoc = $db->prepare("SELECT status FROM user_documents WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$vDoc->execute([$user['id']]);
$vStatus = $vDoc->fetchColumn() ?: 'unverified';
if(isset($user['is_verified']) && $user['is_verified']) $vStatus = 'approved';

// --- DASHBOARD DATA ---
// 1. Stats
$activeContractsCount = $db->prepare("SELECT COUNT(*) FROM contracts WHERE client_id = ? AND status = 'active'");
$activeContractsCount->execute([$user['id']]);
$stats['active_contracts'] = $activeContractsCount->fetchColumn();

$openProposalsCount = $db->prepare("SELECT COUNT(*) FROM proposals p JOIN jobs j ON p.job_id = j.id WHERE j.client_id = ? AND p.status = 'pending'");
$openProposalsCount->execute([$user['id']]);
$stats['open_proposals'] = $openProposalsCount->fetchColumn();

$totalSpent = $db->prepare("SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payee_id != ? AND status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$totalSpent->execute([$user['id'], $user['id']]);
$stats['total_spent'] = $totalSpent->fetchColumn() ?: 0;

// 2. Active Contracts List
$contractsStmt = $db->prepare("SELECT c.*, j.title as job_title, u.name as freelancer_name, u.avatar_url as freelancer_avatar FROM contracts c 
                                JOIN jobs j ON c.job_id = j.id 
                                JOIN users u ON c.freelancer_id = u.id 
                                WHERE c.client_id = ? AND c.status = 'active' ORDER BY c.created_at DESC LIMIT 4");
$contractsStmt->execute([$user['id']]);
$activeContracts = $contractsStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Open Jobs List
$jobsStmt = $db->prepare("SELECT j.*, (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) as proposal_count 
                          FROM jobs j WHERE j.client_id = ? AND j.status = 'open' ORDER BY j.created_at DESC LIMIT 3");
$jobsStmt->execute([$user['id']]);
$openJobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);

$allOpenJobsStmt = $db->prepare("SELECT id, title, budget_type, budget FROM jobs WHERE client_id = ? AND status = 'open' ORDER BY created_at DESC");
$allOpenJobsStmt->execute([$user['id']]);
$allOpenJobs = $allOpenJobsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// 4. Conversations for Messages Page
require_once __DIR__ . '/../includes/client_blocks.php';
$blockedFreelancersStmt = $db->prepare("
    SELECT u.id, u.name, u.avatar_url, b.created_at as blocked_at
    FROM client_blocked_freelancers b
    JOIN users u ON u.id = b.freelancer_id
    WHERE b.client_id = ?
    ORDER BY b.created_at DESC
");
$blockedFreelancersStmt->execute([$user['id']]);
$blockedFreelancers = $blockedFreelancersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
    WHERE u.id NOT IN (
        SELECT freelancer_id FROM client_blocked_freelancers WHERE client_id = ?
    )
    ORDER BY m1.created_at DESC
");
$conversationsStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $conversationsStmt->fetchAll(PDO::FETCH_ASSOC);
$recentMessages = array_slice($conversations, 0, 5);
// Map other_name to sender_name and match expected keys for consistency in the dashboard view
foreach($recentMessages as &$rm) {
    $rm['sender_name'] = $rm['other_name'];
    $rm['sender_avatar'] = $rm['other_avatar'];
    $rm['created_at'] = $rm['last_time'];
    $rm['message'] = $rm['last_message'];
}
unset($rm);


$unreadCount = $db->prepare("
    SELECT COUNT(*) FROM messages m
    WHERE m.receiver_id = ? AND m.is_read = 0
    AND m.sender_id NOT IN (
        SELECT freelancer_id FROM client_blocked_freelancers WHERE client_id = ?
    )
");
$unreadCount->execute([$user['id'], $user['id']]);
$unreadMessagesCount = $unreadCount->fetchColumn();

// 5. All Jobs for Jobs Page
$allJobsStmt = $db->prepare("SELECT j.*, (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) as proposal_count 
                             FROM jobs j WHERE j.client_id = ? ORDER BY j.created_at DESC");
$allJobsStmt->execute([$user['id']]);
$allJobs = $allJobsStmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for job statuses
$jobCounts = [
    'open' => 0,
    'in_progress' => 0,
    'paused' => 0,
    'closed' => 0,
    'cancelled' => 0
];
foreach ($allJobs as $aj) {
    if (isset($jobCounts[$aj['status']])) {
        $jobCounts[$aj['status']]++;
    }
}

// 6. All Proposals for Proposals Page
$proposalsStmt = $db->prepare("SELECT p.*, j.title as job_title, j.budget_type, u.name as freelancer_name, u.email as freelancer_email, u.avatar_url as freelancer_avatar, u.title as freelancer_title, u.hourly_rate as freelancer_hourly_rate, a.name as agency_name
                                FROM proposals p 
                                JOIN jobs j ON p.job_id = j.id 
                                JOIN users u ON p.freelancer_id = u.id 
                                LEFT JOIN agencies a ON p.agency_id = a.id
                                WHERE j.client_id = ? 
                                ORDER BY p.created_at DESC");
$proposalsStmt->execute([$user['id']]);
$allProposals = $proposalsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch milestones for these proposals
if (!empty($allProposals)) {
    $proposalIds = array_column($allProposals, 'id');
    $placeholders = implode(',', array_fill(0, count($proposalIds), '?'));
    $mStmt = $db->prepare("SELECT proposal_id, milestones.* FROM milestones WHERE proposal_id IN ($placeholders) ORDER BY id ASC");
    $mStmt->execute($proposalIds);
    $allMilestones = $mStmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

    
    foreach ($allProposals as &$p) {
        $p['milestones'] = $allMilestones[$p['id']] ?? [];
        $fStats = getFreelancerStats($p['freelancer_id']);
        $p['freelancer_rating'] = $fStats['rating'];
        $p['freelancer_reviews_count'] = $fStats['reviews_count'];
        $p['freelancer_jss'] = $fStats['jss'];
        $p['freelancer_badge'] = $fStats['badge_label'] ?: '';
    }
    unset($p);
}



$proposalCounts = [
    'pending' => 0,
    'shortlisted' => 0,
    'archived' => 0,
    'accepted' => 0
];
foreach ($allProposals as $ap) {
    if (isset($proposalCounts[$ap['status']])) {
        $proposalCounts[$ap['status']]++;
    }
}

// 7. All Contracts for Contracts Page
$allContractsStmt = $db->prepare("SELECT c.*, j.title as job_title, u.name as freelancer_name, u.avatar_url as freelancer_avatar FROM contracts c 
                                  JOIN jobs j ON c.job_id = j.id 
                                  JOIN users u ON c.freelancer_id = u.id 
                                  WHERE c.client_id = ? ORDER BY c.created_at DESC");
$allContractsStmt->execute([$user['id']]);
$allContracts = $allContractsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch milestones for these contracts
if (!empty($allContracts)) {
    $contractIds = array_column($allContracts, 'id');
    $placeholders = implode(',', array_fill(0, count($contractIds), '?'));
    $mStmt = $db->prepare("SELECT contract_id, milestones.* FROM milestones WHERE contract_id IN ($placeholders) ORDER BY id ASC");
    $mStmt->execute($contractIds);
    $allMilestones = $mStmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

    $latestRefundByMilestone = [];
    $milestoneIds = [];
    foreach ($allMilestones as $rows) {
        foreach ($rows as $row) {
            $milestoneIds[] = (int)$row['id'];
        }
    }
    $milestoneIds = array_values(array_unique(array_filter($milestoneIds)));
    if (!empty($milestoneIds)) {
        try {
            $mPlaceholders = implode(',', array_fill(0, count($milestoneIds), '?'));
            $refundStmt = $db->prepare("
                SELECT rr.*
                FROM milestone_refund_requests rr
                INNER JOIN (
                    SELECT milestone_id, MAX(id) AS latest_id
                    FROM milestone_refund_requests
                    WHERE milestone_id IN ($mPlaceholders)
                    GROUP BY milestone_id
                ) latest ON latest.latest_id = rr.id
            ");
            $refundStmt->execute($milestoneIds);
            foreach (($refundStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $rr) {
                $latestRefundByMilestone[(int)$rr['milestone_id']] = $rr;
            }
        } catch (Throwable $e) {
            // Refund table may not exist yet; skip enrichment gracefully.
        }
    }

    
    foreach ($allContracts as &$c) {
        $c['milestones'] = $allMilestones[$c['id']] ?? [];
        foreach ($c['milestones'] as &$ms) {
            $refund = $latestRefundByMilestone[(int)$ms['id']] ?? null;
            $ms['refund_request_status'] = $refund['status'] ?? null;
            $ms['refund_requested_at'] = $refund['created_at'] ?? null;
            $ms['refund_response_at'] = $refund['responded_at'] ?? null;
            $ms['refund_client_note'] = $refund['client_note'] ?? null;
            $ms['refund_freelancer_note'] = $refund['freelancer_note'] ?? null;
        }
        unset($ms);
    }
    unset($c);
}



$contractCounts = ['active' => 0, 'completed' => 0, 'paused' => 0, 'cancelled' => 0, 'disputed' => 0];
foreach ($allContracts as $ac) {
    if (isset($contractCounts[$ac['status']])) {
        $contractCounts[$ac['status']]++;
    }
}
// 9. Transaction History for Payments Page
$clientTransactionsStmt = $db->prepare("
    SELECT p.*, u.name as freelancer_name, j.title as job_title 
    FROM payments p 
    LEFT JOIN users u ON (p.payee_id = u.id AND p.payer_id = ?) OR (p.payer_id = u.id AND p.payee_id = ?)
    LEFT JOIN jobs j ON p.job_id = j.id
    WHERE (p.payer_id = ? OR p.payee_id = ?) AND (p.payment_method != 'Escrow Release' OR p.payment_method IS NULL)
    ORDER BY p.created_at DESC LIMIT 50
");
$clientTransactionsStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$clientTransactions = $clientTransactionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly spending comparison
$lastMonthSpentStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payee_id != ? AND status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)");
$lastMonthSpentStmt->execute([$user['id'], $user['id']]);
$lastMonthSpent = $lastMonthSpentStmt->fetchColumn() ?: 0;
$spendChange = 0;
if ($lastMonthSpent > 0) {
    $spendChange = (($stats['total_spent'] - $lastMonthSpent) / $lastMonthSpent) * 100;
}

// Escrow calculation (pending payments)
$escrowStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payee_id != ? AND status = 'pending'");
$escrowStmt->execute([$user['id'], $user['id']]);
$escrowAmount = $escrowStmt->fetchColumn() ?: 0;

// Monthly spend for chart (last 7 months including current)
$monthlySpendData = [];
$maxSpend = 1; // Avoid division by zero
for ($i = 6; $i >= 0; $i--) {
    $monthDate = date('Y-m-d', strtotime("-$i months"));
    $monthLabel = date('M', strtotime($monthDate));
    $stmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payee_id != ? AND status = 'completed' AND MONTH(created_at) = MONTH(?) AND YEAR(created_at) = YEAR(?)");
    $stmt->execute([$user['id'], $user['id'], $monthDate, $monthDate]);
    $amt = (float)$stmt->fetchColumn() ?: 0;
    $monthlySpendData[] = ['label' => $monthLabel, 'amount' => $amt];
    if ($amt > $maxSpend) $maxSpend = $amt;
}

// Avg Rating Given Dynamically
$stmt = $db->prepare("SELECT COUNT(*), AVG(rating) FROM reviews WHERE reviewer_id = ?");
$stmt->execute([$user['id']]);
$revRow = $stmt->fetch(PDO::FETCH_NUM);
$stats['review_count'] = (int)$revRow[0];
$stats['avg_rating'] = $revRow[1] !== null ? number_format((float)$revRow[1], 1) : '0.0';

// 11. Pending Work Logs for Review (Excluded for hourly since weekly cron bills them automatically)
$pendingWorkLogs = [];

// Pending Milestones for Review
$pendingMilestonesStmt = $db->prepare("
    SELECT m.*, c.job_id, j.title as job_title, u.name as freelancer_name
    FROM milestones m
    JOIN contracts c ON m.contract_id = c.id
    JOIN jobs j ON c.job_id = j.id
    JOIN users u ON c.freelancer_id = u.id
    WHERE c.client_id = ? AND m.status = 'requested'
    ORDER BY m.updated_at DESC
");
$pendingMilestonesStmt->execute([$user['id']]);
$pendingMilestones = $pendingMilestonesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];


// All Freelancers (find-talent: identity-verified only)
$allFreelancersStmt = $db->prepare("SELECT * FROM users WHERE role = 'freelancer' AND status = 'active' AND is_verified = 1 ORDER BY created_at DESC");
$allFreelancersStmt->execute();
$allTalent = $allFreelancersStmt->fetchAll(PDO::FETCH_ASSOC);

// Saved Talent
$savedTalentStmt = $db->prepare("SELECT u.* FROM users u JOIN saved_talent s ON u.id = s.freelancer_id WHERE s.client_id = ? ORDER BY s.created_at DESC");
$savedTalentStmt->execute([$user['id']]);
$savedTalent = $savedTalentStmt->fetchAll(PDO::FETCH_ASSOC);

// Active Hired Team
$teamTalentStmt = $db->prepare("
    SELECT u.*, MAX(c.created_at) as last_hired_at, 
    (SELECT status FROM contracts WHERE freelancer_id = u.id AND client_id = ? ORDER BY created_at DESC LIMIT 1) as last_contract_status
    FROM users u 
    JOIN contracts c ON u.id = c.freelancer_id 
    WHERE c.client_id = ? AND c.status IN ('active', 'paused')
    GROUP BY u.id 
    ORDER BY last_hired_at DESC
");
$teamTalentStmt->execute([$user['id'], $user['id']]);
$teamTalent = $teamTalentStmt->fetchAll(PDO::FETCH_ASSOC);

// Previously Hired Talent (completed/closed)
$previousTalentStmt = $db->prepare("
    SELECT u.*, MAX(c.created_at) as last_hired_at, 
    (SELECT status FROM contracts WHERE freelancer_id = u.id AND client_id = ? ORDER BY created_at DESC LIMIT 1) as last_contract_status,
    COALESCE((SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payee_id = u.id AND status = 'completed'), 0) as total_paid
    FROM users u 
    JOIN contracts c ON u.id = c.freelancer_id 
    WHERE c.client_id = ? AND c.status NOT IN ('active', 'paused')
      AND u.id NOT IN (
          SELECT freelancer_id FROM contracts WHERE client_id = ? AND status IN ('active', 'paused')
      )
    GROUP BY u.id 
    ORDER BY last_hired_at DESC
");
$previousTalentStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$previousTalent = $previousTalentStmt->fetchAll(PDO::FETCH_ASSOC);

// Invited Talent (freelancers who have pending invitations from this client)
$invitedTalentStmt = $db->prepare("
    SELECT u.*, i.created_at as invited_at, j.title as job_title, i.id as invitation_id
    FROM users u
    JOIN job_invitations i ON u.id = i.freelancer_id
    JOIN jobs j ON i.job_id = j.id
    WHERE i.client_id = ? AND i.status = 'pending'
    ORDER BY i.created_at DESC
");
$invitedTalentStmt->execute([$user['id']]);
$invitedTalent = $invitedTalentStmt->fetchAll(PDO::FETCH_ASSOC);

$talentCounts = [
    'team' => count($teamTalent),
    'invited' => count($invitedTalent),
    'saved' => count($savedTalent),
    'previous' => count($previousTalent)
];

// 11. Reports Data
$reportStatsStmt = $db->prepare("
    SELECT 
        (SELECT COALESCE(SUM(amount + COALESCE(platform_fee, 0)), 0) FROM payments WHERE payer_id = ? AND payee_id != ? AND payment_method != 'Escrow Release') as total_spent_all_time,
        (SELECT COUNT(DISTINCT id) FROM jobs WHERE client_id = ?) as total_jobs_posted,
        (SELECT COUNT(DISTINCT freelancer_id) FROM contracts WHERE client_id = ?) as freelancers_hired,
        (SELECT COUNT(*) FROM contracts WHERE client_id = ? AND status = 'completed') as contracts_completed,
        (SELECT COALESCE(SUM(wl.hours), 0) FROM work_logs wl JOIN contracts c ON wl.contract_id = c.id WHERE c.client_id = ?) as total_hours_tracked,
        (SELECT COUNT(*) FROM contracts WHERE client_id = ? AND status = 'disputed') as disputes_filed
");
$reportStatsStmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
$reportStats = $reportStatsStmt->fetch(PDO::FETCH_ASSOC);

// Apply admin stat offsets for continuous accumulation from adjusted values
$reportStats['total_spent_all_time'] += (float)($user['admin_spent_offset'] ?? 0);
$reportStats['contracts_completed'] += (int)($user['admin_hires_offset'] ?? 0);

// Fetch dynamic Spend by Category
$categorySpendStmt = $db->prepare("
    SELECT j.category, SUM(p.amount + COALESCE(p.platform_fee, 0)) as total_spent
    FROM payments p
    JOIN jobs j ON p.job_id = j.id
    WHERE p.payer_id = ? AND p.payment_method != 'Escrow Release'
    GROUP BY j.category
    ORDER BY total_spent DESC
");
$categorySpendStmt->execute([$user['id']]);
$categorySpendList = $categorySpendStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>RemoWorkers – Client Dashboard</title>
<?php include __DIR__ . '/../includes/google-analytics.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=Neue+Haas+Grotesk+Display+Pro:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo baseUrl("client/css/style.css"); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/css/ui-alerts.css'); ?>">
<script src="<?php echo baseUrl('assets/js/pagination.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/js/ui-alerts.js'); ?>"></script>
<script>const BASE_URL = '<?php echo baseUrl(); ?>';</script>
<script>
window.openModal = function(id) {
  if (window.__openModalImpl) return window.__openModalImpl(id);
  window.__pendingModalId = id;
};
window.closeModal = function() {
  if (window.__closeModalImpl) return window.__closeModalImpl();
  var ov = document.getElementById('overlay');
  if (ov) ov.classList.remove('open');
  document.body.classList.remove('modal-open');
  document.body.style.top = '';
  document.body.style.overflow = '';
};
</script>
</head>
<body>



<!-- MODAL OVERLAY -->
<div class="overlay" id="overlay" role="presentation">
  <div class="overlay-backdrop" id="overlay-backdrop" aria-hidden="true"></div>
  <div class="modal" id="modal-panel" role="dialog" aria-modal="true" aria-labelledby="mh-title">
    <div class="mh"><h2 id="mh-title">Detail</h2><button type="button" class="mclose" aria-label="Close">✕</button></div>
    <div class="mc" id="mc-body"></div>
  </div>
</div>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeMobSidebar()"></div>

<!-- MOBILE FAB -->
<button type="button" class="mob-fab" id="mob-fab" aria-label="Post a job">+</button>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <a class="sb-logo" href="<?php echo baseUrl(); ?>" style="display:flex;align-items:center;gap:8px"><img src="<?php echo baseUrl('favicon.png'); ?>" style="width:24px;height:24px;object-fit:contain;border-radius:50%"><div class="sb-wordmark">Remo<em>Workers</em></div></a>
  <div class="sb-user" onclick="showPage('settings', document.querySelector('.sb-item[onclick*=\'settings\']'))" style="cursor:pointer">
    <div class="sb-av"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
    <div>
      <div class="sb-name"><?php echo htmlspecialchars($user['name']); ?></div>
      <div class="sb-role">Client Account</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Main</div>
    <div class="sb-item" onclick="showPage('home',this)"><span class="ico">🏠</span>Home</div>
    <div class="sb-item" onclick="showPage('jobs',this)"><span class="ico">📋</span>My Jobs</div>
    <div class="sb-item" onclick="showPage('proposals',this)"><span class="ico">📩</span>Proposals</div>
    <div class="sb-item" onclick="showPage('contracts',this)"><span class="ico">🤝</span>Contracts</div>
    <div class="sb-item" onclick="showPage('review',this)"><span class="ico">📝</span>Review Work <?php if((count($pendingWorkLogs) + count($pendingMilestones)) > 0): ?><span class="sb-badge"><?php echo (count($pendingWorkLogs) + count($pendingMilestones)); ?></span><?php endif; ?></div>

    <div class="sb-item" onclick="showPage('talent',this)"><span class="ico">👥</span>Talent</div>
    <div class="sb-section">Tools</div>
    <div class="sb-item" onclick="showPage('messages',this)"><span class="ico">💬</span>Messages<?php if($unreadMessagesCount > 0): ?><span class="sb-badge"><?php echo $unreadMessagesCount; ?></span><?php endif; ?></div>
    <div class="sb-item" onclick="openDashboardLiveChat()"><span class="ico">🟢</span>Chat with us</div>
    <div class="sb-item" onclick="showPage('payments',this)"><span class="ico">💳</span>Payments</div>
    <div class="sb-item" onclick="showPage('reports',this)"><span class="ico">📊</span>Reports</div>
    <div class="sb-item" onclick="showPage('verification',this)"><span class="ico">🪪</span>Identity Verification</div>
    <div class="sb-item" onclick="toast('Uma AI','AI work assistant analyzing your active projects...')"><span class="ico">✨</span>AI Assistant</div>
    <div class="sb-section">Account</div>
    <div class="sb-item" onclick="showPage('settings',this)"><span class="ico">⚙️</span>Settings</div>
    <div class="sb-item" onclick="toast('Help Center','Loading support articles...')"><span class="ico">❓</span>Help & Support</div>
  </nav>
  <div class="sb-footer">
    <a onclick="toast('Upgrade','Opening Business Plus details')">⬆️ Upgrade to Business Plus</a>
    <a href="<?php echo baseUrl('logout'); ?>">🚪 Sign Out</a>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <button class="mob-menu-btn" onclick="openMobSidebar()" aria-label="Open menu">☰</button>
    <div class="tb-title" id="page-title">Home</div>
    <div class="tb-search">
      <span class="tb-s-ico">🔍</span>
      <input type="text" placeholder="Search jobs, freelancers, contracts…">
    </div>
    <div class="tb-actions">
      <div class="tb-ico-btn" onclick="toast('Notifications','You have 4 unread notifications')">🔔<div class="notif-dot"></div></div>
      <div class="tb-ico-btn" onclick="showPage('messages',document.querySelector('[onclick*=messages]'))">💬</div>
      <button class="btn btn-g btn-sm" onclick="openModal('post-job')">+ Post a Job</button>
      <div class="tb-av" onclick="showPage('settings', document.querySelector('.sb-item[onclick*=\'settings\']'))"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- ══ HOME PAGE ══ -->
    <div class="page active" id="page-home">
      <div class="pg-header" style="margin-bottom:18px">
        <div>
          <div class="pg-title">Welcome back, <?php echo htmlspecialchars($user['name']); ?> 👋</div>
          <div class="pg-sub">You have <?php echo $unreadMessagesCount; ?> unread messages and <?php echo $stats['open_proposals']; ?> new proposals waiting.</div>
        </div>
        <button class="btn btn-g btn-lg" onclick="openModal('post-job')">+ Post a Job</button>
      </div>

      <!-- Stat Cards -->
      <div class="stat-row">
        <div class="stat-c" onclick="showPage('contracts',document.querySelector('[onclick*=contracts]'))">
          <div class="stat-label">Active Contracts<div class="stat-icon">🤝</div></div>
          <div class="stat-val"><?php echo $stats['active_contracts']; ?></div>
          <div class="stat-sub">Active work streams</div>
        </div>
        <div class="stat-c" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'))">
          <div class="stat-label">Open Proposals<div class="stat-icon">📩</div></div>
          <div class="stat-val"><?php echo $stats['open_proposals']; ?></div>
          <div class="stat-sub">Waiting for review</div>
        </div>
        <div class="stat-c" onclick="showPage('payments',document.querySelector('[onclick*=payments]'))">
          <div class="stat-label">Total Spent (<?php echo date('M'); ?>)<div class="stat-icon">💳</div></div>
          <div class="stat-val">$<?php echo number_format($stats['total_spent']); ?></div>
          <div class="stat-sub">This month's billing</div>
        </div>
        <div class="stat-c" onclick="toast('Satisfaction','Based on completed contract reviews')">
          <div class="stat-label">Avg Rating Given<div class="stat-icon">⭐</div></div>
          <div class="stat-val"><?php echo $stats['avg_rating']; ?></div>
          <div class="stat-sub">From <?php echo $stats['review_count']; ?> reviews</div>
        </div>
      </div>

      <div class="g2">
        <!-- Active Contracts -->
        <div class="card">
          <div class="card-head">
            <h3>Active Contracts</h3>
            <button class="btn btn-w btn-sm" onclick="showPage('contracts',document.querySelector('[onclick*=contracts]'))">View all</button>
          </div>
          <div class="card-body" style="padding:0 20px">
            <?php if (empty($activeContracts)): ?>
                <div style="padding:20px;text-align:center;color:var(--uw-gray)">No active contracts found.</div>
            <?php else: ?>
                <?php foreach ($activeContracts as $c): ?>
                <div class="contract-row">
                  <div class="av">
                    <?php if (!empty($c['freelancer_avatar'])): ?>
                      <img src="<?php echo baseUrl($c['freelancer_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%"><?php echo strtoupper(substr($c['freelancer_name'], 0, 2)); ?></div>
                    <?php else: ?>
                      <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%"><?php echo strtoupper(substr($c['freelancer_name'], 0, 2)); ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="cr-info">
                    <div class="cr-title"><?php echo htmlspecialchars($c['job_title']); ?></div>
                    <div class="cr-sub"><?php echo htmlspecialchars($c['freelancer_name']); ?> · <?php echo ucfirst($c['contract_type']); ?> · Active</div>
                  </div>
                  <div class="cr-amt">$<?php echo number_format($c['amount']); ?><?php echo $c['contract_type'] === 'hourly' ? '/hr' : ''; ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Messages -->
        <div class="card">
          <div class="card-head">
            <h3>Messages</h3>
            <button class="btn btn-w btn-sm" onclick="showPage('messages',document.querySelector('[onclick*=messages]'))">View all</button>
          </div>
          <div class="card-body" style="padding:6px 12px">
            <?php if (empty($recentMessages)): ?>
                <div style="padding:20px;text-align:center;color:var(--uw-gray)">No messages found.</div>
            <?php else: ?>
                <?php foreach ($recentMessages as $m): ?>
                <div class="msg-item <?php echo $m['is_read'] ? '' : 'unread'; ?>" onclick="showPage('messages',document.querySelector('[onclick*=messages]'))">
                  <div class="av">
                    <?php if (!empty($m['sender_avatar'])): ?>
                      <img src="<?php echo baseUrl($m['sender_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                    <?php else: ?>
                      <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%"><?php echo strtoupper(substr($m['sender_name'], 0, 2)); ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="msg-meta">
                    <div class="msg-name"><?php echo htmlspecialchars($m['sender_name']); ?><span class="msg-time"><?php echo date('M j', strtotime($m['created_at'])); ?></span></div>
                    <div class="msg-text"><?php echo htmlspecialchars($m['message']); ?></div>
                  </div>
                  <?php if (!$m['is_read']): ?><div class="msg-dot"></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Open Jobs -->
      <div class="sec-hd" style="margin-top:8px">
        <div class="sec-h">Open Job Posts</div>
        <button class="sec-link" onclick="showPage('jobs',document.querySelector('[onclick*=jobs]'))">View all jobs →</button>
      </div>

      <?php if (empty($openJobs)): ?>
          <div class="job-card" style="justify-content:center;color:var(--uw-gray)">You have no open job posts at the moment.</div>
      <?php else: ?>
          <?php foreach ($openJobs as $j): ?>
          <div class="job-card" onclick="showPage('jobs',document.querySelector('[onclick*=jobs]'))">
            <div class="job-card-ico">📄</div>
            <div style="flex:1">
              <h4><?php echo htmlspecialchars($j['title']); ?></h4>
              <p><?php echo htmlspecialchars(substr($j['description'], 0, 150)); ?>...</p>
              <div class="job-meta">
                <span class="jm g">$<?php echo number_format($j['budget']); ?><?php echo $j['budget_type'] === 'hourly' ? '/hr' : ''; ?></span>
                <span class="jm"><?php echo $j['budget_type'] === 'fixed' ? 'Fixed-price' : 'Hourly'; ?></span>
                <span class="jm"><?php echo $j['category']; ?></span>
                <span class="jm"><?php echo $j['proposal_count']; ?> proposals</span>
              </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <span class="badge b-green"><?php echo ucfirst($j['status']); ?></span>
              <div style="font-size:11px;color:var(--uw-gray);margin-top:6px">Posted <?php echo date('M j', strtotime($j['created_at'])); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
      <?php endif; ?>

      <!-- Spend Chart -->
      <div class="card" style="margin-top:6px">
        <div class="card-head">
          <h3>Monthly Spend</h3>
          <button class="sec-link" onclick="showPage('reports',document.querySelector('[onclick*=reports]'))">Full report →</button>
        </div>
        <div class="card-body">
          <div style="font-size:24px;font-weight:700;color:var(--uw-black);margin-bottom:6px">
            $<?php echo number_format($stats['total_spent']); ?> <span style="font-size:13px;font-weight:400;color:var(--uw-gray)"><?php echo date('M Y'); ?></span>
          </div>
          <div class="chart-area">
            <div class="chart-bars">
              <?php foreach ($monthlySpendData as $index => $data): 
                $height = ($data['amount'] / $maxSpend) * 100;
                $active = ($index === count($monthlySpendData) - 1) ? 'active' : '';
              ?>
                <div class="chart-bar <?php echo $active; ?>" style="height:<?php echo max(5, $height); ?>%" onclick="toast('<?php echo $data['label']; ?>','$<?php echo number_format($data['amount']); ?> spent')"></div>
              <?php endforeach; ?>
            </div>
            <div class="chart-labels">
              <?php foreach ($monthlySpendData as $data): ?>
                <div class="chart-lbl"><?php echo $data['label']; ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ JOBS PAGE ══ -->
    <div class="page" id="page-jobs">
      <div class="pg-header">
        <div>
          <div class="pg-title">My Job Posts</div>
          <div class="pg-sub"><?php echo count($allJobs); ?> total posts · <?php echo $jobCounts['open']; ?> open · <?php echo $jobCounts['in_progress']; ?> active · <?php echo $jobCounts['paused']; ?> paused · <?php echo $jobCounts['closed']; ?> completed · <?php echo $jobCounts['cancelled']; ?> cancelled</div>
        </div>
        <button class="btn btn-g btn-lg" onclick="openModal('post-job')">+ Post a New Job</button>
      </div>
      <div class="tab-bar">
        <div class="tab" data-tab-status="all" onclick="setTab(this)">All (<?php echo count($allJobs); ?>)</div>
        <div class="tab on" data-tab-status="open" onclick="setTab(this)">Open (<?php echo $jobCounts['open']; ?>)</div>
        <div class="tab" data-tab-status="in_progress" onclick="setTab(this)">Active (<?php echo $jobCounts['in_progress']; ?>)</div>
        <div class="tab" data-tab-status="paused" onclick="setTab(this)">Paused (<?php echo $jobCounts['paused']; ?>)</div>
        <div class="tab" data-tab-status="closed" onclick="setTab(this)">Completed (<?php echo $jobCounts['closed']; ?>)</div>
        <div class="tab" data-tab-status="cancelled" onclick="setTab(this)">Cancelled (<?php echo $jobCounts['cancelled']; ?>)</div>
      </div>
      <div class="desk-only">
        <div class="card" style="margin-bottom:0;overflow-x:auto">
          <table class="tbl" style="min-width: 950px;">
            <thead><tr><th>Job Title</th><th class="hide-mob">Category</th><th class="hide-mob">Subcategory</th><th>Budget</th><th>Type</th><th>Proposals</th><th class="hide-mob">Posted</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              <?php if (empty($allJobs)): ?>
                  <tr><td colspan="9" style="text-align:center;padding:20px;color:var(--uw-gray)">No job posts found.</td></tr>
              <?php else: ?>
                  <?php foreach ($allJobs as $aj): ?>
                  <tr data-status="<?php echo $aj['status']; ?>" <?php echo $aj['status'] !== 'open' ? 'style="display:none"' : ''; ?>>
                    <td class="cl" onclick="toast('Job Details','Viewing <?php echo htmlspecialchars($aj['title']); ?>')" title="<?php echo htmlspecialchars($aj['title']); ?>" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($aj['title']); ?></td>
                    <td class="hide-mob"><span class="badge b-gray"><?php echo htmlspecialchars($aj['category']); ?></span></td>
                    <td class="hide-mob"><span class="badge b-blue" style="font-size:11px"><?php echo htmlspecialchars($aj['subcategory'] ?? 'General'); ?></span></td>
                    <td>$<?php echo number_format($aj['budget']); ?></td>
                    <td><span class="badge b-blue" style="font-size:10px"><?php echo $aj['budget_type'] === 'fixed' ? 'Fixed-price' : 'Hourly'; ?></span></td>
                    <td><strong style="color:var(--uw-green)"><?php echo $aj['proposal_count']; ?></strong></td>
                    <td class="hide-mob"><?php echo date('M j', strtotime($aj['created_at'])); ?></td>
                    <td><span class="badge b-<?php echo ($aj['status'] === 'open' ? 'green' : ($aj['status'] === 'paused' ? 'yellow' : 'gray')); ?>"><?php echo ($aj['status'] === 'in_progress' ? 'Active' : ucfirst($aj['status'])); ?></span></td>

                    <td style="white-space:nowrap">
                      <button class="btn btn-w btn-sm" onclick="viewJobDetails(<?php echo htmlspecialchars(json_encode($aj)); ?>)">Manage</button>
                      <button class="btn btn-w btn-sm" title="Copy public job link" onclick="event.stopPropagation();copyJobShareLink(<?php echo (int)$aj['id']; ?>)">Share</button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile Card View -->
      <div class="mob-only">
        <?php if (empty($allJobs)): ?>
            <div class="card" style="padding:20px;text-align:center;color:var(--uw-gray)">No job posts found.</div>
        <?php else: ?>
            <?php foreach ($allJobs as $aj): ?>
            <div class="job-card" data-status="<?php echo $aj['status']; ?>" <?php echo $aj['status'] !== 'open' ? 'style="display:none"' : ''; ?> onclick="viewJobDetails(<?php echo htmlspecialchars(json_encode($aj)); ?>)">
              <div style="flex:1">
                <h4 style="color:var(--uw-green);margin-bottom:8px"><?php echo htmlspecialchars($aj['title']); ?></h4>

                <div class="job-meta">
                  <span class="jm g">$<?php echo number_format($aj['budget']); ?></span>
                  <span class="jm"><?php echo $aj['budget_type'] === 'fixed' ? 'Fixed-price' : 'Hourly'; ?></span>
                  <span class="jm"><strong style="color:var(--uw-green)"><?php echo $aj['proposal_count']; ?></strong> Proposals</span>
                  <span class="badge b-<?php echo ($aj['status'] === 'open' ? 'green' : ($aj['status'] === 'paused' ? 'yellow' : 'gray')); ?>" style="font-size:10px"><?php echo ($aj['status'] === 'in_progress' ? 'Active' : ucfirst($aj['status'])); ?></span>
                </div>

                <div style="font-size:11.5px;color:var(--uw-gray);margin-top:10px;line-height:1.4">
                  <strong><?php echo htmlspecialchars($aj['category']); ?></strong> 
                  <?php if(!empty($aj['subcategory'])): ?> • <?php echo htmlspecialchars($aj['subcategory']); ?><?php endif; ?>
                  <br>Posted <?php echo date('M j, Y', strtotime($aj['created_at'])); ?>
                </div>
              </div>
              <div style="align-self:center;flex-shrink:0;display:flex;flex-direction:column;gap:6px">
                <button class="btn btn-w btn-sm" onclick="event.stopPropagation();viewJobDetails(<?php echo htmlspecialchars(json_encode($aj)); ?>)">Manage</button>
                <button class="btn btn-w btn-sm" onclick="event.stopPropagation();copyJobShareLink(<?php echo (int)$aj['id']; ?>)">Share link</button>
              </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>

    <!-- ══ PROPOSALS PAGE ══ -->
    <div class="page" id="page-proposals">
      <div class="pg-header">
        <div>
          <div class="pg-title">Proposals</div>
          <div class="pg-sub"><?php echo $proposalCounts['pending']; ?> pending · <?php echo $proposalCounts['shortlisted']; ?> shortlisted · <?php echo $proposalCounts['archived']; ?> archived · <?php echo $proposalCounts['accepted']; ?> hired</div>
        </div>
      </div>
      <div class="tab-bar">
        <div class="tab on" data-tab-status="all" onclick="setTab(this)">All (<?php echo count($allProposals); ?>)</div>
        <div class="tab" data-tab-status="pending" onclick="setTab(this)">Pending (<?php echo $proposalCounts['pending']; ?>)</div>
        <div class="tab" data-tab-status="shortlisted" onclick="setTab(this)">Shortlisted (<?php echo $proposalCounts['shortlisted']; ?>)</div>
        <div class="tab" data-tab-status="archived" onclick="setTab(this)">Archived (<?php echo $proposalCounts['archived']; ?>)</div>
        <div class="tab" data-tab-status="accepted" onclick="setTab(this)">Hired (<?php echo $proposalCounts['accepted']; ?>)</div>
      </div>

      <?php if (empty($allProposals)): ?>
          <div class="card" style="padding:40px;text-align:center;color:var(--uw-gray)">No pending proposals found.</div>
      <?php else: ?>
          <?php foreach ($allProposals as $p): ?>
          <div class="prop-card" data-status="<?php echo $p['status']; ?>" onclick="viewProposalDetails(<?php echo htmlspecialchars(json_encode($p)); ?>)">
            <div class="prop-top">
                <div class="av" style="width:42px;height:42px">
                    <?php if (!empty($p['freelancer_avatar'])): ?>
                      <img src="<?php echo baseUrl($p['freelancer_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-size:13px"><?php echo strtoupper(substr($p['freelancer_name'], 0, 2)); ?></div>
                    <?php else: ?>
                      <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:13px"><?php echo strtoupper(substr($p['freelancer_name'], 0, 2)); ?></div>
                    <?php endif; ?>
                </div>
              <div class="prop-info">
                <div style="display:flex;align-items:center;gap:8px">
                  <h4 style="margin:0"><?php echo htmlspecialchars($p['freelancer_name']); ?></h4>
                  <?php if (!empty($p['agency_name'])): ?>
                    <span class="badge b-blue" style="font-size:9px;padding:2px 6px">Agency: <?php echo htmlspecialchars($p['agency_name']); ?></span>
                  <?php endif; ?>
                  <span class="badge b-<?php echo ($p['status']==='shortlisted'?'blue':($p['status']==='archived'?'gray':'green')); ?>" style="font-size:9px;padding:2px 6px"><?php echo ucfirst($p['status']); ?></span>
                </div>
                <?php 
                  $fInfo = getFreelancerStats($p['freelancer_id']);
                ?>
                <p>
                  <?php echo htmlspecialchars($p['freelancer_title'] ?: 'Freelancer'); ?> 
                  · ★ <?php echo $fInfo['rating']; ?> (<?php echo $fInfo['reviews_count']; ?> reviews)
                  · JSS: <?php echo $fInfo['jss']; ?> 
                  · $<?php echo number_format($p['freelancer_hourly_rate'] ?: 0, 2); ?>/hr
                  <?php if ($fInfo['badge_label']): ?>
                    · <strong style="color:var(--uw-green)"><?php echo $fInfo['badge_label']; ?></strong>
                  <?php endif; ?>
                </p>
              </div>
              <div style="margin-left:auto;text-align:right;flex-shrink:0">
                <div class="prop-rate">$<?php echo number_format($p['bid_amount']); ?></div>
                <div class="prop-job-target" style="font-size:11px;color:var(--uw-gray);margin-top:2px">For: <?php echo htmlspecialchars($p['job_title']); ?></div>
                <?php if (!empty($p['milestones'])): ?>
                  <div style="margin-top:8px">
                    <span class="badge b-purple" style="font-size:9px;padding:3px 8px;border-radius:12px;background:rgba(124, 58, 237, 0.1);color:#7c3aed;border:none">
                      ⛓️ <?php echo count($p['milestones']); ?> Milestones
                    </span>
                  </div>
                <?php endif; ?>
              </div>

            </div>
            <div class="prop-body">"<?php echo htmlspecialchars(substr($p['cover_letter'], 0, 200)); ?>..."</div>
            


            <div class="prop-foot">
              <div style="font-size:11.5px;color:var(--uw-gray)">Submitted <?php echo date('M j', strtotime($p['created_at'])); ?></div>
              <div class="prop-actions">
                <?php if ($p['status'] === 'accepted'): ?>
                  <button class="btn btn-w btn-sm" onclick="event.stopPropagation();showChatWithFreelancer(<?php echo $p['freelancer_id']; ?>, '<?php echo addslashes($p['freelancer_name']); ?>', '<?php echo $p['freelancer_avatar'] ?? ''; ?>')">💬 Message</button>

                  <button class="btn btn-w btn-sm" style="color:#ef4444" onclick="event.stopPropagation();cancelHiring(<?php echo $p['id']; ?>)">Cancel</button>
                <?php else: ?>
                  <button class="btn btn-w btn-sm" onclick="event.stopPropagation();updateProposalStatus(<?php echo $p['id']; ?>, 'archived')"><?php echo $p['status']==='archived'?'Unarchive':'Archive'; ?></button>
                  <button class="btn btn-o btn-sm" onclick="event.stopPropagation();updateProposalStatus(<?php echo $p['id']; ?>, '<?php echo $p['status']==='shortlisted'?'pending':'shortlisted'; ?>')"><?php echo $p['status']==='shortlisted'?'Unshortlist':'Shortlist'; ?></button>
                  <button class="btn btn-w btn-sm" onclick="event.stopPropagation();showChatWithFreelancer(<?php echo $p['freelancer_id']; ?>, '<?php echo addslashes($p['freelancer_name']); ?>', '<?php echo $p['freelancer_avatar'] ?? ''; ?>')">💬 Message</button>
                  <button class="btn btn-g btn-sm" onclick="event.stopPropagation();hireFreelancer(<?php echo $p['id']; ?>, <?php echo (float)$p['bid_amount']; ?>, '<?php echo htmlspecialchars($p['budget_type'] ?? 'fixed', ENT_QUOTES); ?>')">Hire →</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ══ CONTRACTS PAGE ══ -->
    <div class="page" id="page-contracts">
      <div class="pg-header">
        <div>
          <div class="pg-title">Contracts</div>
          <div class="pg-sub"><?php echo $contractCounts['active']; ?> active contracts</div>
        </div>
      </div>
      <div class="tab-bar">
        <div class="tab" data-tab-status="all" onclick="setTab(this)">All (<?php echo count($allContracts); ?>)</div>
        <div class="tab on" data-tab-status="active" onclick="setTab(this)">Active (<?php echo $contractCounts['active']; ?>)</div>
        <div class="tab" data-tab-status="paused" onclick="setTab(this)">Paused (<?php echo $contractCounts['paused']; ?>)</div>
        <div class="tab" data-tab-status="disputed" onclick="setTab(this)">Disputed (<?php echo $contractCounts['disputed']; ?>)</div>
        <div class="tab" data-tab-status="completed" onclick="setTab(this)">Completed (<?php echo $contractCounts['completed']; ?>)</div>
        <div class="tab" data-tab-status="cancelled" onclick="setTab(this)">Cancelled (<?php echo $contractCounts['cancelled']; ?>)</div>
      </div>

      <div class="desk-only">
        <div class="card" style="margin-bottom:0;overflow-x:auto">
          <table class="tbl" style="min-width: 850px;">
            <thead><tr><th>Freelancer</th><th>Job Title</th><th class="hide-mob">Type</th><th>Budget</th><th class="hide-mob">Start Date</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              <?php if (empty($allContracts)): ?>
                  <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--uw-gray)">No contracts found.</td></tr>
              <?php else: ?>
                  <?php foreach ($allContracts as $ac): ?>
                  <tr data-status="<?php echo $ac['status']; ?>" <?php echo $ac['status'] !== 'active' ? 'style="display:none"' : ''; ?>>
                    <td class="cl" style="color:var(--uw-green);font-weight:600;cursor:pointer" onclick="event.stopPropagation();showChatWithFreelancer(<?php echo $ac['freelancer_id']; ?>, '<?php echo addslashes($ac['freelancer_name']); ?>', '<?php echo $ac['freelancer_avatar'] ?? ''; ?>')"><?php echo htmlspecialchars($ac['freelancer_name']); ?></td>
                    <td title="<?php echo htmlspecialchars($ac['job_title']); ?>" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($ac['job_title']); ?></td>
                    <td class="hide-mob"><?php echo ucfirst($ac['contract_type']); ?></td>
                    <td>$<?php echo number_format($ac['amount']); ?></td>
                    <td class="hide-mob"><?php echo date('M j, Y', strtotime($ac['start_date'])); ?></td>
                    <td><span class="badge <?php echo ($ac['status'] === 'active' ? 'b-green' : ($ac['status'] === 'disputed' ? 'b-red' : 'b-gray')); ?>"><?php echo ucfirst($ac['status']); ?></span></td>
                    <td><button class="btn btn-w btn-sm" onclick="event.stopPropagation();manageContract(<?php echo htmlspecialchars(json_encode($ac)); ?>)">Manage</button></td>
                  </tr>
                  <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile Card View -->
      <div class="mob-only">
        <?php if (empty($allContracts)): ?>
            <div class="card" style="padding:20px;text-align:center;color:var(--uw-gray)">No contracts found.</div>
        <?php else: ?>
            <?php foreach ($allContracts as $ac): ?>
            <div class="prop-card" data-status="<?php echo $ac['status']; ?>" <?php echo $ac['status'] !== 'active' ? 'style="display:none"' : ''; ?> onclick="manageContract(<?php echo htmlspecialchars(json_encode($ac)); ?>)">
              <div class="prop-top" style="margin-bottom:12px">
                <div class="av" style="width:40px;height:40px">
                    <?php if (!empty($ac['freelancer_avatar'])): ?>
                      <img src="<?php echo baseUrl($ac['freelancer_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-size:14px"><?php echo strtoupper(substr($ac['freelancer_name'], 0, 2)); ?></div>
                    <?php else: ?>
                      <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:14px"><?php echo strtoupper(substr($ac['freelancer_name'], 0, 2)); ?></div>
                    <?php endif; ?>
                </div>
                <div class="prop-info">
                  <h4 style="margin-bottom:2px"><?php echo htmlspecialchars($ac['freelancer_name']); ?></h4>
                  <div style="font-size:12px;color:var(--uw-gray)"><?php echo ucfirst($ac['contract_type']); ?> Contract</div>
                </div>
                <div style="margin-left:auto;text-align:right">
                  <div class="prop-rate">$<?php echo number_format($ac['amount']); ?></div>
                  <span class="badge <?php echo ($ac['status'] === 'active' ? 'b-green' : ($ac['status'] === 'disputed' ? 'b-red' : 'b-gray')); ?>" style="font-size:9px"><?php echo ucfirst($ac['status']); ?></span>
                </div>
              </div>
              <div style="font-size:13.5px;font-weight:600;margin-bottom:10px;color:var(--uw-dark)"><?php echo htmlspecialchars($ac['job_title']); ?></div>
              <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--uw-border)">
                <div style="font-size:11px;color:var(--uw-gray)">Started <?php echo date('M j, Y', strtotime($ac['start_date'])); ?></div>
                <div style="display:flex;gap:8px">
                  <button class="btn btn-w btn-sm" onclick="event.stopPropagation();showChatWithFreelancer(<?php echo $ac['freelancer_id']; ?>, '<?php echo addslashes($ac['freelancer_name']); ?>', '<?php echo $ac['freelancer_avatar'] ?? ''; ?>')">💬</button>
                  <button class="btn btn-g btn-sm" onclick="event.stopPropagation();manageContract(<?php echo htmlspecialchars(json_encode($ac)); ?>)">Manage</button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>

    <!-- ══ REVIEW WORK PAGE ══ -->
    <div class="page" id="page-review">
      <div class="pg-header">
        <div>
          <div class="pg-title">Review Work</div>
          <div class="pg-sub">Verify submissions and release milestone payments</div>
        </div>
      </div>

      <?php if(empty($pendingWorkLogs) && empty($pendingMilestones)): ?>
        <div style="text-align:center;padding:80px 40px;background:white;border-radius:16px;border:1px solid var(--uw-border);box-shadow:var(--sh)">
          <div style="font-size:56px;margin-bottom:20px">✨</div>
          <div style="font-weight:700;font-size:20px;margin-bottom:8px;color:var(--uw-black)">No pending reviews</div>
          <div style="font-size:14.5px;color:var(--uw-gray);max-width:320px;margin:0 auto">You're all caught up! Freelancer submissions will appear here for your approval.</div>
        </div>
      <?php else: ?>
        <div style="display:grid;gap:20px">
          <!-- Milestones to Review -->
          <?php foreach($pendingMilestones as $pm): ?>
            <div class="card" style="padding:28px;border-radius:16px;box-shadow:var(--sh);border:1px solid var(--uw-border); border-left: 5px solid var(--uw-green)">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:24px">
                <div style="flex:1;min-width:300px">
                  <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                    <div style="font-weight:700;font-size:18px;color:var(--uw-black)"><?php echo htmlspecialchars($pm['description']); ?></div>
                    <span class="badge b-green" style="font-size:10px;letter-spacing:0.02em">MILESTONE</span>
                  </div>
                  <div style="font-size:13px;color:var(--uw-gray);margin-bottom:10px">
                    Project: <strong><?php echo htmlspecialchars($pm['job_title']); ?></strong>
                  </div>
                  <div style="font-size:13px;color:var(--uw-gray);margin-bottom:20px;display:flex;align-items:center;gap:8px">
                    <div class="sb-av" style="width:24px;height:24px;font-size:10px"><?php echo strtoupper(substr($pm['freelancer_name'],0,1)); ?></div>
                    <span>By <strong><?php echo htmlspecialchars($pm['freelancer_name']); ?></strong> · Requested <?php echo date('M d, Y', strtotime($pm['updated_at'])); ?></span>
                  </div>
                </div>
                <div style="text-align:right;min-width:200px;border-left:1px solid var(--uw-border);padding-left:24px">
                  <div style="font-size:11px;color:var(--uw-gray);text-transform:uppercase;margin-bottom:8px;font-weight:700;letter-spacing:0.05em">Milestone Amount</div>
                  <div style="font-size:32px;font-weight:800;color:var(--uw-black);margin-bottom:24px">$<?php echo number_format($pm['amount'], 2); ?></div>
                  <div style="display:flex;flex-direction:column;gap:10px">
                    <button class="btn btn-g" onclick="releaseMilestone(<?php echo $pm['id']; ?>, this)" style="width:100%;justify-content:center;padding:12px">Approve & Release</button>
                    <button class="btn btn-w" onclick="rejectMilestone(<?php echo $pm['id']; ?>, this)" style="width:100%;justify-content:center;padding:12px;border-color:#ddd">Reject Submission</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Work Logs to Review -->
          <?php foreach($pendingWorkLogs as $wl): ?>
            <div class="card" style="padding:28px;border-radius:16px;box-shadow:var(--sh);border:1px solid var(--uw-border)">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:24px">
                <div style="flex:1;min-width:300px">
                  <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                    <div style="font-weight:700;font-size:18px;color:var(--uw-black)"><?php echo htmlspecialchars($wl['job_title']); ?></div>
                    <span class="badge <?php echo $wl['contract_type']==='hourly'?'b-blue':'b-green'; ?>" style="font-size:10px;letter-spacing:0.02em"><?php echo strtoupper($wl['contract_type']); ?></span>
                  </div>
                  <div style="font-size:13px;color:var(--uw-gray);margin-bottom:20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <div class="sb-av" style="width:24px;height:24px;font-size:10px"><?php echo strtoupper(substr($wl['freelancer_name'],0,1)); ?></div>
                    <span>By <strong><?php echo htmlspecialchars($wl['freelancer_name']); ?></strong> · Submitted <?php echo date('M d, Y', strtotime($wl['created_at'])); ?></span>
                    <?php if (!empty($wl['start_time']) && !empty($wl['end_time'])): ?>
                      <span style="color:#4b5563; font-weight:600; background:#f3f4f6; padding:3px 8px; border-radius:4px; font-size:11px; margin-left:8px; display:inline-flex; align-items:center; gap:4px">
                        🕒 <?php echo substr($wl['start_time'], 0, 5); ?> - <?php echo substr($wl['end_time'], 0, 5); ?> UTC
                      </span>
                    <?php endif; ?>
                  </div>
                  <div style="background:#f8faf9;padding:20px;border-radius:12px;font-size:14.5px;line-height:1.7;color:#333;border:1px solid #eef2ee">
                    <?php echo nl2br(htmlspecialchars($wl['description'])); ?>
                  </div>
                  <?php if($wl['attachments']): ?>
                    <div style="margin-top:16px;display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--uw-green-light);border-radius:8px;width:fit-content">
                      <span style="font-size:14px">📎</span>
                      <span style="font-size:13px;color:var(--uw-green-dark);font-weight:600"><?php echo htmlspecialchars($wl['attachments']); ?></span>
                    </div>
                  <?php endif; ?>
                </div>
                <div style="text-align:right;min-width:200px;border-left:1px solid var(--uw-border);padding-left:24px">
                  <div style="font-size:11px;color:var(--uw-gray);text-transform:uppercase;margin-bottom:8px;font-weight:700;letter-spacing:0.05em">Payment Amount</div>
                  <div style="font-size:32px;font-weight:800;color:var(--uw-black);margin-bottom:24px">$<?php echo number_format($wl['amount'], 2); ?></div>
                  <div style="display:flex;flex-direction:column;gap:10px">
                    <button class="btn btn-g" onclick="processWorkLog(<?php echo $wl['id']; ?>, 'approved')" style="width:100%;justify-content:center;padding:12px">Approve & Pay</button>
                    <button class="btn btn-w" onclick="processWorkLog(<?php echo $wl['id']; ?>, 'rejected')" style="width:100%;justify-content:center;padding:12px;border-color:#ddd">Reject Submission</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>


    <!-- ══ SETTINGS PAGE ══ -->
    <div class="page" id="page-settings">
      <div class="pg-header">
        <div>
          <div class="pg-title">Profile Settings</div>
          <div class="pg-sub">Manage your personal and company information</div>
        </div>
        <button class="btn btn-g" onclick="saveClientProfile(this)">Save Changes</button>
      </div>

      <div class="card" style="padding:32px;border-radius:16px;box-shadow:var(--sh);max-width:800px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
          <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:8px;color:var(--uw-black)">Full Name</label>
            <input type="text" id="client-name" value="<?php echo htmlspecialchars($user['name']); ?>" style="width:100%;padding:12px;border:1.5px solid var(--uw-border);border-radius:10px;outline:none" placeholder="e.g. John Doe">
          </div>
          <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:8px;color:var(--uw-black)">Company Name</label>
            <input type="text" id="client-company" value="<?php echo htmlspecialchars($user['title'] ?? ''); ?>" style="width:100%;padding:12px;border:1.5px solid var(--uw-border);border-radius:10px;outline:none" placeholder="e.g. Acme Corp">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
          <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:8px;color:var(--uw-black)">Email Address (Read-only)</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="width:100%;padding:12px;border:1.5px solid var(--uw-border);border-radius:10px;background:#f9fafb;color:var(--uw-gray);cursor:not-allowed">
          </div>
          <div>
            <label style="display:block;font-size:13px;font-weight:700;margin-bottom:8px;color:var(--uw-black)">Country / Location</label>
            <select id="client-country" style="width:100%;padding:12px;border:1.5px solid var(--uw-border);border-radius:10px;outline:none;background:#fff;color:var(--uw-black);font-size:14px;font-family:inherit">
              <?php echo buildCountryOptionsHtml($user['country'] ?? 'United Kingdom'); ?>
            </select>
          </div>
        </div>

        <div style="margin-bottom:24px">
          <label style="display:block;font-size:13px;font-weight:700;margin-bottom:8px;color:var(--uw-black)">Company Bio / Description</label>
          <textarea id="client-bio" style="width:100%;padding:12px;border:1.5px solid var(--uw-border);border-radius:10px;outline:none;height:120px;line-height:1.6"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
        </div>

        <div style="border-top:1px solid var(--uw-border);padding-top:24px;display:flex;justify-content:flex-end">
            <button class="btn btn-g" style="padding:12px 30px;font-size:15px" onclick="saveClientProfile(this)">Save Profile Changes</button>
        </div>
      </div>

      <!-- Client Reviews and Ratings Feed -->
      <?php
      $clientReviewsStmt = $db->prepare("
          SELECT r.*, j.title as job_title, u.name as freelancer_name, u.country as freelancer_country
          FROM reviews r
          JOIN contracts c ON r.contract_id = c.id
          JOIN jobs j ON c.job_id = j.id
          JOIN users u ON r.reviewer_id = u.id
          WHERE r.reviewee_id = ?
          ORDER BY r.created_at DESC
      ");
      $clientReviewsStmt->execute([$user['id']]);
      $clientReviews = $clientReviewsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
      ?>
      <div class="card" style="padding:32px;border-radius:16px;box-shadow:var(--sh);max-width:800px;margin-top:24px">
        <h3 style="font-size:17px;font-weight:800;color:var(--uw-black);margin-bottom:16px;border-bottom:1.5px solid var(--uw-border);padding-bottom:12px">
          ⭐ Reviews & Ratings from Freelancers (<?php echo count($clientReviews); ?>)
        </h3>
        
        <?php if (empty($clientReviews)): ?>
          <div style="text-align:center;padding:40px 20px;color:var(--uw-gray)">
            <div style="font-size:40px;margin-bottom:12px">🤝</div>
            <strong style="color:var(--uw-black);font-size:14px">No reviews from freelancers yet</strong>
            <p style="font-size:12.5px;color:var(--uw-gray);margin-top:4px;line-height:1.5">Completed contracts with feedback left by freelancers will be showcased here.</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:16px">
            <?php foreach ($clientReviews as $cr): 
              $stars = str_repeat('★', (int)round($cr['rating'])) . str_repeat('☆', 5 - (int)round($cr['rating']));
              $dateStr = date('M d, Y', strtotime($cr['created_at']));
            ?>
              <div style="padding:20px;border:1px solid var(--uw-border);border-radius:12px;background:#fcfdfc;transition:transform .2s">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:8px">
                  <div>
                    <h4 style="margin:0 0 4px 0;font-size:13.5px;color:var(--uw-black);font-weight:700"><?php echo htmlspecialchars($cr['job_title']); ?></h4>
                    <div style="font-size:11.5px;color:var(--uw-gray)">Rated by <?php echo htmlspecialchars($cr['freelancer_name']); ?> · <?php echo htmlspecialchars(getCountryName($cr['freelancer_country'] ?? 'Global')); ?></div>
                  </div>
                  <span style="font-size:13px;font-weight:700;color:#d97706;background:#fef3c7;padding:4px 8px;border-radius:8px;white-space:nowrap">
                    <?php echo $stars; ?> <?php echo number_format($cr['rating'], 1); ?>
                  </span>
                </div>
                <p style="margin:0;font-size:13px;line-height:1.6;color:#374151;font-style:italic">
                  "<?php echo htmlspecialchars($cr['feedback'] ?: 'No comment provided.'); ?>"
                </p>
                <div style="font-size:11px;color:var(--uw-gray);text-align:right;margin-top:8px">Reviewed on <?php echo $dateStr; ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ══ TALENT PAGE ══ -->
    <div class="page" id="page-talent">
      <div class="pg-header">
        <div>
          <div class="pg-title">Talent</div>
          <div class="pg-sub">Manage and hire freelancers for your team</div>
        </div>
        <button class="btn btn-g" onclick="showPage('find-talent')">🔍 Find New Talent</button>
      </div>
      
      <div class="tab-bar" style="margin-bottom:24px">
        <div class="tab on" onclick="setTab(this, 'all-talent')">Team (<?php echo $talentCounts['team']; ?>)</div>
        <div class="tab" onclick="setTab(this, 'invited-talent')">Invited (<?php echo $talentCounts['invited']; ?>)</div>
        <div class="tab" onclick="setTab(this, 'saved-talent')">Saved (<?php echo $talentCounts['saved']; ?>)</div>
        <div class="tab" onclick="setTab(this, 'hired-talent')">Previous (<?php echo $talentCounts['previous']; ?>)</div>
      </div>
      
      <div class="card" style="margin-bottom:0;overflow:auto;border-radius:12px;box-shadow:var(--sh)">
        <table class="tbl talent-list" id="all-talent">
          <thead><tr><th>Freelancer</th><th class="hide-mob">Role</th><th class="hide-mob">Rating</th><th>Rate</th><th>Status</th><th class="hide-mob">Last Contract</th><th>Action</th></tr></thead>
          <tbody>
            <?php if(empty($teamTalent)): ?>
              <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--uw-gray)">You haven't hired any team members yet.</td></tr>
            <?php else: ?>
              <?php foreach($teamTalent as $t): 
                $initials = strtoupper(substr($t['name'], 0, 1) . substr(explode(' ', $t['name'])[1] ?? '', 0, 1));
                $isSaved = in_array($t['id'], array_column($savedTalent, 'id'));
                $statusLabel = ucfirst($t['last_contract_status'] ?? 'Active');
                $badgeClass = ($t['last_contract_status'] === 'paused') ? 'b-gray' : 'b-green';
              ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:10px">
                      <div class="av">
                        <?php if (!empty($t['avatar_url'])): ?>
                          <img src="<?php echo baseUrl($t['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                        <?php else: ?>
                          <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%"><?php echo $initials; ?></div>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div style="font-weight:600"><?php echo htmlspecialchars($t['name']); ?></div>
                        <div style="font-size:11px;color:var(--uw-gray)"><?php echo htmlspecialchars(getCountryName($t['country'] ?? 'Unknown')); ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($t['title'] ?? 'Freelancer'); ?></td>
                  <td>★ 0.0 (0)</td>
                  <td>$<?php echo number_format($t['hourly_rate'] ?? 0); ?>/hr</td>
                  <td>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusLabel; ?></span>
                  </td>
                  <td class="hide-mob">Active now</td>
                  <td><button class="btn btn-w btn-sm" onclick="event.stopPropagation();openChatWith(<?php echo $t['id']; ?>, '<?php echo addslashes($t['name']); ?>', '<?php echo $initials; ?>', '<?php echo $t['avatar_url'] ?? ''; ?>')">Message</button></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <table class="tbl talent-list" id="invited-talent" style="display:none">
          <thead><tr><th>Name</th><th class="hide-mob">Invited For</th><th class="hide-mob">Rating</th><th>Rate</th><th class="hide-mob">Date Invited</th><th>Action</th></tr></thead>
          <tbody>
            <?php if(empty($invitedTalent)): ?>
              <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--uw-gray)">You haven't invited any talent yet.</td></tr>
            <?php else: ?>
              <?php foreach($invitedTalent as $t): 
                $initials = strtoupper(substr($t['name'], 0, 1) . substr(explode(' ', $t['name'])[1] ?? '', 0, 1));
              ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:10px">
                      <div class="av">
                        <?php if (!empty($t['avatar_url'])): ?>
                          <img src="<?php echo baseUrl($t['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                        <?php else: ?>
                          <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%"><?php echo $initials; ?></div>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div style="font-weight:600"><?php echo htmlspecialchars($t['name']); ?></div>
                        <div style="font-size:11px;color:var(--uw-gray)"><?php echo htmlspecialchars(getCountryName($t['country'] ?? 'Unknown')); ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="hide-mob"><?php echo htmlspecialchars($t['job_title']); ?></td>
                  <td class="hide-mob">★ 0.0 (0)</td>
                  <td>$<?php echo number_format($t['hourly_rate'] ?? 0); ?>/hr</td>
                  <td class="hide-mob"><?php echo date('M d, Y', strtotime($t['invited_at'])); ?></td>
                  <td>
                    <button class="btn btn-w btn-sm" onclick="event.stopPropagation();openChatWith(<?php echo $t['id']; ?>, '<?php echo addslashes($t['name']); ?>', '<?php echo $initials; ?>', '<?php echo $t['avatar_url'] ?? ''; ?>')">Message</button>
                    <button class="btn btn-red-outline btn-sm" onclick="revokeInvitation(<?php echo $t['invitation_id']; ?>, this)">Revoke</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <table class="tbl talent-list" id="saved-talent" style="display:none">
          <thead><tr><th>Name</th><th class="hide-mob">Skill</th><th class="hide-mob">Rating</th><th>Rate</th><th class="hide-mob">Date Saved</th><th>Action</th></tr></thead>
          <tbody>
            <?php if(empty($savedTalent)): ?>
              <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--uw-gray)">You haven't saved any talent yet.</td></tr>
            <?php else: ?>
              <?php foreach($savedTalent as $t): 
                $initials = strtoupper(substr($t['name'], 0, 1) . substr(explode(' ', $t['name'])[1] ?? '', 0, 1));
              ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:10px">
                      <div class="av">
                        <?php if (!empty($t['avatar_url'])): ?>
                          <img src="<?php echo baseUrl($t['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                        <?php else: ?>
                          <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%"><?php echo $initials; ?></div>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div style="font-weight:600"><?php echo htmlspecialchars($t['name']); ?></div>
                        <div style="font-size:11px;color:var(--uw-gray)"><?php echo htmlspecialchars(getCountryName($t['country'] ?? 'Unknown')); ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="hide-mob"><?php echo htmlspecialchars($t['title'] ?? 'Freelancer'); ?></td>
                  <td class="hide-mob">★ 0.0 (0)</td>
                  <td>$<?php echo number_format($t['hourly_rate'] ?? 0); ?>/hr</td>
                  <td class="hide-mob"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                  <td>
                    <button class="btn btn-w btn-sm" onclick="event.stopPropagation();openChatWith(<?php echo $t['id']; ?>, '<?php echo addslashes($t['name']); ?>', '<?php echo $initials; ?>', '<?php echo $t['avatar_url'] ?? ''; ?>')">Message</button>
                    <button class="btn btn-g btn-sm" onclick="openModal('invite-freelancer-<?php echo $t['id']; ?>')">Invite</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <table class="tbl talent-list" id="hired-talent" style="display:none">
          <thead><tr><th>Name</th><th>Skill</th><th>Last Contract</th><th>Total Paid</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php if(empty($previousTalent)): ?>
              <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--uw-gray)">You haven't hired anyone yet.</td></tr>
            <?php else: ?>
              <?php foreach($previousTalent as $t): 
                $initials = strtoupper(substr($t['name'], 0, 1) . substr(explode(' ', $t['name'])[1] ?? '', 0, 1));
              ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:10px">
                      <div class="av">
                        <?php if (!empty($t['avatar_url'])): ?>
                          <img src="<?php echo baseUrl($t['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                        <?php else: ?>
                          <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%"><?php echo $initials; ?></div>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div style="font-weight:600"><?php echo htmlspecialchars($t['name']); ?></div>
                        <div style="font-size:11px;color:var(--uw-gray)"><?php echo htmlspecialchars(getCountryName($t['country'] ?? 'Unknown')); ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($t['title'] ?? 'Freelancer'); ?></td>
                  <td><?php echo date('M d, Y', strtotime($t['last_hired_at'])); ?></td>
                  <td>$<?php echo number_format($t['total_paid']); ?></td>
                  <td><span class="badge b-gray"><?php echo ucfirst($t['last_contract_status'] ?? 'Closed'); ?></span></td>
                  <td><button class="btn btn-w btn-sm" onclick="event.stopPropagation();openChatWith(<?php echo $t['id']; ?>, '<?php echo addslashes($t['name']); ?>', '<?php echo $initials; ?>', '<?php echo $t['avatar_url'] ?? ''; ?>')">Message</button></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ FIND TALENT PAGE ══ -->
    <div class="page" id="page-find-talent">
      <div class="pg-header">
        <div>
          <div class="pg-title">Find Talent</div>
          <div class="pg-sub">Discover and hire the best freelancers for your projects</div>
        </div>
      </div>

      <div class="talent-search-wrap" style="margin-bottom:20px">
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <div style="flex:1;min-width:240px;position:relative">
            <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--uw-gray)">🔍</span>
            <input type="text" id="talent-search" placeholder="Search by skill, name or title..." style="width:100%;padding:12px 12px 12px 40px;border:1.5px solid var(--uw-border);border-radius:10px;font-family:inherit;outline:none" onkeyup="filterTalent(this.value)">
          </div>
          <button class="btn btn-w" style="flex-shrink:0" onclick="toast('Filters','Advanced filters coming soon')">Filters</button>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px" id="talent-grid">
        <?php foreach($allTalent as $t): 
          $initials = strtoupper(substr($t['name'], 0, 1) . substr(explode(' ', $t['name'])[1] ?? '', 0, 1));
          $fStats = getFreelancerStats($t['id']);
          $rating = $fStats['rating'];
          $reviews = $fStats['reviews_count'];
        ?>
          <div class="card talent-card" style="margin-bottom:0;transition:transform .2s, border-color .2s" onmouseover="this.style.borderColor='var(--uw-green)'" onmouseout="this.style.borderColor='var(--uw-border)'">
            <div class="card-body">
              <div style="display:flex;gap:15px;margin-bottom:15px">
                <div class="av" style="width:50px;height:50px">
                  <?php if (!empty($t['avatar_url'])): ?>
                    <img src="<?php echo baseUrl($t['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                  <?php else: ?>
                    <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:18px"><?php echo $initials; ?></div>
                  <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0">
                  <div style="font-weight:700;font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($t['name']); ?></div>
                  <div style="font-size:13px;color:var(--uw-green);font-weight:600"><?php echo htmlspecialchars($t['title'] ?? 'Freelancer'); ?></div>
                  <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--uw-gray);margin-top:2px;flex-wrap:wrap">
                    <span>★ <?php echo $rating; ?> (<?php echo $reviews; ?> reviews)</span>
                    <?php if ($fStats['badge_label']): ?>
                      <span style="color:var(--uw-green);font-weight:700">· <?php echo $fStats['badge_label']; ?></span>
                    <?php endif; ?>
                    <span>· JSS: <?php echo $fStats['jss']; ?></span>
                  </div>
                </div>
              </div>
              <div style="display:flex;gap:15px;font-size:13px;color:var(--uw-gray);margin-bottom:15px">
                <div><strong>$<?php echo number_format($t['hourly_rate'] ?? 0); ?></strong> / hr</div>
                <div><strong>$<?php echo number_format($fStats['total_earned']); ?>+</strong> earned</div>
                <div>📍 <?php echo htmlspecialchars(getCountryName($t['country'] ?? 'Global')); ?></div>
              </div>
              <div style="display:flex;gap:6px">
                <button class="btn btn-g btn-sm" style="flex:1.2;justify-content:center;font-size:12px;padding:6px 8px" onclick="openModal('invite-freelancer-<?php echo $t['id']; ?>')">Invite to Job</button>
                <button class="btn btn-w btn-sm" style="flex:1;justify-content:center;font-size:12px;padding:6px 8px" onclick="openModal('hire-freelancer-<?php echo $t['id']; ?>')">Direct Hire</button>
                <button class="btn btn-w btn-sm" style="flex:0.4;justify-content:center;font-size:12px;padding:6px 4px" title="Message Freelancer" onclick="openChatWith(<?php echo $t['id']; ?>, '<?php echo addslashes($t['name']); ?>', '<?php echo $initials; ?>', '<?php echo $t['avatar_url'] ?? ''; ?>')">💬</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══ MESSAGES PAGE ══ -->
    <div class="page" id="page-messages">
      <div class="pg-header">
        <div class="pg-title">Messages</div>
      </div>
      <div style="background:white;border:1.5px solid var(--uw-border);border-radius:var(--radius);display:flex;min-height:480px;overflow:hidden">
        <!-- Sidebar list -->
        <div style="width:270px;border-right:1.5px solid var(--uw-border);flex-shrink:0">
          <div style="padding:12px 14px;border-bottom:1px solid var(--uw-border)">
            <input style="width:100%;padding:8px 12px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:12.5px;font-family:inherit;outline:none;background:var(--uw-bg)" placeholder="Search messages…" onkeyup="filterConversations(this.value)">
          </div>
          <div style="padding:6px 0;max-height:600px;overflow-y:auto" id="conversations-list">
            <?php if(empty($conversations)): ?>
              <div style="padding:20px;text-align:center;color:var(--uw-gray);font-size:13px">No conversations yet.</div>
            <?php else: ?>
              <?php foreach($conversations as $c): 
                $initials = strtoupper(substr($c['other_name'], 0, 1) . substr(explode(' ', $c['other_name'])[1] ?? '', 0, 1));
                $isUnread = ($c['is_read'] == 0 && $c['sender_id'] != $user['id']);
                $time = date('H:i', strtotime($c['last_time']));
              ?>
                <div class="msg-item <?php echo $isUnread ? 'unread' : ''; ?>" style="border-radius:0;margin:0;padding:12px 14px" onclick="loadChat(<?php echo $c['other_id']; ?>, '<?php echo addslashes($c['other_name']); ?>', '<?php echo $initials; ?>', this, '<?php echo $c['other_avatar'] ?? ''; ?>')">
                  <div class="av">
                    <?php if (!empty($c['other_avatar'])): ?>
                      <img src="<?php echo baseUrl($c['other_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:13px"><?php echo $initials; ?></div>
                    <?php else: ?>
                      <div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:13px"><?php echo $initials; ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="msg-meta">
                    <div class="msg-name"><?php echo htmlspecialchars($c['other_name']); ?><span class="msg-time"><?php echo $time; ?></span></div>
                    <div class="msg-text"><?php echo htmlspecialchars($c['last_message']); ?></div>
                  </div>
                  <?php if($isUnread): ?><div class="msg-dot"></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <?php if (!empty($blockedFreelancers)): ?>
          <div style="border-top:1px solid var(--uw-border);padding:10px 14px 12px">
            <div style="font-size:11px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Blocked</div>
            <div id="blocked-freelancers-list">
              <?php foreach ($blockedFreelancers as $bf):
                $bInitials = strtoupper(substr($bf['name'], 0, 1) . substr(explode(' ', $bf['name'])[1] ?? '', 0, 1));
              ?>
              <div class="blocked-freelancer-item" data-freelancer-id="<?php echo (int)$bf['id']; ?>" style="display:flex;align-items:center;gap:10px;padding:8px 0">
                <div class="av" style="width:28px;height:28px;flex-shrink:0">
                  <?php if (!empty($bf['avatar_url'])): ?>
                    <img src="<?php echo baseUrl($bf['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;opacity:.7" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div style="display:none;background:#f3f4f6;color:var(--uw-gray);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:11px"><?php echo $bInitials; ?></div>
                  <?php else: ?>
                    <div style="background:#f3f4f6;color:var(--uw-gray);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:11px"><?php echo $bInitials; ?></div>
                  <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0">
                  <div style="font-size:12.5px;font-weight:600;color:var(--uw-gray2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($bf['name']); ?></div>
                </div>
                <button type="button" class="btn btn-sm" style="font-size:11px;padding:4px 10px;flex-shrink:0" onclick="unblockFreelancer(<?php echo (int)$bf['id']; ?>, '<?php echo addslashes($bf['name']); ?>', this)">Unblock</button>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <!-- Chat window -->
        <div style="flex:1;display:flex;flex-direction:column" id="chat-window">
          <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--uw-gray);flex-direction:column;gap:15px">
            <span style="font-size:40px">💬</span>
            <div>Select a conversation to start chatting</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ PAYMENTS PAGE ══ -->
    <div class="page" id="page-payments">
      <div class="pg-header">
        <div>
          <div class="pg-title">Payments</div>
          <div class="pg-sub">Billing balance, transaction history & payment methods</div>
        </div>
        <div style="display:flex;gap:10px">
          <button class="btn btn-g" onclick="openModal('add-funds')">+ Add Funds</button>
        </div>
      </div>

      <div class="g3" style="margin-bottom:18px">
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Available Balance</div>
            <div id="client-available-balance" style="font-size:30px;font-weight:700;color:var(--uw-black)">$<?php echo number_format($user['balance'] ?? 0, 2); ?></div>
            <button class="btn btn-g btn-sm" style="margin-top:12px;width:100%;justify-content:center" onclick="openModal('add-funds')">Add Funds</button>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">In Escrow</div>
            <div style="font-size:30px;font-weight:700;color:#1d4ed8">$<?php echo number_format($escrowAmount, 2); ?></div>
            <div style="font-size:12px;color:var(--uw-gray);margin-top:8px">Protected milestone funds</div>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Spent This Month</div>
            <div style="font-size:30px;font-weight:700;color:var(--uw-black)">$<?php echo number_format($stats['total_spent'], 0); ?></div>
            <div style="font-size:12px;color:<?php echo $spendChange >= 0 ? 'var(--uw-green)' : '#dc2626'; ?>;margin-top:8px;font-weight:600">
              <?php echo $spendChange >= 0 ? '↑' : '↓'; ?> <?php echo abs(round($spendChange)); ?>% vs <?php echo date('F', strtotime('-1 month')); ?>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h3>Transaction History</h3></div>
        
        <div class="desk-only" style="overflow-x:auto">
          <table class="tbl" style="min-width: 900px;">
            <thead><tr><th>Date</th><th>Description</th><th>Freelancer / Source</th><th>Type</th><th>Amount</th><th>Platform Fee</th><th>Total Charge</th><th>Status</th></tr></thead>
            <tbody>
              <?php if(empty($clientTransactions)): ?>
                  <tr><td colspan="8" style="text-align:center;padding:20px;color:var(--uw-gray)">No transactions found.</td></tr>
              <?php else: ?>
                  <?php foreach($clientTransactions as $ct): 
                      $isDeposit = ($ct['payee_id'] == $user['id']);
                      $fee = (float)($ct['platform_fee'] ?? 0);
                      $total = (float)$ct['amount'] + ($isDeposit ? 0 : $fee);
                  ?>
                  <tr>
                    <td><?php echo date('M j, Y', strtotime($ct['created_at'])); ?></td>
                    <td><?php echo $isDeposit ? 'Add Funds (Deposit)' : (!empty($ct['job_title']) ? 'Payment for: ' . htmlspecialchars($ct['job_title']) : 'Payment for contract'); ?></td>
                    <td><?php echo $isDeposit ? (strcasecmp($ct['payment_method'] ?? '', 'paystack') === 0 ? 'Paystack' : ucfirst($ct['payment_method'] ?? 'Deposit')) : ($ct['freelancer_name'] ?: 'System'); ?></td>
                    <td><span class="badge <?php echo $isDeposit ? 'b-blue' : 'b-purple'; ?>"><?php echo $isDeposit ? 'Deposit' : 'Fixed'; ?></span></td>
                    <td style="font-weight:600;color:<?php echo $isDeposit ? 'var(--uw-green)' : 'var(--uw-dark)'; ?>">
                      <?php echo $isDeposit ? '+' : ''; ?>$<?php echo number_format($ct['amount'], 2); ?>
                    </td>
                    <td style="color:#dc2626;font-weight:600">
                      $<?php echo number_format($fee, 2); ?>
                    </td>
                    <td style="font-weight:700;color:<?php echo $isDeposit ? 'var(--uw-green)' : '#dc2626'; ?>">
                      <?php echo $isDeposit ? '+' : '−'; ?>$<?php echo number_format($total, 2); ?>
                    </td>
                    <td><span class="badge b-green"><?php echo ucfirst($ct['status']); ?></span></td>
                  </tr>
                  <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mob-only">
          <div style="padding:0 20px">
            <?php if(empty($clientTransactions)): ?>
                <div style="text-align:center;padding:20px;color:var(--uw-gray)">No transactions found.</div>
            <?php else: ?>
                <?php foreach($clientTransactions as $ct): 
                    $isDeposit = ($ct['payee_id'] == $user['id']);
                    $fee = (float)($ct['platform_fee'] ?? 0);
                    $total = (float)$ct['amount'] + ($isDeposit ? 0 : $fee);
                ?>
                <div class="tx-item" style="padding:15px 0;border-bottom:1px solid var(--uw-border);display:flex;justify-content:space-between;align-items:flex-start">
                  <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:14px;color:var(--uw-black);margin-bottom:2px"><?php echo $isDeposit ? 'Add Funds (Deposit)' : (!empty($ct['job_title']) ? 'Payment for: ' . htmlspecialchars($ct['job_title']) : 'Payment for contract'); ?></div>
                    <div style="font-size:11.5px;color:var(--uw-gray);margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                      <?php echo $isDeposit ? (strcasecmp($ct['payment_method'] ?? '', 'paystack') === 0 ? 'Paystack' : ucfirst($ct['payment_method'] ?? 'Deposit')) : ($ct['freelancer_name'] ?: 'System'); ?>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                      <span class="badge <?php echo $isDeposit ? 'b-blue' : 'b-purple'; ?>" style="font-size:9px;padding:1px 6px"><?php echo $isDeposit ? 'Deposit' : 'Fixed'; ?></span>
                      <span style="font-size:11px;color:var(--uw-gray2)"><?php echo date('M j, Y', strtotime($ct['created_at'])); ?></span>
                      <?php if ($fee > 0): ?>
                        <span style="font-size:10px;color:#dc2626;background:#fef2f2;padding:1px 6px;border-radius:4px;font-weight:600">Fee: $<?php echo number_format($fee, 2); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div style="text-align:right;flex-shrink:0">
                    <div style="font-weight:700;font-size:15px;color:<?php echo $isDeposit ? 'var(--uw-green)' : '#dc2626'; ?>;margin-bottom:4px">
                      <?php echo $isDeposit ? '+' : '−'; ?>$<?php echo number_format($total, 2); ?>
                    </div>
                    <span class="badge b-green" style="font-size:9px;padding:2px 6px"><?php echo ucfirst($ct['status']); ?></span>
                  </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </div>

    <!-- ══ REPORTS PAGE ══ -->
    <div class="page" id="page-reports">
      <div class="pg-header">
        <div class="pg-title">Reports</div>
        <div style="display:flex;gap:10px;align-items:center">
          <select style="padding:8px 14px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:13px;font-family:inherit;color:var(--uw-dark);background:white;outline:none">
            <option>Last 30 days</option><option>Last 90 days</option><option>This Year</option><option>All Time</option>
          </select>
          <button class="btn btn-w" onclick="toast('Export','CSV download starting...')">Export CSV</button>
        </div>
      </div>

      <div class="g3" style="margin-bottom:18px">
        <div class="report-metric"><div class="rm-lbl">Total Spent (All Time)</div><div class="rm-val">$<?php echo number_format($reportStats['total_spent_all_time'] ?? 0); ?></div></div>
        <div class="report-metric"><div class="rm-lbl">Contracts Completed</div><div class="rm-val"><?php echo $reportStats['contracts_completed']; ?></div></div>
        <div class="report-metric">
          <div class="rm-lbl">Avg Spend / Contract</div>
          <div class="rm-val">
            <?php 
              $contracts_count = $reportStats['contracts_completed'] ?: ($reportStats['freelancers_hired'] ?: 1);
              $avgSpendContract = $reportStats['total_spent_all_time'] / $contracts_count;
            ?>
            $<?php echo number_format($avgSpendContract); ?>
          </div>
        </div>
      </div>

      <div class="g2">
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3>Spend by Category</h3></div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px">
              <?php
              $displayCategories = [
                  'Engineering' => 0.0,
                  'Design' => 0.0,
                  'Marketing / SEO' => 0.0,
                  'AI / ML' => 0.0
              ];
              foreach ($categorySpendList as $cs) {
                  $displayCategories[$cs['category']] = (float)$cs['total_spent'];
              }
              arsort($displayCategories);
              $maxVal = max(1.0, (float)current($displayCategories));
              foreach ($displayCategories as $cat => $spent):
                  $pct = round(($spent / $maxVal) * 100);
                  if ($spent > 0 && $pct < 3) $pct = 3;
              ?>
                <div>
                  <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                    <span><?php echo htmlspecialchars($cat); ?></span>
                    <span style="font-weight:700">$<?php echo number_format($spent); ?></span>
                  </div>
                  <div class="progress-bar" style="height:8px">
                    <div class="progress-fill" style="width:<?php echo $pct; ?>%"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3>Account Summary</h3></div>
          <div class="card-body">
            <table class="tbl" style="font-size:13px">
              <tr><td style="color:var(--uw-gray)">Total jobs posted</td><td><strong><?php echo $reportStats['total_jobs_posted']; ?></strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Freelancers hired</td><td><strong><?php echo $reportStats['freelancers_hired']; ?></strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Contracts completed</td><td><strong><?php echo $reportStats['contracts_completed']; ?></strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Total spent (all time)</td><td><strong>$<?php echo number_format($reportStats['total_spent_all_time'] ?? 0); ?></strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Total hours tracked</td><td><strong><?php echo number_format($reportStats['total_hours_tracked'], 1); ?> hrs</strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Disputes filed</td><td><strong style="color:<?php echo $reportStats['disputes_filed'] > 0 ? '#ef4444' : 'var(--uw-green)'; ?>"><?php echo $reportStats['disputes_filed']; ?></strong></td></tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ VERIFICATION PAGE ══ -->
    <div class="page" id="page-verification">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div>
          <div style="font-size:20px;font-weight:700">Identity Verification</div>
          <div style="font-size:13px;color:var(--uw-gray);margin-top:3px">Verify your identity to ensure a safe and secure marketplace.</div>
        </div>
        <?php if($vStatus === 'approved'): ?>
          <span class="badge b-green" style="font-size:12px;padding:5px 12px">✅ Verified</span>
        <?php elseif($vStatus === 'pending'): ?>
          <span class="badge b-yellow" style="font-size:12px;padding:5px 12px">⏳ Pending</span>
        <?php else: ?>
          <span class="badge b-gray" style="font-size:12px;padding:5px 12px">🛡️ Unverified</span>
        <?php endif; ?>
      </div>

      <?php if($vStatus === 'approved'): ?>
        <div class="card" style="border:2px solid var(--uw-green);background:var(--uw-green-light)">
          <div class="card-body" style="text-align:center;padding:36px 24px">
            <div style="font-size:52px;margin-bottom:16px">✅</div>
            <div style="font-size:20px;font-weight:700;margin-bottom:8px;color:var(--uw-green)">Identity Verified!</div>
            <div style="font-size:13.5px;color:var(--uw-gray);line-height:1.7;max-width:380px;margin:0 auto 20px">Your identity has been successfully verified. Your account is now fully active and trusted by freelancers.</div>
            <div>
              <button class="btn btn-g" onclick="showPage('home',document.querySelector('[onclick*=home]'))">Back to Dashboard</button>
            </div>
          </div>
        </div>
      <?php elseif($vStatus === 'pending'): ?>
        <div class="card" style="border:1px solid #fde68a;background:#fffbeb">
          <div class="card-body" style="text-align:center;padding:36px 24px">
            <div style="font-size:52px;margin-bottom:16px">⏳</div>
            <div style="font-size:20px;font-weight:700;margin-bottom:8px;color:#92400e">Verification Under Review</div>
            <div style="font-size:13.5px;color:#92400e;line-height:1.7;max-width:380px;margin:0 auto 20px">Your identity documents have been received and are currently under review. This process typically takes 1-3 business days. We'll notify you once it's complete.</div>
            <div style="display:inline-flex;align-items:center;gap:8px;background:white;border:1px solid #fde68a;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#b27b16">
              Status: <strong>Pending Review</strong>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Multi-step Design for Clients -->
        <div style="display:flex;align-items:center;gap:0;margin-bottom:22px;background:white;border:1px solid var(--uw-border);border-radius:10px;overflow:hidden">
          <div id="cvstep-1" style="flex:1;padding:14px 16px;border-right:1px solid var(--uw-border);cursor:pointer;transition:background .15s;background:var(--uw-green-light)" onclick="switchCVStep(1)">
            <div style="display:flex;align-items:center;gap:8px">
              <div id="cvstep-1-ico" style="width:24px;height:24px;border-radius:50%;background:var(--uw-green);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">1</div>
              <div>
                <div style="font-size:12px;font-weight:700">Choose ID</div>
                <div style="font-size:11px;color:var(--uw-gray)">Select document</div>
              </div>
            </div>
          </div>
          <div id="cvstep-2" style="flex:1;padding:14px 16px;border-right:1px solid var(--uw-border);cursor:pointer;transition:background .15s" onclick="switchCVStep(2)">
            <div style="display:flex;align-items:center;gap:8px">
              <div id="cvstep-2-ico" style="width:24px;height:24px;border-radius:50%;background:var(--uw-border);color:var(--uw-gray);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">2</div>
              <div>
                <div style="font-size:12px;font-weight:700">Upload</div>
                <div style="font-size:11px;color:var(--uw-gray)">Photo or scan</div>
              </div>
            </div>
          </div>
          <div id="cvstep-3" style="flex:1;padding:14px 16px;cursor:pointer;transition:background .15s" onclick="switchCVStep(3)">
            <div style="display:flex;align-items:center;gap:8px">
              <div id="cvstep-3-ico" style="width:24px;height:24px;border-radius:50%;background:var(--uw-border);color:var(--uw-gray);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">3</div>
              <div>
                <div style="font-size:12px;font-weight:700">Review</div>
                <div style="font-size:11px;color:var(--uw-gray)">Final check</div>
              </div>
            </div>
          </div>
        </div>

        <div id="cvpanel-1" class="card" style="margin-bottom:16px">
          <div class="card-head"><h3>Step 1 — Choose Document Type</h3></div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
              <div class="doc-type-card" id="cdtype-passport" onclick="selectCDocType('Passport','cdtype-passport')" style="border:2px solid var(--uw-border);border-radius:10px;padding:16px;cursor:pointer;text-align:center">
                <div style="font-size:34px;margin-bottom:8px">🛂</div>
                <div style="font-weight:700;font-size:13px">Passport</div>
              </div>
              <div class="doc-type-card" id="cdtype-national-id" onclick="selectCDocType('National ID','cdtype-national-id')" style="border:2px solid var(--uw-border);border-radius:10px;padding:16px;cursor:pointer;text-align:center">
                <div style="font-size:34px;margin-bottom:8px">🪪</div>
                <div style="font-weight:700;font-size:13px">National ID</div>
              </div>
            </div>
            <div id="cdtype-selected-bar" style="display:none;margin-top:16px;background:var(--uw-green-light);border:1px solid var(--uw-green);border-radius:8px;padding:10px 14px;align-items:center;gap:10px">
              <span id="cdtype-selected-text" style="font-size:13px;font-weight:600;color:var(--uw-green)">Passport selected</span>
              <button class="btn btn-sm btn-g" style="margin-left:auto" onclick="switchCVStep(2)">Next →</button>
            </div>
          </div>
        </div>

        <div id="cvpanel-2" class="card" style="margin-bottom:16px;display:none">
          <div class="card-head"><h3>Step 2 — Upload</h3></div>
          <div class="card-body">
            <div id="cvdrop" style="border:2px dashed var(--uw-border);border-radius:10px;padding:30px;text-align:center;background:#fafafa;cursor:pointer" onclick="document.getElementById('cvinput').click()">
              <div id="cv-text" style="font-size:13px;font-weight:600">Click to upload ID photo</div>
              <input type="file" id="cvinput" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="handleCVFile(this)">
            </div>
            <div style="display:flex;gap:10px;margin-top:20px">
              <button class="btn btn-w" onclick="switchCVStep(1)">← Back</button>
              <button class="btn btn-g" id="cvnext-2" style="flex:1;justify-content:center" onclick="switchCVStep(3)" disabled>Next →</button>
            </div>
          </div>
        </div>

        <div id="cvpanel-3" class="card" style="margin-bottom:16px;display:none">
          <div class="card-head"><h3>Step 3 — Submit</h3></div>
          <div class="card-body">
            <div style="background:var(--uw-bg);padding:14px;border-radius:8px;margin-bottom:20px;border:1px solid var(--uw-border)">
              <div style="font-size:12px;color:var(--uw-gray)">Document: <strong id="cvreview-type">Passport</strong></div>
              <div style="font-size:12px;color:var(--uw-gray)">File: <strong id="cvreview-file">none</strong></div>
            </div>
            <div style="display:flex;gap:10px">
              <button class="btn btn-w" onclick="switchCVStep(2)">← Back</button>
              <button class="btn btn-g" id="v-submit-btn" style="flex:1;justify-content:center" onclick="submitClientFinalVerification()">
                <span id="v-btn-text">Submit for Review</span>
              </button>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ══ MOBILE BOTTOM NAV ══ -->
<nav class="mob-bottom-nav" id="mob-bottom-nav">
  <div class="mob-bottom-nav-inner">
    <button class="mob-nav-item active" id="mbn-home" onclick="showPage('home',document.querySelector('[onclick*=\'home\']'));setMobNav('home')">
      <span class="mn-ico">🏠</span>Home
    </button>
    <button class="mob-nav-item" id="mbn-jobs" onclick="showPage('jobs',document.querySelector('[onclick*=\'jobs\']'));setMobNav('jobs')">
      <span class="mn-ico">📋</span>Jobs
    </button>
    <button class="mob-nav-item" id="mbn-proposals" onclick="showPage('proposals',document.querySelector('[onclick*=\'proposals\']'));setMobNav('proposals')">
      <span class="mn-ico">📩</span>Proposals
    </button>
    <button class="mob-nav-item" id="mbn-messages" onclick="showPage('messages',document.querySelector('[onclick*=\'messages\']'));setMobNav('messages')">
      <span class="mn-ico">💬</span>Messages
    </button>
    <button class="mob-nav-item" id="mbn-more" onclick="openMobSidebar()">
      <span class="mn-ico">☰</span>More
    </button>
  </div>
</nav>
<!-- ══ DYNAMIC DIRECT HIRE MODALS AND HANDLERS ══ -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php foreach($allTalent as $t): ?>
    <?php
      $initials = strtoupper(substr($t['name'], 0, 1) . substr(explode(' ', $t['name'])[1] ?? '', 0, 1));
      $rateVal = (int)($t['hourly_rate'] ?? 20);
    ?>
    MODALS['hire-freelancer-<?php echo $t['id']; ?>'] = {
      t: 'Hire <?php echo addslashes(htmlspecialchars($t['name'])); ?>',
      b: `
        <div class="mc" style="padding:16px 20px calc(24px + env(safe-area-inset-bottom))">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:15px;padding-bottom:12px;border-bottom:1px solid var(--uw-border)">
            <div class="av" style="width:40px;height:40px;background:var(--uw-green-light);color:var(--uw-green);font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;border-radius:50%">
              <?php echo $initials; ?>
            </div>
            <div>
              <div style="font-weight:700;font-size:15px;color:var(--uw-black)"><?php echo addslashes(htmlspecialchars($t['name'])); ?></div>
              <div style="font-size:12px;color:var(--uw-green);font-weight:600"><?php echo addslashes(htmlspecialchars($t['title'] ?? 'Freelancer')); ?></div>
            </div>
          </div>
          
          <form id="hire-form-<?php echo $t['id']; ?>" onsubmit="submitDirectHire(event, <?php echo $t['id']; ?>)">
            <div class="fg" style="margin-bottom:14px">
              <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--uw-black)">Select Job to Hire For</label>
              <select id="hire-job-id-<?php echo $t['id']; ?>" required style="width:100%;padding:10px;border:1.5px solid var(--uw-border);border-radius:8px;font-family:inherit;font-size:14px;outline:none" onchange="updateHireRates(<?php echo $t['id']; ?>)">
                <option value="">— Select an open job —</option>
                <?php foreach($allOpenJobs as $j): ?>
                  <option value="<?php echo $j['id']; ?>" data-type="<?php echo $j['budget_type']; ?>" data-budget="<?php echo $j['budget']; ?>">
                    <?php echo addslashes(htmlspecialchars($j['title'])); ?> ($<?php echo number_format($j['budget']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="fg" style="margin-bottom:14px">
              <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--uw-black)">Contract Type</label>
              <select id="hire-contract-type-<?php echo $t['id']; ?>" onchange="updateHireRates(<?php echo $t['id']; ?>)" required style="width:100%;padding:10px;border:1.5px solid var(--uw-border);border-radius:8px;font-family:inherit;font-size:14px;outline:none">
                <option value="hourly">Hourly Rate</option>
                <option value="fixed">Fixed Price</option>
              </select>
            </div>

            <div class="fg" style="margin-bottom:14px">
              <label id="hire-rate-label-<?php echo $t['id']; ?>" style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--uw-black)">Hourly Rate ($ / hr)</label>
              <input type="number" id="hire-amount-<?php echo $t['id']; ?>" value="<?php echo $rateVal; ?>" required min="1" style="width:100%;padding:10px;border:1.5px solid var(--uw-border);border-radius:8px;font-family:inherit;font-size:14px;outline:none">
            </div>

            <div class="fg" style="margin-bottom:16px">
              <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--uw-black)">Invitation Message / Terms</label>
              <textarea id="hire-message-<?php echo $t['id']; ?>" placeholder="Write a message explaining the terms, expectations, and details..." required style="width:100%;padding:10px;border:1.5px solid var(--uw-border);border-radius:8px;min-height:80px;font-family:inherit;font-size:13px;outline:none;resize:vertical"></textarea>
            </div>

            <div style="margin-top:20px;display:flex;gap:10px">
              <button type="submit" class="btn btn-g" style="flex:1;justify-content:center;padding:12px;font-size:14px">Send Offer & Start Contract</button>
              <button type="button" class="btn btn-w" onclick="closeModal()" style="flex:1;justify-content:center;padding:12px;font-size:14px">Cancel</button>
            </div>
          </form>
        </div>
      `
    };

    MODALS['invite-freelancer-<?php echo $t['id']; ?>'] = {
      t: 'Invite <?php echo addslashes(htmlspecialchars($t['name'])); ?> to Apply',
      b: `
        <div class="mc" style="padding:16px 20px calc(24px + env(safe-area-inset-bottom))">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:15px;padding-bottom:12px;border-bottom:1px solid var(--uw-border)">
            <div class="av" style="width:40px;height:40px;background:var(--uw-green-light);color:var(--uw-green);font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;border-radius:50%">
              <?php echo $initials; ?>
            </div>
            <div>
              <div style="font-weight:700;font-size:15px;color:var(--uw-black)"><?php echo addslashes(htmlspecialchars($t['name'])); ?></div>
              <div style="font-size:12px;color:var(--uw-green);font-weight:600"><?php echo addslashes(htmlspecialchars($t['title'] ?? 'Freelancer')); ?></div>
            </div>
          </div>
          
          <form id="invite-form-<?php echo $t['id']; ?>" onsubmit="submitJobInvite(event, <?php echo $t['id']; ?>)">
            <div class="fg" style="margin-bottom:14px">
              <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--uw-black)">Select Job to Invite For</label>
              <select id="invite-job-id-<?php echo $t['id']; ?>" required style="width:100%;padding:10px;border:1.5px solid var(--uw-border);border-radius:8px;font-family:inherit;font-size:14px;outline:none">
                <option value="">— Select an open job —</option>
                <?php foreach($allOpenJobs as $j): ?>
                  <option value="<?php echo $j['id']; ?>">
                    <?php echo addslashes(htmlspecialchars($j['title'])); ?> ($<?php echo number_format($j['budget']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="fg" style="margin-bottom:16px">
              <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--uw-black)">Invitation Message</label>
              <textarea id="invite-message-<?php echo $t['id']; ?>" placeholder="Write a personalized invitation message telling the freelancer why they are a good fit..." required style="width:100%;padding:10px;border:1.5px solid var(--uw-border);border-radius:8px;min-height:100px;font-family:inherit;font-size:13px;outline:none;resize:vertical"></textarea>
            </div>

            <div style="margin-top:20px;display:flex;gap:10px">
              <button type="submit" class="btn btn-g" style="flex:1;justify-content:center;padding:12px;font-size:14px">Send Invitation</button>
              <button type="button" class="btn btn-w" onclick="closeModal()" style="flex:1;justify-content:center;padding:12px;font-size:14px">Cancel</button>
            </div>
          </form>
        </div>
      `
    };
  <?php endforeach; ?>
});

function updateHireRates(fid) {
  const jobSel = document.getElementById('hire-job-id-' + fid);
  const typeSel = document.getElementById('hire-contract-type-' + fid);
  const amountInput = document.getElementById('hire-amount-' + fid);
  const labelEl = document.getElementById('hire-rate-label-' + fid);

  if (typeSel.value === 'fixed') {
    labelEl.innerText = 'Fixed Price Amount ($)';
    const selectedOpt = jobSel.options[jobSel.selectedIndex];
    if (selectedOpt && selectedOpt.value) {
      amountInput.value = parseFloat(selectedOpt.getAttribute('data-budget')) || 500;
    } else {
      amountInput.value = 500;
    }
  } else {
    labelEl.innerText = 'Hourly Rate ($ / hr)';
    amountInput.value = 20; // Default hourly
  }
}

function submitDirectHire(event, fid) {
  event.preventDefault();
  const jobVal = document.getElementById('hire-job-id-' + fid).value;
  const typeVal = document.getElementById('hire-contract-type-' + fid).value;
  const amountVal = document.getElementById('hire-amount-' + fid).value;
  const messageVal = document.getElementById('hire-message-' + fid).value;

  if (!jobVal) {
    remoAlert('Please select an open job first.', 'Hire');
    return;
  }

  if (typeVal === 'hourly' && (typeof availableBalance === 'undefined' || availableBalance < 1)) {
    remoAlert('Add at least $1.00 to your account balance before starting an hourly contract.', 'Balance required');
    return;
  }

  fetch(BASE_URL + 'client/api/direct-hire.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      freelancer_id: fid,
      job_id: jobVal,
      contract_type: typeVal,
      amount: amountVal,
      message: messageVal
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toast('Success', data.message);
      closeModal();
      // Redirect to contracts page dynamically
      showPage('contracts', document.querySelector('.sb-item[onclick*=\'contracts\']'));
      setTimeout(() => location.reload(), 1000);
    } else {
      toast('Error', data.message);
    }
  })
  .catch(err => {
    console.error(err);
    toast('Error', 'Failed to submit hire request.');
  });
}

function submitJobInvite(event, fid) {
  event.preventDefault();
  const jobVal = document.getElementById('invite-job-id-' + fid).value;
  const messageVal = document.getElementById('invite-message-' + fid).value;

  if (!jobVal) {
    remoAlert('Please select an open job first.', 'Invite');
    return;
  }

  const submitBtn = event.target.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Sending Invite...';

  fetch(BASE_URL + 'client/api/send-invite.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      freelancer_id: fid,
      job_id: jobVal,
      message: messageVal
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toast('Success 🎉', data.message);
      closeModal();
    } else {
      toast('Error', data.message);
      submitBtn.disabled = false;
      submitBtn.innerText = 'Send Invitation';
    }
  })
  .catch(err => {
    console.error(err);
    toast('Error', 'Failed to send invitation.');
    submitBtn.disabled = false;
    submitBtn.innerText = 'Send Invitation';
  });
}

async function revokeInvitation(inviteId, btn) {
  if (!(await remoConfirm('This invitation will be cancelled.', 'Revoke invitation?'))) return;

  btn.disabled = true;
  btn.textContent = 'Revoking...';

  fetch(BASE_URL + 'client/api/revoke-invite.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ invitation_id: inviteId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toast('Success 🎉', 'Invitation revoked successfully.');
      setTimeout(() => location.reload(), 1000);
    } else {
      toast('Error', data.message);
      btn.disabled = false;
      btn.textContent = 'Revoke';
    }
  })
  .catch(err => {
    console.error(err);
    toast('Error', 'Failed to revoke invitation.');
    btn.disabled = false;
    btn.textContent = 'Revoke';
  });
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
