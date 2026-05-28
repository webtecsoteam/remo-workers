<?php
require_once __DIR__ . '/../includes/config.php';

echo "--- REMOWORKERS PLATFORM SETTINGS VERIFICATION ---\n\n";

try {
    echo "1. Initializing DB and platform_settings table...\n";
    ensurePlatformSettingsTable();
    echo "✓ Table verified/created successfully.\n\n";

    echo "2. Testing Platform Settings getters...\n";
    $fixedFreelancer = getPlatformSetting('freelancer_fee_fixed', 10);
    $fixedClient = getPlatformSetting('client_fee_fixed', 0);
    $hourlyFreelancer = getPlatformSetting('freelancer_fee_hourly', 10);
    $hourlyClient = getPlatformSetting('client_fee_hourly', 0);

    echo "  - Fixed Price Freelancer Fee: {$fixedFreelancer}%\n";
    echo "  - Fixed Price Client Fee:     {$fixedClient}%\n";
    echo "  - Hourly Freelancer Fee:      {$hourlyFreelancer}%\n";
    echo "  - Hourly Client Fee:          {$hourlyClient}%\n\n";

    echo "3. Testing Platform Settings DB representation...\n";
    $db = getDB();
    $stmt = $db->query("SELECT * FROM platform_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "  - [{$row['setting_key']}]: value = '{$row['setting_value']}' ({$row['description']})\n";
    }
    echo "\n✓ All platform settings retrieved from DB.\n\n";

    echo "--- VERIFICATION COMPLETE (SUCCESS) ---\n";
} catch (Exception $e) {
    echo "❌ VERIFICATION FAILED: " . $e->getMessage() . "\n";
}
