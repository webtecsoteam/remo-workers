<?php
define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$_SESSION['user_id'] = 17; // Use freelancer ID 17
ob_start();
include __DIR__ . '/../freelancer/index.php';
$html = ob_get_clean();

echo "Checking compiled DOM elements:\n";
echo "===============================\n";
echo "page-home: " . (strpos($html, 'id="page-home"') !== false ? "FOUND" : "NOT FOUND") . "\n";
echo "page-connects: " . (strpos($html, 'id="page-connects"') !== false ? "FOUND" : "NOT FOUND") . "\n";
echo "page-verification: " . (strpos($html, 'id="page-verification"') !== false ? "FOUND" : "NOT FOUND") . "\n";
echo "page-profile: " . (strpos($html, 'id="page-profile"') !== false ? "FOUND" : "NOT FOUND") . "\n";
echo "===============================\n";
?>
