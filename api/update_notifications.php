<?php
/**
 * PESO - Toggle notification preference
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? 1 : 0;

$pdo->prepare('UPDATE users SET notify_budget_alerts = ? WHERE id = ?')->execute([$enabled, $userId]);

echo json_encode(['success' => true]);
