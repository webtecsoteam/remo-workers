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
if (!$user) {
    json_response(['success' => false, 'error' => 'Login required']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $job_id = intval($_POST['job_id'] ?? 0);

    if (!$receiver_id || !$message) {
        json_response(['success' => false, 'error' => 'Receiver and message are required']);
    }

    $db = getDB();
    try {
        $stmt = $db->prepare("
            INSERT INTO messages (sender_id, receiver_id, job_id, message, is_read) 
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([$user['id'], $receiver_id, $job_id ?: null, $message]);
        $newMsgId = $db->lastInsertId();
        
        // Check if receiver is offline to send email notification
        $rcvStmt = $db->prepare("SELECT name, email, role, last_active_at FROM users WHERE id = ?");
        $rcvStmt->execute([$receiver_id]);
        $receiver = $rcvStmt->fetch();
        
        if ($receiver) {
            $isAutomated = false;
            $automatedPrefixes = ['CREATED MILESTONE:', 'AUTOMATED MESSAGE:', 'PROPOSED MILESTONE:'];
            foreach ($automatedPrefixes as $prefix) {
                if (strpos($message, $prefix) === 0) {
                    $isAutomated = true;
                    break;
                }
            }
            
            if (!$isAutomated) {
                $isOffline = false;
                if (empty($receiver['last_active_at'])) {
                    $isOffline = true;
                } else {
                    $lastActiveTime = strtotime($receiver['last_active_at']);
                    // Consider offline if no activity in last 5 minutes (300 seconds)
                    if ((time() - $lastActiveTime) > 300) {
                        $isOffline = true;
                    }
                }
                
                if ($isOffline) {
                    require_once __DIR__ . '/../includes/classes/Mailer.php';
                    
                    $senderName = $user['name'];
                    $subject = "New Message on RemoWorkers from " . $senderName;
                    
                    $dashboardUrl = baseUrl($receiver['role'] === 'client' ? 'client' : 'remoworkers-dashboard');
                    $logoUrl = baseUrl('favicon.png');
                    
                    $msgPreview = htmlspecialchars($message);
                    if (strlen($msgPreview) > 300) {
                        $msgPreview = substr($msgPreview, 0, 300) . '...';
                    }
                    
                    $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #d5e0d5; border-radius: 12px; background-color: #ffffff;'>
                        <div style='text-align: center; margin-bottom: 25px;'>
                            <img src='" . $logoUrl . "' style='width: 32px; height: 32px; vertical-align: middle; margin-right: 8px;'>
                            <span style='color: #14a800; font-size: 24px; font-weight: 800; vertical-align: middle;'>RemoWorkers</span>
                        </div>
                        <div style='font-size: 15px; line-height: 1.6; color: #374151;'>
                            <p>Hello " . htmlspecialchars($receiver['name']) . ",</p>
                            <p>You received a new message from <strong>" . htmlspecialchars($senderName) . "</strong> while you were offline:</p>
                            <div style='background-color: #f9fafb; border-left: 4px solid #14a800; border-radius: 4px; padding: 15px; margin: 20px 0; font-style: italic; color: #4b5563; font-size: 14.5px; line-height: 1.5;'>
                                \"" . nl2br($msgPreview) . "\"
                            </div>
                            <div style='text-align: center; margin: 35px 0;'>
                                <a href='" . $dashboardUrl . "' style='background-color: #14a800; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 15px; box-shadow: 0 4px 12px rgba(20,168,0,0.2);'>Reply in Chat</a>
                            </div>
                            <hr style='border: 0; border-top: 1px solid #d5e0d5; margin: 30px 0;'>
                            <p style='font-size: 11px; color: #9ca3af;'>To stop receiving offline email notifications, log back into RemoWorkers.<br>Best regards,<br><strong>The RemoWorkers Team</strong></p>
                        </div>
                    </div>";
                    
                    Mailer::send($receiver['email'], $subject, $body);
                    
                    // Log offline email notification for easy testing
                    $logDir = __DIR__ . '/../scratch';
                    if (!file_exists($logDir)) {
                        mkdir($logDir, 0777, true);
                    }
                    $logFile = $logDir . '/email_notifications.log';
                    $logEntry = "[" . date('Y-m-d H:i:s') . "] OFFLINE MSG EMAIL TO: " . $receiver['email'] . " (" . $receiver['name'] . ") from " . $senderName . "\n";
                    $logEntry .= "SUBJECT: " . $subject . "\n";
                    $logEntry .= "BODY (HTML):\n" . $body . "\n";
                    $logEntry .= "--------------------------------------------------\n\n";
                    file_put_contents($logFile, $logEntry, FILE_APPEND);
                }
            }
        }
        
        json_response(['success' => true, 'message_id' => $newMsgId]);
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'error' => 'Invalid request method']);
}
