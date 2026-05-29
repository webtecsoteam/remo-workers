<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$freelancerId = (int)($_GET['id'] ?? 0);
if ($freelancerId <= 0) {
    redirect(baseUrl());
}

ensureFreelancerSchema();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'freelancer'");
$stmt->execute([$freelancerId]);
$freelancer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freelancer) {
    http_response_code(404);
    echo "<div style='padding:50px;text-align:center;font-family:sans-serif;'><h2>Freelancer not found</h2><a href='" . htmlspecialchars(baseUrl()) . "'>Go Home</a></div>";
    exit;
}

$stats = getFreelancerStats($freelancerId);
$skills = !empty($freelancer['skills']) ? json_decode($freelancer['skills'], true) : [];
if (!is_array($skills)) {
    $skills = [];
}

$associatedAgency = getActiveAgencyForUser($freelancerId);
$hasAssociatedAgency = !empty($associatedAgency) && (int)($associatedAgency['id'] ?? 0) > 0;
$agencyTotalEarnings = 0.0;
if ($hasAssociatedAgency) {
    $agencyId = (int)($associatedAgency['id'] ?? 0);
    if ($agencyId > 0) {
        $agencyEarnStmt = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0) AS total_earned
            FROM payments p
            INNER JOIN contracts c
                ON c.job_id = p.job_id
               AND c.freelancer_id = p.payee_id
            WHERE c.agency_id = ?
              AND p.status = 'completed'
              AND p.transaction_id NOT LIKE 'ESC-%'
        ");
        $agencyEarnStmt->execute([$agencyId]);
        $agencyTotalEarnings = (float)($agencyEarnStmt->fetchColumn() ?: 0);
        $agencyTotalEarnings += (float)($associatedAgency['agency_earnings_offset'] ?? 0);
        if ($agencyTotalEarnings < 0) {
            $agencyTotalEarnings = 0;
        }
    }
}

$reviewsStmt = $db->prepare("
    SELECT r.*, j.title as job_title, u.name as client_name, u.country as client_country
    FROM reviews r
    JOIN contracts c ON r.contract_id = c.id
    JOIN jobs j ON c.job_id = j.id
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewee_id = ?
    ORDER BY r.created_at DESC
    LIMIT 20
");
$reviewsStmt->execute([$freelancerId]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$viewer = Auth::user();
$publicProfileUrl = baseUrl('f/' . encodeFreelancerId($freelancerId));

$avail = $freelancer['availability'] ?? 'available';
$availLabel = 'Available for work';
if ($avail === 'limited') {
    $availLabel = 'Limited availability';
} elseif ($avail === 'unavailable') {
    $availLabel = 'Not available';
}

function freelancerBadgeHtml($badge) {
    if ($badge === 'expert_vetted') {
        return '<span class="fp-badge fp-purple">⭐ Expert Vetted</span>';
    }
    if ($badge === 'top_rated_plus') {
        return '<span class="fp-badge fp-green">✦ Top Rated Plus</span>';
    }
    if ($badge === 'top_rated') {
        return '<span class="fp-badge fp-green">✦ Top Rated</span>';
    }
    if ($badge === 'rising_talent') {
        return '<span class="fp-badge fp-blue">↑ Rising Talent</span>';
    }
    return '';
}

function formatCompactNumber($value) {
    $number = (float)$value;
    $abs = abs($number);
    if ($abs < 1000) {
        return number_format($number, 0);
    }

    $suffix = 'K';
    $divisor = 1000;
    if ($abs >= 1000000000) {
        $suffix = 'B';
        $divisor = 1000000000;
    } elseif ($abs >= 1000000) {
        $suffix = 'M';
        $divisor = 1000000;
    }

    $short = $number / $divisor;
    $formatted = number_format($short, 1);
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return $formatted . $suffix;
}

include __DIR__ . '/includes/header.php';
?>

<style>
.public-profile-page { max-width: 1000px; margin: 40px auto; padding: 0 20px 60px; font-family: 'Plus Jakarta Sans', sans-serif; }
.fp-header { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 32px; margin-bottom: 24px; }
.fp-header-top { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; }
.fp-avatar { width: 88px; height: 88px; border-radius: 50%; background: #c8f135; color: var(--forest); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 28px; flex-shrink: 0; overflow: hidden; }
.fp-avatar img { width: 100%; height: 100%; object-fit: cover; }
.fp-name { font-family: 'Instrument Serif', serif; font-size: 34px; line-height: 1.1; margin: 0 0 8px; color: var(--text); }
.fp-meta { font-size: 14px; color: var(--muted); margin-bottom: 12px; }
.fp-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.fp-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
.fp-green { background: rgba(20,168,0,0.1); color: #14a800; }
.fp-blue { background: #ede9fe; color: #5b21b6; }
.fp-purple { background: #f3e8ff; color: #7c3aed; }
.fp-gray { background: #f3f4f6; color: #4b5563; }
.fp-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.fp-stat { background: #f9fafb; border: 1px solid var(--border); border-radius: 10px; padding: 14px; text-align: center; }
.fp-stat-val { font-size: 20px; font-weight: 700; color: var(--text); }
.fp-stat-lbl { font-size: 11px; color: var(--muted); margin-top: 4px; }
.fp-grid { display: grid; grid-template-columns: 1fr 280px; gap: 24px; align-items: start; }
.fp-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
.fp-card-head { padding: 16px 20px; border-bottom: 1px solid var(--border); font-weight: 700; font-size: 15px; }
.fp-card-body { padding: 20px; font-size: 14px; line-height: 1.7; color: var(--text-2); }
.fp-skills { display: flex; flex-wrap: wrap; gap: 8px; }
.fp-skill { background: rgba(20,168,0,0.08); color: #14a800; font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 20px; }
.fp-review { padding: 18px 20px; border-bottom: 1px solid var(--border); }
.fp-review:last-child { border-bottom: none; }
.fp-review-stars { color: #b45309; font-weight: 700; font-size: 13px; }
.fp-side-btn { display: block; width: 100%; text-align: center; margin-bottom: 10px; text-decoration: none; }
@media (max-width: 800px) {
  .fp-grid { grid-template-columns: 1fr; }
  .fp-stats { grid-template-columns: repeat(2, 1fr); }
  .fp-name { font-size: 28px; }
}
</style>

<div class="public-profile-page">
    <div class="fp-header">
        <div class="fp-header-top">
            <div class="fp-avatar">
                <?php if (!empty($freelancer['avatar_url'])): ?>
                    <img src="<?php echo baseUrl($freelancer['avatar_url']); ?>" alt="">
                <?php else: ?>
                    <?php echo strtoupper(substr($freelancer['name'] ?? 'RW', 0, 2)); ?>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
                <h1 class="fp-name"><?php echo htmlspecialchars($freelancer['name']); ?></h1>
                <div class="fp-meta">
                    <?php echo htmlspecialchars($freelancer['title'] ?: 'Freelancer'); ?>
                    · $<?php echo number_format((float)($freelancer['hourly_rate'] ?? 0), 2); ?>/hr
                    · <?php echo htmlspecialchars(getCountryName($freelancer['country'] ?? 'Global')); ?>
                </div>
                <div class="fp-badges">
                    <?php echo freelancerBadgeHtml($stats['badge'] ?? null); ?>
                    <?php if (!empty($freelancer['is_verified'])): ?>
                        <span class="fp-badge fp-green">✓ ID Verified</span>
                    <?php endif; ?>
                    <span class="fp-badge fp-gray"><?php echo htmlspecialchars($availLabel); ?></span>
                </div>
                <div class="fp-stats">
                    <div class="fp-stat">
                        <div class="fp-stat-val">★ <?php echo htmlspecialchars($stats['rating']); ?></div>
                        <div class="fp-stat-lbl"><?php echo (int)$stats['reviews_count']; ?> reviews</div>
                    </div>
                    <div class="fp-stat">
                        <div class="fp-stat-val"><?php echo htmlspecialchars($stats['jss']); ?></div>
                        <div class="fp-stat-lbl">Job success</div>
                    </div>
                    <div class="fp-stat">
                        <div class="fp-stat-val"><?php echo (int)$stats['completed_contracts']; ?></div>
                        <div class="fp-stat-lbl">Jobs completed</div>
                    </div>
                    <div class="fp-stat">
                        <div class="fp-stat-val">$<?php echo formatCompactNumber((float)$stats['total_earned']); ?>+</div>
                        <div class="fp-stat-lbl">Total earned</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="fp-grid">
        <div>
            <div class="fp-card">
                <div class="fp-card-head">About</div>
                <div class="fp-card-body" style="white-space:pre-wrap"><?php
                    echo !empty($freelancer['bio'])
                        ? htmlspecialchars($freelancer['bio'])
                        : 'This freelancer has not added a profile overview yet.';
                ?></div>
            </div>

            <?php if (!empty($skills)): ?>
            <div class="fp-card">
                <div class="fp-card-head">Skills & Expertise</div>
                <div class="fp-card-body">
                    <div class="fp-skills">
                        <?php foreach ($skills as $sk): ?>
                            <span class="fp-skill"><?php echo htmlspecialchars((string)$sk); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="fp-card">
                <div class="fp-card-head">Work history & reviews (<?php echo count($reviews); ?>)</div>
                <?php if (empty($reviews)): ?>
                    <div class="fp-card-body" style="text-align:center;color:var(--muted)">No client reviews yet.</div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev):
                        $stars = str_repeat('★', (int)round($rev['rating'])) . str_repeat('☆', 5 - (int)round($rev['rating']));
                    ?>
                    <div class="fp-review">
                        <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:8px">
                            <strong style="font-size:14px"><?php echo htmlspecialchars($rev['job_title']); ?></strong>
                            <span class="fp-review-stars"><?php echo $stars; ?> <?php echo number_format((float)$rev['rating'], 1); ?></span>
                        </div>
                        <p style="margin:0 0 8px;font-style:italic;color:var(--text-2)">"<?php echo htmlspecialchars($rev['feedback'] ?: 'No comment provided.'); ?>"</p>
                        <div style="font-size:12px;color:var(--muted)">
                            <?php echo htmlspecialchars($rev['client_name']); ?>
                            · <?php echo date('M j, Y', strtotime($rev['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="fp-card">
                <div class="fp-card-head">Hire on RemoWorkers</div>
                <div class="fp-card-body">
                    <?php if ($viewer && ($viewer['role'] ?? '') === 'client'): ?>
                        <a href="<?php echo baseUrl('client#messages'); ?>" class="btn btn-green btn-full fp-side-btn">Message via dashboard</a>
                        <p style="font-size:12px;color:var(--muted);text-align:center;margin:0">Post a job or send a direct invite from your client dashboard.</p>
                    <?php elseif ($viewer && ($viewer['role'] ?? '') === 'freelancer'): ?>
                        <p style="font-size:13px;color:var(--muted);margin:0 0 12px">You are viewing a public freelancer profile.</p>
                        <a href="<?php echo baseUrl('remoworkers-dashboard#profile'); ?>" class="btn btn-outline btn-full fp-side-btn">My profile</a>
                    <?php else: ?>
                        <a href="<?php echo baseUrl('?show_login=1'); ?>" class="btn btn-green btn-full fp-side-btn">Sign in to hire</a>
                        <a href="<?php echo baseUrl('client'); ?>" class="btn btn-outline btn-full fp-side-btn">Post a job</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="fp-card">
                <div class="fp-card-head">Profile link</div>
                <div class="fp-card-body">
                    <input type="text" readonly value="<?php echo htmlspecialchars($publicProfileUrl); ?>" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:12px;background:#f9fafb;margin-bottom:8px" onclick="this.select();document.execCommand('copy');showToast('Copied','Profile link copied')">
                    <p style="font-size:11px;color:var(--muted);margin:0;text-align:center">Click to copy · share with clients</p>
                </div>
            </div>

            <div class="fp-card">
                <div class="fp-card-head">Details</div>
                <div class="fp-card-body" style="display:flex;flex-direction:column;gap:10px;font-size:13px">
                    <?php if ($hasAssociatedAgency): ?>
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <span style="color:var(--muted)">Associated agency</span>
                        <strong style="text-align:right"><?php echo htmlspecialchars((string)$associatedAgency['name']); ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <span style="color:var(--muted)">Complete agency earnings</span>
                        <strong>$<?php echo formatCompactNumber((float)$agencyTotalEarnings); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between"><span style="color:var(--muted)">Hourly rate</span><strong>$<?php echo number_format((float)($freelancer['hourly_rate'] ?? 0), 2); ?>/hr</strong></div>
                    <div style="display:flex;justify-content:space-between"><span style="color:var(--muted)">Location</span><span><?php echo htmlspecialchars(getCountryName($freelancer['country'] ?? 'Global')); ?></span></div>
                    <div style="display:flex;justify-content:space-between"><span style="color:var(--muted)">Member since</span><span><?php echo date('F Y', strtotime($freelancer['created_at'])); ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
