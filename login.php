<?php
/**
 * PESO - Login handler
 * Processes the Log In form submitted from the modal on index.php.
 * Always redirects back (no standalone page) so the landing page stays the UI.
 */
session_start();
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' && $password === '') {
    $errors[] = 'Please enter your email and password.';
} elseif ($email === '') {
    $errors[] = 'Please enter your email.';
} elseif ($password === '') {
    $errors[] = 'Please enter your password.';
} else {
    $stmt = $pdo->prepare('SELECT id, full_name, password, has_seen_tour FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['just_logged_in'] = true;

        // First login after signup: show the "Welcome to PESO" dialog and the
        // guided tour once, then remember it so later logins skip it.
        if (empty($user['has_seen_tour'])) {
            $_SESSION['first_login'] = true;
            $pdo->prepare('UPDATE users SET has_seen_tour = 1 WHERE id = ?')->execute([$user['id']]);
        }

        header('Location: dashboard.php');
        exit;
    }

    $errors[] = 'Incorrect email or password.';
}

$_SESSION['auth_modal'] = 'login';
$_SESSION['auth_errors'] = $errors;
$_SESSION['auth_old'] = ['email' => $email];

header('Location: index.php');
exit;
