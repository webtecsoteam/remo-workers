<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'get_stats':
        try {
            $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $jobCount = $db->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
            $paymentCount = $db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
            $revenue = $db->query("SELECT SUM(platform_fee) FROM payments WHERE status = 'completed'")->fetchColumn() ?: 0;

            echo json_encode([
                'success' => true,
                'data' => [
                    'total_users' => $userCount,
                    'total_jobs' => $jobCount,
                    'total_payments' => $paymentCount,
                    'total_revenue' => $revenue
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_users':
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $stmt = $db->prepare("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $users]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'run_migrations':
        // Capture output from migrate.php
        ob_start();
        include __DIR__ . '/../migrate.php';
        $output = ob_get_clean();
        
        echo json_encode(['success' => true, 'message' => $output]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
