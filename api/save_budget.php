<?php
/** PESO - Set or update the overall budget for a period */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/budget_period.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$amount = (float) ($_POST['amount'] ?? 0);
$periodType = trim($_POST['period_type'] ?? 'month');

if (!in_array($periodType, PESO_PERIOD_TYPES, true)) {
    $periodType = 'month';
}

// The budget page accepts only one overall amount per period.
$range = peso_period_range($periodType);
$periodStart = $range['start'];
$periodEnd = $range['end'];

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid budget amount.']);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO budgets (user_id, category, period_type, period_start, period_end, amount) VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE amount = VALUES(amount), period_type = VALUES(period_type)'
);
$stmt->execute([$userId, '', $periodType, $periodStart, $periodEnd, $amount]);

echo json_encode(['success' => true]);
