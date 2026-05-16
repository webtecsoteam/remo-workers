<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
try {
    $db->exec("ALTER TABLE jobs ADD COLUMN subcategory VARCHAR(255) NULL AFTER category");
    echo "Added subcategory column.\n";
} catch (Exception $e) {
    echo "Subcategory error: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE specialty VARCHAR(255) NULL AFTER subcategory");
    // Wait, the above is wrong. It should be ALTER TABLE jobs ADD COLUMN specialty
} catch (Exception $e) {}

try {
    $db->exec("ALTER TABLE jobs ADD COLUMN specialty VARCHAR(255) NULL AFTER subcategory");
    echo "Added specialty column.\n";
} catch (Exception $e) {
    echo "Specialty error: " . $e->getMessage() . "\n";
}
