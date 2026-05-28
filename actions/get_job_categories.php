<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $activeOnly = ($_GET['active_only'] ?? '1') !== '0';
    $categories = getJobCategories($activeOnly);
    echo json_encode([
        'success' => true,
        'data' => $categories,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load job categories',
    ]);
}
