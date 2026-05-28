<?php
/**
 * Short-lived file cache for expensive homepage aggregates.
 */

function homeCacheDirectory(): string
{
    return dirname(__DIR__) . '/storage/cache/home';
}

function homeCacheKey(string $suffix): string
{
    try {
        $db = getDB();
        $name = (string) $db->query('SELECT DATABASE()')->fetchColumn();
    } catch (Throwable $e) {
        $name = 'default';
    }
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '_' . $suffix;
}

/**
 * @return array<string, mixed>|null
 */
function homeCacheGet(string $suffix, int $ttlSeconds = 300): ?array
{
    $file = homeCacheDirectory() . '/' . homeCacheKey($suffix) . '.json';
    if (!is_file($file)) {
        return null;
    }
    if (filemtime($file) + $ttlSeconds < time()) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

/**
 * @param array<string, mixed> $data
 */
function homeCacheSet(string $suffix, array $data): void
{
    $dir = homeCacheDirectory();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return;
    }
    $file = $dir . '/' . homeCacheKey($suffix) . '.json';
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
}
