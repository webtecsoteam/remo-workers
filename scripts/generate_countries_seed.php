<?php
/**
 * One-off: generates includes/countries_seed.php from existing ISO list + phone codes API.
 * Run: php scripts/generate_countries_seed.php
 */

$existing = require __DIR__ . '/countries_iso_names.php';

$json = @file_get_contents('https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries.json');
if (!$json) {
    fwrite(STDERR, "Failed to fetch phone codes JSON\n");
    exit(1);
}

$data = json_decode($json, true);
$phones = [];
foreach ($data as $row) {
    $iso = strtoupper((string) ($row['iso2'] ?? ''));
    $phone = $row['phonecode'] ?? '';
    if ($iso !== '' && $phone !== '' && $phone !== null) {
        $phones[$iso] = '+' . ltrim((string) $phone, '+');
    }
}

$out = [];
foreach ($existing as $code => $name) {
    $out[] = [
        'country_code' => $code,
        'name' => $name,
        'phone_code' => $phones[$code] ?? '+0',
        'is_enabled' => 1,
    ];
}

$target = dirname(__DIR__) . '/includes/countries_seed.php';
$content = "<?php\n// Country seed data (ISO code, name, phone code). Regenerate via scripts/generate_countries_seed.php\nreturn " . var_export($out, true) . ";\n";
file_put_contents($target, $content);
echo 'Wrote ' . count($out) . ' countries to ' . $target . "\n";
