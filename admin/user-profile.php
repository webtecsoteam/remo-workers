<?php
/**
 * Deep link to admin user profile view (handled in index.php).
 */
require_once __DIR__ . '/../includes/config.php';

$userId = (int)($_GET['id'] ?? $_GET['user_id'] ?? 0);
$url = baseUrl('admin/index.php');
if ($userId > 0) {
    $url .= '?user_id=' . $userId;
}
header('Location: ' . $url);
exit;
