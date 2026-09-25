<?php
/**
 * PESO - Verify OTP and Reset Password
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($email === '' || $otp === '') {
    echo json_encode(['success' => false, 'message' => 'Missing email or code.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, expires_at FROM password_resets
     WHERE email = ? AND otp_code = ? AND used = 0
     ORDER BY id DESC LIMIT 1'
);
$stmt->execute([$email, $otp]);
$reset = $stmt->fetch();

if (!$reset) {
    echo json_encode(['success' => false, 'message' => 'Invalid code. Please check and try again.']);
    exit;
}

if (strtotime($reset['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'This code has expired. Please request a new one.']);
    exit;
}

$userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$userStmt->execute([$email]);
$user = $userStmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hashedPassword, $user['id']]);
$pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);

echo json_encode(['success' => true, 'message' => 'Your password has been reset. You can now log in.']);
