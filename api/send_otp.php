<?php
/**
 * PESO - Send Password Reset OTP
 * Emails a 6-digit code valid for 3 minutes.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

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
$headers = "From: PESO <no-reply@peso.local>\r\nContent-Type: text/plain; charset=UTF-8";

$mailSent = @mail($email, $subject, $message, $headers);

// Local/dev fallback: XAMPP does not send real mail unless sendmail/SMTP is configured.
// Log the OTP server-side so it can still be tested during development.
if (!$mailSent) {
    error_log("[PESO OTP - mail() not configured] {$email} -> {$otp} (expires {$expiresAt})");
}

echo json_encode([
    'success' => true,
    'message' => 'A 6-digit code has been sent to your email.',
    'expires_in' => 180,
]);
