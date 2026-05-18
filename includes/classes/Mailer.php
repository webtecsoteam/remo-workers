<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class Mailer {
    /**
     * Send an email using SMTP configurations from .env
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML content of the email
     * @param string $textBody Plain text backup content
     * @return bool True if sent successfully, False otherwise
     */
    public static function send($to, $subject, $htmlBody, $textBody = '') {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST', 'smtp.hostinger.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME', 'support@remoworkers.com');
            $mail->Password   = env('MAIL_PASSWORD', 'u8e9-dwaj-5hmh-lihk');
            $mail->Port       = intval(env('MAIL_PORT', 465));

            // Determine encryption based on Port
            if ($mail->Port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            }

            // Connection timeout
            $mail->Timeout = 10;

            // Recipients
            $fromAddress = env('MAIL_FROM_ADDRESS', 'support@remoworkers.com');
            $fromName    = env('MAIL_FROM_NAME', 'RemoWorkers');
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
