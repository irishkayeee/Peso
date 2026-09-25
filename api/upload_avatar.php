<?php
/**
 * PESO - Upload / replace profile picture
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'Please choose an image to upload.']);
    exit;
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed. Please try again.']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Image must be 2MB or smaller.']);
    exit;
}

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.']);
    exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/avatars/';
$fileName = 'user_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];

$stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
$stmt->execute([$userId]);
$oldPicture = $stmt->fetchColumn();

if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
    echo json_encode(['success' => false, 'message' => 'Could not save the image. Please try again.']);
    exit;
}

$relativePath = 'assets/uploads/avatars/' . $fileName;

$pdo->prepare('UPDATE users SET profile_picture = ? WHERE id = ?')->execute([$relativePath, $userId]);

if ($oldPicture && $oldPicture !== $relativePath && is_file(__DIR__ . '/../' . $oldPicture)) {
    unlink(__DIR__ . '/../' . $oldPicture);
}

echo json_encode(['success' => true, 'message' => 'Profile picture updated.', 'profile_picture' => $relativePath]);
