<?php
/**
 * Site-wide SEO settings and meta tag rendering.
 */

/** @return list<string> */
function seoSettingKeys(): array
{
    return [
        'seo_site_title',
        'seo_site_description',
        'seo_site_keywords',
        'seo_home_title',
        'seo_home_description',
        'seo_home_keywords',
        'seo_og_image',
    ];
}

function ensureSeoSettings(): void
{
    static $schemaChecked = false;
    ensurePlatformSettingsTable();
    if ($schemaChecked) {
        return;
    }
    $schemaChecked = true;

    $db = getDB();

    try {
        $col = $db->query("SHOW COLUMNS FROM platform_settings LIKE 'setting_value'")->fetch(PDO::FETCH_ASSOC);
        if ($col && stripos((string) ($col['Type'] ?? ''), 'varchar') !== false) {
            $db->exec('ALTER TABLE platform_settings MODIFY COLUMN setting_value TEXT NOT NULL');
        }
    } catch (PDOException $e) {
        // Table may not exist yet
    }

    $defaults = [
        'seo_site_title' => ['Remoworkers – Where Great Work Gets Done', 'Default site title.'],
        'seo_site_description' => ['Access vetted freelancers across every skill. Post jobs free and hire with payment protection.', 'Default meta description.'],
        'seo_site_keywords' => ['freelance, remote work, hire freelancers, jobs, Remoworkers', 'Default meta keywords.'],
        'seo_home_title' => ['Remoworkers – Where Great Work Gets Done', 'Homepage title.'],
        'seo_home_description' => ['Access 5 million+ vetted professionals. Post your job free — get proposals in hours.', 'Homepage meta description.'],
        'seo_home_keywords' => ['hire freelancers, remote talent, freelance marketplace, post a job', 'Homepage meta keywords.'],
        'seo_og_image' => ['', 'Open Graph image URL.'],
    ];

    $check = $db->prepare('SELECT COUNT(*) FROM platform_settings WHERE setting_key = ?');
    $insert = $db->prepare('INSERT INTO platform_settings (setting_key, setting_value, description) VALUES (?, ?, ?)');
    foreach ($defaults as $key => [$value, $desc]) {
        $check->execute([$key]);
        if ((int) $check->fetchColumn() === 0) {
            $insert->execute([$key, $value, $desc]);
        }
    }
}

/**
 * @return array<string, string>
 */
function getSeoSettings(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    ensureSeoSettings();
    $keys = seoSettingKeys();
    $out = array_fill_keys($keys, '');

    try {
        $db = getDB();
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $db->prepare(
            "SELECT setting_key, setting_value FROM platform_settings WHERE setting_key IN ($placeholders)"
        );
        $stmt->execute($keys);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $k = (string) ($row['setting_key'] ?? '');
            if ($k !== '' && array_key_exists($k, $out)) {
                $out[$k] = (string) ($row['setting_value'] ?? '');
            }
        }
    } catch (PDOException $e) {
        foreach ($keys as $key) {
            $out[$key] = getPlatformSettingString($key, '');
        }
    }

    $cached = $out;
    return $out;
}

/**
 * @param array{title?: string, description?: string, keywords?: string, canonical?: string, og_image?: string} $overrides
 */
function renderSeoMetaTags(array $overrides = [], bool $isHome = false): void
{
    $site = getSeoSettings();

    $title = trim($overrides['title'] ?? '');
    if ($title === '') {
        $title = $isHome
            ? ($site['seo_home_title'] ?: $site['seo_site_title'])
            : $site['seo_site_title'];
    }

    $description = trim($overrides['description'] ?? '');
    if ($description === '') {
        $description = $isHome
            ? ($site['seo_home_description'] ?: $site['seo_site_description'])
            : $site['seo_site_description'];
    }

    $keywords = trim($overrides['keywords'] ?? '');
    if ($keywords === '') {
        $keywords = $isHome
            ? ($site['seo_home_keywords'] ?: $site['seo_site_keywords'])
            : $site['seo_site_keywords'];
    }

    $ogImage = trim($overrides['og_image'] ?? $site['seo_og_image'] ?? '');
    $canonical = trim($overrides['canonical'] ?? '');

    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</title>\n";
    if ($description !== '') {
        echo '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "\">\n";
    }
    if ($keywords !== '') {
        echo '<meta name="keywords" content="' . htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') . "\">\n";
    }
    echo '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "\">\n";
    if ($description !== '') {
        echo '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "\">\n";
    }
    if ($ogImage !== '') {
        echo '<meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . "\">\n";
    }
    if ($canonical !== '') {
        echo '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . "\">\n";
    }
}

/**
 * SEO for a CMS page row.
 *
 * @param array<string, mixed> $page
 * @return array{title: string, description: string, keywords: string, canonical: string}
 */
function seoForCmsPage(array $page): array
{
    $site = getSeoSettings();
    $name = (string) ($page['name'] ?? '');
    $slug = (string) ($page['slug'] ?? '');

    $title = trim((string) ($page['seo_title'] ?? ''));
    if ($title === '') {
        $title = $name !== '' ? $name . ' | Remoworkers' : $site['seo_site_title'];
    }

    $description = trim((string) ($page['seo_description'] ?? ''));
    if ($description === '' && !empty($page['description'])) {
        $plain = trim(strip_tags((string) $page['description']));
        $description = mb_strlen($plain) > 160 ? rtrim(mb_substr($plain, 0, 157)) . '…' : $plain;
    }
    if ($description === '') {
        $description = $site['seo_site_description'];
    }

    $keywords = trim((string) ($page['seo_keywords'] ?? ''));
    if ($keywords === '') {
        $keywords = $site['seo_site_keywords'];
    }

    return [
        'title' => $title,
        'description' => $description,
        'keywords' => $keywords,
        'canonical' => $slug !== '' ? baseUrl('page/' . rawurlencode($slug)) : baseUrl(),
    ];
}
