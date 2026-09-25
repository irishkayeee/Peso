<?php
/**
 * PESO - Set / Update Budget (overall or per-category, for a given period)
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/categories.php';
require_once __DIR__ . '/../includes/budget_period.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$category = trim($_POST['category'] ?? '');
$amount = (float) ($_POST['amount'] ?? 0);
$periodType = trim($_POST['period_type'] ?? 'month');

if (!in_array($periodType, PESO_PERIOD_TYPES, true)) {
    $periodType = 'month';
}

// Day/Week/Month are always the current instance - never trust client-posted dates.
$range = peso_period_range($periodType);
$periodStart = $range['start'];
$periodEnd = $range['end'];

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid budget amount.']);
    exit;
}

if ($category !== '' && !in_array($category, $CATEGORIES, true)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid category.']);
    exit;
}

if ($category === '') {
    // Overall budget: can't drop below what's already allocated to categories in this same period.
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM budgets WHERE user_id = ? AND category != '' AND period_start = ? AND period_end = ?");
    $stmt->execute([$userId, $periodStart, $periodEnd]);
    $categorySum = (float) $stmt->fetchColumn();

    if ($amount < $categorySum) {
        echo json_encode(['success' => false, 'message' => 'Overall budget can\'t be lower than what you\'ve already allocated to categories (₱' . number_format($categorySum, 2) . '). Lower those category budgets first.']);
        exit;
    }
} else {
    // Category budget: total across all categories can't exceed the overall budget for this same period (if one is set).
    $stmt = $pdo->prepare("SELECT amount FROM budgets WHERE user_id = ? AND category = '' AND period_start = ? AND period_end = ?");
    $stmt->execute([$userId, $periodStart, $periodEnd]);
    $overallBudget = (float) ($stmt->fetchColumn() ?: 0);

    if ($overallBudget > 0) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM budgets WHERE user_id = ? AND category != '' AND category != ? AND period_start = ? AND period_end = ?");
        $stmt->execute([$userId, $category, $periodStart, $periodEnd]);
        $otherCategorySum = (float) $stmt->fetchColumn();

        if ($otherCategorySum + $amount > $overallBudget) {
            $remaining = max($overallBudget - $otherCategorySum, 0);
            echo json_encode(['success' => false, 'message' => 'That exceeds your overall budget. You have ₱' . number_format($remaining, 2) . ' left to allocate.']);
            exit;
        }
    }
}

$stmt = $pdo->prepare(
    'INSERT INTO budgets (user_id, category, period_type, period_start, period_end, amount) VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE amount = VALUES(amount), period_type = VALUES(period_type)'
);
$stmt->execute([$userId, $category, $periodType, $periodStart, $periodEnd, $amount]);

echo json_encode(['success' => true]);
