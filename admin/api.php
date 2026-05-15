<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
header('Content-Type: application/json');

// Security Check: Only admins can access API
$user = Auth::user();
if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

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
            $stmt = $db->prepare("SELECT id, name, email, role, balance, status, created_at FROM users ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $users]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_balance':
        try {
            $userId = $_GET['user_id'] ?? 0;
            $amount = (float)($_GET['amount'] ?? 0);
            $mode = $_GET['mode'] ?? 'add'; // 'add' or 'set'

            if ($mode === 'add') {
                $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            } else {
                $stmt = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
            }
            $stmt->execute([$amount, $userId]);

            echo json_encode(['success' => true, 'message' => 'Balance updated successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'change_password':
        try {
            $currentPassword = $_GET['current_password'] ?? '';
            $newPassword = $_GET['new_password'] ?? '';
            $confirmPassword = $_GET['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }

            // Get current admin password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $adminData = $stmt->fetch();

            if (!password_verify($currentPassword, $adminData['password'])) {
                throw new Exception("Incorrect current password");
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);

            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_verifications':
        try {
            $stmt = $db->query("SELECT d.*, u.name as user_name, u.email as user_email 
                               FROM user_documents d 
                               JOIN users u ON d.user_id = u.id 
                               WHERE d.status = 'pending' 
                               ORDER BY d.created_at ASC");
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $docs]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_verification':
        try {
            $id = $_GET['id'] ?? 0;
            $status = $_GET['status'] ?? ''; // 'approved' or 'rejected'
            $reason = $_GET['reason'] ?? null;

            $stmt = $db->prepare("UPDATE user_documents SET status = ?, rejection_reason = ? WHERE id = ?");
            $stmt->execute([$status, $reason, $id]);

            // If approved, mark user as verified
            if ($status === 'approved') {
                $doc = $db->prepare("SELECT user_id FROM user_documents WHERE id = ?");
                $doc->execute([$id]);
                $userId = $doc->fetchColumn();
                $db->prepare("UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = ?")->execute([$userId]);
            }

            echo json_encode(['success' => true, 'message' => 'Verification status updated']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'run_migrations':
        ob_start();
        include __DIR__ . '/../migrate.php';
        $output = ob_get_clean();
        echo json_encode(['success' => true, 'message' => $output]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
