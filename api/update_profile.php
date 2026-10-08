<?php
/**
 * PESO - Update profile information (first + last name, email)
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
$firstName = trim(preg_replace('/\s+/', ' ', $_POST['first_name'] ?? ''));
$lastName = trim(preg_replace('/\s+/', ' ', $_POST['last_name'] ?? ''));
$email = trim($_POST['email'] ?? '');

if ($firstName === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your first name.']);
    exit;
}

if ($lastName === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your last name.']);
    exit;
}

// Still stored as a single full_name column.
$fullName = $firstName . ' ' . $lastName;

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
