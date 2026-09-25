<?php
/**
 * PESO - Delete Expense
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid expense.']);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Expense not found.']);
    exit;
}

echo json_encode(['success' => true]);
