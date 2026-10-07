<?php
/**
 * PESO - Send Password Reset OTP
 * Emails a 6-digit code valid for 3 minutes.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/mailer.php';

$email = trim($_POST['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
    exit;
}

// Invalidate any previous unused codes for this email
$pdo->prepare('UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0')->execute([$email]);

$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', time() + 180); // valid for 3 minutes

$stmt = $pdo->prepare('INSERT INTO password_resets (email, otp_code, expires_at) VALUES (?, ?, ?)');
$stmt->execute([$email, $otp, $expiresAt]);

$subject = 'Your PESO password reset code';
$message = "Hi {$user['full_name']},\n\n"
    . "Your PESO password reset code is: {$otp}\n"
    . "This code is valid for 3 minutes.\n\n"
    . "If you didn't request this, you can safely ignore this email.\n\n"
    . "— PESO Team";
$safeName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
$bodyHtml = '<p>Hi ' . $safeName . ',</p>'
    . '<p>Your PESO password reset code is:</p>'
    . '<p style="font-size:24px;font-weight:bold;letter-spacing:6px;">' . $otp . '</p>'
    . '<p>This code is valid for 3 minutes.</p>'
    . "<p>If you didn't request this, you can safely ignore this email.</p>"
    . '<p>&mdash; PESO Team</p>';

if (!peso_send_mail($email, $subject, $bodyHtml, $message)) {
    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE email = ? AND otp_code = ? AND used = 0')
        ->execute([$email, $otp]);

    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to send the reset email right now. Please check the mail server configuration and try again.',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'A 6-digit code has been sent to your email.',
    'expires_in' => 180,
]);
