<?php
/**
 * Public blog helpers (homepage & API).
 */

function ensureBlogsTable(): void
{
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS blogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NULL,
        image VARCHAR(255) NULL,
        description LONGTEXT NULL,
        status ENUM('draft', 'published', 'unpublished') NOT NULL DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Blog categories (must match admin blog post form).
 *
 * @return list<string>
 */
function blogCategoryOptions(): array
{
    return [
        'Hiring Guide',
        'Hiring',
        'Freelancing',
        'Freelancer Tips',
        'AI & Tech',
        'AI & Future of Work',
        'Career Resources',
        'Platform News',
        'General',
    ];
}

/** @return list<array{id: string, label: string}> */
function blogFilterOptionsForApi(): array
{
    $filters = [['id' => 'all', 'label' => 'All']];
    foreach (blogCategoryOptions() as $category) {
        $filters[] = ['id' => $category, 'label' => $category];
    }
    return $filters;
}

function blogCategoryMatchesFilter(?string $category, string $filter): bool
{
    $filter = trim($filter);
    if ($filter === '' || strcasecmp($filter, 'all') === 0) {
        return true;
    }
    return strcasecmp(trim((string) $category), $filter) === 0;
}

function blogCategoryEmoji(?string $category): string
{
    $c = strtolower(trim((string) $category));
    if ($c === '') {
        return '📚';
    }
    if (preg_match('/hiring/i', $c)) {
        return '🧠';
    }
    if (preg_match('/\bai\b|tech|machine|future of work/i', $c)) {
        return '🤖';
    }
    if (preg_match('/freelanc|career/i', $c)) {
        return '💡';
    }
    if (preg_match('/platform|news/i', $c)) {
        return '📢';
    }
    if (preg_match('/payment|finance/i', $c)) {
        return '💳';
    }
    return '📚';
}

function blogImageUrl(?string $image): ?string
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

function blogPlainText(?string $html): string
{
    $text = trim(strip_tags((string) $html));
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return preg_replace('/\s+/u', ' ', $text) ?? '';
}

function blogExcerpt(?string $html, int $maxLen = 160): string
{
    $text = blogPlainText($html);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $maxLen) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $maxLen - 1)) . '…';
}

function blogReadMinutes(?string $html): int
{
    $words = preg_split('/\s+/u', blogPlainText($html), -1, PREG_SPLIT_NO_EMPTY);
    $count = is_array($words) ? count($words) : 0;
    return max(1, (int) ceil($count / 200));
}

function blogFormatMetaDate(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    return date('F Y', $ts);
}

/**
 * @return list<array<string, mixed>>
 */
function getPublishedBlogs(?string $filter = null, ?int $limit = null): array
{
    ensureBlogsTable();
    $db = getDB();
    $sql = "SELECT id, name, category, image, description, created_at, updated_at
            FROM blogs WHERE status = 'published'";
    $params = [];
    $filter = $filter !== null ? trim($filter) : '';
    if ($filter !== '' && strcasecmp($filter, 'all') !== 0) {
        $sql .= ' AND category = ?';
        $params[] = $filter;
    }
    $sql .= ' ORDER BY created_at DESC';
    if ($limit !== null && $limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return array_map('blogNormalizeRow', $rows);
}

/**
 * @return array<string, mixed>|null
 */
function getPublishedBlogById(int $id): ?array
{
    ensureBlogsTable();
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT id, name, category, image, description, created_at, updated_at
         FROM blogs WHERE id = ? AND status = 'published' LIMIT 1"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? blogNormalizeRow($row) : null;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function blogNormalizeRow(array $row): array
{
    $description = (string) ($row['description'] ?? '');
    $created = $row['created_at'] ?? null;
    $readMin = blogReadMinutes($description);
    $dateLabel = blogFormatMetaDate($created);
    $meta = $readMin . ' min read' . ($dateLabel !== '' ? ' · ' . $dateLabel : '');

    return [
        'id' => (int) $row['id'],
        'name' => (string) ($row['name'] ?? ''),
        'category' => (string) ($row['category'] ?? ''),
        'image' => (string) ($row['image'] ?? ''),
        'image_url' => blogImageUrl($row['image'] ?? null),
        'description' => $description,
        'excerpt' => blogExcerpt($description),
        'read_minutes' => $readMin,
        'date_label' => $dateLabel,
        'meta' => $meta,
        'emoji' => blogCategoryEmoji($row['category'] ?? null),
        'created_at' => $created,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

/**
 * Lightweight blog row for homepage inline JSON (no full article body).
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function blogNormalizeRowForEmbed(array $row): array
{
    $full = blogNormalizeRow($row);
    unset($full['description'], $full['image']);
    return $full;
}

/**
 * @return list<array<string, mixed>>
 */
function getPublishedBlogsForHomeEmbed(?string $filter = null, int $limit = 9): array
{
    $rows = getPublishedBlogs($filter, $limit);
    return array_map('blogNormalizeRowForEmbed', $rows);
}
