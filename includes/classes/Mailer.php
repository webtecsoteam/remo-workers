<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class Mailer {
    /**
     * Send an email using primary SMTP (MAIL_* in .env).
     */
    public static function send($to, $subject, $htmlBody, $textBody = '') {
        return self::sendSmtp($to, $subject, $htmlBody, $textBody, 'default');
    }

    /**
     * Send an email using Brevo SMTP (MAIL_BREVO_* in .env).
     */
    public static function sendViaBrevo($to, $subject, $htmlBody, $textBody = '') {
        return self::sendSmtp($to, $subject, $htmlBody, $textBody, 'brevo');
    }

    /**
     * @param 'default'|'brevo' $driver
     */
    private static function sendSmtp($to, $subject, $htmlBody, $textBody, $driver) {
        $mail = new PHPMailer(true);

        try {
            $prefix = $driver === 'brevo' ? 'MAIL_BREVO_' : 'MAIL_';
            $defaults = $driver === 'brevo'
                ? ['host' => 'smtp-relay.brevo.com', 'port' => 587, 'user' => '', 'pass' => '']
                : ['host' => 'smtp.hostinger.com', 'port' => 465, 'user' => 'support@remoworkers.com', 'pass' => ''];

            $mail->isSMTP();
            $mail->Host       = env($prefix . 'HOST', $defaults['host']);
            $mail->SMTPAuth   = true;
            $mail->Username   = env($prefix . 'USERNAME', $defaults['user']);
            $mail->Password   = env($prefix . 'PASSWORD', $defaults['pass']);
            $mail->Port       = intval(env($prefix . 'PORT', (string) $defaults['port']));

            if ($mail->Port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Timeout = 10;

            if ($driver === 'brevo') {
                $fromAddress = env('MAIL_BREVO_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'support@remoworkers.com'));
                $fromName    = env('MAIL_BREVO_FROM_NAME', env('MAIL_FROM_NAME', 'RemoWorkers'));
            } else {
                $fromAddress = env('MAIL_FROM_ADDRESS', 'support@remoworkers.com');
                $fromName    = env('MAIL_FROM_NAME', 'RemoWorkers');
            }

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer Error (' . $driver . '): ' . $mail->ErrorInfo);
            return false;
        }
    }
}
