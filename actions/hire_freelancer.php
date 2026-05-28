<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

header('Content-Type: application/json');

$suspendedError = Auth::suspendedClientError();
if ($suspendedError) {
    echo json_encode(['success' => false, 'error' => $suspendedError]);
    exit;
}

$user = Auth::user();
if (!$user || $user['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$db = getDB();
ensureAgencySchema();
$proposalId = $_POST['proposal_id'] ?? 0;

if (!$proposalId) {
    echo json_encode(['success' => false, 'error' => 'Proposal ID is required']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Get proposal, job and freelancer details
    $stmt = $db->prepare("
        SELECT p.*, j.client_id, j.budget_type, j.title as job_title,
               u.name as freelancer_name, u.email as freelancer_email
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$proposalId]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposal || $proposal['client_id'] != $user['id']) {
        throw new Exception("Invalid proposal");
    }

    if (($proposal['budget_type'] ?? '') === 'hourly') {
        $balanceError = Auth::hourlyContractBalanceError($user['id'], $db, true);
        if ($balanceError) {
            throw new Exception($balanceError);
        }
    }

    // 2. Create contract
    $cStmt = $db->prepare("INSERT INTO contracts (job_id, client_id, freelancer_id, agency_id, proposal_id, amount, contract_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $cStmt->execute([
        $proposal['job_id'],
        $user['id'],
        $proposal['freelancer_id'],
        $proposal['agency_id'] ?: null,
        $proposal['id'],
        $proposal['bid_amount'],
        $proposal['budget_type']
    ]);
    $contractId = $db->lastInsertId();

    // 2.1 Link milestones to the contract
    $db->prepare("UPDATE milestones SET contract_id = ? WHERE proposal_id = ?")->execute([$contractId, $proposalId]);

    // 3. Update proposal status
    $db->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?")->execute([$proposalId]);

    // 4. Update job status to in_progress
    $db->prepare("UPDATE jobs SET status = 'in_progress' WHERE id = ?")->execute([$proposal['job_id']]);

    $db->commit();

    // Send congratulatory email to the freelancer
    $subject = "Congratulations! You've been hired on RemoWorkers";
    $contractUrl = baseUrl('remoworkers-dashboard?page=contracts');
    $logoUrl = baseUrl('favicon.png');
    
    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($proposal['freelancer_name']) . ",</p>
            <p style='font-size: 18px; color: #14a800; font-weight: bold; margin-bottom: 20px;'>Congratulations! You've been hired!</p>
            <p><strong>" . htmlspecialchars($user['name']) . "</strong> has hired you for the project: <strong style='color: #111827;'>" . htmlspecialchars($proposal['job_title']) . "</strong>.</p>
            
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f9fafb; border-radius: 8px; overflow: hidden;'>
                <tr>
                    <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; font-weight: bold; color: #4b5563; width: 40%;'>Contract Type:</td>
                    <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #111827; text-transform: capitalize;'>" . htmlspecialchars($proposal['budget_type']) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px 15px; font-weight: bold; color: #4b5563;'>Budget / Rate:</td>
                    <td style='padding: 12px 15px; color: #111827; font-weight: bold;'>$" . number_format($proposal['bid_amount'], 2) . "</td>
                </tr>
            </table>

            <p>Your contract is now active. You can view your contract details, start tracking time or request milestones directly from your dashboard.</p>

            <div style='text-align: center; margin: 35px 0;'>
                <a href='" . $contractUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Go to My Contracts</a>
            </div>
            
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Team</strong></p>
        </div>
    </div>";

    try {
        require_once __DIR__ . '/../includes/classes/Mailer.php';
        Mailer::send($proposal['freelancer_email'], $subject, $emailBody);
    } catch (Exception $mailEx) {
        error_log("Proposal hire congratulations email failed: " . $mailEx->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Freelancer hired successfully!']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
