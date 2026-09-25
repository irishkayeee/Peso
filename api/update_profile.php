<?php
/**
 * PESO - Update profile information (full name, email)
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_validation.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($fullName === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your full name.']);
    exit;
}

$emailError = peso_validate_real_email($email);
if ($emailError !== null) {
    echo json_encode(['success' => false, 'message' => $emailError]);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
$stmt->execute([$email, $userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'That email is already used by another account.']);
    exit;
}

$stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?');
$stmt->execute([$fullName, $email, $userId]);

$_SESSION['full_name'] = $fullName;

echo json_encode(['success' => true, 'message' => 'Profile updated successfully.', 'full_name' => $fullName]);
