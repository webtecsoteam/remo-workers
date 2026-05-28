<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$jobId = $_GET['id'] ?? 0;
if (!$jobId) {
    redirect(baseUrl());
}

ensureFreelancerSchema();
$db = getDB();
$stmt = $db->prepare("
    SELECT j.*, u.name as client_name, u.country as client_country, u.is_verified as client_verified, u.created_at as client_since,
    COALESCE((SELECT SUM(amount) FROM payments WHERE payer_id = j.client_id AND status = 'completed'), 0) + COALESCE(u.admin_spent_offset, 0) as client_total_spent,
    COALESCE((SELECT COUNT(*) FROM contracts WHERE client_id = j.client_id), 0) + COALESCE(u.admin_hires_offset, 0) as client_hires,
    COALESCE((SELECT COUNT(*) FROM proposals WHERE job_id = j.id), 0) as proposal_count,
    COALESCE((SELECT AVG(rating) FROM reviews WHERE reviewee_id = j.client_id), 0.0) as client_rating
    FROM jobs j
    JOIN users u ON j.client_id = u.id
    WHERE j.id = ?
");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    http_response_code(404);
    echo "<div style='padding:50px;text-align:center;font-family:sans-serif;'><h2>Job not found</h2><a href='" . baseUrl() . "'>Go Home</a></div>";
    exit;
}

$user = Auth::user();
$displayProposalCount = ((int) ($job['proposal_count'] ?? 0)) + 5;

if (isset($_GET['apply_login'])) {
    $_SESSION['redirect_to'] = 'remoworkers-dashboard/j/' . encodeJobId($jobId) . '?apply=1';
    redirect(baseUrl('?show_login=1'));
}

$jobDescPlain = trim(strip_tags((string) ($job['description'] ?? '')));
$seoMeta = [
    'title' => trim((string) ($job['title'] ?? '')) . ' | Remoworkers',
    'description' => $jobDescPlain !== '' ? (mb_strlen($jobDescPlain) > 160 ? rtrim(mb_substr($jobDescPlain, 0, 157)) . '…' : $jobDescPlain) : '',
    'canonical' => baseUrl('j/' . encodeJobId((int) $job['id'])),
];

include __DIR__ . '/includes/header.php';
?>

<style>
.public-job-page { max-width: 1000px; margin: 40px auto; padding: 0 20px; font-family: 'Plus Jakarta Sans', sans-serif; display: grid; grid-template-columns: 1fr 300px; gap: 30px; align-items: start; }
.job-main { background: #fff; padding: 40px; border-radius: 16px; border: 1px solid var(--border); }
.job-sidebar { background: #fff; padding: 30px; border-radius: 16px; border: 1px solid var(--border); }
.job-title { font-family: 'Instrument Serif', serif; font-size: 38px; color: var(--text); line-height: 1.1; margin-bottom: 20px; }
.job-meta-top { display: flex; gap: 15px; font-size: 13px; color: var(--muted); margin-bottom: 30px; flex-wrap: wrap; }
.job-meta-badge { background: #f3f4f6; padding: 4px 12px; border-radius: 20px; color: var(--text); font-weight: 500; }
.job-desc { font-size: 15px; line-height: 1.8; color: var(--text-2); margin-bottom: 40px; white-space: pre-wrap; }
.job-skills { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 30px; }
.sk-tag { background: rgba(20,168,0,0.08); color: #14a800; font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 20px; }

.side-sect { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
.side-sect:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.client-stat { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px; }
.client-stat-ico { font-size: 18px; }
.client-stat-text h4 { font-size: 14px; margin-bottom: 2px; color: var(--text); }
.client-stat-text p { font-size: 12px; color: var(--muted); }

@media (max-width: 800px) {
  .public-job-page { grid-template-columns: 1fr; }
  .job-title { font-size: 30px; }
}
</style>

<div class="public-job-page">
    <div class="job-main">
        <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
        <div class="job-meta-top">
            <span class="job-meta-badge">Posted <?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
            <span class="job-meta-badge">🌍 <?php echo $job['location_pref'] ?? 'Worldwide'; ?></span>
            <span class="job-meta-badge"><?php echo $job['experience_level'] ?? 'Expert'; ?> Level</span>
        </div>
        
        <div style="display:flex;gap:40px;margin-bottom:30px;padding-bottom:30px;border-bottom:1px solid var(--border)">
            <div>
                <div style="font-size:12px;color:var(--muted);margin-bottom:5px">Project Type</div>
                <div style="font-weight:600;font-size:15px;display:flex;align-items:center;gap:6px">
                    <?php echo $job['budget_type'] === 'fixed' ? '💰 Fixed Price' : '⏱️ Hourly'; ?>
                </div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--muted);margin-bottom:5px">Budget</div>
                <div style="font-weight:600;font-size:15px">
                    <?php echo $job['budget_type'] === 'fixed' ? '$' . number_format($job['budget']) : '$' . number_format($job['budget']) . '/hr'; ?>
                </div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--muted);margin-bottom:5px">Duration</div>
                <div style="font-weight:600;font-size:15px">
                    <?php echo $job['duration'] ?? 'Less than 1 month'; ?>
                </div>
            </div>
        </div>

        <?php if ($user): ?>
        <p style="margin-bottom:20px"><a href="<?php echo $user['role'] === 'freelancer' ? baseUrl('remoworkers-dashboard/j/' . encodeJobId($job['id'])) : baseUrl('j/' . encodeJobId($job['id'])); ?>" style="font-size:13px;color:var(--g);font-weight:600;text-decoration:none">View full job page →</a></p>
        <?php endif; ?>

        <div class="job-desc"><?php echo htmlspecialchars($job['description']); ?></div>

        <h4 style="font-size:15px;margin-bottom:12px">Required Skills</h4>
        <div class="job-skills">
            <?php 
            $skills = explode(',', $job['skills'] ?? 'Web Development,React,PHP');
            foreach($skills as $sk): 
            ?>
            <span class="sk-tag"><?php echo trim(htmlspecialchars($sk)); ?></span>
            <?php endforeach; ?>
        </div>

        <div style="padding-top:20px;border-top:1px solid var(--border);margin-top:20px;">
            <div style="font-size:13px;color:var(--muted);margin-bottom:10px">Activity on this job</div>
            <div style="font-size:14px;color:var(--text)">Proposals: <strong><?php echo $displayProposalCount; ?>+</strong></div>
        </div>
    </div>

    <div class="job-sidebar">
        <div class="side-sect" style="text-align:center">
            <?php if ($user && $user['role'] === 'freelancer'): ?>
                <a href="<?php echo baseUrl('remoworkers-dashboard/j/' . encodeJobId($job['id']) . '?apply=1'); ?>" class="btn btn-green btn-full btn-lg" style="margin-bottom:10px;font-size:15px">Apply Now</a>
            <?php elseif ($user && $user['role'] === 'client'): ?>
                <div style="background:#f3f4f6;color:var(--muted);padding:12px;border-radius:8px;font-size:13px">You are logged in as a Client. To apply, log in as a Freelancer.</div>
            <?php else: ?>
                <a href="<?php echo baseUrl('j/' . encodeJobId($job['id']) . '?apply_login=1'); ?>" class="btn btn-green btn-full btn-lg" style="margin-bottom:10px;font-size:15px">Apply Now</a>
                <p style="font-size:12px;color:var(--muted)">You'll be asked to sign in or create a free account.</p>
            <?php endif; ?>
        </div>

        <div class="side-sect">
            <h3 style="font-size:16px;margin-bottom:18px">About the client</h3>
            
            <div class="client-stat">
                <div class="client-stat-ico">
                    <?php echo $job['client_verified'] ? '<span style="color:#14a800">✓</span>' : 'ℹ️'; ?>
                </div>
                <div class="client-stat-text">
                    <h4>Payment method <?php echo $job['client_verified'] ? 'verified' : 'unverified'; ?></h4>
                    <p>
                        <?php if($job['client_rating'] > 0): ?>
                        <span style="color:#eab308">★</span> <?php echo number_format($job['client_rating'], 1); ?> of 5
                        <?php else: ?>
                        No reviews yet
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="client-stat">
                <div class="client-stat-ico">🌍</div>
                <div class="client-stat-text">
                    <h4><?php echo htmlspecialchars($job['client_country'] ?: 'Global'); ?></h4>
                    <p>Client timezone</p>
                </div>
            </div>

            <div class="client-stat">
                <div class="client-stat-ico">💼</div>
                <div class="client-stat-text">
                    <h4><?php echo $job['client_hires']; ?> hires</h4>
                    <p>$<?php echo number_format($job['client_total_spent']); ?> total spent</p>
                </div>
            </div>

            <div style="font-size:12px;color:var(--muted);margin-top:15px;">
                Member since <?php echo date('M Y', strtotime($job['client_since'] ?? $job['created_at'])); ?>
            </div>
        </div>
        
        <div class="side-sect">
            <h3 style="font-size:14px;margin-bottom:10px;color:var(--muted)">Job Link</h3>
            <input type="text" readonly value="<?php echo baseUrl('j/' . encodeJobId($job['id'])); ?>" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#f9fafb;color:var(--muted)" onclick="this.select();document.execCommand('copy');showToast('Copied','Link copied to clipboard');">
            <p style="font-size:11px;color:var(--muted);margin-top:6px;text-align:center">Click to copy</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
