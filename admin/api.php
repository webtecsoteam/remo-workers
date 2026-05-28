<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/cms_pages.php';
require_once __DIR__ . '/../includes/seo_public.php';

// Security Check: Only admins can access API
$user = Auth::user();
if (!$user || $user['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = getDB();

/**
 * Shared filters for admin user list and export.
 *
 * @return array{conditions: string[], bind: array<string, string>}
 */
function adminUserListFiltersFromRequest(): array
{
    ensureFreelancerSchema();

    $conditions = [];
    $bind = [];

    $role = trim($_GET['role'] ?? '');
    if ($role !== '' && in_array($role, ['client', 'freelancer', 'admin'], true)) {
        $conditions[] = 'role = :role';
        $bind[':role'] = $role;
    }

    $status = trim($_GET['status'] ?? '');
    if ($status !== '' && in_array($status, ['active', 'suspended', 'closed', 'pending'], true)) {
        $conditions[] = 'status = :status';
        $bind[':status'] = $status;
    }

    $emailVerified = trim($_GET['email_verified'] ?? '');
    if ($emailVerified === '1') {
        $conditions[] = 'email_verified_at IS NOT NULL';
    } elseif ($emailVerified === '0') {
        $conditions[] = 'email_verified_at IS NULL';
    }

    $name = trim($_GET['name'] ?? '');
    if ($name !== '') {
        $conditions[] = 'name LIKE :name';
        $bind[':name'] = '%' . $name . '%';
    }

    $email = trim($_GET['email'] ?? '');
    if ($email !== '') {
        $conditions[] = 'email LIKE :email_filter';
        $bind[':email_filter'] = '%' . $email . '%';
    }

    $search = trim($_GET['search'] ?? '');
    if ($search !== '' && $name === '' && $email === '') {
        $conditions[] = '(name LIKE :search_name OR email LIKE :search_email)';
        $bind[':search_name'] = '%' . $search . '%';
        $bind[':search_email'] = '%' . $search . '%';
    }

    return ['conditions' => $conditions, 'bind' => $bind];
}

function adminExportUsersSpreadsheet(PDO $db): void
{
    $filters = adminUserListFiltersFromRequest();
    $sql = 'SELECT id, name, email, role, status, balance, email_verified_at, is_verified, created_at FROM users';
    if ($filters['conditions']) {
        $sql .= ' WHERE ' . implode(' AND ', $filters['conditions']);
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $db->prepare($sql);
    foreach ($filters['bind'] as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $filename = 'users-export-' . date('Y-m-d-His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, [
        'ID',
        'Name',
        'Email',
        'Role',
        'Status',
        'Email Verified',
        'Identity Verified',
        'Balance',
        'Joined',
    ]);

    foreach ($rows as $row) {
        fputcsv($out, [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['role'],
            $row['status'],
            !empty($row['email_verified_at']) ? 'Yes' : 'No',
            !empty($row['is_verified']) ? 'Yes' : 'No',
            number_format((float)($row['balance'] ?? 0), 2, '.', ''),
            $row['created_at'] ?? '',
        ]);
    }
    fclose($out);
}

if ($action === 'export_users') {
    try {
        adminExportUsersSpreadsheet($db);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

header('Content-Type: application/json');

switch ($action) {
    case 'get_stats':
        try {
            ensureFreelancerSchema();
            $onlineMinutes = 5;

            $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $jobCount = $db->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
            $paymentCount = $db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
            $revenue = $db->query("SELECT SUM(platform_fee) FROM payments WHERE status = 'completed'")->fetchColumn() ?: 0;

            $onlineStmt = $db->prepare("
                SELECT
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END), 0) AS clients,
                    COALESCE(SUM(CASE WHEN role = 'freelancer' THEN 1 ELSE 0 END), 0) AS freelancers
                FROM users
                WHERE role != 'admin'
                  AND status = 'active'
                  AND last_active_at IS NOT NULL
                  AND last_active_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $onlineStmt->execute([$onlineMinutes]);
            $online = $onlineStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                'success' => true,
                'data' => [
                    'total_users' => $userCount,
                    'total_jobs' => $jobCount,
                    'total_payments' => $paymentCount,
                    'total_revenue' => $revenue,
                    'online_users' => (int)($online['total'] ?? 0),
                    'online_clients' => (int)($online['clients'] ?? 0),
                    'online_freelancers' => (int)($online['freelancers'] ?? 0),
                    'online_threshold_minutes' => $onlineMinutes,
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_online_users':
        try {
            ensureFreelancerSchema();
            $onlineMinutes = min(60, max(1, (int)($_GET['minutes'] ?? 5)));
            $role = trim($_GET['role'] ?? '');
            if ($role !== '' && !in_array($role, ['client', 'freelancer'], true)) {
                $role = '';
            }

            $sql = "
                SELECT id, name, email, role, avatar_url, last_active_at, status
                FROM users
                WHERE role != 'admin'
                  AND status = 'active'
                  AND last_active_at IS NOT NULL
                  AND last_active_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ";
            $params = [$onlineMinutes];
            if ($role !== '') {
                $sql .= ' AND role = ?';
                $params[] = $role;
            }
            $sql .= ' ORDER BY last_active_at DESC LIMIT 100';

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $countStmt = $db->prepare("
                SELECT
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END), 0) AS clients,
                    COALESCE(SUM(CASE WHEN role = 'freelancer' THEN 1 ELSE 0 END), 0) AS freelancers
                FROM users
                WHERE role != 'admin'
                  AND status = 'active'
                  AND last_active_at IS NOT NULL
                  AND last_active_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $countStmt->execute([$onlineMinutes]);
            $counts = $countStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                'success' => true,
                'data' => $users,
                'counts' => [
                    'total' => (int)($counts['total'] ?? 0),
                    'clients' => (int)($counts['clients'] ?? 0),
                    'freelancers' => (int)($counts['freelancers'] ?? 0),
                ],
                'threshold_minutes' => $onlineMinutes,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_users':
        try {
            $limit = min(5000, max(1, (int)($_GET['limit'] ?? 1000)));
            $filters = adminUserListFiltersFromRequest();

            $sql = 'SELECT id, name, email, role, balance, status, email_verified_at, is_verified, created_at FROM users';
            if ($filters['conditions']) {
                $sql .= ' WHERE ' . implode(' AND ', $filters['conditions']);
            }
            $sql .= ' ORDER BY created_at DESC LIMIT :limit';

            $stmt = $db->prepare($sql);
            foreach ($filters['bind'] as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($users as &$row) {
                $row['email_verified'] = !empty($row['email_verified_at']);
                $row['identity_verified'] = !empty($row['is_verified']);
            }
            unset($row);

            echo json_encode([
                'success' => true,
                'data' => $users,
                'count' => count($users),
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_agencies_admin':
        try {
            ensureAgencySchema();
            $search = trim((string)($_GET['search'] ?? ''));
            $where = '';
            $params = [];
            if ($search !== '') {
                $where = "WHERE a.name LIKE ? OR owner.name LIKE ? OR owner.email LIKE ?";
                $like = '%' . $search . '%';
                $params = [$like, $like, $like];
            }

            $sql = "
                SELECT
                    a.id, a.name, a.slug, a.description, a.owner_user_id, a.created_at, a.updated_at,
                    COALESCE(a.agency_earnings_offset, 0) AS agency_earnings_offset,
                    owner.name AS owner_name, owner.email AS owner_email,
                    (
                        SELECT COUNT(*)
                        FROM agency_members amc
                        WHERE amc.agency_id = a.id AND amc.status = 'active'
                    ) AS members_count,
                    (
                        SELECT COALESCE(SUM(p.amount), 0)
                        FROM payments p
                        INNER JOIN contracts c
                            ON c.job_id = p.job_id
                           AND c.freelancer_id = p.payee_id
                        WHERE c.agency_id = a.id
                          AND p.status = 'completed'
                          AND p.transaction_id NOT LIKE 'ESC-%'
                    ) AS agency_earned_live
                FROM agencies a
                LEFT JOIN users owner ON owner.id = a.owner_user_id
                $where
                ORDER BY a.created_at DESC
                LIMIT 500
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $membersByAgency = [];
            if (!empty($agencies)) {
                $agencyIds = array_map(static fn($a) => (int)$a['id'], $agencies);
                $placeholders = implode(',', array_fill(0, count($agencyIds), '?'));
                $mStmt = $db->prepare("
                    SELECT am.agency_id, am.user_id, am.role, am.status, am.created_at, u.name, u.email
                    FROM agency_members am
                    JOIN users u ON u.id = am.user_id
                    WHERE am.agency_id IN ($placeholders) AND am.status = 'active'
                    ORDER BY am.agency_id ASC, FIELD(am.role, 'owner', 'admin', 'member'), u.name ASC
                ");
                $mStmt->execute($agencyIds);
                foreach (($mStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
                    $aid = (int)$row['agency_id'];
                    if (!isset($membersByAgency[$aid])) {
                        $membersByAgency[$aid] = [];
                    }
                    $membersByAgency[$aid][] = $row;
                }
            }

            foreach ($agencies as &$agency) {
                $agency['members'] = $membersByAgency[(int)$agency['id']] ?? [];
                $live = (float)($agency['agency_earned_live'] ?? 0);
                $offset = (float)($agency['agency_earnings_offset'] ?? 0);
                $agency['agency_earned_total'] = max(0, $live + $offset);
            }
            unset($agency);

            echo json_encode(['success' => true, 'data' => $agencies]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_agency_admin':
        try {
            ensureAgencySchema();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $agencyId = (int)($input['agency_id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));

            if ($agencyId <= 0) throw new Exception('Invalid agency ID');
            if ($name === '') throw new Exception('Agency name is required');

            $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
            if ($slug === '') {
                $slug = 'agency-' . $agencyId;
            }

            $slugStmt = $db->prepare("SELECT id FROM agencies WHERE slug = ? AND id <> ? LIMIT 1");
            $slugStmt->execute([$slug, $agencyId]);
            if ($slugStmt->fetchColumn()) {
                $slug .= '-' . $agencyId;
            }

            $stmt = $db->prepare("
                UPDATE agencies
                SET name = ?, slug = ?, description = ?
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$name, $slug, $description !== '' ? $description : null, $agencyId]);
            if ($stmt->rowCount() < 1) {
                $existsStmt = $db->prepare("SELECT id FROM agencies WHERE id = ? LIMIT 1");
                $existsStmt->execute([$agencyId]);
                if (!$existsStmt->fetchColumn()) {
                    throw new Exception('Agency not found');
                }
            }

            echo json_encode(['success' => true, 'message' => 'Agency updated successfully']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'adjust_agency_earnings_admin':
        try {
            ensureAgencySchema();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $agencyId = (int)($input['agency_id'] ?? 0);
            $mode = strtolower(trim((string)($input['mode'] ?? 'add')));
            $amount = (float)($input['amount'] ?? 0);

            if ($agencyId <= 0) throw new Exception('Invalid agency ID');
            if (!in_array($mode, ['add', 'subtract'], true)) {
                throw new Exception('Mode must be add or subtract');
            }
            if ($amount <= 0) throw new Exception('Amount must be greater than 0');

            $delta = ($mode === 'subtract') ? -$amount : $amount;
            $stmt = $db->prepare("
                UPDATE agencies
                SET agency_earnings_offset = COALESCE(agency_earnings_offset, 0) + ?
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$delta, $agencyId]);
            if ($stmt->rowCount() < 1) {
                throw new Exception('Agency not found');
            }

            echo json_encode(['success' => true, 'message' => 'Agency earnings updated successfully']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_agency_admin':
        try {
            ensureAgencySchema();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $agencyId = (int)($input['agency_id'] ?? 0);
            if ($agencyId <= 0) throw new Exception('Invalid agency ID');

            $db->beginTransaction();

            $resetUsers = $db->prepare("
                UPDATE users
                SET account_mode = 'individual', agency_id = NULL
                WHERE agency_id = ?
            ");
            $resetUsers->execute([$agencyId]);

            // Hard cleanup membership/invitation rows even on legacy schemas
            // where FK cascade may be missing.
            $deleteInvites = $db->prepare("DELETE FROM agency_member_invitations WHERE agency_id = ?");
            $deleteInvites->execute([$agencyId]);

            $deleteMembers = $db->prepare("DELETE FROM agency_members WHERE agency_id = ?");
            $deleteMembers->execute([$agencyId]);

            $stmt = $db->prepare("DELETE FROM agencies WHERE id = ? LIMIT 1");
            $stmt->execute([$agencyId]);
            if ($stmt->rowCount() < 1) {
                throw new Exception('Agency not found');
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Agency deleted successfully']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_agency_member_admin':
        try {
            ensureAgencySchema();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $agencyId = (int)($input['agency_id'] ?? 0);
            $userId = (int)($input['user_id'] ?? 0);
            $role = strtolower(trim((string)($input['role'] ?? 'member')));

            if ($agencyId <= 0 || $userId <= 0) throw new Exception('Invalid member payload');
            if (!in_array($role, ['admin', 'member'], true)) {
                throw new Exception('Only admin/member role can be assigned');
            }

            $ownerStmt = $db->prepare("SELECT owner_user_id FROM agencies WHERE id = ? LIMIT 1");
            $ownerStmt->execute([$agencyId]);
            $ownerId = (int)($ownerStmt->fetchColumn() ?: 0);
            if ($ownerId <= 0) throw new Exception('Agency not found');
            if ($ownerId === $userId) throw new Exception('Owner role cannot be changed');

            $stmt = $db->prepare("
                UPDATE agency_members
                SET role = ?
                WHERE agency_id = ? AND user_id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$role, $agencyId, $userId]);
            if ($stmt->rowCount() < 1) {
                throw new Exception('Member not found or already inactive');
            }

            echo json_encode(['success' => true, 'message' => 'Member role updated']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'remove_agency_member_admin':
        try {
            ensureAgencySchema();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $agencyId = (int)($input['agency_id'] ?? 0);
            $userId = (int)($input['user_id'] ?? 0);

            if ($agencyId <= 0 || $userId <= 0) throw new Exception('Invalid member payload');

            $ownerStmt = $db->prepare("SELECT owner_user_id FROM agencies WHERE id = ? LIMIT 1");
            $ownerStmt->execute([$agencyId]);
            $ownerId = (int)($ownerStmt->fetchColumn() ?: 0);
            if ($ownerId <= 0) throw new Exception('Agency not found');
            if ($ownerId === $userId) throw new Exception('Owner cannot be removed from the agency');

            $db->beginTransaction();

            $memberRole = '';
            $existsStmt = $db->prepare("
                SELECT role
                FROM agency_members
                WHERE agency_id = ? AND user_id = ?
                LIMIT 1
            ");
            $existsStmt->execute([$agencyId, $userId]);
            $memberRole = (string)($existsStmt->fetchColumn() ?: '');
            if ($memberRole === 'owner') {
                throw new Exception('Owner cannot be removed from the agency');
            }

            // Remove active/inactive membership rows for this agency-user relation.
            $deleteMember = $db->prepare("
                DELETE FROM agency_members
                WHERE agency_id = ? AND user_id = ? AND role <> 'owner'
            ");
            $deleteMember->execute([$agencyId, $userId]);

            // Also remove any pending/old agency invitations so the member does not
            // continue to appear in frontend agency list as pending.
            $deleteInvites = $db->prepare("
                DELETE FROM agency_member_invitations
                WHERE agency_id = ? AND user_id = ?
            ");
            $deleteInvites->execute([$agencyId, $userId]);

            if ($deleteMember->rowCount() < 1 && $deleteInvites->rowCount() < 1) {
                throw new Exception('Member not found in this agency');
            }

            $unlinkUser = $db->prepare("
                UPDATE users
                SET account_mode = 'individual', agency_id = NULL
                WHERE id = ? AND agency_id = ?
            ");
            $unlinkUser->execute([$userId, $agencyId]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Member removed from agency']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_user_profile':
        try {
            require_once __DIR__ . '/../includes/classes/Auth.php';
            ensureFreelancerSchema();

            $userId = (int)($_GET['user_id'] ?? 0);
            if ($userId <= 0) {
                throw new Exception('Invalid user ID');
            }

            $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$profileUser) {
                throw new Exception('User not found');
            }

            unset($profileUser['password'], $profileUser['password_reset_token'], $profileUser['email_verification_token']);

            $skills = [];
            if (!empty($profileUser['skills'])) {
                $decoded = json_decode($profileUser['skills'], true);
                $skills = is_array($decoded) ? $decoded : [];
            }

            $documents = [];
            $docStmt = $db->prepare(
                'SELECT id, doc_type, file_path, status, rejection_reason, created_at
                 FROM user_documents WHERE user_id = ? ORDER BY created_at DESC'
            );
            $docStmt->execute([$userId]);
            $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $freelancerStats = null;
            $clientStats = null;
            $services = [];
            $reviews = [];
            $activity = [
                'jobs_posted' => 0,
                'proposals_sent' => 0,
                'contracts_as_client' => 0,
                'contracts_as_freelancer' => 0,
                'active_contracts' => 0,
            ];

            if ($profileUser['role'] === 'freelancer') {
                $freelancerStats = getFreelancerStats($userId);

                $svcStmt = $db->prepare(
                    'SELECT id, title, price, delivery_days, created_at FROM services WHERE freelancer_id = ? ORDER BY created_at DESC LIMIT 12'
                );
                $svcStmt->execute([$userId]);
                $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $revStmt = $db->prepare(
                    "SELECT r.rating, r.feedback, r.created_at, j.title AS job_title, u.name AS client_name
                     FROM reviews r
                     JOIN contracts c ON r.contract_id = c.id
                     JOIN jobs j ON c.job_id = j.id
                     JOIN users u ON r.reviewer_id = u.id
                     WHERE r.reviewee_id = ?
                     ORDER BY r.created_at DESC LIMIT 15"
                );
                $revStmt->execute([$userId]);
                $reviews = $revStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $propStmt = $db->prepare('SELECT COUNT(*) FROM proposals WHERE freelancer_id = ?');
                $propStmt->execute([$userId]);
                $activity['proposals_sent'] = (int)$propStmt->fetchColumn();

                $fcStmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ?");
                $fcStmt->execute([$userId]);
                $activity['contracts_as_freelancer'] = (int)$fcStmt->fetchColumn();

                $faStmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE freelancer_id = ? AND status = 'active'");
                $faStmt->execute([$userId]);
                $activity['active_contracts'] = (int)$faStmt->fetchColumn();
            }

            if ($profileUser['role'] === 'client') {
                $spentStmt = $db->prepare(
                    "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ? AND status = 'completed'"
                );
                $spentStmt->execute([$userId]);
                $realSpent = (float)$spentStmt->fetchColumn();

                $hiresStmt = $db->prepare('SELECT COUNT(*) FROM contracts WHERE client_id = ?');
                $hiresStmt->execute([$userId]);
                $realHires = (int)$hiresStmt->fetchColumn();

                $spentOffset = (float)($profileUser['admin_spent_offset'] ?? 0);
                $hiresOffset = (int)($profileUser['admin_hires_offset'] ?? 0);

                $clientStats = [
                    'join_date' => date('Y-m-d', strtotime($profileUser['created_at'])),
                    'real_spent' => $realSpent,
                    'real_hires' => $realHires,
                    'spent_offset' => $spentOffset,
                    'hires_offset' => $hiresOffset,
                    'effective_spent' => $realSpent + $spentOffset,
                    'effective_hires' => $realHires + $hiresOffset,
                ];

                $jobStmt = $db->prepare('SELECT COUNT(*) FROM jobs WHERE client_id = ?');
                $jobStmt->execute([$userId]);
                $activity['jobs_posted'] = (int)$jobStmt->fetchColumn();

                $ccStmt = $db->prepare('SELECT COUNT(*) FROM contracts WHERE client_id = ?');
                $ccStmt->execute([$userId]);
                $activity['contracts_as_client'] = (int)$ccStmt->fetchColumn();

                $caStmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE client_id = ? AND status = 'active'");
                $caStmt->execute([$userId]);
                $activity['active_contracts'] = (int)$caStmt->fetchColumn();
            }

            $recentJobs = [];
            if ($profileUser['role'] === 'client') {
                $jobsStmt = $db->prepare(
                    'SELECT id, title, status, budget, budget_type, created_at
                     FROM jobs WHERE client_id = ? ORDER BY created_at DESC LIMIT 8'
                );
                $jobsStmt->execute([$userId]);
                $recentJobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $publicUrl = null;
            if ($profileUser['role'] === 'freelancer') {
                $publicUrl = baseUrl('f/' . encodeFreelancerId($userId));
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'user' => $profileUser,
                    'skills' => $skills,
                    'documents' => $documents,
                    'freelancer_stats' => $freelancerStats,
                    'client_stats' => $clientStats,
                    'services' => $services,
                    'reviews' => $reviews,
                    'recent_jobs' => $recentJobs,
                    'activity' => $activity,
                    'country_name' => getCountryName($profileUser['country'] ?? ''),
                    'email_verified' => Auth::isEmailVerified($profileUser),
                    'identity_verified' => Auth::isIdentityVerified($profileUser),
                    'public_profile_url' => $publicUrl,
                ],
            ]);
        } catch (Exception $e) {
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
            $reason = trim($_GET['reason'] ?? '');
            if (!in_array($status, ['active', 'suspended', 'closed'])) {
                throw new Exception("Invalid status provided");
            }
            if ($userId === $user['id']) {
                throw new Exception("Cannot change your own status");
            }
            if ($status === 'suspended' && $reason === '') {
                throw new Exception("A suspension reason is required");
            }
            if (strlen($reason) > 2000) {
                throw new Exception("Suspension reason is too long (max 2000 characters)");
            }

            $stmt = $db->prepare("SELECT id, name, email, status FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$targetUser) {
                throw new Exception("User not found");
            }

            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $userId]);

            $message = 'User status updated';
            if ($status === 'suspended') {
                require_once __DIR__ . '/../includes/classes/Mailer.php';
                $logoUrl = baseUrl('favicon.png');
                $supportEmail = env('MAIL_FROM_ADDRESS', 'support@remoworkers.com');
                $reasonHtml = nl2br(htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'));
                $subject = 'Your RemoWorkers Account Has Been Suspended';
                $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
                    <div style='text-align: center; margin-bottom: 25px;'>
                        <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
                        <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
                    </div>
                    <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
                        <p>Hello " . htmlspecialchars($targetUser['name']) . ",</p>
                        <p>Your RemoWorkers account has been <strong style='color:#dc2626;'>suspended</strong> by our administration team. You will not be able to sign in or use platform features until your account is reactivated.</p>
                        <div style='background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 20px 0;'>
                            <p style='margin: 0 0 8px; font-size: 12px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.4px;'>Reason for suspension</p>
                            <p style='margin: 0; color: #374151; font-size: 14px; line-height: 1.55;'>" . $reasonHtml . "</p>
                        </div>
                        <p>If you believe this was a mistake or would like to appeal, please contact us at <a href='mailto:" . htmlspecialchars($supportEmail) . "' style='color: #14a800;'>" . htmlspecialchars($supportEmail) . "</a> and reference your account email (<strong>" . htmlspecialchars($targetUser['email']) . "</strong>).</p>
                        <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
                        <p style='font-size: 11px; color: #9ca3af;'>This is an automated notice from RemoWorkers.<br><br>Best regards,<br><strong>The RemoWorkers Team</strong></p>
                    </div>
                </div>";
                if (Mailer::send($targetUser['email'], $subject, $body)) {
                    $message = 'User suspended and notification email sent';
                } else {
                    $message = 'User suspended, but the notification email could not be sent. Please check mail settings.';
                }
            }

            echo json_encode(['success' => true, 'message' => $message]);
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

    case 'reset_user_password':
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $targetUserId = (int)($input['user_id'] ?? 0);
            $newPassword = $input['new_password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';

            if ($targetUserId <= 0) {
                throw new Exception('Invalid user');
            }
            if (strlen($newPassword) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Passwords do not match');
            }

            $stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetUser) {
                throw new Exception('User not found');
            }
            if ($targetUser['role'] === 'admin') {
                throw new Exception('Cannot reset password for admin accounts from here');
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $targetUserId]);

            echo json_encode([
                'success' => true,
                'message' => 'Password reset successfully for ' . $targetUser['name'],
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'change_user_email':
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $targetUserId = (int)($input['user_id'] ?? 0);
            $newEmail = trim($input['new_email'] ?? '');

            if ($targetUserId <= 0) {
                throw new Exception('Invalid user');
            }
            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address');
            }

            $stmt = $db->prepare("SELECT id, name, role, email FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetUser) {
                throw new Exception('User not found');
            }
            if ($targetUser['role'] === 'admin') {
                throw new Exception('Cannot change email for admin accounts from here');
            }
            if (strcasecmp($targetUser['email'], $newEmail) === 0) {
                throw new Exception('New email is the same as the current email');
            }

            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $targetUserId]);
            if ($stmt->fetch()) {
                throw new Exception('This email is already in use by another account');
            }

            $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$newEmail, $targetUserId]);

            echo json_encode([
                'success' => true,
                'message' => 'Email updated successfully for ' . $targetUser['name'],
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_verifications':
        try {
            $status = $_GET['status'] ?? 'pending';
            if ($status === 'verified') {
                $status = 'approved';
            }
            $allowed = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $allowed, true)) {
                $status = 'pending';
            }

            $stmt = $db->prepare(
                "SELECT d.*, u.name as user_name, u.email as user_email
                 FROM user_documents d
                 JOIN users u ON d.user_id = u.id
                 WHERE d.status = ?
                 ORDER BY d.created_at DESC"
            );
            $stmt->execute([$status]);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $docs, 'status' => $status]);
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

    case 'get_disputes':
        try {
            $stmt = $db->query("
                SELECT d.id, d.contract_id, d.raised_by, d.reason, d.status,
                       d.resolution_notes, d.created_at,
                       u.name AS raised_by_name, u.email AS raised_by_email, u.role AS raised_by_role,
                       c.client_id, c.freelancer_id, c.job_id,
                       cl.name AS client_name, cl.email AS client_email, cl.role AS client_role,
                       fr.name AS freelancer_name, fr.email AS freelancer_email, fr.role AS freelancer_role,
                       j.title AS job_title, c.amount AS contract_amount,
                       (
                           SELECT COALESCE(SUM(p.amount + COALESCE(p.platform_fee, 0)), 0)
                           FROM payments p
                           WHERE p.job_id = c.job_id
                             AND p.payer_id = c.client_id
                             AND p.payee_id = c.freelancer_id
                             AND p.status = 'pending'
                       ) AS escrow_held
                FROM disputes d
                LEFT JOIN users u ON d.raised_by = u.id
                LEFT JOIN contracts c ON d.contract_id = c.id
                LEFT JOIN users cl ON c.client_id = cl.id
                LEFT JOIN users fr ON c.freelancer_id = fr.id
                LEFT JOIN jobs j ON c.job_id = j.id
                ORDER BY d.created_at DESC
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'resolve_dispute':
        try {
            require_once __DIR__ . '/../includes/dispute_resolution.php';
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $disputeId = (int)($input['dispute_id'] ?? 0);
            $resolution = $input['resolution'] ?? '';
            $notes = trim($input['notes'] ?? '');

            if (!$disputeId) {
                throw new Exception('Dispute ID is required');
            }
            if (!in_array($resolution, ['pay_freelancer', 'refund_client'], true)) {
                throw new Exception('Invalid resolution action');
            }

            $db->beginTransaction();

            if ($resolution === 'pay_freelancer') {
                $result = resolveDisputePayFreelancer($db, $disputeId, $notes, $user);
                $message = 'Freelancer paid successfully. Net released: $'
                    . number_format($result['amount_released'], 2);
            } else {
                $result = resolveDisputeRefundClient($db, $disputeId, $notes, $user);
                $message = 'Client refunded successfully. Total: $'
                    . number_format($result['amount_refunded'], 2)
                    . '. The freelancer has been notified by email.';
            }

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_payments':
        try {
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 5000) : 1000;
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            $where = ' WHERE 1=1';
            $filterParams = [];

            if ($status !== '') {
                $where .= ' AND status = ?';
                $filterParams[] = $status;
            }
            if ($search !== '') {
                $where .= ' AND (transaction_id LIKE ? OR CAST(id AS CHAR) LIKE ? OR description LIKE ?)';
                $like = '%' . $search . '%';
                $filterParams[] = $like;
                $filterParams[] = $like;
                $filterParams[] = $like;
            }

            $summarySql = "
                SELECT
                    COUNT(*) AS count,
                    COALESCE(SUM(amount), 0) AS total_amount,
                    COALESCE(SUM(platform_fee), 0) AS total_fees,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS completed_amount,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending_amount,
                    SUM(CASE WHEN status = 'disputed' THEN 1 ELSE 0 END) AS disputed_count
                FROM payments
                {$where}
            ";
            $summaryStmt = $db->prepare($summarySql);
            foreach ($filterParams as $i => $val) {
                $summaryStmt->bindValue($i + 1, $val, PDO::PARAM_STR);
            }
            $summaryStmt->execute();
            $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $sql = "
                SELECT id, transaction_id, payer_id, payee_id, job_id,
                       amount, platform_fee, currency, payment_method,
                       status, created_at, description
                FROM payments
                {$where}
                ORDER BY created_at DESC LIMIT ?
            ";
            $params = array_merge($filterParams, [$limit]);

            $stmt = $db->prepare($sql);
            foreach ($params as $i => $val) {
                $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'summary' => [
                    'count' => (int)($summary['count'] ?? 0),
                    'total_amount' => (float)($summary['total_amount'] ?? 0),
                    'total_fees' => (float)($summary['total_fees'] ?? 0),
                    'completed_amount' => (float)($summary['completed_amount'] ?? 0),
                    'pending_amount' => (float)($summary['pending_amount'] ?? 0),
                    'disputed_count' => (int)($summary['disputed_count'] ?? 0),
                ],
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_payment_holds':
        try {
            require_once __DIR__ . '/../includes/payment_hold.php';
            $sql = paymentHoldListSql() . ' ORDER BY p.created_at ASC';
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as &$row) {
                $row['net_amount'] = calculatePaymentNetAmount($row);
            }
            unset($row);
            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'approve_payment_hold':
        try {
            require_once __DIR__ . '/../includes/payment_hold.php';
            $id = (int)($_REQUEST['id'] ?? $_REQUEST['payment_id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid payment ID');
            }

            $db->beginTransaction();
            $result = approvePendingPaymentHold($db, $id);
            $payment = $result['payment'];
            $netAmount = $result['net_amount'];

            $jobId = isset($payment['job_id']) ? (int)$payment['job_id'] : 0;
            if ($jobId > 0) {
                $jobCheck = $db->prepare('SELECT id FROM jobs WHERE id = ?');
                $jobCheck->execute([$jobId]);
                if ($jobCheck->fetchColumn()) {
                    $msg = 'AUTOMATED MESSAGE: An administrator has approved your payment hold ($'
                        . number_format($netAmount, 2)
                        . '). Funds are now in your available balance and can be withdrawn.';
                    $msgStmt = $db->prepare("
                        INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read)
                        VALUES (?, ?, ?, ?, 0)
                    ");
                    $msgStmt->execute([$user['id'], $payment['payee_id'], $jobId, $msg]);
                }
            }

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Payment hold approved. $' . number_format($netAmount, 2) . ' credited to freelancer available balance.',
                'net_amount' => $netAmount,
                'new_balance' => $result['new_balance'],
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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

            $numericKeys = [
                'freelancer_fee_fixed',
                'freelancer_fee_hourly',
                'freelancer_fee_monthly',
                'client_fee_fixed',
                'client_fee_hourly',
                'client_fee_monthly',
            ];

            $formatted = [];
            foreach ($settings as $s) {
                $key = $s['setting_key'];
                $raw = $s['setting_value'];
                $formatted[$key] = [
                    'value' => in_array($key, $numericKeys, true) ? (float)$raw : $raw,
                    'description' => $s['description'],
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

            $feeKeys = [
                'freelancer_fee_fixed',
                'freelancer_fee_hourly',
                'freelancer_fee_monthly',
                'client_fee_fixed',
                'client_fee_hourly',
                'client_fee_monthly',
            ];

            $stmt = $db->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?");

            foreach ($feeKeys as $key) {
                if (isset($input[$key])) {
                    $stmt->execute([number_format((float)$input[$key], 2, '.', ''), $key]);
                }
            }

            if (array_key_exists('google_analytics_enabled', $input)) {
                $enabled = filter_var($input['google_analytics_enabled'], FILTER_VALIDATE_BOOLEAN)
                    || $input['google_analytics_enabled'] === '1'
                    || $input['google_analytics_enabled'] === 1;
                $stmt->execute([$enabled ? '1' : '0', 'google_analytics_enabled']);
            }

            if (array_key_exists('google_analytics_id', $input)) {
                $gaId = trim((string)$input['google_analytics_id']);
                if ($gaId !== '' && !preg_match('/^G-[A-Z0-9]{4,}$/i', $gaId)) {
                    throw new Exception('Invalid Google Analytics Measurement ID. Use format G-XXXXXXXXXX.');
                }
                $stmt->execute([$gaId, 'google_analytics_id']);
            }

            echo json_encode(['success' => true, 'message' => 'Platform settings saved successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_job_categories':
        try {
            ensureJobCategoriesTable();
            $stmt = $db->query("SELECT id, name, image, status, created_at, updated_at FROM job_categories ORDER BY created_at DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_job_category':
        try {
            ensureJobCategoriesTable();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $id = (int)($input['id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $image = trim((string)($input['image'] ?? ''));
            $status = trim((string)($input['status'] ?? 'active'));

            if ($name === '') {
                throw new Exception('Category name is required');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                throw new Exception('Invalid status');
            }

            if ($id > 0) {
                $stmt = $db->prepare("UPDATE job_categories SET name = ?, image = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $image !== '' ? $image : null, $status, $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO job_categories (name, image, status) VALUES (?, ?, ?)");
                $stmt->execute([$name, $image !== '' ? $image : null, $status]);
            }

            echo json_encode(['success' => true, 'message' => 'Job category saved successfully']);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                echo json_encode(['success' => false, 'message' => 'Category name must be unique']);
            } else {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_job_category':
        try {
            ensureJobCategoriesTable();
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid category ID');
            }

            $stmt = $db->prepare("DELETE FROM job_categories WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Job category deleted']);
        } catch (Throwable $e) {
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

    case 'get_contracts':
        try {
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 5000) : 2000;

            $sql = "
                SELECT c.id, c.job_id, c.client_id, c.freelancer_id, c.proposal_id,
                       c.amount, c.contract_type, c.status, c.start_date, c.end_date, c.created_at,
                       j.title AS job_title, j.status AS job_status,
                       cl.name AS client_name, cl.email AS client_email,
                       fr.name AS freelancer_name, fr.email AS freelancer_email
                FROM contracts c
                JOIN jobs j ON c.job_id = j.id
                JOIN users cl ON c.client_id = cl.id
                JOIN users fr ON c.freelancer_id = fr.id
                WHERE 1=1
            ";
            $params = [];

            if ($status !== '') {
                $sql .= ' AND c.status = ?';
                $params[] = $status;
            }
            if ($search !== '') {
                $sql .= ' AND (j.title LIKE ? OR cl.name LIKE ? OR cl.email LIKE ? OR fr.name LIKE ? OR fr.email LIKE ? OR CAST(c.id AS CHAR) LIKE ?)';
                $like = '%' . $search . '%';
                $params = array_merge($params, array_fill(0, 6, $like));
            }

            $sql .= ' ORDER BY c.created_at DESC LIMIT ?';
            $params[] = $limit;

            $stmt = $db->prepare($sql);
            foreach ($params as $i => $val) {
                $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_job_detail':
        try {
            $jobId = (int)($_GET['job_id'] ?? 0);
            if ($jobId <= 0) {
                throw new Exception('Invalid job ID');
            }

            $jobStmt = $db->prepare("
                SELECT j.*, u.id AS client_user_id, u.name AS client_name, u.email AS client_email,
                       u.avatar_url AS client_avatar, u.status AS client_status, u.created_at AS client_joined
                FROM jobs j
                LEFT JOIN users u ON j.client_id = u.id
                WHERE j.id = ?
            ");
            $jobStmt->execute([$jobId]);
            $job = $jobStmt->fetch(PDO::FETCH_ASSOC);
            if (!$job) {
                throw new Exception('Job not found');
            }

            $skills = [];
            if (!empty($job['skills_required'])) {
                $decoded = json_decode($job['skills_required'], true);
                $skills = is_array($decoded) ? $decoded : [];
            }
            $job['skills'] = $skills;
            unset($job['skills_required']);

            $propStmt = $db->prepare("
                SELECT p.*, u.name AS freelancer_name, u.email AS freelancer_email, u.avatar_url AS freelancer_avatar
                FROM proposals p
                JOIN users u ON p.freelancer_id = u.id
                WHERE p.job_id = ?
                ORDER BY p.created_at DESC
            ");
            $propStmt->execute([$jobId]);
            $proposals = $propStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $proposalIds = array_column($proposals, 'id');
            $proposalMilestones = [];
            if ($proposalIds) {
                $ph = implode(',', array_fill(0, count($proposalIds), '?'));
                $mStmt = $db->prepare("SELECT * FROM milestones WHERE proposal_id IN ($ph) ORDER BY id ASC");
                $mStmt->execute($proposalIds);
                foreach ($mStmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
                    $proposalMilestones[$m['proposal_id']][] = $m;
                }
            }
            foreach ($proposals as &$prop) {
                $prop['milestones'] = $proposalMilestones[$prop['id']] ?? [];
            }
            unset($prop);

            $contractStmt = $db->prepare("
                SELECT c.*, u.name AS freelancer_name, u.email AS freelancer_email, u.avatar_url AS freelancer_avatar
                FROM contracts c
                JOIN users u ON c.freelancer_id = u.id
                WHERE c.job_id = ?
                ORDER BY c.created_at DESC
            ");
            $contractStmt->execute([$jobId]);
            $contracts = $contractStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $contractIds = array_column($contracts, 'id');
            $contractMilestones = [];
            $contractWorkLogs = [];
            $contractReviews = [];
            if ($contractIds) {
                $ph = implode(',', array_fill(0, count($contractIds), '?'));
                $cmStmt = $db->prepare("SELECT * FROM milestones WHERE contract_id IN ($ph) ORDER BY id ASC");
                $cmStmt->execute($contractIds);
                foreach ($cmStmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
                    $contractMilestones[$m['contract_id']][] = $m;
                }

                try {
                    $wlStmt = $db->prepare("SELECT * FROM work_logs WHERE contract_id IN ($ph) ORDER BY created_at DESC");
                    $wlStmt->execute($contractIds);
                    foreach ($wlStmt->fetchAll(PDO::FETCH_ASSOC) as $wl) {
                        $contractWorkLogs[$wl['contract_id']][] = $wl;
                    }
                } catch (PDOException $e) {
                    // work_logs table may not exist on older installs
                }

                $revStmt = $db->prepare("
                    SELECT r.*, rev.name AS reviewer_name, rev.role AS reviewer_role
                    FROM reviews r
                    JOIN users rev ON r.reviewer_id = rev.id
                    WHERE r.contract_id IN ($ph)
                    ORDER BY r.created_at DESC
                ");
                $revStmt->execute($contractIds);
                foreach ($revStmt->fetchAll(PDO::FETCH_ASSOC) as $rev) {
                    $contractReviews[$rev['contract_id']][] = $rev;
                }
            }
            foreach ($contracts as &$contract) {
                $cid = $contract['id'];
                $contract['milestones'] = $contractMilestones[$cid] ?? [];
                $contract['work_logs'] = $contractWorkLogs[$cid] ?? [];
                $contract['reviews'] = $contractReviews[$cid] ?? [];
            }
            unset($contract);

            $payStmt = $db->prepare("
                SELECT p.*, payer.name AS payer_name, payer.email AS payer_email,
                       payee.name AS payee_name, payee.email AS payee_email
                FROM payments p
                LEFT JOIN users payer ON p.payer_id = payer.id
                LEFT JOIN users payee ON p.payee_id = payee.id
                WHERE p.job_id = ?
                ORDER BY p.created_at DESC
            ");
            $payStmt->execute([$jobId]);
            $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $disputeStmt = $db->prepare("
                SELECT d.*, u.name AS raised_by_name, u.role AS raised_by_role,
                       c.id AS contract_ref_id, c.amount AS contract_amount, c.status AS contract_status,
                       fr.name AS freelancer_name, cl.name AS client_name
                FROM disputes d
                JOIN contracts c ON d.contract_id = c.id
                LEFT JOIN users u ON d.raised_by = u.id
                LEFT JOIN users fr ON c.freelancer_id = fr.id
                LEFT JOIN users cl ON c.client_id = cl.id
                WHERE c.job_id = ?
                ORDER BY d.created_at DESC
            ");
            $disputeStmt->execute([$jobId]);
            $disputes = $disputeStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $msgStmt = $db->prepare("
                SELECT m.*, s.name AS sender_name, s.email AS sender_email,
                       r.name AS receiver_name, r.email AS receiver_email
                FROM messages m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                WHERE m.job_id = ?
                ORDER BY m.created_at DESC
                LIMIT 500
            ");
            $msgStmt->execute([$jobId]);
            $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $invitations = [];
            try {
                $invStmt = $db->prepare("
                    SELECT i.*, u.name AS freelancer_name, u.email AS freelancer_email
                    FROM job_invitations i
                    JOIN users u ON i.freelancer_id = u.id
                    WHERE i.job_id = ?
                    ORDER BY i.created_at DESC
                ");
                $invStmt->execute([$jobId]);
                $invitations = $invStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (PDOException $e) {
                // optional table
            }

            $sumStmt = $db->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE job_id = ? AND status = 'completed'"
            );
            $sumStmt->execute([$jobId]);
            $paymentsCompleted = (float)$sumStmt->fetchColumn();

            $stats = [
                'proposals_count' => count($proposals),
                'contracts_count' => count($contracts),
                'active_contracts' => count(array_filter($contracts, fn($c) => $c['status'] === 'active')),
                'payments_count' => count($payments),
                'payments_completed' => $paymentsCompleted,
                'disputes_count' => count($disputes),
                'messages_count' => count($messages),
                'invitations_count' => count($invitations),
            ];

            $escrowStmt = $db->prepare(
                "SELECT COALESCE(SUM(amount + COALESCE(platform_fee, 0)), 0) FROM payments WHERE job_id = ? AND status = 'pending'"
            );
            $escrowStmt->execute([$jobId]);
            $stats['escrow_pending'] = (float)$escrowStmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => [
                    'job' => $job,
                    'client' => [
                        'id' => (int)($job['client_id'] ?? $job['client_user_id'] ?? 0),
                        'name' => $job['client_name'] ?? '',
                        'email' => $job['client_email'] ?? '',
                        'avatar_url' => $job['client_avatar'] ?? null,
                        'status' => $job['client_status'] ?? '',
                        'joined' => $job['client_joined'] ?? null,
                    ],
                    'stats' => $stats,
                    'proposals' => $proposals,
                    'contracts' => $contracts,
                    'payments' => $payments,
                    'disputes' => $disputes,
                    'messages' => $messages,
                    'invitations' => $invitations,
                ],
            ]);
        } catch (Exception $e) {
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

    case 'get_client_stats':
        try {
            ensureFreelancerSchema();
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) throw new Exception("Invalid user ID");

            $stmt = $db->prepare("SELECT id, name, created_at, admin_spent_offset, admin_hires_offset FROM users WHERE id = ? AND role = 'client'");
            $stmt->execute([$userId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$client) throw new Exception("Client not found");

            // Real total spent (sum of completed payments made by this client)
            $spentStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ? AND status = 'completed'");
            $spentStmt->execute([$userId]);
            $realSpent = (float)$spentStmt->fetchColumn();

            // Real total hires (count of contracts as client)
            $hiresStmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE client_id = ?");
            $hiresStmt->execute([$userId]);
            $realHires = (int)$hiresStmt->fetchColumn();

            $spentOffset = (float)($client['admin_spent_offset'] ?? 0);
            $hiresOffset = (int)($client['admin_hires_offset'] ?? 0);

            echo json_encode([
                'success' => true,
                'data' => [
                    'name' => $client['name'],
                    'join_date' => date('Y-m-d', strtotime($client['created_at'])),
                    'real_spent' => $realSpent,
                    'real_hires' => $realHires,
                    'spent_offset' => $spentOffset,
                    'hires_offset' => $hiresOffset,
                    'effective_spent' => $realSpent + $spentOffset,
                    'effective_hires' => $realHires + $hiresOffset
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_client_stats':
        try {
            ensureFreelancerSchema();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $userId = (int)($input['user_id'] ?? 0);
            if (!$userId) throw new Exception("Invalid user ID");

            // Verify client exists
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'client'");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) throw new Exception("Client not found");

            // Get real values to compute offsets
            $spentStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = ? AND status = 'completed'");
            $spentStmt->execute([$userId]);
            $realSpent = (float)$spentStmt->fetchColumn();

            $hiresStmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE client_id = ?");
            $hiresStmt->execute([$userId]);
            $realHires = (int)$hiresStmt->fetchColumn();

            $desiredSpent = (float)($input['total_spent'] ?? $realSpent);
            $desiredHires = (int)($input['total_hires'] ?? $realHires);
            $joinDate = !empty($input['join_date']) ? $input['join_date'] : null;

            $spentOffset = $desiredSpent - $realSpent;
            $hiresOffset = $desiredHires - $realHires;

            if ($joinDate) {
                $stmt = $db->prepare("UPDATE users SET created_at = ?, admin_spent_offset = ?, admin_hires_offset = ? WHERE id = ?");
                $stmt->execute([$joinDate . ' 00:00:00', $spentOffset, $hiresOffset, $userId]);
            } else {
                $stmt = $db->prepare("UPDATE users SET admin_spent_offset = ?, admin_hires_offset = ? WHERE id = ?");
                $stmt->execute([$spentOffset, $hiresOffset, $userId]);
            }

            echo json_encode(['success' => true, 'message' => 'Client statistics updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_blogs':
        try {
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $sql = "SELECT id, name, category, image, status, created_at, updated_at FROM blogs WHERE 1=1";
            $params = [];
            if ($status !== '') {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            if ($search !== '') {
                $sql .= " AND (name LIKE ? OR category LIKE ?)";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_blog':
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('Invalid blog ID');
            }
            $stmt = $db->prepare("SELECT * FROM blogs WHERE id = ?");
            $stmt->execute([$id]);
            $blog = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$blog) {
                throw new Exception('Blog not found');
            }
            echo json_encode(['success' => true, 'data' => $blog]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_blog':
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $id = isset($input['id']) && $input['id'] ? (int)$input['id'] : null;
            $name = trim($input['name'] ?? '');
            $category = trim($input['category'] ?? '') ?: null;
            $description = $input['description'] ?? '';
            $image = trim($input['image'] ?? '') ?: null;
            $status = $input['status'] ?? 'draft';

            if ($name === '') {
                throw new Exception('Blog title is required');
            }
            if ($category === null || $category === '') {
                throw new Exception('Category is required');
            }
            if (strlen($category) > 100) {
                throw new Exception('Category must be 100 characters or less');
            }
            if (!in_array($status, ['draft', 'published', 'unpublished'], true)) {
                throw new Exception('Invalid status');
            }

            if ($id) {
                $stmt = $db->prepare("UPDATE blogs SET name = ?, category = ?, image = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $category, $image, $description, $status, $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO blogs (name, category, image, description, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $category, $image, $description, $status]);
                $id = (int)$db->lastInsertId();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Blog saved successfully',
                'id' => $id,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_blog':
        try {
            $id = (int)($_REQUEST['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('Invalid blog ID');
            }
            $stmt = $db->prepare("DELETE FROM blogs WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Blog deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'sync_cms_builtin_pages':
        try {
            $sync = cmsSyncBuiltinPagesToDatabase($db);
            echo json_encode([
                'success' => true,
                'message' => $sync['inserted'] > 0
                    ? "Imported {$sync['inserted']} default page(s)."
                    : 'All default pages are already in the database.',
                'sync' => $sync,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_cms_pages':
        try {
            ensureCmsPagesTable();
            $sync = cmsSyncBuiltinPagesToDatabase($db);
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $section = trim($_GET['footer_section'] ?? '');
            $sql = 'SELECT id, name, slug, footer_section, link_type, link_target, sort_order, show_in_footer, status, seo_title, created_at, updated_at FROM cms_pages WHERE 1=1';
            $params = [];
            if ($search !== '') {
                $sql .= ' AND (name LIKE ? OR slug LIKE ?)';
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }
            if ($status !== '' && in_array($status, ['draft', 'published'], true)) {
                $sql .= ' AND status = ?';
                $params[] = $status;
            }
            if ($section !== '' && in_array($section, cmsFooterSectionOptions(), true)) {
                $sql .= ' AND footer_section = ?';
                $params[] = $section;
            }
            $sql .= ' ORDER BY footer_section ASC, sort_order ASC, name ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            echo json_encode([
                'success' => true,
                'data' => $rows,
                'sync' => $sync,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_cms_page':
        try {
            ensureCmsPagesTable();
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('Invalid page ID');
            }
            $stmt = $db->prepare('SELECT * FROM cms_pages WHERE id = ?');
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$page) {
                throw new Exception('Page not found');
            }
            echo json_encode(['success' => true, 'data' => $page]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_cms_page':
        try {
            ensureCmsPagesTable();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_REQUEST;
            $id = isset($input['id']) && $input['id'] ? (int) $input['id'] : null;
            $name = trim($input['name'] ?? '');
            $slugInput = trim($input['slug'] ?? '');
            $description = $input['description'] ?? '';
            $footerSection = trim($input['footer_section'] ?? '');
            $footerSection = $footerSection !== '' && in_array($footerSection, cmsFooterSectionOptions(), true)
                ? $footerSection : null;
            $linkType = $input['link_type'] ?? 'content';
            if (!in_array($linkType, cmsLinkTypeOptions(), true)) {
                throw new Exception('Invalid link type');
            }
            $linkTarget = trim($input['link_target'] ?? '') ?: null;
            $sortOrder = (int) ($input['sort_order'] ?? 0);
            $showInFooter = !empty($input['show_in_footer']) ? 1 : 0;
            $status = $input['status'] ?? 'draft';
            if (!in_array($status, ['draft', 'published'], true)) {
                throw new Exception('Invalid status');
            }
            $seoTitle = trim($input['seo_title'] ?? '') ?: null;
            $seoDescription = trim($input['seo_description'] ?? '') ?: null;
            $seoKeywords = trim($input['seo_keywords'] ?? '') ?: null;

            if ($name === '') {
                throw new Exception('Page name is required');
            }
            if ($linkType === 'content' && trim(strip_tags((string) $description)) === '') {
                throw new Exception('Page content is required for content pages');
            }
            if ($linkType === 'modal' && ($linkTarget === null || $linkTarget === '')) {
                throw new Exception('Modal key is required for modal links');
            }
            if ($linkType === 'external' && ($linkTarget === null || $linkTarget === '')) {
                throw new Exception('URL is required for external links');
            }

            $slugBase = $slugInput !== '' ? $slugInput : $name;
            $slug = cmsUniqueSlug($db, $slugBase, $id);

            if ($id) {
                $stmt = $db->prepare(
                    'UPDATE cms_pages SET name = ?, slug = ?, description = ?, footer_section = ?, link_type = ?, link_target = ?, sort_order = ?, show_in_footer = ?, status = ?, seo_title = ?, seo_description = ?, seo_keywords = ? WHERE id = ?'
                );
                $stmt->execute([
                    $name, $slug, $description, $footerSection, $linkType, $linkTarget,
                    $sortOrder, $showInFooter, $status, $seoTitle, $seoDescription, $seoKeywords, $id,
                ]);
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO cms_pages (name, slug, description, footer_section, link_type, link_target, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $name, $slug, $description, $footerSection, $linkType, $linkTarget,
                    $sortOrder, $showInFooter, $status, $seoTitle, $seoDescription, $seoKeywords,
                ]);
                $id = (int) $db->lastInsertId();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Page saved successfully',
                'id' => $id,
                'slug' => $slug,
                'public_url' => $linkType === 'content' ? baseUrl('page/' . rawurlencode($slug)) : null,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_cms_page':
        try {
            ensureCmsPagesTable();
            $id = (int) ($_REQUEST['id'] ?? $_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('Invalid page ID');
            }
            $stmt = $db->prepare('DELETE FROM cms_pages WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Page deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_seo_settings':
        try {
            echo json_encode(['success' => true, 'data' => getSeoSettings()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_seo_settings':
        try {
            ensureSeoSettings();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            if (!$input) {
                throw new Exception('No SEO data received');
            }
            $stmt = $db->prepare('UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?');
            foreach (seoSettingKeys() as $key) {
                if (array_key_exists($key, $input)) {
                    $stmt->execute([trim((string) $input[$key]), $key]);
                }
            }
            echo json_encode(['success' => true, 'message' => 'SEO settings saved successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'upload_blog_image':
        try {
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No image uploaded');
            }
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedExts, true)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('Image must be under 5MB');
            }
            $uploadDir = BASE_PATH . '/uploads/blogs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'blog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                throw new Exception('Failed to save image');
            }
            $relativePath = 'uploads/blogs/' . $filename;
            echo json_encode([
                'success' => true,
                'path' => $relativePath,
                'url' => baseUrl($relativePath),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_message_conversations':
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            $search = trim($_GET['search'] ?? '');

            $searchSql = '';
            $searchParams = [];
            if ($search !== '') {
                $searchSql = " AND (
                    ua.name LIKE ? OR ua.email LIKE ? OR
                    ub.name LIKE ? OR ub.email LIKE ?
                )";
                $like = '%' . $search . '%';
                $searchParams = [$like, $like, $like, $like];
            }

            $countSql = "
                SELECT COUNT(*) FROM (
                    SELECT LEAST(sender_id, receiver_id) AS user_a_id, GREATEST(sender_id, receiver_id) AS user_b_id
                    FROM messages
                    GROUP BY user_a_id, user_b_id
                ) pair
                JOIN users ua ON ua.id = pair.user_a_id
                JOIN users ub ON ub.id = pair.user_b_id
                WHERE 1=1 {$searchSql}
            ";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($searchParams);
            $total = (int)$countStmt->fetchColumn();

            $sql = "
                SELECT
                    pair.user_a_id,
                    pair.user_b_id,
                    pair.msg_count,
                    m_last.id AS last_message_id,
                    m_last.message AS last_message,
                    m_last.created_at AS last_time,
                    m_last.sender_id AS last_sender_id,
                    ua.name AS user_a_name,
                    ua.email AS user_a_email,
                    ua.role AS user_a_role,
                    ub.name AS user_b_name,
                    ub.email AS user_b_email,
                    ub.role AS user_b_role
                FROM (
                    SELECT
                        LEAST(sender_id, receiver_id) AS user_a_id,
                        GREATEST(sender_id, receiver_id) AS user_b_id,
                        MAX(id) AS last_msg_id,
                        COUNT(*) AS msg_count
                    FROM messages
                    GROUP BY user_a_id, user_b_id
                ) pair
                JOIN messages m_last ON m_last.id = pair.last_msg_id
                JOIN users ua ON ua.id = pair.user_a_id
                JOIN users ub ON ub.id = pair.user_b_id
                WHERE 1=1 {$searchSql}
                ORDER BY m_last.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $db->prepare($sql);
            $stmt->execute($searchParams);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $conversations,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $total > 0 ? (int)ceil($total / $limit) : 0,
                ],
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_conversation_messages':
        try {
            $userA = (int)($_GET['user_a_id'] ?? 0);
            $userB = (int)($_GET['user_b_id'] ?? 0);
            if ($userA <= 0 || $userB <= 0 || $userA === $userB) {
                throw new Exception('Invalid conversation participants');
            }
            if ($userA > $userB) {
                [$userA, $userB] = [$userB, $userA];
            }

            $limit = min(50, max(10, (int)($_GET['limit'] ?? 30)));
            $beforeId = isset($_GET['before_id']) && $_GET['before_id'] !== ''
                ? (int)$_GET['before_id']
                : null;

            $params = [$userA, $userB, $userB, $userA];
            $beforeSql = '';
            if ($beforeId) {
                $beforeSql = ' AND m.id < ?';
                $params[] = $beforeId;
            }
            $fetchLimit = $limit + 1;

            $stmt = $db->prepare("
                SELECT
                    m.id,
                    m.sender_id,
                    m.receiver_id,
                    m.job_id,
                    m.message,
                    m.attachment_path,
                    m.attachment_name,
                    m.attachment_mime,
                    m.is_read,
                    m.created_at,
                    s.name AS sender_name,
                    s.role AS sender_role,
                    r.name AS receiver_name,
                    j.title AS job_title
                FROM messages m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                LEFT JOIN jobs j ON m.job_id = j.id
                WHERE (
                    (m.sender_id = ? AND m.receiver_id = ?)
                    OR (m.sender_id = ? AND m.receiver_id = ?)
                ) {$beforeSql}
                ORDER BY m.id DESC
                LIMIT " . (int)$fetchLimit . "
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $hasMore = count($rows) > $limit;
            if ($hasMore) {
                array_pop($rows);
            }
            $messages = array_reverse($rows);

            echo json_encode([
                'success' => true,
                'data' => $messages,
                'has_more' => $hasMore,
                'user_a_id' => $userA,
                'user_b_id' => $userB,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'send_admin_direct_message':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $receiverId = (int)($input['receiver_id'] ?? 0);
            $message = trim((string)($input['message'] ?? ''));
            $jobId = isset($input['job_id']) ? (int)$input['job_id'] : 0;

            if ($receiverId <= 0) {
                throw new Exception('Receiver is required');
            }
            if ($receiverId === (int)$user['id']) {
                throw new Exception('Cannot send message to yourself');
            }
            if ($message === '') {
                throw new Exception('Message cannot be empty');
            }

            $receiverStmt = $db->prepare("
                SELECT id, role, status
                FROM users
                WHERE id = ?
                LIMIT 1
            ");
            $receiverStmt->execute([$receiverId]);
            $receiver = $receiverStmt->fetch(PDO::FETCH_ASSOC);
            if (!$receiver) {
                throw new Exception('Receiver not found');
            }
            if ($receiver['role'] === 'admin') {
                throw new Exception('Please select a freelancer or client');
            }
            if (!in_array($receiver['status'] ?? 'active', ['active', 'suspended'], true)) {
                throw new Exception('Cannot message this user in current status');
            }

            $insertStmt = $db->prepare("
                INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $insertStmt->execute([
                (int)$user['id'],
                $receiverId,
                $jobId > 0 ? $jobId : null,
                $message,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Direct message sent successfully',
                'message_id' => (int)$db->lastInsertId(),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_marketing_freelancers':
        try {
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $verified = isset($_GET['verified']) ? trim($_GET['verified']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'active';
            $limit = min(2000, max(1, (int)($_GET['limit'] ?? 2000)));

            $sql = "SELECT id, name, email, is_verified, email_verified_at, status, created_at
                    FROM users WHERE role = 'freelancer'";
            $conditions = [];
            $bind = [];

            if ($status !== '' && in_array($status, ['active', 'suspended', 'closed'], true)) {
                $conditions[] = 'status = :status';
                $bind[':status'] = $status;
            }
            if ($verified === '1') {
                $conditions[] = 'is_verified = 1';
            } elseif ($verified === '0') {
                $conditions[] = '(is_verified = 0 OR is_verified IS NULL)';
            }
            if ($search !== '') {
                $conditions[] = '(name LIKE :search_name OR email LIKE :search_email)';
                $term = '%' . $search . '%';
                $bind[':search_name'] = $term;
                $bind[':search_email'] = $term;
            }
            if ($conditions) {
                $sql .= ' AND ' . implode(' AND ', $conditions);
            }
            $sql .= ' ORDER BY created_at DESC LIMIT :limit';

            $stmt = $db->prepare($sql);
            foreach ($bind as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $freelancers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($freelancers as &$row) {
                $row['is_verified'] = (int)($row['is_verified'] ?? 0);
            }
            unset($row);

            echo json_encode(['success' => true, 'data' => $freelancers, 'count' => count($freelancers)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_countries':
        try {
            ensureCountriesTable();
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');

            $sql = 'SELECT id, name, country_code, phone_code, is_enabled FROM countries WHERE 1=1';
            $bind = [];

            if ($status === 'enabled') {
                $sql .= ' AND is_enabled = 1';
            } elseif ($status === 'disabled') {
                $sql .= ' AND is_enabled = 0';
            }

            if ($search !== '') {
                $sql .= ' AND (name LIKE ? OR country_code LIKE ? OR phone_code LIKE ?)';
                $like = '%' . $search . '%';
                $bind = [$like, $like, $like];
            }

            $sql .= ' ORDER BY name ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute($bind);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $enabledCount = (int) $db->query('SELECT COUNT(*) FROM countries WHERE is_enabled = 1')->fetchColumn();
            $disabledCount = (int) $db->query('SELECT COUNT(*) FROM countries WHERE is_enabled = 0')->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => array_map(static function ($row) {
                    return [
                        'id' => (int) $row['id'],
                        'name' => $row['name'],
                        'country_code' => strtoupper((string) $row['country_code']),
                        'phone_code' => (string) $row['phone_code'],
                        'is_enabled' => (int) $row['is_enabled'] === 1,
                    ];
                }, $rows),
                'stats' => [
                    'enabled' => $enabledCount,
                    'disabled' => $disabledCount,
                    'total' => $enabledCount + $disabledCount,
                ],
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_country_status':
        try {
            ensureCountriesTable();
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid country id');
            }

            if (!array_key_exists('is_enabled', $input)) {
                throw new Exception('is_enabled is required');
            }

            $isEnabled = !empty($input['is_enabled']) ? 1 : 0;
            $stmt = $db->prepare('UPDATE countries SET is_enabled = ? WHERE id = ?');
            $stmt->execute([$isEnabled, $id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Country not found');
            }

            invalidateCountriesCache();

            echo json_encode([
                'success' => true,
                'message' => $isEnabled ? 'Country enabled for signup and profiles' : 'Country hidden from dropdowns',
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'send_promotional_email':
        try {
            require_once __DIR__ . '/../includes/classes/Mailer.php';

            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $subject = trim($input['subject'] ?? '');
            $message = trim($input['message'] ?? '');
            $messageHtmlInput = trim((string)($input['message_html'] ?? ''));
            $userIds = $input['user_ids'] ?? [];

            if ($subject === '') {
                throw new Exception('Email subject is required');
            }
            if ($message === '') {
                throw new Exception('Email message is required');
            }
            if (strlen($subject) > 200) {
                throw new Exception('Subject is too long (max 200 characters)');
            }
            if (strlen($message) > 50000) {
                throw new Exception('Message is too long (max 50000 characters)');
            }

            if (!is_array($userIds)) {
                $userIds = [];
            }
            $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

            if (!$userIds) {
                $verified = isset($input['verified']) ? trim((string)$input['verified']) : '';
                $status = isset($input['status']) ? trim((string)$input['status']) : 'active';
                $search = isset($input['search']) ? trim((string)$input['search']) : '';

                $sql = "SELECT id FROM users WHERE role = 'freelancer'";
                $conditions = [];
                $bind = [];

                if ($status !== '' && in_array($status, ['active', 'suspended', 'closed'], true)) {
                    $conditions[] = 'status = :status';
                    $bind[':status'] = $status;
                }
                if ($verified === '1') {
                    $conditions[] = 'is_verified = 1';
                } elseif ($verified === '0') {
                    $conditions[] = '(is_verified = 0 OR is_verified IS NULL)';
                }
                if ($search !== '') {
                    $conditions[] = '(name LIKE :search_name OR email LIKE :search_email)';
                    $term = '%' . $search . '%';
                    $bind[':search_name'] = $term;
                    $bind[':search_email'] = $term;
                }
                if ($conditions) {
                    $sql .= ' AND ' . implode(' AND ', $conditions);
                }
                $sql .= ' ORDER BY id ASC LIMIT 500';

                $stmt = $db->prepare($sql);
                foreach ($bind as $key => $value) {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
                $stmt->execute();
                $userIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            }

            if (!$userIds) {
                throw new Exception('No recipients selected');
            }
            if (count($userIds) > 500) {
                throw new Exception('Maximum 500 recipients per send. Narrow your selection.');
            }

            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $db->prepare(
                "SELECT id, name, email FROM users WHERE id IN ($placeholders) AND role = 'freelancer'"
            );
            $stmt->execute($userIds);
            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$recipients) {
                throw new Exception('No valid freelancer recipients found');
            }

            $logoUrl = baseUrl('favicon.png');
            $dashboardUrl = baseUrl('freelancer/');

            $messageHtml = '';
            if ($messageHtmlInput !== '') {
                // Allow limited formatting from admin editor while stripping risky markup/attributes.
                $messageHtml = preg_replace('/<\s*(script|style)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $messageHtmlInput);
                $messageHtml = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $messageHtml);
                $messageHtml = preg_replace('/\sstyle\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $messageHtml);
                $messageHtml = preg_replace('/href\s*=\s*([\"\']?)\s*javascript:[^\"\']*\1/i', 'href="#"', $messageHtml);
                $messageHtml = strip_tags($messageHtml, '<p><br><strong><b><em><i><u><ul><ol><li><a><h3><blockquote>');
                $messageHtml = trim($messageHtml);
            }
            if ($messageHtml === '') {
                $messageHtml = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
            }
            $sent = 0;
            $failed = [];

            foreach ($recipients as $recipient) {
                $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
                    <div style='text-align: center; margin-bottom: 25px;'>
                        <img src='" . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;' alt=''>
                        <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
                    </div>
                    <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
                        <p>Hello " . htmlspecialchars($recipient['name'], ENT_QUOTES, 'UTF-8') . ",</p>
                        <div style='margin: 16px 0;'>" . $messageHtml . "</div>
                        <p style='margin-top: 24px;'>
                            <a href='" . htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') . "' style='display: inline-block; background: #14a800; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600;'>Visit RemoWorkers</a>
                        </p>
                        <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
                        <p style='font-size: 11px; color: #9ca3af;'>You received this promotional email because you have a freelancer account on RemoWorkers.<br><br>Best regards,<br><strong>The RemoWorkers Team</strong></p>
                    </div>
                </div>";

                if (Mailer::sendViaBrevo($recipient['email'], $subject, $body)) {
                    $sent++;
                } else {
                    $failed[] = $recipient['email'];
                }
            }

            $msg = "Sent {$sent} of " . count($recipients) . " email(s).";
            if ($failed) {
                $msg .= ' Failed: ' . implode(', ', array_slice($failed, 0, 5));
                if (count($failed) > 5) {
                    $msg .= ' and ' . (count($failed) - 5) . ' more';
                }
            }

            echo json_encode([
                'success' => $sent > 0,
                'message' => $msg,
                'sent' => $sent,
                'failed' => count($failed),
                'total' => count($recipients),
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
