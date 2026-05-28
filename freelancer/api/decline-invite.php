<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/classes/Auth.php';
require_once __DIR__ . '/../../includes/classes/Mailer.php';

header('Content-Type: application/json');
ob_start();

$user = Auth::user();
if (!$user || ($user['role'] ?? '') !== 'freelancer') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if ($data === null) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$invitationId = isset($data['invitation_id']) ? (int)$data['invitation_id'] : 0;

if ($invitationId <= 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid invitation ID.']);
    exit;
}

$db = getDB();
try {
    // Verify invitation belongs to this freelancer and is pending, and fetch client/job details
    $check = $db->prepare("
        SELECT i.id, i.client_id, u.email as client_email, u.name as client_name, j.title as job_title
        FROM job_invitations i
        JOIN users u ON i.client_id = u.id
        JOIN jobs j ON i.job_id = j.id
        WHERE i.id = ? AND i.freelancer_id = ? AND i.status = 'pending'
    ");
    $check->execute([$invitationId, $user['id']]);
    $invite = $check->fetch(PDO::FETCH_ASSOC);
    if (!$invite) {
        throw new Exception('Invitation not found or already processed.');
    }

    // Update status to declined
    $stmt = $db->prepare("UPDATE job_invitations SET status = 'declined' WHERE id = ?");
    $stmt->execute([$invitationId]);

    // Send email to client
    $subject = "Invitation Declined: " . $user['name'] . " for " . $invite['job_title'];
    $logoUrl = baseUrl('favicon.png');
    $findTalentUrl = baseUrl('remoworkers-dashboard?page=find-talent');
    
    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 25px;'>
            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
        </div>
        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
            <p>Hello " . htmlspecialchars($invite['client_name']) . ",</p>
            <p>Freelancer <strong>" . htmlspecialchars($user['name']) . "</strong> has <strong>declined</strong> your invitation to apply for your job post: <strong style='color: #111827;'>" . htmlspecialchars($invite['job_title']) . "</strong>.</p>
            
            <p>You can find and invite other talented freelancers on RemoWorkers to build your dream team.</p>

            <div style='text-align: center; margin: 35px 0;'>
                <a href='" . $findTalentUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Find More Talent</a>
            </div>
            
            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>Best regards,<br><strong>The RemoWorkers Team</strong></p>
        </div>
    </div>";

    try {
        Mailer::send($invite['client_email'], $subject, $emailBody);
    } catch (Exception $mailEx) {
        error_log("Declined invitation email failed: " . $mailEx->getMessage());
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Invitation declined successfully.']);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
