<?php
/**
 * Homepage featured freelancer cards.
 */

require_once __DIR__ . '/home_categories.php';
require_once __DIR__ . '/home_cache.php';

/** @return list<array{id: string, label: string}> */
function homeTalentFilterOptions(): array
{
    return [
        ['id' => 'all', 'label' => 'All'],
        ['id' => 'dev', 'label' => 'Development'],
        ['id' => 'design', 'label' => 'Design'],
        ['id' => 'marketing', 'label' => 'Marketing'],
        ['id' => 'writing', 'label' => 'Writing'],
        ['id' => 'ai', 'label' => 'AI & ML'],
        ['id' => 'finance', 'label' => 'Finance'],
        ['id' => 'video', 'label' => 'Video'],
    ];
}

/**
 * @return list<array{bg: string, color: string}>
 */
function homeTalentAvatarPalette(): array
{
    return [
        ['bg' => '#d1fae5', 'color' => '#065f46'],
        ['bg' => '#dbeafe', 'color' => '#1e40af'],
        ['bg' => '#fef3c7', 'color' => '#92400e'],
        ['bg' => '#ede9fe', 'color' => '#5b21b6'],
        ['bg' => '#fce7f3', 'color' => '#9d174d'],
        ['bg' => '#ffedd5', 'color' => '#c2410c'],
    ];
}

function homeTalentInitials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) >= 2) {
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
    return mb_strtoupper(mb_substr($name, 0, 2));
}

function homeTalentAvatarStyle(int $userId): array
{
    $palette = homeTalentAvatarPalette();
    return $palette[$userId % count($palette)];
}

/**
 * @param list<string> $skills
 * @return list<string> home category ids
 */
function homeFreelancerCategoryIds(array $skills): array
{
    $map = homeBuildSkillToCategoryMap();
    $ids = [];
    foreach ($skills as $skill) {
        if (!is_string($skill) || trim($skill) === '') {
            continue;
        }
        $key = homeNormalizeSkillKey($skill);
        foreach ($map[$key] ?? [] as $homeId) {
            $ids[$homeId] = true;
        }
    }
    return array_keys($ids);
}

function homeTalentBadgeHtml(?string $badge): string
{
    return match ($badge) {
        'expert_vetted' => '<span class="tc-badge ev">★ Expert Vetted</span>',
        'top_rated_plus' => '<span class="tc-badge tr">✦ Top Rated Plus</span>',
        'top_rated' => '<span class="tc-badge tr">✦ Top Rated</span>',
        'rising_talent' => '<span class="tc-badge rs">↑ Rising Talent</span>',
        default => '',
    };
}

/**
 * @return list<array<string, mixed>>
 */
function getFeaturedFreelancersUncached(int $limit = 12): array
{
    ensureFreelancerSchema();
    $out = [];

    try {
        $db = getDB();
        $sql = "
            SELECT u.*,
                COALESCE(rv.cnt, 0) AS reviews_count,
                COALESCE(rv.avg_rating, 0) AS avg_rating
            FROM users u
            LEFT JOIN (
                SELECT reviewee_id, COUNT(*) AS cnt, AVG(rating) AS avg_rating
                FROM reviews
                GROUP BY reviewee_id
            ) rv ON rv.reviewee_id = u.id
            WHERE u.role = 'freelancer'
              AND u.status = 'active'
              AND (u.deleted_at IS NULL)
              AND (
                  (u.title IS NOT NULL AND TRIM(u.title) <> '')
                  OR (u.skills IS NOT NULL AND u.skills <> '' AND u.skills <> '[]')
              )
            ORDER BY u.is_verified DESC, rv.cnt DESC, rv.avg_rating DESC, u.created_at DESC
            LIMIT " . (int) $limit;
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $usersById = [];
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $ids[] = $id;
            $usersById[$id] = $row;
        }
        $statsById = getFreelancerStatsBatch($ids, $usersById);

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $stats = $statsById[$id] ?? [];
            $skills = !empty($row['skills']) ? json_decode($row['skills'], true) : [];
            if (!is_array($skills)) {
                $skills = [];
            }
            $skills = array_values(array_filter($skills, static fn ($s) => is_string($s) && trim($s) !== ''));
            $categoryIds = homeFreelancerCategoryIds($skills);
            $avatar = homeTalentAvatarStyle($id);
            $hourly = (float) ($row['hourly_rate'] ?? 0);
            $profileUrl = baseUrl('f/' . encodeFreelancerId($id));

            $out[] = [
                'id' => $id,
                'name' => (string) ($row['name'] ?? 'Freelancer'),
                'title' => trim((string) ($row['title'] ?? '')),
                'role' => trim((string) ($row['title'] ?? '')) ?: 'Freelancer',
                'avatar_url' => publicAvatarUrl($row['avatar_url'] ?? null),
                'initials' => homeTalentInitials((string) ($row['name'] ?? 'F')),
                'avatar_bg' => $avatar['bg'],
                'avatar_color' => $avatar['color'],
                'profile_url' => $profileUrl,
                'rating' => $stats['rating'] ?? '0.0',
                'reviews_count' => (int) ($stats['reviews_count'] ?? 0),
                'jss' => $stats['jss'] ?? 'N/A',
                'badge' => $stats['badge'] ?? null,
                'badge_html' => homeTalentBadgeHtml($stats['badge'] ?? null),
                'skills' => array_slice($skills, 0, 4),
                'hourly_rate' => $hourly,
                'hourly_label' => $hourly > 0 ? '$' . number_format($hourly, 0) : '—',
                'category_ids' => $categoryIds,
                'category_attr' => implode(',', $categoryIds),
            ];
        }
    } catch (Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Featured talent load failed: ' . $e->getMessage());
        }
    }

    return $out;
}

function getFeaturedFreelancers(int $limit = 12): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cacheKey = 'featured_' . $limit;
    $fromFile = homeCacheGet($cacheKey, 300);
    if (is_array($fromFile) && isset($fromFile['items']) && is_array($fromFile['items'])) {
        $cached = $fromFile['items'];
        return $cached;
    }

    $out = getFeaturedFreelancersUncached($limit);
    homeCacheSet($cacheKey, ['items' => $out, 'built_at' => time()]);
    $cached = $out;
    return $out;
}

function renderHomeTalentCard(array $t): string
{
    $profileUrl = htmlspecialchars($t['profile_url'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8');
    $role = htmlspecialchars($t['role'], ENT_QUOTES, 'UTF-8');
    $rating = htmlspecialchars((string) $t['rating'], ENT_QUOTES, 'UTF-8');
    $reviews = (int) $t['reviews_count'];
    $jss = htmlspecialchars((string) $t['jss'], ENT_QUOTES, 'UTF-8');
    $hourlyVal = (float) ($t['hourly_rate'] ?? 0);
    $catAttr = htmlspecialchars($t['category_attr'] ?? '', ENT_QUOTES, 'UTF-8');

    $avatarHtml = '';
    if (!empty($t['avatar_url'])) {
        $img = htmlspecialchars($t['avatar_url'], ENT_QUOTES, 'UTF-8');
        $avatarHtml = '<img src="' . $img . '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    } else {
        $avatarHtml = htmlspecialchars($t['initials'], ENT_QUOTES, 'UTF-8');
    }

    $tags = '';
    foreach ($t['skills'] as $skill) {
        $tags .= '<span class="tc-tag">' . htmlspecialchars($skill, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    return '<div class="tc" data-talent-categories="' . $catAttr . '" data-profile-url="' . $profileUrl . '" role="link" tabindex="0">'
        . '<span class="tc-save" onclick="toggleSave(this,event)" aria-hidden="true">♡</span>'
        . '<div class="tc-top">'
        . '<div class="tc-av" style="background:' . htmlspecialchars($t['avatar_bg'], ENT_QUOTES, 'UTF-8') . ';color:' . htmlspecialchars($t['avatar_color'], ENT_QUOTES, 'UTF-8') . '">' . $avatarHtml . '</div>'
        . ($t['badge_html'] ?? '')
        . '</div>'
        . '<div class="tc-name">' . $name . '</div>'
        . '<div class="tc-role">' . $role . '</div>'
        . '<div class="tc-row">'
        . '<div class="tc-m"><span class="tc-mv">★ ' . $rating . '</span><span class="tc-ml">Rating</span></div>'
        . '<div class="tc-m"><span class="tc-mv">' . number_format($reviews) . '</span><span class="tc-ml">Reviews</span></div>'
        . '<div class="tc-m"><span class="tc-mv">' . $jss . '</span><span class="tc-ml">Job Success</span></div>'
        . '</div>'
        . '<div class="tc-div"></div>'
        . '<div class="tc-tags">' . $tags . '</div>'
        . '<div class="tc-ft">'
        . ($hourlyVal > 0
            ? '<span class="tc-rate">$' . number_format($hourlyVal, 0) . '<span>/hr</span></span>'
            : '<span class="tc-rate">—</span>')
        . '<a class="tc-btn" href="' . $profileUrl . '" onclick="event.stopPropagation()">View Profile</a>'
        . '</div>'
        . '</div>';
}
