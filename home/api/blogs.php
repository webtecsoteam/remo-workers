<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/blog_public.php';

header('Content-Type: application/json; charset=utf-8');

$filter = isset($_GET['filter']) ? trim((string) $_GET['filter']) : 'all';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    if ($id > 0) {
        $blog = getPublishedBlogById($id);
        if (!$blog) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Article not found']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $blog]);
        exit;
    }

    $blogs = getPublishedBlogs(
        $filter === '' ? 'all' : $filter,
        $limit > 0 ? $limit : null
    );

    echo json_encode([
        'success' => true,
        'data' => $blogs,
        'categories' => blogCategoryOptions(),
        'filters' => blogFilterOptionsForApi(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    $message = APP_DEBUG ? $e->getMessage() : 'Unable to load articles';
    echo json_encode(['success' => false, 'message' => $message]);
}
