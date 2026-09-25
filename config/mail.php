<?php
/**
 * PESO - Outgoing mail configuration (SMTP via PHPMailer)
 *
 * Fill in the values below with your sending account's details, e.g. for
 * Gmail: host smtp.gmail.com, port 587, your Gmail address as the username,
 * and a Google Account "App Password" (not your normal password) as
 * MAIL_PASSWORD - generate one at https://myaccount.google.com/apppasswords
 *
 * Any other SMTP provider (Outlook, a custom domain mailbox, etc.) works the
 * same way - just use that provider's host/port/username/password.
 */

define('MAIL_HOST', '');           // e.g. 'smtp.gmail.com'
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');       // the PESO sending email address
define('MAIL_PASSWORD', '');       // SMTP password / app password
define('MAIL_FROM_EMAIL', '');     // usually same as MAIL_USERNAME
define('MAIL_FROM_NAME', 'PESO');
define('MAIL_ENCRYPTION', 'tls');  // 'tls' or 'ssl'

// Where "new account created" notifications are sent. Defaults to the
// sending address itself if left blank.
define('MAIL_NOTIFY_TO', '');
