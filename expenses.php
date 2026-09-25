<?php
/**
 * PESO - Expense Management
 */
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/categories.php';
require_once __DIR__ . '/includes/budget_period.php';

$userId = (int) $_SESSION['user_id'];
$pageTitle = "Expenses - PESO";
$activePage = 'expenses';

$budgetSnapshot = peso_budget_snapshot($pdo, $userId, $CATEGORIES);

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$periodTypeFilter = trim($_GET['period_type'] ?? '');
if (!in_array($periodTypeFilter, PESO_PERIOD_TYPES, true)) {
    $periodTypeFilter = '';
}

$where = ['user_id = ?'];
$params = [$userId];

if ($search !== '') {
    $where[] = '(description LIKE ? OR category LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($categoryFilter !== '' && in_array($categoryFilter, $CATEGORIES, true)) {
    $where[] = 'category = ?';
    $params[] = $categoryFilter;
}

if ($dateFrom !== '') {
    $where[] = 'expense_date >= ?';
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = 'expense_date <= ?';
    $params[] = $dateTo;
}

if ($periodTypeFilter !== '') {
    $where[] = 'period_type = ?';
    $params[] = $periodTypeFilter;
}

$whereSql = implode(' AND ', $where);

$categoryViewLabel = null;
if ($periodTypeFilter !== '' && in_array($categoryFilter, $CATEGORIES, true) && $dateFrom !== '' && $dateTo !== '') {
    $categoryViewLabel = htmlspecialchars($categoryFilter) . ' &middot; ' . htmlspecialchars(peso_period_label($periodTypeFilter, $dateFrom, $dateTo));
}

$stmt = $pdo->prepare("SELECT id, amount, category, payment_method, description, expense_date, period_type, created_at FROM expenses WHERE {$whereSql} ORDER BY expense_date DESC, id DESC LIMIT 200");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$totalFiltered = array_sum(array_column($expenses, 'amount'));

$topbarIcon = 'bi-receipt';
$topbarTitle = 'Expenses';
$topbarSubtitle = 'Manage and track all your transactions.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Caveat:wght@500;600&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/auth.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="dash-body">

<div class="dash-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="dash-card mb-3">
      <form class="filter-bar" method="GET" action="expenses.php">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" name="search" placeholder="Search description or category" value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <select class="dash-select" name="category">
          <option value="">All Categories</option>
          <?php foreach ($CATEGORIES as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
          <?php endforeach; ?>
        </select>

        <input type="date" class="dash-select" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" title="From date">
        <input type="date" class="dash-select" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" title="To date">

        <button type="submit" class="btn btn-outline-forest">Filter</button>
        <?php if ($search || $categoryFilter || $dateFrom || $dateTo): ?>
          <a href="expenses.php" class="btn btn-outline-forest">Clear</a>
        <?php endif; ?>

        <button type="button" class="btn btn-forest ms-auto" data-bs-toggle="modal" data-bs-target="#expenseModal">
          <i class="bi bi-plus-lg me-1"></i> Add Expense
        </button>
      </form>
    </div>

    <div class="dash-card">
      <div class="dash-card-header">
        <h2>
          Transactions
          <?php if ($categoryViewLabel): ?>
            <span class="text-muted" style="font-weight:500; font-size:0.85rem;">&mdash; <?php echo $categoryViewLabel; ?></span>
          <?php endif; ?>
        </h2>
        <span class="dash-select" style="border:none; background:none; font-weight:700; color:var(--forest-green);">
          Total: <?php echo peso_format_currency($totalFiltered); ?>
        </span>
      </div>

      <?php if (empty($expenses)): ?>
        <div class="empty-state">
          <i class="bi bi-receipt"></i>
          No expenses found<?php echo ($search || $categoryFilter || $dateFrom || $dateTo) ? ' for these filters.' : ' yet. Add your first expense!'; ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="dash-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Payment Method</th>
                <th>Applies To</th>
                <th class="text-end">Amount</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($expenses as $e): ?>
                <tr>
                  <td>
                    <?php echo date('M j, Y', strtotime($e['expense_date'])); ?>
                    <div class="text-muted small"><?php echo date('g:i A', strtotime($e['created_at'])); ?> PHT</div>
                  </td>
                  <td>
                    <span class="category-pill" style="background-color:<?php echo peso_category_color($e['category']); ?>22; color:<?php echo peso_category_color($e['category']); ?>;">
                      <i class="bi <?php echo peso_category_icon($e['category']); ?>"></i> <?php echo htmlspecialchars($e['category']); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($e['description'] ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($e['payment_method']); ?></td>
                  <td><span class="period-type-pill"><?php echo htmlspecialchars(peso_period_type_label($e['period_type'])); ?></span></td>
                  <td class="text-end fw-semibold"><?php echo peso_format_currency($e['amount']); ?></td>
                  <td class="text-end">
                    <button type="button" class="row-action-btn js-edit-expense"
                      data-bs-toggle="modal" data-bs-target="#expenseModal"
                      data-id="<?php echo $e['id']; ?>"
                      data-amount="<?php echo $e['amount']; ?>"
                      data-category="<?php echo htmlspecialchars($e['category']); ?>"
                      data-date="<?php echo $e['expense_date']; ?>"
                      data-payment_method="<?php echo htmlspecialchars($e['payment_method']); ?>"
                      data-period_type="<?php echo htmlspecialchars($e['period_type']); ?>"
                      data-description="<?php echo htmlspecialchars($e['description']); ?>"
                      title="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="row-action-btn danger js-delete-expense"
                      data-confirm-delete data-name="this expense" data-id="<?php echo $e['id']; ?>" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/includes/expense_modal.php'; ?>
<?php include __DIR__ . '/includes/ui_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/loading.js"></script>
<script src="assets/js/dashboard.js"></script>
<script src="assets/js/ui-modals.js"></script>
<script>window.PESO_BUDGET_SNAPSHOT = <?php echo json_encode($budgetSnapshot); ?>;</script>
<script src="assets/js/expense-modal.js"></script>
</body>
</html>
