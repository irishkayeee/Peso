<?php
/**
 * PESO - Outgoing mail configuration (SMTP via PHPMailer)
 *
 * The Gmail address and Google Account App Password go in
 * config/mail.local.php (gitignored, never committed), e.g.:
 *
 *   <?php
 *   define('MAIL_USERNAME', 'you@gmail.com');
 *   define('MAIL_PASSWORD', 'yourapppassword');
 *
 * Do not use your normal Gmail password.
 */

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);

if (is_file(__DIR__ . '/mail.local.php')) {
    require __DIR__ . '/mail.local.php';
}
defined('MAIL_USERNAME') || define('MAIL_USERNAME', '');
defined('MAIL_PASSWORD') || define('MAIL_PASSWORD', '');

define('MAIL_FROM_EMAIL', MAIL_USERNAME);
define('MAIL_FROM_NAME', 'PESO');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_CA_FILE', __DIR__ . '/mail-ca-bundle.pem');

// Where "new account created" notifications are sent. Defaults to the
// sending address itself if left blank.
define('MAIL_NOTIFY_TO', '');
