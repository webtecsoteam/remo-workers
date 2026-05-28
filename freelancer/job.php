<?php
/**
 * Dedicated job detail page (logged-in users).
 * URL: /remoworkers-dashboard/j/{encoded-job-id}
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

ensureFreelancerSchema();
$db = getDB();

$user = Auth::user();
$jobId = (int)($_GET['id'] ?? 0);

if (!$user) {
    if ($jobId > 0) {
        $_SESSION['redirect_to'] = 'remoworkers-dashboard/j/' . encodeJobId($jobId) . (isset($_GET['apply']) ? '?apply=1' : '');
    }
    redirect(baseUrl('?show_login=1'));
}

if ($jobId <= 0) {
    redirect(baseUrl('remoworkers-dashboard#find-work'));
}

$isFreelancer = ($user['role'] ?? '') === 'freelancer';

$jobStmt = $db->prepare("
    SELECT j.*, u.name as client_name, u.country as client_country, u.is_verified as client_verified,
    u.created_at as client_since,
    COALESCE((SELECT SUM(amount) FROM payments WHERE payer_id = j.client_id AND status = 'completed'), 0) + COALESCE(u.admin_spent_offset, 0) as client_total_spent,
    COALESCE((SELECT COUNT(*) FROM contracts WHERE client_id = j.client_id), 0) + COALESCE(u.admin_hires_offset, 0) as client_hires,
    COALESCE((SELECT COUNT(*) FROM proposals WHERE job_id = j.id), 0) as proposal_count,
    COALESCE((SELECT AVG(rating) FROM reviews WHERE reviewee_id = j.client_id), 0.0) as client_rating,
    COALESCE((SELECT COUNT(*) FROM contracts WHERE job_id = j.id), 0) as project_hires
    FROM jobs j
    JOIN users u ON j.client_id = u.id
    WHERE j.id = ?
");
$jobStmt->execute([$jobId]);
$currentJob = $jobStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentJob) {
    http_response_code(404);
    echo '<div style="padding:50px;text-align:center;font-family:sans-serif;"><h2>Job not found</h2><a href="' . htmlspecialchars(baseUrl('remoworkers-dashboard#find-work')) . '">Back to Find Work</a></div>';
    exit;
}

if (!$isFreelancer) {
    redirect(baseUrl('j/' . encodeJobId($jobId)));
}

$allJobs = [$currentJob];
$userSkills = !empty($user['skills']) ? json_decode($user['skills'], true) : [];
$savedJobs = [];
$submittedProposals = [];
$jobInvitations = [];
$allContracts = [];
$myServices = [];
$activeAgency = getActiveAgencyForUser((int)$user['id']);
$canApplyAsAgency = !empty($activeAgency['id']);
$totalProposals = 0;
$totalContracts = 0;
$unreadMessages = 0;
$fStats = ['jss' => '—', 'badge' => null];
$dynStats = ['badge_label' => 'Freelancer'];
$userProposal = null;
$autoApply = isset($_GET['apply']);

try {
    $dynStats = getFreelancerStats($user['id']);
    $fStats['jss'] = $dynStats['jss'];
    $fStats['badge'] = $dynStats['badge'];

    $savedJobsStmt = $db->prepare("SELECT job_id as id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $savedJobsStmt->execute([$user['id'], $jobId]);
    $savedJobs = $savedJobsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $propStmt = $db->prepare("SELECT * FROM proposals WHERE freelancer_id = ? AND job_id = ? LIMIT 1");
    $propStmt->execute([$user['id'], $jobId]);
    $userProposal = $propStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($userProposal) {
        $userProposal['job_title'] = $currentJob['title'];
        $submittedProposals = [$userProposal];
    }

    $invStmt = $db->prepare("SELECT * FROM job_invitations WHERE freelancer_id = ? AND job_id = ? AND status = 'pending'");
    $invStmt->execute([$user['id'], $jobId]);
    $jobInvitations = $invStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $countProposals = $db->prepare("SELECT COUNT(*) FROM proposals WHERE freelancer_id = ?");
    $countProposals->execute([$user['id']]);
    $totalProposals = (int)$countProposals->fetchColumn();

    $countContracts = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'active'");
    $countContracts->execute([$user['id']]);
    $totalContracts = (int)$countContracts->fetchColumn();

    $countMessages = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $countMessages->execute([$user['id']]);
    $unreadMessages = (int)$countMessages->fetchColumn();
} catch (Exception $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log('Job page load error: ' . $e->getMessage());
    }
}

function jobPageParseSkills($skillsField) {
    if (empty($skillsField)) {
        return [];
    }
    if (is_array($skillsField)) {
        return array_filter(array_map('trim', $skillsField));
    }
    $trimmed = trim((string)$skillsField);
    if ($trimmed === '') {
        return [];
    }
    if ($trimmed[0] === '[') {
        $parsed = json_decode($trimmed, true);
        if (is_array($parsed)) {
            return array_filter(array_map(function ($s) {
                return trim(str_replace(['[', ']', '"', "'"], '', (string)$s));
            }, $parsed));
        }
    }
    return array_filter(array_map('trim', explode(',', $trimmed)));
}

function jobPageBudgetLabel(array $job) {
    $type = $job['budget_type'] ?? 'fixed';
    if ($type === 'hourly') {
        if (!empty($job['min_hourly_rate']) && !empty($job['max_hourly_rate'])) {
            return 'Hourly · $' . number_format((float)$job['min_hourly_rate']) . '/hr - $' . number_format((float)$job['max_hourly_rate']) . '/hr';
        }
        return 'Hourly · $' . number_format((float)($job['budget'] ?? 0)) . '/hr';
    }
    if ($type === 'monthly') {
        return 'Monthly · $' . number_format((float)($job['budget'] ?? 0)) . '/month';
    }
    return 'Fixed Price · $' . number_format((float)($job['budget'] ?? 0));
}

function jobPageDefaultRate(array $job) {
    $type = $job['budget_type'] ?? 'fixed';
    if ($type === 'hourly') {
        return !empty($job['min_hourly_rate']) ? (float)$job['min_hourly_rate'] : (float)($job['budget'] ?? 0);
    }
    return (float)($job['budget'] ?? 0);
}

$skills = jobPageParseSkills($currentJob['skills_required'] ?? $currentJob['skills'] ?? '');
$encodedSlug = encodeJobId($jobId);
$publicJobUrl = baseUrl('j/' . $encodedSlug);
$isSaved = !empty($savedJobs);
$hasInvite = !empty($jobInvitations);
$budgetLabel = jobPageBudgetLabel($currentJob);
$defaultRate = jobPageDefaultRate($currentJob);
$isHourly = ($currentJob['budget_type'] ?? '') === 'hourly';
$isMonthly = ($currentJob['budget_type'] ?? '') === 'monthly';
$rateLabel = $isHourly ? 'Your Proposed Hourly Rate ($/hr)' : ($isMonthly ? 'Your Proposed Monthly Rate ($/mo)' : 'Your Proposed Rate ($)');

include __DIR__ . '/includes/header.php';
?>

<style>
.job-detail-page { max-width: 1100px; margin: 0 auto; }
.job-detail-back { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted2); text-decoration: none; margin-bottom: 20px; cursor: pointer; }
.job-detail-back:hover { color: var(--g); }
.job-detail-grid { display: grid; grid-template-columns: 1fr 300px; gap: 0; background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.job-detail-main { padding: 30px; border-right: 1px solid var(--border); }
.job-detail-side { padding: 30px; background: #f9fafb; }
.job-detail-title { font-size: 24px; font-weight: 700; color: var(--dark); line-height: 1.3; margin-bottom: 12px; }
.job-detail-desc { font-size: 15px; line-height: 1.8; color: var(--dark3); white-space: pre-line; margin-bottom: 30px; }
.job-apply-panel { margin-top: 30px; padding-top: 30px; border-top: 1px solid var(--border); }
@media (max-width: 900px) {
  .job-detail-grid { grid-template-columns: 1fr; }
  .job-detail-main { border-right: none; border-bottom: 1px solid var(--border); }
}
</style>

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

  <div class="sb-user" onclick="window.location.href='<?php echo baseUrl('remoworkers-dashboard#profile'); ?>'">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <div class="sb-av">
        <?php if (!empty($user['avatar_url'])): ?>
          <img src="<?php echo baseUrl($user['avatar_url']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
        <?php else: ?>
          <?php echo strtoupper(substr($user['name'] ?? 'CH', 0, 2)); ?>
        <?php endif; ?>
      </div>
      <div style="min-width:0">
        <div class="sb-name"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
        <div class="sb-role">Freelancer</div>
      </div>
    </div>
    <div class="sb-stats">
      <div class="sb-stat"><div class="sb-stat-val"><?php echo htmlspecialchars($fStats['jss']); ?></div><div class="sb-stat-lbl">Job Success</div></div>
      <div class="sb-stat" onclick="window.location.href='<?php echo baseUrl('remoworkers-dashboard#connects'); ?>'" style="cursor:pointer">
        <div class="sb-stat-val" id="sb-connects-val"><?php echo (int)($user['connects'] ?? 0); ?></div>
        <div class="sb-stat-lbl">Connects</div>
      </div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-section">Dashboard</div>
    <div class="sb-item" onclick="window.location.href='<?php echo baseUrl('remoworkers-dashboard'); ?>'"><span class="sb-ico">🏠</span>Home</div>
    <div class="sb-item active"><span class="sb-ico">🔍</span>Find Work</div>
    <div class="sb-item" onclick="window.location.href='<?php echo baseUrl('remoworkers-dashboard#proposals'); ?>'"><span class="sb-ico">📝</span>My Proposals<?php if ($totalProposals > 0): ?><span class="sb-badge green"><?php echo $totalProposals; ?></span><?php endif; ?></div>
    <div class="sb-item" onclick="window.location.href='<?php echo baseUrl('remoworkers-dashboard#contracts'); ?>'"><span class="sb-ico">🤝</span>My Contracts<?php if ($totalContracts > 0): ?><span class="sb-badge"><?php echo $totalContracts; ?></span><?php endif; ?></div>
    <div class="sb-item" onclick="window.location.href='<?php echo baseUrl('remoworkers-dashboard#messages'); ?>'"><span class="sb-ico">💬</span>Messages<?php if ($unreadMessages > 0): ?><span class="sb-badge"><?php echo $unreadMessages; ?></span><?php endif; ?></div>
  </nav>

  <div class="sb-footer">
    <a href="<?php echo baseUrl('logout'); ?>" style="display:flex;align-items:center;gap:8px;font-size:12.5px;padding:6px 0;color:#f87171;text-decoration:none"><span style="font-size:14px">🚪</span> Log Out</a>
  </div>
</aside>

<main class="main">
  <header class="top-bar" style="background:white;padding:0 20px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
    <button class="mob-toggle" onclick="toggleSidebar()" style="background:none;border:none;font-size:20px;cursor:pointer">☰</button>
    <div class="tb-title" id="page-title" style="font-weight:700">Job Details</div>
    <div class="tb-av" style="width:34px;height:34px;border-radius:50%;background:var(--lime);color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;cursor:pointer;overflow:hidden" onclick="window.location.href='<?php echo baseUrl('remoworkers-dashboard#profile'); ?>'">
      <?php if (!empty($user['avatar_url'])): ?>
        <img src="<?php echo baseUrl($user['avatar_url']); ?>" style="width:100%;height:100%;object-fit:cover">
      <?php else: ?>
        <?php echo strtoupper(substr($user['name'] ?? 'CH', 0, 2)); ?>
      <?php endif; ?>
    </div>
  </header>

  <div class="content">
    <div class="job-detail-page">
      <a class="job-detail-back" href="<?php echo baseUrl('remoworkers-dashboard#find-work'); ?>">← Back to Find Work</a>

      <div style="background:#fff;padding:24px 30px;border:1px solid var(--border);border-radius:12px 12px 0 0;border-bottom:none">
        <div style="display:flex;justify-content:space-between;align-items:start;gap:16px;flex-wrap:wrap">
          <h1 class="job-detail-title" style="margin:0;flex:1"><?php echo htmlspecialchars($currentJob['title']); ?></h1>
          <span id="job-match-badge" style="background:#e8f5e3;color:#14a800;font-size:12px;padding:4px 12px;border-radius:12px;font-weight:600;white-space:nowrap">—% match</span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:20px;color:var(--muted2);font-size:13.5px;margin-top:12px">
          <span style="color:var(--g);font-weight:600" id="job-posted-ago">Posted</span>
          <span>📍 <?php echo htmlspecialchars($currentJob['client_country'] ?: 'Remote'); ?></span>
        </div>
      </div>

      <div class="job-detail-grid">
        <div class="job-detail-main">
          <div class="job-detail-desc"><?php echo htmlspecialchars($currentJob['description'] ?: 'No description provided.'); ?></div>

          <?php if (!empty($skills)): ?>
          <div style="border-top:1px solid var(--border);padding-top:25px;margin-bottom:30px">
            <h4 style="margin-bottom:15px;font-size:15px;font-weight:700">Skills and Expertise</h4>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <?php foreach ($skills as $sk): ?>
                <span class="badge b-gray" style="padding:6px 14px;font-size:12.5px;background:#f3f4f6;border:none;border-radius:20px;font-weight:500;color:var(--dark)"><?php echo htmlspecialchars($sk); ?></span>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div style="background:#f8fafc;padding:20px;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:15px">
            <div style="display:flex;justify-content:space-between;margin-bottom:12px">
              <span style="font-weight:600;color:var(--dark)">Connects Required</span>
              <span style="font-weight:700"><?php echo $hasInvite ? '0 (Invited)' : Auth::CONNECTS_PER_APPLICATION; ?> Connects</span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="font-weight:600;color:var(--dark)">Your Connects</span>
              <span style="color:var(--g);font-weight:700" id="job-page-connects"><?php echo (int)($user['connects'] ?? 0); ?> Connects</span>
            </div>
          </div>

          <div style="background:#f8fafc;padding:20px;border-radius:12px;border:1px solid #e2e8f0">
            <div style="font-weight:700;font-size:13.5px;color:var(--dark);margin-bottom:12px">Activity on this job</div>
            <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:13px">
              <span style="color:var(--muted2)">Proposals:</span>
              <span style="font-weight:600;color:var(--dark)"><?php echo ((int)($currentJob['proposal_count'] ?? 0) + 5); ?>+</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px">
              <span style="color:var(--muted2)">Hired:</span>
              <span style="font-weight:600;color:var(--dark)"><?php echo (int)($currentJob['project_hires'] ?? 0); ?> freelancer<?php echo ((int)($currentJob['project_hires'] ?? 0) === 1) ? '' : 's'; ?></span>
            </div>
          </div>

          <div class="job-apply-panel" id="job-apply-section">
            <h3 style="font-size:18px;font-weight:700;margin-bottom:20px">Submit Proposal</h3>
            <?php if ($userProposal): ?>
              <div style="background:#f0f7ef;color:#14a800;padding:16px 20px;border-radius:8px;border:1px solid #d4e8d4;font-size:14px">
                You already submitted a proposal for this job (status: <strong><?php echo htmlspecialchars($userProposal['status'] ?? 'pending'); ?></strong>).
                <a href="<?php echo baseUrl('remoworkers-dashboard#proposals'); ?>" style="color:#14a800;font-weight:600;margin-left:6px">View My Proposals →</a>
              </div>
            <?php else: ?>
              <div id="job-apply-banner" style="background:#f0f7ef;color:#14a800;padding:12px 18px;border-radius:8px;font-size:13.5px;margin-bottom:25px;border:1px solid #d4e8d4">
                <?php echo htmlspecialchars($budgetLabel); ?> ·
                <?php echo $hasInvite ? '0 Connects (Invited)' : Auth::CONNECTS_PER_APPLICATION . ' Connects required'; ?> ·
                You have <?php echo (int)($user['connects'] ?? 0); ?>
              </div>

              <div style="margin-bottom:20px">
                <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px"><?php echo htmlspecialchars($rateLabel); ?></label>
                <input type="number" id="prop-rate" value="<?php echo htmlspecialchars((string)$defaultRate); ?>" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px">
              </div>

              <div style="margin-bottom:20px">
                <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Estimated Duration</label>
                <select id="prop-duration" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px;background:#fff">
                  <option value="30">Less than 1 month</option>
                  <option value="90">1 to 3 months</option>
                  <option value="180">3 to 6 months</option>
                  <option value="365">More than 6 months</option>
                </select>
              </div>

              <div style="margin-bottom:20px">
                <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Apply As</label>
                <select id="prop-apply-as" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px;background:#fff">
                  <option value="individual">Individual</option>
                  <?php if ($canApplyAsAgency): ?>
                  <option value="agency">Agency<?php echo !empty($activeAgency['name']) ? ' (' . htmlspecialchars((string)$activeAgency['name']) . ')' : ''; ?></option>
                  <?php endif; ?>
                </select>
                <?php if (!$canApplyAsAgency): ?>
                <div style="margin-top:8px;font-size:12px;color:var(--muted2)">Create or join an agency from Profile to apply as agency.</div>
                <?php endif; ?>
              </div>

              <div style="margin-bottom:20px">
                <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Cover Letter *</label>
                <textarea id="prop-letter" style="width:100%;height:150px;padding:12px;border:1px solid var(--border);border-radius:8px;font-size:14px;line-height:1.6" placeholder="Write your proposal here..."></textarea>
              </div>

              <div style="margin-bottom:30px">
                <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Attachments (portfolio, case study)</label>
                <input type="text" id="prop-attach" placeholder="Paste Figma or portfolio link..." style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px">
              </div>

              <div style="display:flex;gap:12px;flex-wrap:wrap">
                <a href="<?php echo baseUrl('remoworkers-dashboard#find-work'); ?>" class="btn btn-w" style="flex:1;min-width:120px;padding:12px;font-size:14px;text-align:center;text-decoration:none">Cancel</a>
                <button type="button" id="submit-proposal-btn" class="btn btn-g" style="flex:1.5;min-width:160px;padding:12px;font-size:14px;font-weight:700" onclick="submitProposalForm(<?php echo (int)$jobId; ?>)">Submit Proposal →</button>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="job-detail-side">
          <?php if (!$userProposal): ?>
          <button type="button" class="btn btn-g" style="width:100%;padding:14px;font-size:15px;font-weight:700;margin-bottom:12px;border-radius:8px" onclick="document.getElementById('job-apply-section').scrollIntoView({behavior:'smooth'})">Apply Now</button>
          <?php endif; ?>
          <button type="button" id="job-save-btn" class="btn btn-w" style="width:100%;padding:12px;font-size:14px;margin-bottom:25px;border:1px solid var(--border);<?php echo $isSaved ? 'background-color:#10b981;color:#fff;border-color:#10b981;' : ''; ?>" onclick="toggleSaveJob(<?php echo (int)$jobId; ?>, this)"><?php echo $isSaved ? 'Saved' : 'Save Job'; ?></button>

          <div style="margin-bottom:25px">
            <h4 style="font-size:14px;margin-bottom:15px;font-weight:700">About the Client</h4>
            <?php if (!empty($currentJob['client_verified'])): ?>
              <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">Payment verified ✅</div>
            <?php endif; ?>
            <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">
              ★ <?php
                $rating = (float)($currentJob['client_rating'] ?? 0);
                $hires = (int)($currentJob['client_hires'] ?? 0);
                echo ($rating > 0 ? number_format($rating, 1) : 'No reviews') . ' (' . $hires . ' ' . ($hires === 1 ? 'hire' : 'hires') . ')';
              ?>
            </div>
            <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">
              $<?php echo number_format((float)($currentJob['client_total_spent'] ?? 0)); ?>+ total spent
            </div>
            <div style="font-size:13px;color:var(--muted2)">📍 <?php echo htmlspecialchars($currentJob['client_country'] ?: 'Remote'); ?></div>
          </div>

          <div style="border-top:1px solid var(--border);padding-top:20px">
            <h4 style="font-size:14px;margin-bottom:12px;font-weight:700">Job Link</h4>
            <div style="display:flex;gap:8px;align-items:center">
              <input type="text" readonly value="<?php echo htmlspecialchars($publicJobUrl); ?>" style="width:100%;padding:10px 12px;font-size:13px;border:1.5px solid var(--border);border-radius:8px;background:#f9fafb;color:var(--dark);outline:none;font-family:inherit;cursor:pointer" onclick="this.select();document.execCommand('copy');toast('Copied!','Job link copied to clipboard')">
              <button type="button" class="btn btn-w" style="padding:10px;height:38px;border-radius:8px;border:1.5px solid var(--border);flex-shrink:0;cursor:pointer" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($publicJobUrl, ENT_QUOTES); ?>');toast('Copied!','Job link copied to clipboard')" title="Copy Link">📋</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
window.showPage = function(id) {
  window.location.href = BASE_URL + 'remoworkers-dashboard' + (id ? '#' + id : '');
};
window.CURRENT_JOB_ID = <?php echo (int)$jobId; ?>;
window.JOB_PAGE_AUTO_APPLY = <?php echo $autoApply ? 'true' : 'false'; ?>;
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const job = JOBS.find(function(j) { return j.id == CURRENT_JOB_ID; });
  if (job) {
    const badge = document.getElementById('job-match-badge');
    if (badge && typeof getMatchPercentage === 'function') {
      badge.textContent = getMatchPercentage(job) + '% match';
    }
    const posted = document.getElementById('job-posted-ago');
    if (posted && typeof timeAgo === 'function') {
      posted.textContent = 'Posted ' + timeAgo(job.created_at);
    }
  }
  if (JOB_PAGE_AUTO_APPLY) {
    const section = document.getElementById('job-apply-section');
    if (section) section.scrollIntoView({ behavior: 'smooth' });
  }
  const origSubmit = window.submitProposalForm;
  if (typeof origSubmit === 'function') {
    window.submitProposalForm = function(jobId) {
      origSubmit(jobId);
    };
  }
});
</script>
