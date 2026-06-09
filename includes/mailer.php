<?php
/**
 * Thin PHPMailer wrapper for NEO V-TRACK outbound mail (OTP codes, notices).
 * Config comes from includes/secrets.php -> 'smtp'. Returns bool; on failure the
 * human-readable reason is written to $error (also error_log'd).
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/secrets_loader.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/SMTP.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('nv_send_mail')) {
    /**
     * @param array $override per-call SMTP overrides (host/port/secure/...), used by the self-test.
     */
    function nv_send_mail(string $to, string $subject, string $htmlBody, ?string &$error = null, array $override = []): bool
    {
        $cfg = array_merge([
            'host' => 'localhost', 'port' => 25, 'secure' => '', 'auth' => false,
            'username' => '', 'password' => '',
            'from_email' => 'noreply@uitm.edu.my', 'from_name' => 'NEO V-TRACK', 'timeout' => 12,
        ], (array) nv_secret('smtp', []), $override);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host    = $cfg['host'];
            $mail->Port    = (int) $cfg['port'];
            $mail->Timeout = (int) $cfg['timeout'];
            $mail->SMTPAuth = (bool) $cfg['auth'];
            if ($cfg['auth']) {
                $mail->Username = $cfg['username'];
                $mail->Password = $cfg['password'];
            }
            if ($cfg['secure'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($cfg['secure'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            // Intranet relays often use self-signed certs.
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $htmlBody;
            $mail->AltBody = trim(strip_tags(preg_replace('/<br\s*\/?>(\n)?|<\/p>/i', "\n", $htmlBody)));
            $mail->CharSet = 'UTF-8';

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            if (!empty($mail->ErrorInfo)) {
                $error .= ' | ' . $mail->ErrorInfo;
            }
            error_log('nv_send_mail failed: ' . $error);
            return false;
        }
    }
}
