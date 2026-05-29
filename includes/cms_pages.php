<?php
/**
 * CMS pages (footer links & static content).
 */

require_once __DIR__ . '/cms_builtin_pages.php';

function ensureCmsPagesTable(): void
{
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS cms_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description LONGTEXT NULL,
        footer_section ENUM('clients', 'talent', 'resources', 'company', 'legal') NULL,
        link_type ENUM('content', 'modal', 'external') NOT NULL DEFAULT 'content',
        link_target VARCHAR(500) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        show_in_footer TINYINT(1) NOT NULL DEFAULT 1,
        status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
        seo_title VARCHAR(255) NULL,
        seo_description VARCHAR(500) NULL,
        seo_keywords VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_cms_pages_slug (slug),
        KEY idx_cms_pages_footer (footer_section, show_in_footer, status, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Insert built-in pages into cms_pages when missing (does not overwrite existing rows).
 *
 * @return array{inserted: int, skipped: int, total: int}
 */
function cmsSyncBuiltinPagesToDatabase(?PDO $db = null): array
{
    ensureCmsPagesTable();
    $db = $db ?? getDB();
    $catalog = cmsBuiltinPageCatalog();
    $check = $db->prepare('SELECT id FROM cms_pages WHERE slug = ? LIMIT 1');
    $insert = $db->prepare(
        'INSERT INTO cms_pages (name, slug, description, footer_section, link_type, link_target, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
         VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)'
    );

    $sectionSort = array_fill_keys(cmsFooterSectionOptions(), 0);
    $inserted = 0;
    $skipped = 0;

    foreach ($catalog as $slug => $page) {
        $check->execute([$slug]);
        if ($check->fetch()) {
            $skipped++;
            continue;
        }

        $section = $page['footer_section'] ?? null;
        if ($section !== null && isset($sectionSort[$section])) {
            $sectionSort[$section] += 10;
            $sortOrder = $sectionSort[$section];
        } else {
            $sortOrder = (int) ($page['sort_order'] ?? 0);
        }

        $showInFooter = ($page['show_in_footer'] ?? true) && $section !== null ? 1 : 0;

        $insert->execute([
            (string) ($page['name'] ?? ''),
            $slug,
            (string) ($page['description'] ?? ''),
            $section,
            (string) ($page['link_type'] ?? 'content'),
            $sortOrder,
            $showInFooter,
            (string) ($page['status'] ?? 'published'),
            (string) ($page['seo_title'] ?? ''),
            (string) ($page['seo_description'] ?? ''),
            (string) ($page['seo_keywords'] ?? ''),
        ]);
        $inserted++;
    }

    return [
        'inserted' => $inserted,
        'skipped' => $skipped,
        'total' => count($catalog),
    ];
}

/** @return list<string> */
function cmsFooterSectionOptions(): array
{
    return ['clients', 'talent', 'resources', 'company', 'legal'];
}

/** @return list<string> */
function cmsLinkTypeOptions(): array
{
    return ['content', 'modal', 'external'];
}

function cmsSlugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'page';
}

function cmsPageUrl(string $slug): string
{
    return baseUrl('page/' . rawurlencode(cmsSlugify($slug)));
}

/** @return array<string, string> */
function socialProfileUrls(): array
{
    return [
        'facebook' => 'https://www.facebook.com/remoworkershub',
        'x' => 'https://x.com/remoworkers',
        'linkedin' => 'https://www.linkedin.com/company/palmstake',
        'youtube' => 'https://www.youtube.com/@Remoworkers',
        'instagram' => 'https://www.instagram.com/remoworkers',
    ];
}

function socialProfileUrl(string $network): string
{
    return socialProfileUrls()[$network] ?? '';
}

function blogHubUrl(?string $category = null): string
{
    $url = baseUrl('blog');
    $category = $category !== null ? trim($category) : '';
    if ($category !== '' && strcasecmp($category, 'all') !== 0) {
        $url .= '?category=' . rawurlencode($category);
    }
    return $url;
}

function blogArticleUrl(int $id): string
{
    return baseUrl('blog/' . max(1, $id));
}

function cmsUniqueSlug(PDO $db, string $baseSlug, ?int $excludeId = null): string
{
    $slug = cmsSlugify($baseSlug);
    $candidate = $slug;
    $n = 1;
    while (true) {
        $sql = 'SELECT id FROM cms_pages WHERE slug = ?';
        $params = [$candidate];
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $n;
        $n++;
    }
}

/**
 * @return array<string, list<array<string, mixed>>>
 */
function getFooterPagesGrouped(): array
{
    ensureCmsPagesTable();
    $db = getDB();
    $stmt = $db->query(
        "SELECT id, name, slug, footer_section, link_type, link_target
         FROM cms_pages
         WHERE status = 'published' AND show_in_footer = 1 AND footer_section IS NOT NULL
         ORDER BY footer_section ASC, sort_order ASC, name ASC"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $grouped = array_fill_keys(cmsFooterSectionOptions(), []);
    foreach ($rows as $row) {
        $section = $row['footer_section'] ?? '';
        if (isset($grouped[$section])) {
            $grouped[$section][] = cmsNormalizeFooterLink($row);
        }
    }
    return $grouped;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function cmsNormalizeFooterLink(array $row): array
{
    $linkType = $row['link_type'] ?? 'content';
    $slug = (string) ($row['slug'] ?? '');
    $target = trim((string) ($row['link_target'] ?? ''));

    $href = null;
    if ($linkType === 'external' && $target !== '') {
        $href = $target;
    } elseif ($slug !== '') {
        $href = cmsPageUrl($slug);
    } elseif ($target !== '') {
        $href = cmsPageUrl($target);
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'slug' => $slug,
        'link_type' => $linkType,
        'href' => $href,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function getPublishedCmsPageBySlug(string $slug): ?array
{
    ensureCmsPagesTable();
    $slug = cmsSlugify($slug);
    if ($slug === '') {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT * FROM cms_pages WHERE slug = ? AND status = 'published' LIMIT 1"
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return cmsNormalizePage($row);
    }

    $builtin = cmsBuiltinPageCatalog();
    if (isset($builtin[$slug])) {
        return cmsNormalizePage($builtin[$slug]);
    }

    return null;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function cmsNormalizePage(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'slug' => (string) ($row['slug'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'footer_section' => $row['footer_section'] ?? null,
        'link_type' => (string) ($row['link_type'] ?? 'content'),
        'link_target' => $row['link_target'] ?? null,
        'sort_order' => (int) ($row['sort_order'] ?? 0),
        'show_in_footer' => (bool) ($row['show_in_footer'] ?? false),
        'status' => (string) ($row['status'] ?? 'draft'),
        'seo_title' => (string) ($row['seo_title'] ?? ''),
        'seo_description' => (string) ($row['seo_description'] ?? ''),
        'seo_keywords' => (string) ($row['seo_keywords'] ?? ''),
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

/**
 * Render a footer link (&lt;a&gt; attributes).
 */
function cmsFooterLinkHtml(array $link): string
{
    $name = htmlspecialchars($link['name'] ?? '', ENT_QUOTES, 'UTF-8');
    if (!empty($link['href'])) {
        $href = htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8');
        $external = str_starts_with($link['href'], 'http');
        $extra = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
        return '<a href="' . $href . '"' . $extra . '>' . $name . '</a>';
    }
    return '<span>' . $name . '</span>';
}

/**
 * Default footer links when no CMS rows exist for a section.
 *
 * @return array<string, list<array{name: string, href: string}>>
 */
function cmsFooterDefaultLinks(): array
{
    return [
        'clients' => [
            ['name' => 'How to Hire', 'href' => cmsPageUrl('how-to-hire')],
            ['name' => 'Talent Marketplace', 'href' => cmsPageUrl('talent-marketplace')],
            ['name' => 'Project Catalog', 'href' => cmsPageUrl('project-catalog')],
            ['name' => 'Talent Scout', 'href' => cmsPageUrl('talent-scout')],
            ['name' => 'Enterprise Solutions', 'href' => cmsPageUrl('enterprise')],
            ['name' => 'Payment Protection', 'href' => cmsPageUrl('trust-safety')],
            ['name' => 'Pricing', 'href' => cmsPageUrl('pricing')],
        ],
        'talent' => [
            ['name' => 'How to Find Work', 'href' => cmsPageUrl('how-to-find-work')],
            ['name' => 'Browse Jobs', 'href' => baseUrl('remoworkers-dashboard#find-work')],
            ['name' => 'Sell Services', 'href' => cmsPageUrl('sell-services')],
            ['name' => 'Build Your Profile', 'href' => baseUrl('register')],
            ['name' => 'Community Forum', 'href' => cmsPageUrl('community-forum')],
            ['name' => 'Career Resources', 'href' => blogHubUrl('Career Resources')],
            ['name' => 'Certifications', 'href' => cmsPageUrl('certifications')],
        ],
        'resources' => [
            ['name' => 'Help Center', 'href' => cmsPageUrl('help-center')],
            ['name' => 'Blog & Insights', 'href' => blogHubUrl()],
            ['name' => 'Success Stories', 'href' => cmsPageUrl('success-stories')],
            ['name' => 'Hiring Guides', 'href' => blogHubUrl('Hiring Guide')],
            ['name' => 'Templates', 'href' => cmsPageUrl('templates')],
            ['name' => 'Trust & Safety', 'href' => cmsPageUrl('trust-safety')],
            ['name' => 'Community', 'href' => cmsPageUrl('community')],
        ],
        'company' => [
            ['name' => 'Careers', 'href' => cmsPageUrl('careers')],
            ['name' => 'Press Room', 'href' => cmsPageUrl('press-room')],
            ['name' => 'Investor Relations', 'href' => cmsPageUrl('investor-relations')],
            ['name' => 'Partners', 'href' => cmsPageUrl('partners')],
            ['name' => 'Affiliates', 'href' => cmsPageUrl('affiliates')],
            ['name' => 'Contact Us', 'href' => cmsPageUrl('contact-us')],
        ],
        'legal' => [
            ['name' => 'Privacy Policy', 'href' => cmsPageUrl('privacy-policy')],
            ['name' => 'Terms of Service', 'href' => cmsPageUrl('terms-of-service')],
            ['name' => 'Cookie Settings', 'href' => cmsPageUrl('cookie-settings')],
            ['name' => 'Accessibility', 'href' => cmsPageUrl('accessibility')],
        ],
    ];
}

/**
 * @param list<array{name: string, href: string}> $links
 */
function cmsRenderFooterLinksHtml(array $links): string
{
    $html = '';
    foreach ($links as $link) {
        $html .= '<li>' . cmsFooterLinkHtml($link) . '</li>';
    }
    return $html;
}
