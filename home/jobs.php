<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/home_categories.php';

ensureFreelancerSchema();
$db = getDB();

function jobsTableHasColumn(PDO $db, string $column): bool
{
    static $columnCache = [];
    if (array_key_exists($column, $columnCache)) {
        return $columnCache[$column];
    }

    $stmt = $db->prepare("
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'jobs'
          AND column_name = ?
        LIMIT 1
    ");
    $stmt->execute([$column]);
    $columnCache[$column] = (bool) $stmt->fetchColumn();
    return $columnCache[$column];
}

$selectLocationPref = jobsTableHasColumn($db, 'location_pref')
    ? 'j.location_pref'
    : "'' AS location_pref";
$selectExperienceLevel = jobsTableHasColumn($db, 'experience_level')
    ? 'j.experience_level'
    : "'' AS experience_level";
$selectDuration = jobsTableHasColumn($db, 'duration')
    ? 'j.duration'
    : "'' AS duration";
$selectSkillsRequired = jobsTableHasColumn($db, 'skills_required')
    ? 'j.skills_required'
    : "'' AS skills_required";
$selectSkills = jobsTableHasColumn($db, 'skills')
    ? 'j.skills'
    : "'' AS skills";

$homeCategorySlug = trim((string) ($_GET['home_category'] ?? ''));
$searchQuery = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$homeCategory = null;
if ($homeCategorySlug !== '') {
    foreach (homeCategoryDefinitions() as $def) {
        if ((string) ($def['id'] ?? '') === $homeCategorySlug) {
            $homeCategory = $def;
            break;
        }
    }
}

$whereParts = [];
$params = [];
if (is_array($homeCategory)) {
    $jobCategories = array_values(array_filter(array_map(static function ($name): string {
        return trim((string) $name);
    }, (array) ($homeCategory['job_categories'] ?? []))));
    if ($jobCategories) {
        $placeholders = implode(',', array_fill(0, count($jobCategories), '?'));
        $whereParts[] = "j.category IN ($placeholders)";
        $params = array_merge($params, $jobCategories);
    }
}

if ($searchQuery !== '') {
    $likeParam = '%' . $searchQuery . '%';
    $searchParts = [
        'j.title LIKE ?',
        'j.description LIKE ?',
        'j.category LIKE ?',
    ];
    $searchParams = [$likeParam, $likeParam, $likeParam];

    if (jobsTableHasColumn($db, 'skills_required')) {
        $searchParts[] = 'j.skills_required LIKE ?';
        $searchParams[] = $likeParam;
    }
    if (jobsTableHasColumn($db, 'skills')) {
        $searchParts[] = 'j.skills LIKE ?';
        $searchParams[] = $likeParam;
    }
    if (jobsTableHasColumn($db, 'experience_level')) {
        $searchParts[] = 'j.experience_level LIKE ?';
        $searchParams[] = $likeParam;
    }

    $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
    $params = array_merge($params, $searchParams);
}

$whereSql = $whereParts ? (' WHERE ' . implode(' AND ', $whereParts)) : '';

$jobsSql = "
    SELECT
        j.id,
        j.title,
        j.description,
        j.budget_type,
        j.budget,
        j.status,
        {$selectLocationPref},
        {$selectExperienceLevel},
        {$selectDuration},
        {$selectSkills},
        {$selectSkillsRequired},
        j.created_at,
        u.name AS client_name,
        u.country AS client_country,
        COALESCE((SELECT COUNT(*) FROM proposals p WHERE p.job_id = j.id), 0) AS proposal_count
    FROM jobs j
    JOIN users u ON u.id = j.client_id
    {$whereSql}
    ORDER BY j.created_at DESC
";
$jobsStmt = $db->prepare($jobsSql);
$jobsStmt->execute($params);
$jobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

function jobListBudgetLabel(array $job): string
{
    $budgetType = (string) ($job['budget_type'] ?? 'fixed');
    $budget = (float) ($job['budget'] ?? 0);

    if ($budgetType === 'hourly') {
        return '$' . number_format($budget) . '/hr';
    }

    if ($budgetType === 'monthly') {
        return '$' . number_format($budget) . '/month';
    }

    return '$' . number_format($budget);
}

function jobListSkills(array $job): array
{
    $raw = trim((string) ($job['skills_required'] ?: $job['skills'] ?: ''));
    if ($raw === '') {
        return [];
    }

    if ($raw[0] === '[') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(static function ($item) {
                return trim((string) $item);
            }, $decoded)));
        }
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

$selectedCategoryName = is_array($homeCategory) ? trim((string) ($homeCategory['name'] ?? '')) : '';
$searchLabel = $searchQuery !== '' ? (' for "' . $searchQuery . '"') : '';
$seoMeta = [
    'title' => ($selectedCategoryName !== '' ? ($selectedCategoryName . ' Jobs') : 'Find Jobs') . $searchLabel . ' | Remoworkers',
    'description' => $selectedCategoryName !== ''
        ? ('Browse ' . $selectedCategoryName . ' jobs on Remoworkers' . ($searchQuery !== '' ? (' matching "' . $searchQuery . '"') : '') . '. Newest opportunities appear first.')
        : ('Browse all jobs on Remoworkers' . ($searchQuery !== '' ? (' matching "' . $searchQuery . '"') : '') . '. Newest opportunities appear first.'),
    'canonical' => baseUrl('jobs'),
];

include __DIR__ . '/includes/header.php';
?>

<style>
.jobs-page-wrap {
  max-width: 1140px;
  margin: 0 auto;
  padding: 0 20px;
}
.jobs-page-head {
  margin-bottom: 24px;
}
.jobs-page-title {
  margin: 0 0 8px;
  font-family: "Instrument Serif", serif;
  font-size: 44px;
  line-height: 1.1;
  color: #121826;
}
.jobs-page-subtitle {
  margin: 0;
  color: #4b5563;
  font-size: 15px;
}
.jobs-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}
.job-row {
  display: block;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 20px;
  text-decoration: none;
  color: inherit;
  transition: box-shadow .2s ease, border-color .2s ease, transform .2s ease;
}
.job-row:hover {
  border-color: #d1d5db;
  box-shadow: 0 10px 22px rgba(17, 24, 39, .08);
  transform: translateY(-2px);
}
.job-row-top {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 10px;
}
.job-row-title {
  margin: 0;
  font-size: 20px;
  line-height: 1.3;
  color: #111827;
}
.job-row-date {
  flex-shrink: 0;
  font-size: 12px;
  color: #6b7280;
}
.job-row-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}
.job-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 600;
  background: #f3f4f6;
  color: #1f2937;
}
.job-chip.status-open { background: #ecfdf3; color: #047857; }
.job-chip.status-in_progress { background: #eff6ff; color: #1d4ed8; }
.job-chip.status-completed,
.job-chip.status-closed { background: #f3f4f6; color: #4b5563; }
.job-chip.proposal-chip { background: #eff6ff; color: #1d4ed8; }
.job-row-desc {
  margin: 0 0 14px;
  color: #374151;
  font-size: 14px;
  line-height: 1.7;
}
.job-row-bottom {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}
.job-skills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.job-skill {
  font-size: 12px;
  background: rgba(20, 168, 0, 0.08);
  color: #16803c;
  border-radius: 999px;
  padding: 4px 10px;
}
.job-proposals {
  color: #4b5563;
  font-size: 13px;
  font-weight: 600;
}
.jobs-empty {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 36px 24px;
  text-align: center;
  color: #4b5563;
}
@media (max-width: 767px) {
  .jobs-page-title { font-size: 34px; }
  .job-row-title { font-size: 18px; }
}
</style>

<section class="sec cms-page">
  <div class="jobs-page-wrap">
    <div class="jobs-page-head">
      <h1 class="jobs-page-title"><?php echo htmlspecialchars($selectedCategoryName !== '' ? ($selectedCategoryName . ' Jobs') : 'Find a Job', ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="jobs-page-subtitle">
        <?php echo htmlspecialchars($selectedCategoryName !== '' ? ('Showing jobs in ' . $selectedCategoryName . ($searchQuery !== '' ? (' matching "' . $searchQuery . '"') : '') . '. Newest opportunities appear first.') : ('Explore all job postings on Remoworkers' . ($searchQuery !== '' ? (' matching "' . $searchQuery . '"') : '') . '. Newest opportunities appear first.'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
    </div>

    <div class="jobs-grid">
      <?php if (!$jobs): ?>
        <div class="jobs-empty"><?php echo $searchQuery !== '' ? 'No jobs matched your search. Try a different keyword.' : 'No jobs available right now. Please check back soon.'; ?></div>
      <?php else: ?>
        <?php foreach ($jobs as $job): ?>
          <?php
            $jobTitle = trim((string) ($job['title'] ?? 'Untitled Job'));
            $jobDesc = trim(strip_tags((string) ($job['description'] ?? '')));
            if ($jobDesc === '') {
                $jobDesc = 'No description provided yet.';
            }
            if (mb_strlen($jobDesc) > 220) {
                $jobDesc = rtrim(mb_substr($jobDesc, 0, 217)) . '...';
            }
            $skills = array_slice(jobListSkills($job), 0, 4);
            $status = strtolower(trim((string) ($job['status'] ?? 'open')));
            $displayProposalCount = ((int) ($job['proposal_count'] ?? 0)) + 5;
          ?>
          <a class="job-row" href="<?php echo htmlspecialchars(baseUrl('j/' . encodeJobId((int) $job['id'])), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="job-row-top">
              <h2 class="job-row-title"><?php echo htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
              <div class="job-row-date"><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $job['created_at'])), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="job-row-meta">
              <span class="job-chip"><?php echo htmlspecialchars(jobListBudgetLabel($job), ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="job-chip proposal-chip"><?php echo $displayProposalCount; ?>+ proposals</span>
              <span class="job-chip"><?php echo htmlspecialchars((string) ($job['experience_level'] ?: 'Not specified'), ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="job-chip"><?php echo htmlspecialchars((string) ($job['location_pref'] ?: ($job['client_country'] ?: 'Worldwide')), ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="job-chip status-<?php echo htmlspecialchars(str_replace(' ', '_', $status), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </div>

            <p class="job-row-desc"><?php echo htmlspecialchars($jobDesc, ENT_QUOTES, 'UTF-8'); ?></p>

            <div class="job-row-bottom">
              <div class="job-skills">
                <?php foreach ($skills as $skill): ?>
                  <span class="job-skill"><?php echo htmlspecialchars($skill, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endforeach; ?>
              </div>
              <div class="job-proposals"><?php echo $displayProposalCount; ?>+ proposals</div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
