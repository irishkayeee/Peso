<?php
/**
 * PESO - Outgoing mail helper (SMTP via PHPMailer)
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/lib/phpmailer/Exception.php';
require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Sends an email via the SMTP account configured in config/mail.php.
 * Returns true on success, false on failure (never throws - callers should
 * treat mail delivery as best-effort and not block the main action on it).
 */
function peso_send_mail(string $to, string $subject, string $bodyHtml, ?string $bodyText = null): bool
{
    if (MAIL_HOST === '' || MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
        error_log("[PESO mail - not configured] to={$to} subject=\"{$subject}\"");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(MAIL_FROM_EMAIL !== '' ? MAIL_FROM_EMAIL : MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = $bodyText ?? trim(strip_tags($bodyHtml));

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('[PESO mail - send failed] ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Notifies the configured admin address that a new account was created.
 */
function peso_notify_new_account(string $fullName, string $email): void
{
    $to = MAIL_NOTIFY_TO !== '' ? MAIL_NOTIFY_TO : MAIL_USERNAME;
    if ($to === '') {
        return;
    }

    $subject = 'New PESO account created';
    $when = date('F j, Y g:i A') . ' PHT';
    $bodyHtml = '<p>A new PESO account was just created:</p>'
        . '<ul>'
        . '<li><strong>Name:</strong> ' . htmlspecialchars($fullName) . '</li>'
        . '<li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>'
        . '<li><strong>Created:</strong> ' . htmlspecialchars($when) . '</li>'
        . '</ul>';

    peso_send_mail($to, $subject, $bodyHtml);
}
