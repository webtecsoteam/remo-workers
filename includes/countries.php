<?php
/**
 * Country list stored in DB (countries table). Dropdowns use enabled rows only.
 */

function invalidateCountriesCache(): void
{
    $GLOBALS['_countries_cache_dirty'] = true;
}

/** @return array{by_code: array<string, array>, by_name: array<string, array>, enabled_names: list<string>} */
function countriesCache(): array
{
    static $cache = null;
    if (!empty($GLOBALS['_countries_cache_dirty'])) {
        $cache = null;
        $GLOBALS['_countries_cache_dirty'] = false;
    }
    if ($cache !== null) {
        return $cache;
    }

    ensureCountriesTable();

    $byCode = [];
    $byName = [];
    $enabledNames = [];

    try {
        $db = getDB();
        $stmt = $db->query(
            'SELECT id, name, country_code, phone_code, is_enabled
             FROM countries
             ORDER BY name ASC'
        );
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $code = strtoupper((string) $row['country_code']);
            $name = (string) $row['name'];
            $entry = [
                'id' => (int) $row['id'],
                'name' => $name,
                'country_code' => $code,
                'phone_code' => (string) $row['phone_code'],
                'is_enabled' => (int) $row['is_enabled'] === 1,
            ];
            $byCode[$code] = $entry;
            $byName[strtolower($name)] = $entry;
            if ($entry['is_enabled']) {
                $enabledNames[] = $name;
            }
        }
    } catch (PDOException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('countriesCache: ' . $e->getMessage());
        }
    }

    if ($byCode === []) {
        foreach (countriesSeedData() as $row) {
            $code = strtoupper((string) $row['country_code']);
            $name = (string) $row['name'];
            $entry = [
                'id' => 0,
                'name' => $name,
                'country_code' => $code,
                'phone_code' => (string) ($row['phone_code'] ?? ''),
                'is_enabled' => !empty($row['is_enabled']),
            ];
            $byCode[$code] = $entry;
            $byName[strtolower($name)] = $entry;
            if ($entry['is_enabled']) {
                $enabledNames[] = $name;
            }
        }
        sort($enabledNames);
    }

    $cache = [
        'by_code' => $byCode,
        'by_name' => $byName,
        'enabled_names' => $enabledNames,
    ];

    return $cache;
}

/** @return list<array{country_code: string, name: string, phone_code: string, is_enabled: int}> */
function countriesSeedData(): array
{
    static $seed = null;
    if ($seed === null) {
        $file = __DIR__ . '/countries_seed.php';
        $seed = is_file($file) ? (require $file) : [];
    }
    return $seed;
}

function ensureCountriesTable(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db = getDB();

    $tableExists = (bool) $db->query("SHOW TABLES LIKE 'countries'")->fetch();
    if (!$tableExists) {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS countries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                country_code CHAR(2) NOT NULL,
                phone_code VARCHAR(12) NOT NULL DEFAULT \'\',
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_countries_country_code (country_code),
                KEY idx_countries_enabled (is_enabled),
                KEY idx_countries_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    $count = (int) $db->query('SELECT COUNT(*) FROM countries')->fetchColumn();
    if ($count === 0) {
        $insert = $db->prepare(
            'INSERT INTO countries (name, country_code, phone_code, is_enabled, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        $order = 0;
        foreach (countriesSeedData() as $row) {
            $insert->execute([
                $row['name'],
                strtoupper((string) $row['country_code']),
                (string) ($row['phone_code'] ?? ''),
                !empty($row['is_enabled']) ? 1 : 0,
                $order++,
            ]);
        }
    }

    $done = true;
}

/** Translates ISO country codes and capitalizes full names cleanly. */
function getCountryName($val): string
{
    if ($val === null || $val === '') {
        return 'United States';
    }

    $val = trim((string) $val);
    $cache = countriesCache();

    if (strlen($val) === 2) {
        $upper = strtoupper($val);
        if (isset($cache['by_code'][$upper])) {
            return $cache['by_code'][$upper]['name'];
        }
    }

    $key = strtolower($val);
    if (isset($cache['by_name'][$key])) {
        return $cache['by_name'][$key]['name'];
    }

    $cleaned = ucwords(strtolower($val));
    return str_ireplace('Kingdon', 'Kingdom', $cleaned);
}

/** Sorted list of enabled country names (for dropdowns). */
function getAllCountries(): array
{
    $cache = countriesCache();
    $names = $cache['enabled_names'];
    $sorted = $names;
    sort($sorted, SORT_STRING);
    return $sorted;
}

/**
 * @return list<array{id: int, name: string, country_code: string, phone_code: string, is_enabled: bool}>
 */
function getCountriesList(bool $enabledOnly = true): array
{
    $cache = countriesCache();
    $rows = [];
    foreach ($cache['by_code'] as $entry) {
        if ($enabledOnly && !$entry['is_enabled']) {
            continue;
        }
        $rows[] = $entry;
    }
    usort($rows, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));
    return $rows;
}

/** ISO code => name map for client-side display (all countries). */
function getCountryCodeNameMapForJs(): array
{
    $cache = countriesCache();
    $map = [];
    foreach ($cache['by_code'] as $code => $entry) {
        $map[$code] = $entry['name'];
    }
    return $map;
}

/**
 * Build <option> HTML for country dropdowns.
 * Option value is country name (matches existing users.country storage).
 */
function buildCountryOptionsHtml(?string $selectedValue = null, ?string $defaultSelected = null): string
{
    $html = '';
    $selected = $selectedValue ?? $defaultSelected ?? '';
    $resolvedSelected = getCountryName($selected !== '' ? $selected : 'United Kingdom');

    foreach (getAllCountries() as $name) {
        $sel = (strcasecmp($resolvedSelected, $name) === 0) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
            . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    return $html;
}
