<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/home_featured_talent.php';

ensureFreelancerSchema();
$db = getDB();
$searchQuery = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));

// Only identity-verified freelancers.
$sql = "
    SELECT
        id,
        name,
        title,
        hourly_rate,
        country,
        avatar_url,
        created_at
    FROM users
    WHERE role = 'freelancer'
      AND status = 'active'
      AND is_verified = 1
      AND deleted_at IS NULL
";
$params = [];
if ($searchQuery !== '') {
    $likeParam = '%' . $searchQuery . '%';
    $sql .= " AND (name LIKE ? OR title LIKE ? OR country LIKE ?)";
    $params[] = $likeParam;
    $params[] = $likeParam;
    $params[] = $likeParam;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$freelancers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Pre-compute stats in chunks to keep SQL bounded (IN (...) size).
$ids = [];
$usersById = [];
foreach ($freelancers as $u) {
    $fid = (int) ($u['id'] ?? 0);
    if ($fid <= 0) continue;
    $ids[] = $fid;
    $usersById[$fid] = $u;
}
$statsById = [];
if ($ids !== []) {
    $chunkSize = 50;
    foreach (array_chunk($ids, $chunkSize) as $chunk) {
        $chunkStats = getFreelancerStatsBatch($chunk, $usersById);
        foreach ($chunkStats as $fid => $stats) {
            $statsById[(int) $fid] = $stats;
        }
    }
}

$seoMeta = [
    'title' => 'Find Talent' . ($searchQuery !== '' ? (' for "' . $searchQuery . '"') : '') . ' | Remoworkers',
    'description' => 'Browse verified freelancers and view their public profiles' . ($searchQuery !== '' ? (' matching "' . $searchQuery . '"') : '') . '.',
    'canonical' => baseUrl('talents'),
];

include __DIR__ . '/includes/header.php';
?>

<style>
.talents-page-wrap { max-width: 1140px; margin: 0 auto; padding: 0 20px 60px; }
.talents-page-head { margin: 36px 0 22px; }
.talents-page-title {
  font-family: "Instrument Serif", serif;
  font-size: 44px;
  line-height: 1.1;
  margin: 0 0 8px;
}
.talents-page-subtitle { margin: 0; color: #4b5563; font-size: 15px; }
.talents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}
.talent-card {
  display: block;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 18px;
  text-decoration: none;
  color: inherit;
  transition: box-shadow .2s ease, border-color .2s ease, transform .2s ease;
}
.talent-card:hover {
  border-color: #d1d5db;
  box-shadow: 0 10px 22px rgba(17, 24, 39, .08);
  transform: translateY(-2px);
}
.talent-card-top { display:flex; gap: 14px; align-items: center; margin-bottom: 10px; }
.talent-avatar {
  width: 54px; height: 54px;
  border-radius: 50%;
  background: #d1fae5;
  display:flex; align-items:center; justify-content:center;
  font-weight: 800; font-size: 16px;
  flex-shrink: 0;
  overflow: hidden;
}
.talent-avatar img { width: 100%; height: 100%; object-fit: cover; }
.talent-name { font-size: 18px; font-weight: 700; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.talent-title { font-size: 13px; color: #16a34a; font-weight: 700; margin-top: 2px; }
.talent-meta { display:flex; flex-wrap: wrap; gap: 8px; color: #6b7280; font-size: 12px; margin-bottom: 12px; }
.talent-chip { background: #f3f4f6; border-radius: 999px; padding: 5px 10px; font-weight: 700; }
.talent-badges { display:flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.talent-badge {
  font-size: 11px; font-weight: 800;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(20, 168, 0, 0.08);
  color: #14a800;
}
.talent-footer { display:flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 8px; }
.talent-rate { font-weight: 800; }
.talent-btn {
  font-weight: 800;
  font-size: 12px;
  background: #ecfdf5;
  border: 1px solid #34d399;
  color: #0f766e;
  padding: 8px 12px;
  border-radius: 10px;
}
.talents-empty {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 26px;
  text-align: center;
  color: #6b7280;
}
.talents-pagination {
  margin-top: 22px;
  display:flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}
.talents-page-link {
  text-decoration: none;
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  color: #111827;
  background: #fff;
}
.talents-page-link:hover { border-color: #d1d5db; }
.talents-page-link.active {
  background: #ecfdf5;
  border-color: #34d399;
  color: #0f766e;
  font-weight: 800;
}
@media (max-width: 480px) {
  .talents-page-title { font-size: 34px; }
}
</style>

<div class="talents-page-wrap">
  <div class="talents-page-head">
    <h1 class="talents-page-title">Find Talent</h1>
    <p class="talents-page-subtitle">
      <?php echo htmlspecialchars('Browse all identity-verified freelancers and open their public profile' . ($searchQuery !== '' ? (' matching "' . $searchQuery . '"') : '') . '.', ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <?php if (!empty($freelancers)): ?>
      <p class="talents-page-subtitle" style="margin-top:10px;color:#6b7280;">
        Showing <?php echo (int) count($freelancers); ?> verified freelancers<?php echo $searchQuery !== '' ? ' for your search' : ''; ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if (empty($freelancers)): ?>
    <div class="talents-empty">
      <?php echo $searchQuery !== '' ? 'No verified freelancers matched your search. Try a different keyword.' : 'No verified freelancers found at the moment.'; ?>
    </div>
  <?php else: ?>
    <div class="talents-grid">
      <?php foreach ($freelancers as $f):
        $fid = (int) ($f['id'] ?? 0);
        if ($fid <= 0) continue;
        $name = (string) ($f['name'] ?? 'Freelancer');
        $title = (string) ($f['title'] ?? 'Freelancer');
        $avatarUrl = publicAvatarUrl($f['avatar_url'] ?? null);
        if ($avatarUrl === '') {
          $avatarUrl = baseUrl('assets/free-home/images/user/avatar.png');
        }
        $initials = homeTalentInitials($name);
        $profileUrl = baseUrl('f/' . encodeFreelancerId($fid));
        $fStats = $statsById[$fid] ?? [];
        $rating = (string) ($fStats['rating'] ?? '0.0');
        $reviewsCount = (int) ($fStats['reviews_count'] ?? 0);
        $hourly = (float) ($f['hourly_rate'] ?? 0);
        $countryLabel = getCountryName($f['country'] ?? 'Global');
        $badgeHtml = homeTalentBadgeHtml($fStats['badge'] ?? null);
      ?>
        <a class="talent-card" href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="talent-card-top">
            <div class="talent-avatar">
              <?php if ($avatarUrl !== ''): ?>
                <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
              <?php else: ?>
                <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
            </div>
            <div style="min-width:0;flex:1;">
              <div class="talent-name"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="talent-title"><?php echo htmlspecialchars($title ?: 'Freelancer', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>

          <div class="talent-badges">
            <span class="talent-badge">✓ ID Verified</span>
            <?php if (!empty($badgeHtml)): ?>
              <?php echo $badgeHtml; ?>
            <?php endif; ?>
          </div>

          <div class="talent-meta">
            <span class="talent-chip">★ <?php echo htmlspecialchars($rating, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="talent-chip"><?php echo (int) $reviewsCount; ?> reviews</span>
            <span class="talent-chip"><?php echo htmlspecialchars($countryLabel ?: 'Global', ENT_QUOTES, 'UTF-8'); ?></span>
          </div>

          <div class="talent-footer">
            <div class="talent-rate">
              <?php if ($hourly > 0): ?>
                $<?php echo number_format($hourly, 0); ?><span style="font-weight:700;color:#6b7280;">/hr</span>
              <?php else: ?>
                <span style="color:#6b7280;font-weight:800;">Rate hidden</span>
              <?php endif; ?>
            </div>
            <div class="talent-btn">View Profile</div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

