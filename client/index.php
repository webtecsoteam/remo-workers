<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
$user = Auth::user();
if(!$user) { redirect(baseUrl()); }

$db = getDB();
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

$totalSpent = $db->prepare("SELECT SUM(amount) FROM payments WHERE payer_id = ? AND status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$totalSpent->execute([$user['id']]);
$stats['total_spent'] = $totalSpent->fetchColumn() ?: 0;

// 2. Active Contracts List
$contractsStmt = $db->prepare("SELECT c.*, j.title as job_title, u.name as freelancer_name FROM contracts c 
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

// 4. Messages
$msgStmt = $db->prepare("SELECT m.*, u.name as sender_name FROM messages m 
                         JOIN users u ON m.sender_id = u.id 
                         WHERE m.receiver_id = ? ORDER BY m.created_at DESC LIMIT 4");
$msgStmt->execute([$user['id']]);
$recentMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$unreadCount->execute([$user['id']]);
$unreadMessagesCount = $unreadCount->fetchColumn();

// 5. All Jobs for Jobs Page
$allJobsStmt = $db->prepare("SELECT j.*, (SELECT COUNT(*) FROM proposals WHERE job_id = j.id) as proposal_count 
                             FROM jobs j WHERE j.client_id = ? ORDER BY j.created_at DESC");
$allJobsStmt->execute([$user['id']]);
$allJobs = $allJobsStmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for job statuses
$jobCounts = [
    'open' => 0,
    'paused' => 0,
    'closed' => 0
];
foreach ($allJobs as $aj) {
    if (isset($jobCounts[$aj['status']])) {
        $jobCounts[$aj['status']]++;
    }
}

// 6. All Proposals for Proposals Page
$proposalsStmt = $db->prepare("SELECT p.*, j.title as job_title, u.name as freelancer_name, u.email as freelancer_email 
                               FROM proposals p 
                               JOIN jobs j ON p.job_id = j.id 
                               JOIN users u ON p.freelancer_id = u.id 
                               WHERE j.client_id = ? AND p.status = 'pending' 
                               ORDER BY p.created_at DESC");
$proposalsStmt->execute([$user['id']]);
$allProposals = $proposalsStmt->fetchAll(PDO::FETCH_ASSOC);

// 7. All Contracts for Contracts Page
$allContractsStmt = $db->prepare("SELECT c.*, j.title as job_title, u.name as freelancer_name FROM contracts c 
                                  JOIN jobs j ON c.job_id = j.id 
                                  JOIN users u ON c.freelancer_id = u.id 
                                  WHERE c.client_id = ? ORDER BY c.created_at DESC");
$allContractsStmt->execute([$user['id']]);
$allContracts = $allContractsStmt->fetchAll(PDO::FETCH_ASSOC);

$contractCounts = ['active' => 0, 'completed' => 0];
foreach ($allContracts as $ac) {
    if (isset($contractCounts[$ac['status']])) {
        $contractCounts[$ac['status']]++;
    }
}
// 9. Transaction History for Payments Page
$clientTransactionsStmt = $db->prepare("
    SELECT p.*, u.name as freelancer_name 
    FROM payments p 
    JOIN users u ON p.payee_id = u.id 
    WHERE p.payer_id = ? 
    ORDER BY p.created_at DESC LIMIT 10
");
$clientTransactionsStmt->execute([$user['id']]);
$clientTransactions = $clientTransactionsStmt->fetchAll(PDO::FETCH_ASSOC);

// 10. Reports Data
$reportStatsStmt = $db->prepare("
    SELECT 
        (SELECT SUM(amount) FROM payments WHERE payer_id = ? AND status = 'completed') as total_spent_all_time,
        COUNT(DISTINCT id) as total_jobs_posted,
        (SELECT COUNT(DISTINCT freelancer_id) FROM contracts WHERE client_id = ?) as freelancers_hired,
        (SELECT COUNT(*) FROM contracts WHERE client_id = ? AND status = 'completed') as contracts_completed
    FROM jobs 
    WHERE client_id = ?
");
$reportStatsStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$reportStats = $reportStatsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upwork – Client Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Neue+Haas+Grotesk+Display+Pro:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo baseUrl("client/css/style.css"); ?>">
<script>const BASE_URL = '<?php echo baseUrl(); ?>';</script>
</head>
<body>

<!-- TOAST -->
<div class="toast" id="toast"><strong id="t-title"></strong><span id="t-msg"></span></div>

<!-- MODAL OVERLAY -->
<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="mh"><h2 id="mh-title">Detail</h2><div class="mclose" onclick="closeModal()">✕</div></div>
    <div class="mc" id="mc-body"></div>
  </div>
</div>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeMobSidebar()"></div>

<!-- MOBILE FAB -->
<button class="mob-fab" id="mob-fab" style="display:none" onclick="openModal('post-job')" aria-label="Post a job">+</button>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <a class="sb-logo" href="<?php echo baseUrl(); ?>"><div class="sb-wordmark">up<em>work</em></div></a>
  <div class="sb-user">
    <div class="sb-av"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
    <div>
      <div class="sb-name"><?php echo htmlspecialchars($user['name']); ?></div>
      <div class="sb-role">Client Account</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Main</div>
    <div class="sb-item active" onclick="showPage('home',this)"><span class="ico">🏠</span>Home</div>
    <div class="sb-item" onclick="showPage('jobs',this)"><span class="ico">📋</span>My Jobs<span class="sb-badge g">3</span></div>
    <div class="sb-item" onclick="showPage('proposals',this)"><span class="ico">📩</span>Proposals<span class="sb-badge">12</span></div>
    <div class="sb-item" onclick="showPage('contracts',this)"><span class="ico">🤝</span>Contracts</div>
    <div class="sb-item" onclick="showPage('talent',this)"><span class="ico">👥</span>Talent</div>
    <div class="sb-section">Tools</div>
    <div class="sb-item" onclick="showPage('messages',this)"><span class="ico">💬</span>Messages<span class="sb-badge">4</span></div>
    <div class="sb-item" onclick="showPage('payments',this)"><span class="ico">💳</span>Payments</div>
    <div class="sb-item" onclick="showPage('reports',this)"><span class="ico">📊</span>Reports</div>
    <div class="sb-item" onclick="showPage('verification',this)"><span class="ico">🪪</span>Identity Verification</div>
    <div class="sb-item" onclick="toast('Uma AI','AI work assistant analyzing your active projects...')"><span class="ico">✨</span>AI Assistant</div>
    <div class="sb-section">Account</div>
    <div class="sb-item" onclick="toast('Settings','Account settings opened')"><span class="ico">⚙️</span>Settings</div>
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
      <div class="tb-av" onclick="toast('Profile','Opening account settings')">NX</div>
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
          <div class="stat-val">4.9</div>
          <div class="stat-sub">From your reviews</div>
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
                  <div class="av" style="background:var(--uw-green-light);color:var(--uw-green)"><?php echo strtoupper(substr($c['freelancer_name'], 0, 2)); ?></div>
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
                  <div class="av" style="background:var(--uw-green-light);color:var(--uw-green)"><?php echo strtoupper(substr($m['sender_name'], 0, 2)); ?></div>
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
                <span class="jm g">$<?php echo number_format($j['budget']); ?></span>
                <span class="jm"><?php echo ucfirst($j['budget_type']); ?> price</span>
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
            $4,820 <span style="font-size:13px;font-weight:400;color:var(--uw-gray)">May 2026</span>
          </div>
          <div class="chart-area">
            <div class="chart-bars">
              <div class="chart-bar" style="height:45%" onclick="toast('November','$2,100 spent')"></div>
              <div class="chart-bar" style="height:60%" onclick="toast('December','$2,800 spent')"></div>
              <div class="chart-bar" style="height:52%" onclick="toast('January','$2,400 spent')"></div>
              <div class="chart-bar" style="height:70%" onclick="toast('February','$3,250 spent')"></div>
              <div class="chart-bar" style="height:75%" onclick="toast('March','$3,500 spent')"></div>
              <div class="chart-bar" style="height:88%" onclick="toast('April','$4,100 spent')"></div>
              <div class="chart-bar active" style="height:100%" onclick="toast('May (current)','$4,820 spent so far')"></div>
            </div>
            <div class="chart-labels">
              <div class="chart-lbl">Nov</div><div class="chart-lbl">Dec</div><div class="chart-lbl">Jan</div>
              <div class="chart-lbl">Feb</div><div class="chart-lbl">Mar</div><div class="chart-lbl">Apr</div>
              <div class="chart-lbl">May</div>
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
          <div class="pg-sub"><?php echo $jobCounts['open']; ?> open · <?php echo $jobCounts['paused']; ?> paused · <?php echo $jobCounts['closed']; ?> closed</div>
        </div>
        <button class="btn btn-g btn-lg" onclick="openModal('post-job')">+ Post a New Job</button>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">All</div>
        <div class="tab" onclick="setTab(this)">Open</div>
        <div class="tab" onclick="setTab(this)">Paused</div>
        <div class="tab" onclick="setTab(this)">Closed</div>
      </div>
      <div class="card" style="margin-bottom:0;overflow:auto">
        <table class="tbl">
          <thead><tr><th>Job Title</th><th>Type</th><th>Budget</th><th>Proposals</th><th>Posted</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php if (empty($allJobs)): ?>
                <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--uw-gray)">No job posts found.</td></tr>
            <?php else: ?>
                <?php foreach ($allJobs as $aj): ?>
                <tr>
                  <td class="cl" onclick="toast('Job Details','Viewing <?php echo htmlspecialchars($aj['title']); ?>')"><?php echo htmlspecialchars($aj['title']); ?></td>
                  <td><?php echo ucfirst($aj['budget_type']); ?></td>
                  <td>$<?php echo number_format($aj['budget']); ?></td>
                  <td><strong style="color:var(--uw-green)"><?php echo $aj['proposal_count']; ?></strong></td>
                  <td><?php echo date('M j', strtotime($aj['created_at'])); ?></td>
                  <td><span class="badge b-<?php echo ($aj['status'] === 'open' ? 'green' : ($aj['status'] === 'paused' ? 'yellow' : 'gray')); ?>"><?php echo ucfirst($aj['status']); ?></span></td>
                  <td><button class="btn btn-w btn-sm" onclick="toast('Job','Viewing details')">View</button></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ PROPOSALS PAGE ══ -->
    <div class="page" id="page-proposals">
      <div class="pg-header">
        <div>
          <div class="pg-title">Proposals</div>
          <div class="pg-sub"><?php echo count($allProposals); ?> new proposals waiting for review</div>
        </div>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">New (<?php echo count($allProposals); ?>)</div>
        <div class="tab" onclick="setTab(this)">Shortlisted (0)</div>
        <div class="tab" onclick="setTab(this)">Archived</div>
      </div>

      <?php if (empty($allProposals)): ?>
          <div class="card" style="padding:40px;text-align:center;color:var(--uw-gray)">No pending proposals found.</div>
      <?php else: ?>
          <?php foreach ($allProposals as $p): ?>
          <div class="prop-card" onclick="toast('Proposal','Viewing proposal details')">
            <div class="prop-top">
              <div class="av" style="background:var(--uw-green-light);color:var(--uw-green);width:42px;height:42px;font-size:13px"><?php echo strtoupper(substr($p['freelancer_name'], 0, 2)); ?></div>
              <div class="prop-info">
                <h4><?php echo htmlspecialchars($p['freelancer_name']); ?></h4>
                <p>Freelancer · 0 reviews · ★ 0.0 · $0/hr</p>
              </div>
              <div style="margin-left:auto;text-align:right;flex-shrink:0">
                <div class="prop-rate">$<?php echo number_format($p['bid_amount']); ?></div>
                <div style="font-size:11px;color:var(--uw-gray);margin-top:2px">For: <?php echo htmlspecialchars($p['job_title']); ?></div>
              </div>
            </div>
            <div class="prop-body">"<?php echo htmlspecialchars(substr($p['cover_letter'], 0, 200)); ?>..."</div>
            <div class="prop-foot">
              <div style="font-size:11.5px;color:var(--uw-gray)">Submitted <?php echo date('M j', strtotime($p['created_at'])); ?></div>
              <div class="prop-actions">
                <button class="btn btn-w btn-sm" onclick="event.stopPropagation();toast('Archived','Proposal archived')">Archive</button>
                <button class="btn btn-o btn-sm" onclick="event.stopPropagation();toast('Shortlisted!','Freelancer added to your shortlist')">Shortlist</button>
                <button class="btn btn-w btn-sm" onclick="event.stopPropagation();toast('Message','Chat feature coming soon')">💬 Message</button>
                <button class="btn btn-g btn-sm" onclick="event.stopPropagation();hireFreelancer(<?php echo $p['id']; ?>)">Hire →</button>
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
        <div class="tab on" onclick="setTab(this)">Active (<?php echo $contractCounts['active']; ?>)</div>
        <div class="tab" onclick="setTab(this)">Completed (<?php echo $contractCounts['completed']; ?>)</div>
        <div class="tab" onclick="setTab(this)">Paused</div>
      </div>

      <div class="card" style="margin-bottom:0;overflow:auto">
        <table class="tbl">
          <thead><tr><th>Freelancer</th><th>Job Title</th><th>Type</th><th>Budget</th><th>Start Date</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php if (empty($allContracts)): ?>
                <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--uw-gray)">No contracts found.</td></tr>
            <?php else: ?>
                <?php foreach ($allContracts as $ac): ?>
                <tr>
                  <td class="cl" onclick="toast('Freelancer','Viewing profile')"><?php echo htmlspecialchars($ac['freelancer_name']); ?></td>
                  <td><?php echo htmlspecialchars($ac['job_title']); ?></td>
                  <td><?php echo ucfirst($ac['contract_type']); ?></td>
                  <td>$<?php echo number_format($ac['amount']); ?></td>
                  <td><?php echo date('M j, Y', strtotime($ac['start_date'])); ?></td>
                  <td><span class="badge b-<?php echo ($ac['status'] === 'active' ? 'green' : 'gray'); ?>"><?php echo ucfirst($ac['status']); ?></span></td>
                  <td><button class="btn btn-w btn-sm" onclick="toast('Contract','Viewing details')">Manage</button></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ TALENT PAGE ══ -->
    <div class="page" id="page-talent">
      <div class="pg-header">
        <div>
          <div class="pg-title">Talent</div>
          <div class="pg-sub">Freelancers you've worked with or saved</div>
        </div>
        <button class="btn btn-g" onclick="toast('Find Talent','Opening talent search...')">🔍 Find New Talent</button>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">All Talent (12)</div>
        <div class="tab" onclick="setTab(this)">Saved (5)</div>
        <div class="tab" onclick="setTab(this)">Previously Hired (7)</div>
      </div>
      <div class="card" style="margin-bottom:0;overflow:auto">
        <table class="tbl">
          <thead><tr><th>Name</th><th>Skill</th><th>Rating</th><th>Rate</th><th>Status</th><th>Last Contract</th><th>Action</th></tr></thead>
          <tbody>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#d1fae5;color:#065f46">AN</div><div><div style="font-weight:600">Anika Nkosi</div><div style="font-size:11px;color:var(--uw-gray)">Berlin, Germany</div></div></div></td><td>UI/UX Design</td><td>★ 5.0 (127)</td><td>$90/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with Anika')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#dbeafe;color:#1e40af">JK</div><div><div style="font-weight:600">James Kowalski</div><div style="font-size:11px;color:var(--uw-gray)">Toronto, Canada</div></div></div></td><td>Full Stack Dev</td><td>★ 4.9 (89)</td><td>$130/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with James')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#fef3c7;color:#92400e">LT</div><div><div style="font-weight:600">Lena Thornton</div><div style="font-size:11px;color:var(--uw-gray)">London, UK</div></div></div></td><td>SEO / Marketing</td><td>★ 5.0 (203)</td><td>$65/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with Lena')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#ede9fe;color:#5b21b6">MP</div><div><div style="font-weight:600">Marcus Patel</div><div style="font-size:11px;color:var(--uw-gray)">Bangalore, India</div></div></div></td><td>AI / ML</td><td>★ 4.8 (41)</td><td>$110/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with Marcus')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#fce7f3;color:#9d174d">ZM</div><div><div style="font-weight:600">Zara Mehta</div><div style="font-size:11px;color:var(--uw-gray)">Dubai, UAE</div></div></div></td><td>Copywriting</td><td>★ 4.9 (74)</td><td>$55/hr</td><td><span class="badge b-gray">Saved</span></td><td>Never</td><td><button class="btn btn-g btn-sm" onclick="toast('Invite sent','Zara invited to your job post')">Invite</button></td></tr>
          </tbody>
        </table>
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
            <input style="width:100%;padding:8px 12px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:12.5px;font-family:inherit;outline:none;background:var(--uw-bg)" placeholder="Search messages…">
          </div>
          <div style="padding:6px 0">
            <div class="msg-item unread" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening Anika Nkosi conversation')">
              <div class="av" style="background:#d1fae5;color:#065f46">AN</div>
              <div class="msg-meta"><div class="msg-name">Anika Nkosi<span class="msg-time">10m</span></div><div class="msg-text">Screens are ready for review!</div></div>
              <div class="msg-dot"></div>
            </div>
            <div class="msg-item unread" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening James Kowalski conversation')">
              <div class="av" style="background:#dbeafe;color:#1e40af">JK</div>
              <div class="msg-meta"><div class="msg-name">James Kowalski<span class="msg-time">2h</span></div><div class="msg-text">All tests passing — please review</div></div>
              <div class="msg-dot"></div>
            </div>
            <div class="msg-item" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening Lena Thornton conversation')">
              <div class="av" style="background:#fef3c7;color:#92400e">LT</div>
              <div class="msg-meta"><div class="msg-name">Lena Thornton<span class="msg-time">1d</span></div><div class="msg-text">Here's the Q2 keyword strategy</div></div>
            </div>
            <div class="msg-item" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening Marcus Patel conversation')">
              <div class="av" style="background:#ede9fe;color:#5b21b6">MP</div>
              <div class="msg-meta"><div class="msg-name">Marcus Patel<span class="msg-time">2d</span></div><div class="msg-text">Milestone 1 submitted for review</div></div>
            </div>
          </div>
        </div>
        <!-- Chat window -->
        <div style="flex:1;display:flex;flex-direction:column">
          <div style="padding:14px 18px;border-bottom:1px solid var(--uw-border);display:flex;align-items:center;gap:12px">
            <div class="av" style="background:#d1fae5;color:#065f46;width:36px;height:36px">AN</div>
            <div>
              <div style="font-weight:700;font-size:14px">Anika Nkosi</div>
              <div style="font-size:12px;color:var(--uw-green);display:flex;align-items:center;gap:4px"><span style="width:6px;height:6px;background:var(--uw-green);border-radius:50%;display:inline-block"></span>Online now</div>
            </div>
            <div style="margin-left:auto;display:flex;gap:8px">
              <button class="btn btn-w btn-sm" onclick="toast('Video call','Opening Upwork meeting room')">📹 Video Call</button>
              <button class="btn btn-w btn-sm" onclick="openModal('contract-anika')">View Contract</button>
            </div>
          </div>
          <div style="flex:1;padding:18px;overflow-y:auto;display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;gap:10px">
              <div class="av" style="background:#d1fae5;color:#065f46;flex-shrink:0">AN</div>
              <div style="max-width:70%">
                <div style="background:var(--uw-bg);border:1.5px solid var(--uw-border);border-radius:0 var(--radius) var(--radius) var(--radius);padding:10px 14px;font-size:13px;line-height:1.6">Hi! I've completed the first set of dashboard screens — 6 screens total. Ready for your review!</div>
                <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px">10:24 AM</div>
              </div>
            </div>
            <div style="display:flex;gap:10px;flex-direction:row-reverse">
              <div class="av" style="background:var(--uw-green);color:#001e00;flex-shrink:0">NX</div>
              <div style="max-width:70%;text-align:right">
                <div style="background:var(--uw-green);color:white;border-radius:var(--radius) 0 var(--radius) var(--radius);padding:10px 14px;font-size:13px;line-height:1.6">Excellent! I'll review them this afternoon and send feedback by EOD.</div>
                <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px">10:31 AM</div>
              </div>
            </div>
          </div>
          <div style="padding:14px 18px;border-top:1px solid var(--uw-border);display:flex;gap:10px">
            <input style="flex:1;padding:9px 14px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:13px;font-family:inherit;outline:none" placeholder="Type a message…" onfocus="this.style.borderColor='var(--uw-green)'" onblur="this.style.borderColor='var(--uw-border)'">
            <button class="btn btn-g" onclick="toast('Sent','Message delivered')">Send</button>
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
          <button class="btn btn-w" onclick="openModal('manage-cards')">Manage Cards</button>
          <button class="btn btn-g" onclick="openModal('add-funds')">+ Add Funds</button>
        </div>
      </div>

      <div class="g3" style="margin-bottom:18px">
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Available Balance</div>
            <div style="font-size:30px;font-weight:700;color:var(--uw-black)">$1,250.00</div>
            <button class="btn btn-g btn-sm" style="margin-top:12px;width:100%;justify-content:center" onclick="openModal('add-funds')">Add Funds</button>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">In Escrow</div>
            <div style="font-size:30px;font-weight:700;color:#1d4ed8">$3,400.00</div>
            <div style="font-size:12px;color:var(--uw-gray);margin-top:8px">Protected milestone funds</div>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Spent This Month</div>
            <div style="font-size:30px;font-weight:700;color:var(--uw-black)">$4,820</div>
            <div style="font-size:12px;color:var(--uw-green);margin-top:8px;font-weight:600">↑ 18% vs April</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h3>Transaction History</h3></div>
        <table class="tbl">
          <thead><tr><th>Date</th><th>Description</th><th>Freelancer</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
            <?php if(empty($clientTransactions)): ?>
                <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--uw-gray)">No transactions found.</td></tr>
            <?php else: ?>
                <?php foreach($clientTransactions as $ct): ?>
                <tr>
                  <td><?php echo date('M j, Y', strtotime($ct['created_at'])); ?></td>
                  <td>Payment for contract</td>
                  <td><?php echo htmlspecialchars($ct['freelancer_name']); ?></td>
                  <td><span class="badge b-purple">Fixed</span></td>
                  <td style="font-weight:700;color:#dc2626">−$<?php echo number_format($ct['amount']); ?></td>
                  <td><span class="badge b-green"><?php echo ucfirst($ct['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
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
        <div class="report-metric"><div class="rm-lbl">Avg Spend / Contract</div><div class="rm-val">$<?php echo $reportStats['contracts_completed'] > 0 ? number_format($reportStats['total_spent_all_time'] / $reportStats['contracts_completed']) : 0; ?></div></div>
      </div>

      <div class="g2">
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3>Spend by Category</h3></div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px">
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Engineering</span><span style="font-weight:700">$18,200</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:75%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Design</span><span style="font-weight:700">$10,500</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:43%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Marketing / SEO</span><span style="font-weight:700">$6,350</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:26%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>AI / ML</span><span style="font-weight:700">$3,400</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:14%"></div></div></div>
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
              <tr><td style="color:var(--uw-gray)">Total hours tracked</td><td><strong>0 hrs</strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Disputes filed</td><td><strong style="color:var(--uw-green)">0</strong></td></tr>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
