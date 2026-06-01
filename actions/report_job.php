<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/job_reports.php';

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$jobId = (int)($_POST['job_id'] ?? 0);
$reportType = trim($_POST['report_type'] ?? '');

if ($jobId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid job ID']);
    exit;
}

if (!isValidJobReportType($reportType)) {
    echo json_encode(['success' => false, 'error' => 'Please select a valid report type']);
    exit;
}

$db = getDB();
ensureJobReportedTable($db);

try {
    $jobStmt = $db->prepare("SELECT id, client_id, title, status, deleted_at FROM jobs WHERE id = ? LIMIT 1");
    $jobStmt->execute([$jobId]);
    $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

    if (!$job || !empty($job['deleted_at'])) {
        echo json_encode(['success' => false, 'error' => 'Job not found']);
        exit;
    }

    if ((int)$job['client_id'] === (int)$user['id']) {
        echo json_encode(['success' => false, 'error' => 'You cannot report your own job']);
        exit;
    }

    if (!in_array($job['status'] ?? '', ['open', 'in_progress', 'pending'], true)) {
        echo json_encode(['success' => false, 'error' => 'This job is no longer available for reporting']);
        exit;
    }

    $insert = $db->prepare("
        INSERT INTO job_reported (job_id, reporter_id, reported_user_id, report_type)
        VALUES (?, ?, ?, ?)
    ");
    $insert->execute([
        $jobId,
        (int)$user['id'],
        (int)$job['client_id'],
        $reportType,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you. Your report has been submitted and will be reviewed by our team.',
    ]);
} catch (PDOException $e) {
    if ((int)$e->errorInfo[1] === 1062) {
        echo json_encode(['success' => false, 'error' => 'You have already reported this job']);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Could not submit report. Please try again.']);
}
