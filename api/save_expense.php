<?php
/**
 * PESO - Add / Update Expense
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
$id = (int) ($_POST['id'] ?? 0);
$amount = (float) ($_POST['amount'] ?? 0);
$category = trim($_POST['category'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? '');
$description = trim($_POST['description'] ?? '');
$expenseDate = trim($_POST['expense_date'] ?? '');
$periodType = trim($_POST['period_type'] ?? 'month');

if (!in_array($periodType, PESO_PERIOD_TYPES, true)) {
    $periodType = 'month';
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid amount.']);
    exit;
}

if (!in_array($category, $CATEGORIES, true)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid category.']);
    exit;
}

if (!in_array($paymentMethod, $PAYMENT_METHODS, true)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid payment method.']);
    exit;
}

$dateObj = DateTime::createFromFormat('Y-m-d', $expenseDate);
if (!$dateObj || $dateObj->format('Y-m-d') !== $expenseDate) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid date.']);
    exit;
}

// ---- Budget cap: don't allow an expense that would push a set budget
// (category-specific, or the overall budget) over its limit for the
// applicable Day/Week/Month period. ----
if ($periodType === 'day') {
    $periodStart = $periodEnd = $expenseDate;
} elseif ($periodType === 'week') {
    $periodStart = peso_monday_of($expenseDate);
    $periodEnd = date('Y-m-d', strtotime($periodStart . ' +6 days'));
} else {
    $periodStart = date('Y-m-01', strtotime($expenseDate));
    $periodEnd = date('Y-m-t', strtotime($expenseDate));
}

$catSpentStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(amount), 0) FROM expenses
     WHERE user_id = ? AND category = ? AND expense_date BETWEEN ? AND ? AND period_type = ? AND id != ?'
);
$catSpentStmt->execute([$userId, $category, $periodStart, $periodEnd, $periodType, $id]);
$categorySpentSoFar = (float) $catSpentStmt->fetchColumn();

$catBudgetStmt = $pdo->prepare(
    "SELECT amount FROM budgets WHERE user_id = ? AND category = ? AND period_start = ? AND period_end = ?"
);
$catBudgetStmt->execute([$userId, $category, $periodStart, $periodEnd]);
$categoryBudget = (float) ($catBudgetStmt->fetchColumn() ?: 0);

if ($categoryBudget > 0 && ($categorySpentSoFar + $amount) > $categoryBudget) {
    $left = max($categoryBudget - $categorySpentSoFar, 0);
    echo json_encode(['success' => false, 'message' => "This would go over your {$category} budget \u{2014} only " . peso_format_currency($left) . ' left for this period.']);
    exit;
}

$totalSpentStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(amount), 0) FROM expenses
     WHERE user_id = ? AND expense_date BETWEEN ? AND ? AND period_type = ? AND id != ?'
);
$totalSpentStmt->execute([$userId, $periodStart, $periodEnd, $periodType, $id]);
$totalSpentSoFar = (float) $totalSpentStmt->fetchColumn();

$overallBudgetStmt = $pdo->prepare(
    "SELECT amount FROM budgets WHERE user_id = ? AND category = '' AND period_start = ? AND period_end = ?"
);
$overallBudgetStmt->execute([$userId, $periodStart, $periodEnd]);
$overallBudget = (float) ($overallBudgetStmt->fetchColumn() ?: 0);

if ($overallBudget > 0 && ($totalSpentSoFar + $amount) > $overallBudget) {
    $left = max($overallBudget - $totalSpentSoFar, 0);
    echo json_encode(['success' => false, 'message' => 'This would go over your overall ' . strtolower(peso_period_type_label($periodType)) . " budget \u{2014} only " . peso_format_currency($left) . ' left for this period.']);
    exit;
}

if ($id > 0) {
    $check = $pdo->prepare('SELECT id FROM expenses WHERE id = ? AND user_id = ?');
    $check->execute([$id, $userId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Expense not found.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'UPDATE expenses SET amount = ?, category = ?, payment_method = ?, description = ?, expense_date = ?, period_type = ? WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$amount, $category, $paymentMethod, $description, $expenseDate, $periodType, $id, $userId]);
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO expenses (user_id, amount, category, payment_method, description, expense_date, period_type) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $amount, $category, $paymentMethod, $description, $expenseDate, $periodType]);
}

echo json_encode(['success' => true]);
