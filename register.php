<?php
/**
 * PESO - Registration handler
 * Processes the Sign Up form submitted from the modal on index.php.
 * Always redirects back (no standalone page) so the landing page stays the UI.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/email_validation.php';
require_once __DIR__ . '/includes/mailer.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$errors = [];
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($firstName === '') {
    $errors[] = 'Please enter your first name.';
}

if ($lastName === '') {
    $errors[] = 'Please enter your last name.';
}

$emailError = peso_validate_real_email($email);
if ($emailError !== null) {
    $errors[] = $emailError;
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
    $errors[] = 'Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

$fullName = trim($firstName . ' ' . $lastName);

if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $errors[] = 'An account with that email already exists.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$fullName, $email, $hashedPassword]);

        peso_notify_new_account($fullName, $email);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['full_name'] = $fullName;
        $_SESSION['just_logged_in'] = true;
        $_SESSION['just_registered'] = true;

        header('Location: dashboard.php');
        exit;
    }
}

$_SESSION['auth_modal'] = 'register';
$_SESSION['auth_errors'] = $errors;
$_SESSION['auth_old'] = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email];

header('Location: index.php');
exit;
