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
    $emailHash = hash('sha256', strtolower($email));
    $pdo->beginTransaction();

    try {
        $pdo->prepare(
            'INSERT IGNORE INTO login_attempts (email_hash, failed_attempts, locked_until)
             VALUES (?, 0, NULL)'
        )->execute([$emailHash]);

        $attemptStmt = $pdo->prepare(
            'SELECT failed_attempts,
                    locked_until IS NOT NULL AND locked_until > NOW() AS is_locked,
                    GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until)) AS cooldown_seconds
             FROM login_attempts
             WHERE email_hash = ?
             FOR UPDATE'
        );
        $attemptStmt->execute([$emailHash]);
        $attempt = $attemptStmt->fetch();

        if ($attempt['is_locked']) {
            $pdo->commit();
            $_SESSION['auth_lockout_until'] = time() + (int) $attempt['cooldown_seconds'];
        } else {
            unset($_SESSION['auth_lockout_until']);
            $failedAttempts = (int) $attempt['failed_attempts'];
            if ($failedAttempts >= 5) {
                $failedAttempts = 0;
                $pdo->prepare(
                    'UPDATE login_attempts SET failed_attempts = 0, locked_until = NULL WHERE email_hash = ?'
                )->execute([$emailHash]);
            }

            $stmt = $pdo->prepare('SELECT id, full_name, password, has_seen_tour FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $pdo->prepare('DELETE FROM login_attempts WHERE email_hash = ?')->execute([$emailHash]);
                $firstLogin = empty($user['has_seen_tour']);
                if ($firstLogin) {
                    $pdo->prepare('UPDATE users SET has_seen_tour = 1 WHERE id = ?')->execute([$user['id']]);
                }
                $pdo->commit();

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['just_logged_in'] = true;

                // First login after signup: show the "Welcome to PESO" dialog and the
                // guided tour once, then remember it so later logins skip it.
                if ($firstLogin) {
                    $_SESSION['first_login'] = true;
                }

                header('Location: dashboard.php');
                exit;
            }

            $failedAttempts++;
            $updateSql = $failedAttempts >= 5
                ? 'UPDATE login_attempts SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE email_hash = ?'
                : 'UPDATE login_attempts SET failed_attempts = ?, locked_until = NULL WHERE email_hash = ?';
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$failedAttempts, $emailHash]);
            $pdo->commit();

            if ($failedAttempts >= 5) {
                $_SESSION['auth_lockout_until'] = time() + 300;
            } else {
                $attemptsRemaining = 5 - $failedAttempts;
                $errors[] = "Incorrect email or password. {$attemptsRemaining} attempt(s) remaining before a 5-minute cooldown.";
            }
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$_SESSION['auth_modal'] = 'login';
$_SESSION['auth_errors'] = $errors;
$_SESSION['auth_old'] = ['email' => $email];

header('Location: index.php');
exit;
