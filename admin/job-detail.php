<?php
/**
 * Deep link to admin job detail view (handled in index.php).
 */
require_once __DIR__ . '/../includes/config.php';

$jobId = (int)($_GET['id'] ?? $_GET['job_id'] ?? 0);
$url = baseUrl('admin/index.php');
if ($jobId > 0) {
    $url .= '?job_id=' . $jobId;
}
header('Location: ' . $url);
exit;
