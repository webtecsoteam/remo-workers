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
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
            $role = isset($_GET['role']) ? $_GET['role'] : '';
            
            if ($role) {
                $stmt = $db->prepare("SELECT id, name, email, role, balance, status, created_at FROM users WHERE role = :role ORDER BY created_at DESC LIMIT :limit");
                $stmt->bindValue(':role', $role, PDO::PARAM_STR);
            } else {
                $stmt = $db->prepare("SELECT id, name, email, role, balance, status, created_at FROM users ORDER BY created_at DESC LIMIT :limit");
            }
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

    case 'update_user_status':
        try {
            $userId = (int)($_GET['user_id'] ?? 0);
            $status = $_GET['status'] ?? 'active'; // 'active', 'suspended', 'closed'
            if (!in_array($status, ['active', 'suspended', 'closed'])) {
                throw new Exception("Invalid status provided");
            }
            if ($userId === $user['id']) {
                throw new Exception("Cannot change your own status");
            }
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $userId]);
            echo json_encode(['success' => true, 'message' => 'User status updated']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_user':
        try {
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) throw new Exception("Invalid user ID");
            if ($userId === $user['id']) throw new Exception("Cannot delete yourself");
            
            // Note: In a real system, you might want soft deletes or to clean up all related records (jobs, proposals, etc.)
            // To be safe, we will just hard delete if no foreign key constraints block it, or catch the exception
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'message' => 'User deleted permanently']);
        } catch (PDOException $e) {
            // Check for foreign key constraint failure
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete user because they have associated records (jobs, proposals, etc). Please close or suspend their account instead.']);
            } else {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } catch (Exception $e) {
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
                
                // Fetch user info to send congratulation email
                $stmt = $db->prepare("SELECT name, email, role FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $u = $stmt->fetch();
                if ($u) {
                    require_once __DIR__ . '/../includes/classes/Mailer.php';
                    $dashboardUrl = baseUrl($u['role'] === 'client' ? 'client' : 'remoworkers-dashboard');
                    $logoUrl = baseUrl('favicon.png');
                    
                    $subject = "Congratulations! Your account is verified";
                    $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
                        <div style='text-align: center; margin-bottom: 25px;'>
                            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
                            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
                        </div>
                        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
                            <p>Hello " . htmlspecialchars($u['name']) . ",</p>
                            <p>Congratulations! Your identity has been successfully verified on Remoworkers.</p>
                            <div style='background-color: #f4fbf4; border: 1px dashed #14a800; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;'>
                                <span style='font-size: 20px; margin-right: 6px; vertical-align: middle;'>🛡️</span>
                                <strong style='color: #14a800; font-size: 16px; vertical-align: middle;'>Verified Badge Active</strong>
                            </div>
                            <p>You now have a <strong>Verified</strong> badge on your profile, which builds instant trust with clients and allows you to fully access all platform features.</p>
                            <div style='text-align: center; margin: 35px 0;'>
                                <a href='" . $dashboardUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Go to Dashboard</a>
                            </div>
                            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
                            <p style='font-size: 11px; color: #9ca3af;'>Thank you for keeping our community safe and trusted.<br><br>Best regards,<br><strong>The Remoworkers Team</strong></p>
                        </div>
                    </div>";
                    Mailer::send($u['email'], $subject, $body);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Verification status updated']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_withdrawals':
        try {
            $stmt = $db->query("
                SELECT p.id, p.transaction_id, p.amount, p.payment_method, p.description, p.created_at, 
                       u.name as user_name, u.email as user_email
                FROM payments p
                JOIN users u ON p.payee_id = u.id
                WHERE p.status = 'pending' AND p.description LIKE 'Withdrawal%'
                ORDER BY p.created_at DESC
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'approve_withdrawal':
        try {
            $id = $_REQUEST['id'] ?? null;
            if (!$id) throw new Exception("Invalid withdrawal ID");
            
            $stmt = $db->prepare("UPDATE payments SET status = 'completed' WHERE id = ? AND description LIKE 'Withdrawal%'");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Withdrawal approved']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'reject_withdrawal':
        try {
            $id = $_REQUEST['id'] ?? null;
            if (!$id) throw new Exception("Invalid withdrawal ID");
            
            $db->beginTransaction();
            
            // Get withdrawal amount and user
            $stmt = $db->prepare("SELECT amount, payee_id FROM payments WHERE id = ? AND status = 'pending' AND description LIKE 'Withdrawal%' FOR UPDATE");
            $stmt->execute([$id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) throw new Exception("Withdrawal not found or already processed");
            
            // Refund balance
            $upd = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $upd->execute([$payment['amount'], $payment['payee_id']]);
            
            // Mark as failed
            $stmt = $db->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
            $stmt->execute([$id]);
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Withdrawal rejected and refunded']);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'run_migrations':
        ob_start();
        include __DIR__ . '/../migrate.php';
        $output = ob_get_clean();
        echo json_encode(['success' => true, 'message' => $output]);
        break;

    case 'get_settings':
        try {
            ensurePlatformSettingsTable();
            $stmt = $db->query("SELECT setting_key, setting_value, description FROM platform_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format as key => value for easy consumption
            $formatted = [];
            foreach ($settings as $s) {
                $formatted[$s['setting_key']] = [
                    'value' => (float)$s['setting_value'],
                    'description' => $s['description']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $formatted]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_settings':
        try {
            ensurePlatformSettingsTable();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            if (!$input) {
                throw new Exception("No settings data received");
            }
            
            $stmt = $db->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?");
            
            $allowedKeys = [
                'freelancer_fee_fixed',
                'freelancer_fee_hourly',
                'freelancer_fee_monthly',
                'client_fee_fixed',
                'client_fee_hourly',
                'client_fee_monthly'
            ];
            
            foreach ($allowedKeys as $key) {
                if (isset($input[$key])) {
                    $stmt->execute([number_format((float)$input[$key], 2, '.', ''), $key]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Platform settings saved successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_jobs':
        try {
            $stmt = $db->query("
                SELECT j.id, j.title, j.budget, j.budget_type as job_type, j.status, j.created_at, u.name as client_name, u.email as client_email
                FROM jobs j
                LEFT JOIN users u ON j.client_id = u.id
                ORDER BY j.created_at DESC
            ");
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $jobs]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_job':
        try {
            $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
            if (!$jobId) {
                throw new Exception("Invalid Job ID");
            }

            // 1. Temporarily disable foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

            $db->beginTransaction();

            // 2. Delete reviews associated with contracts of this job
            $db->prepare("DELETE FROM reviews WHERE contract_id IN (SELECT id FROM contracts WHERE job_id = ?)")->execute([$jobId]);

            // 3. Delete work logs associated with contracts of this job
            $db->prepare("DELETE FROM work_logs WHERE contract_id IN (SELECT id FROM contracts WHERE job_id = ?)")->execute([$jobId]);

            // 4. Delete milestones associated with contracts or proposals of this job
            $db->prepare("DELETE FROM milestones WHERE proposal_id IN (SELECT id FROM proposals WHERE job_id = ?) OR contract_id IN (SELECT id FROM contracts WHERE job_id = ?)")->execute([$jobId, $jobId]);

            // 5. Delete contracts associated with this job
            $db->prepare("DELETE FROM contracts WHERE job_id = ?")->execute([$jobId]);

            // 6. Delete proposals associated with this job
            $db->prepare("DELETE FROM proposals WHERE job_id = ?")->execute([$jobId]);

            // 7. Delete saved jobs (optional, catch error if table doesn't exist)
            try {
                $db->prepare("DELETE FROM saved_jobs WHERE job_id = ?")->execute([$jobId]);
            } catch (PDOException $ex) {
                // Table saved_jobs might not exist
            }

            // 8. Set job_id of messages to NULL to preserve chat history
            $db->prepare("UPDATE messages SET job_id = NULL WHERE job_id = ?")->execute([$jobId]);

            // 9. Delete the job itself
            $stmt = $db->prepare("DELETE FROM jobs WHERE id = ?");
            $stmt->execute([$jobId]);

            $db->commit();

            // 10. Re-enable foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            echo json_encode(['success' => true, 'message' => 'Job deleted successfully']);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // Always ensure foreign key checks are re-enabled in case of exception
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_connects_packages':
        try {
            $stmt = $db->query("SELECT * FROM connects_packages ORDER BY price ASC");
            $pkgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $pkgs]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

     case 'save_connects_package':
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $id = isset($input['id']) && $input['id'] ? (int)$input['id'] : null;
            $amount = (int)($input['amount'] ?? 0);
            $price = (float)($input['price'] ?? 0);
            $badgeText = !empty($input['badge_text']) ? trim($input['badge_text']) : null;
            $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

            if ($amount <= 0 || $price <= 0) {
                throw new Exception("Amount and Price must be greater than zero.");
            }

            if ($id) {
                $stmt = $db->prepare("UPDATE connects_packages SET amount=?, price=?, badge_text=?, is_active=? WHERE id=?");
                $stmt->execute([$amount, $price, $badgeText, $isActive, $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO connects_packages (amount, price, badge_text, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$amount, $price, $badgeText, $isActive]);
            }

            echo json_encode(['success' => true, 'message' => 'Package saved successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_connects_package':
        try {
            $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
            if (!$id) throw new Exception("Invalid package ID");
            $db->prepare("DELETE FROM connects_packages WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Package deleted']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
