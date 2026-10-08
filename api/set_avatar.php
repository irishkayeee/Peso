<?php
/**
 * PESO - Pick one of the preset cute avatars (assets/img/avatars/*.svg)
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$PRESET_AVATARS = ['piggy', 'coin', 'wallet', 'moneybag', 'sprout'];

$avatar = $_POST['avatar'] ?? '';
if (!in_array($avatar, $PRESET_AVATARS, true)) {
    echo json_encode(['success' => false, 'message' => 'Please choose one of the available avatars.']);
    exit;
}

$relativePath = 'assets/img/avatars/' . $avatar . '.svg';

$stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
$stmt->execute([$userId]);
$oldPicture = $stmt->fetchColumn();

$pdo->prepare('UPDATE users SET profile_picture = ? WHERE id = ?')->execute([$relativePath, $userId]);

// Remove the user's previously uploaded photo (never touch the shared preset files)
if ($oldPicture && strpos($oldPicture, 'assets/uploads/avatars/') === 0 && is_file(__DIR__ . '/../' . $oldPicture)) {
    unlink(__DIR__ . '/../' . $oldPicture);
}

echo json_encode(['success' => true, 'message' => 'Avatar updated.', 'profile_picture' => $relativePath]);
