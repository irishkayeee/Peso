<?php
/**
 * PESO - Outgoing mail configuration (SMTP via PHPMailer)
 *
 * Add your Gmail address and Google Account App Password below.
 * Do not use your normal Gmail password.
 *
 * Keep this file out of public repositories and do not commit it after adding
 * credentials.
 */

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', ''); // Your Gmail address
define('MAIL_PASSWORD', ''); // Your Gmail App Password, without spaces
define('MAIL_FROM_EMAIL', MAIL_USERNAME);
define('MAIL_FROM_NAME', 'PESO');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_CA_FILE', __DIR__ . '/mail-ca-bundle.pem');

// Where "new account created" notifications are sent. Defaults to the
// sending address itself if left blank.
define('MAIL_NOTIFY_TO', '');
