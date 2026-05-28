<?php
/**
 * Homepage "Browse by category" cards with live counts.
 */

require_once __DIR__ . '/skill_tree.php';
require_once __DIR__ . '/home_cache.php';

/**
 * @return list<array<string, mixed>>
 */
function homeCategoryDefinitions(): array
{
    return [
        [
            'id' => 'dev',
            'modal_key' => 'cat-dev',
            'name' => 'Development & IT',
            'subtitle' => 'Web, mobile, cloud',
            'emoji' => '🖥️',
            'is_hot' => true,
            'job_categories' => ['Web, Mobile & Software Dev', 'IT & Networking'],
            'sources' => [
                ['tree' => 'Web, Mobile & Software Dev'],
                ['tree' => 'IT & Networking'],
            ],
        ],
        [
            'id' => 'design',
            'modal_key' => 'cat-design',
            'name' => 'Design & Creative',
            'subtitle' => 'UI, branding, illustration',
            'emoji' => '🎨',
            'is_hot' => false,
            'job_categories' => ['Design & Creative'],
            'sources' => [
                ['tree' => 'Design & Creative', 'exclude_subs' => ['Video & Animation', 'Audio & Music Production']],
            ],
        ],
        [
            'id' => 'ai',
            'modal_key' => 'cat-ai',
            'name' => 'AI & Machine Learning',
            'subtitle' => 'LLMs, data science, MLOps',
            'emoji' => '🤖',
            'is_hot' => true,
            'job_categories' => ['Data Science & Analytics', 'Web, Mobile & Software Dev'],
            'sources' => [
                ['tree' => 'Data Science & Analytics', 'subs' => ['AI & Machine Learning']],
                ['tree' => 'Web, Mobile & Software Dev', 'subs' => ['AI Apps & Integration']],
            ],
        ],
        [
            'id' => 'marketing',
            'modal_key' => 'cat-marketing',
            'name' => 'Sales & Marketing',
            'subtitle' => 'SEO, ads, strategy',
            'emoji' => '📈',
            'is_hot' => false,
            'job_categories' => ['Sales & Marketing'],
            'sources' => [['tree' => 'Sales & Marketing']],
        ],
        [
            'id' => 'writing',
            'modal_key' => 'cat-writing',
            'name' => 'Writing & Translation',
            'subtitle' => 'Content, copy, localization',
            'emoji' => '✍️',
            'is_hot' => false,
            'job_categories' => ['Writing', 'Translation'],
            'sources' => [
                ['tree' => 'Writing'],
                ['tree' => 'Translation'],
            ],
        ],
        [
            'id' => 'finance',
            'modal_key' => 'cat-finance',
            'name' => 'Finance & Accounting',
            'subtitle' => 'Bookkeeping, CFO, tax',
            'emoji' => '🔢',
            'is_hot' => false,
            'job_categories' => ['Accounting & Consulting'],
            'sources' => [['tree' => 'Accounting & Consulting']],
        ],
        [
            'id' => 'data',
            'modal_key' => 'cat-data',
            'name' => 'Data Science',
            'subtitle' => 'Analytics, visualization, BI',
            'emoji' => '📊',
            'is_hot' => false,
            'job_categories' => ['Data Science & Analytics'],
            'sources' => [
                [
                    'tree' => 'Data Science & Analytics',
                    'exclude_subs' => ['AI & Machine Learning'],
                ],
            ],
        ],
        [
            'id' => 'video',
            'modal_key' => 'cat-video',
            'name' => 'Video & Animation',
            'subtitle' => 'Editing, motion, 3D',
            'emoji' => '🎬',
            'is_hot' => false,
            'job_categories' => ['Design & Creative'],
            'sources' => [
                ['tree' => 'Design & Creative', 'subs' => ['Video & Animation']],
            ],
        ],
        [
            'id' => 'audio',
            'modal_key' => 'cat-audio',
            'name' => 'Music & Audio',
            'subtitle' => 'Production, voiceover',
            'emoji' => '🎵',
            'is_hot' => false,
            'job_categories' => ['Design & Creative'],
            'sources' => [
                ['tree' => 'Design & Creative', 'subs' => ['Audio & Music Production']],
            ],
        ],
        [
            'id' => 'legal',
            'modal_key' => 'cat-legal',
            'name' => 'Legal',
            'subtitle' => 'Contracts, IP, compliance',
            'emoji' => '⚖️',
            'is_hot' => false,
            'job_categories' => ['Legal'],
            'sources' => [['tree' => 'Legal']],
        ],
        [
            'id' => 'support',
            'modal_key' => 'cat-support',
            'name' => 'Customer Support',
            'subtitle' => 'Chat, email, CRM',
            'emoji' => '🎧',
            'is_hot' => false,
            'job_categories' => ['Customer Service'],
            'sources' => [['tree' => 'Customer Service']],
        ],
        [
            'id' => 'eng',
            'modal_key' => 'cat-eng',
            'name' => 'Engineering',
            'subtitle' => 'CAD, architecture, civil',
            'emoji' => '🏗️',
            'is_hot' => false,
            'job_categories' => ['Engineering & Architecture'],
            'sources' => [['tree' => 'Engineering & Architecture']],
        ],
    ];
}

function homeNormalizeSkillKey(string $skill): string
{
    return mb_strtolower(trim($skill));
}

function homeCategoryImageUrl(?string $image): ?string
{
    $image = trim((string) $image);
    if ($image === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    return baseUrl(ltrim($image, '/'));
}

/**
 * @return array<string, string> job category name => image url
 */
function homeJobCategoryImageMap(): array
{
    $map = [];
    foreach (getJobCategories(true) as $jobCategory) {
        $name = trim((string) ($jobCategory['name'] ?? ''));
        $imageUrl = homeCategoryImageUrl($jobCategory['image'] ?? null);
        if ($name === '' || $imageUrl === null) {
            continue;
        }
        $map[$name] = $imageUrl;
    }
    return $map;
}

/**
 * @return array<string, list<string>> skill_key => home category ids
 */
function homeBuildSkillToCategoryMap(): array
{
    $map = [];
    foreach (homeCategoryDefinitions() as $def) {
        $taxonomy = skillTreeCollectSpecialties($def['sources']);
        foreach ($taxonomy as $skill) {
            $key = homeNormalizeSkillKey($skill);
            if ($key === '') {
                continue;
            }
            if (!isset($map[$key])) {
                $map[$key] = [];
            }
            if (!in_array($def['id'], $map[$key], true)) {
                $map[$key][] = $def['id'];
            }
        }
    }
    return $map;
}

/**
 * @return array<string, string> job category name => home category id
 */
function homeJobCategoryToHomeIdMap(): array
{
    $map = [];
    foreach (homeCategoryDefinitions() as $def) {
        foreach ($def['job_categories'] as $jobCat) {
            $map[$jobCat] = $def['id'];
        }
    }
    return $map;
}

/**
 * @return list<array<string, mixed>>
 */
function getHomeCategoriesWithCountsUncached(): array
{
    $tree = getSkillTree();
    $definitions = homeCategoryDefinitions();
    $skillMap = homeBuildSkillToCategoryMap();
    $jobCatMap = homeJobCategoryToHomeIdMap();
    $jobCategoryImageMap = homeJobCategoryImageMap();

    $stats = [];
    foreach ($definitions as $def) {
        $taxonomySkills = skillTreeCollectSpecialties($def['sources'], $tree);
        $stats[$def['id']] = [
            'taxonomy_skills' => array_fill_keys(array_map('homeNormalizeSkillKey', $taxonomySkills), true),
            'platform_skills' => [],
            'open_jobs' => 0,
            'freelancers' => 0,
        ];
    }

    try {
        $db = getDB();

        $userStmt = $db->query(
            "SELECT skills FROM users WHERE role = 'freelancer' AND skills IS NOT NULL AND skills != '' AND skills != '[]'"
        );
        while ($row = $userStmt->fetch(PDO::FETCH_ASSOC)) {
            $skills = json_decode($row['skills'] ?? '[]', true);
            if (!is_array($skills)) {
                continue;
            }
            $matchedCategories = [];
            foreach ($skills as $skill) {
                if (!is_string($skill) || trim($skill) === '') {
                    continue;
                }
                $key = homeNormalizeSkillKey($skill);
                foreach ($skillMap[$key] ?? [] as $homeId) {
                    $matchedCategories[$homeId] = true;
                    $stats[$homeId]['platform_skills'][$key] = true;
                }
            }
            foreach (array_keys($matchedCategories) as $homeId) {
                $stats[$homeId]['freelancers']++;
            }
        }

        $jobStmt = $db->query(
            "SELECT category, skills_required FROM jobs WHERE status IN ('open', 'in_progress')"
        );
        while ($row = $jobStmt->fetch(PDO::FETCH_ASSOC)) {
            $homeId = $jobCatMap[$row['category'] ?? ''] ?? null;
            if ($homeId && isset($stats[$homeId])) {
                $stats[$homeId]['open_jobs']++;
            }

            $jobSkills = json_decode($row['skills_required'] ?? '[]', true);
            if (!is_array($jobSkills)) {
                continue;
            }
            foreach ($jobSkills as $skill) {
                if (!is_string($skill) || trim($skill) === '') {
                    continue;
                }
                $key = homeNormalizeSkillKey($skill);
                foreach ($skillMap[$key] ?? [] as $skillHomeId) {
                    if (isset($stats[$skillHomeId])) {
                        $stats[$skillHomeId]['platform_skills'][$key] = true;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Home category counts: ' . $e->getMessage());
        }
    }

    $out = [];
    foreach ($definitions as $def) {
        $id = $def['id'];
        $s = $stats[$id];
        $allSkillKeys = array_unique(array_merge(
            array_keys($s['taxonomy_skills']),
            array_keys($s['platform_skills'])
        ));
        $skillsCount = count(array_filter($allSkillKeys, static fn ($k) => $k !== ''));
        $imageUrl = null;
        foreach ($def['job_categories'] as $jobCategoryName) {
            if (isset($jobCategoryImageMap[$jobCategoryName])) {
                $imageUrl = $jobCategoryImageMap[$jobCategoryName];
                break;
            }
        }

        $out[] = array_merge($def, [
            'skills_count' => $skillsCount,
            'skills_label' => homeFormatSkillsCount($skillsCount),
            'open_jobs_count' => $s['open_jobs'],
            'freelancers_count' => $s['freelancers'],
            'image_url' => $imageUrl,
        ]);
    }

    return $out;
}

function getHomeCategoriesWithCounts(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $fromFile = homeCacheGet('categories', 300);
    if (is_array($fromFile) && isset($fromFile['items']) && is_array($fromFile['items'])) {
        $cached = $fromFile['items'];
        return $cached;
    }

    $out = getHomeCategoriesWithCountsUncached();
    homeCacheSet('categories', ['items' => $out, 'built_at' => time()]);
    $cached = $out;
    return $out;
}

function homeFormatSkillsCount(int $count): string
{
    return number_format(max(0, $count)) . ' skill' . ($count === 1 ? '' : 's');
}
