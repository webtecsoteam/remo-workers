<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

function json_response($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    json_response(['success' => false, 'error' => 'Client login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $id = intval($_POST['id'] ?? $_POST['job_id'] ?? 0);

    if (empty($id)) {
        json_response(['success' => false, 'error' => 'Invalid job ID']);
    }

    try {
        // Verify ownership
        $check = $db->prepare("SELECT id FROM jobs WHERE id = ? AND client_id = ?");
        $check->execute([$id, $user['id']]);
        if (!$check->fetch()) {
            json_response(['success' => false, 'error' => 'Unauthorized or job post not found']);
        }

        // 1. Temporarily disable FK checks BEFORE starting the transaction
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 2. Start a database transaction for safe cascading deletion
        $db->beginTransaction();

        // 3. Delete reviews associated with contracts of this job
        $delReviews = $db->prepare("DELETE FROM reviews WHERE contract_id IN (SELECT id FROM contracts WHERE job_id = ?)");
        $delReviews->execute([$id]);

        // 4. Delete work logs associated with contracts of this job
        $delWorkLogs = $db->prepare("DELETE FROM work_logs WHERE contract_id IN (SELECT id FROM contracts WHERE job_id = ?)");
        $delWorkLogs->execute([$id]);

        // 5. Delete milestones associated with contracts or proposals of this job
        $delMilestones = $db->prepare("DELETE FROM milestones WHERE proposal_id IN (SELECT id FROM proposals WHERE job_id = ?) OR contract_id IN (SELECT id FROM contracts WHERE job_id = ?)");
        $delMilestones->execute([$id, $id]);

        // 6. Delete contracts associated with this job
        $delContracts = $db->prepare("DELETE FROM contracts WHERE job_id = ?");
        $delContracts->execute([$id]);

        // 7. Delete associated proposals
        $delProposals = $db->prepare("DELETE FROM proposals WHERE job_id = ?");
        $delProposals->execute([$id]);

        // 8. Delete the job post itself
        $delJob = $db->prepare("DELETE FROM jobs WHERE id = ? AND client_id = ?");
        $delJob->execute([$id, $user['id']]);
        
        // 9. Commit the transaction
        $db->commit();

        // 10. Re-enable FK checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

        json_response(['success' => true, 'message' => 'Job post and all associated records deleted successfully']);
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
