<?php
/**
 * PESO - Budget Management
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
$pageTitle = "Budgets - PESO";
$activePage = 'budgets';

$periodType = $_GET['period'] ?? 'month';
if (!in_array($periodType, PESO_PERIOD_TYPES, true) || $periodType === 'custom') {
    $periodType = 'month';
}

$selectedDay = peso_resolve_day($_GET['day'] ?? null);
$selectedWeekStart = peso_resolve_week($_GET['week'] ?? null);
$selectedMonth = peso_resolve_month($_GET['month'] ?? null);

if ($periodType === 'day') {
    $periodStart = $periodEnd = $selectedDay;
} elseif ($periodType === 'week') {
    $periodStart = $selectedWeekStart;
    $periodEnd = date('Y-m-d', strtotime("{$selectedWeekStart} +6 days"));
} else {
    $periodStart = $selectedMonth . '-01';
    $periodEnd = date('Y-m-t', strtotime($periodStart));
}

$periodLabel = peso_period_label($periodType, $periodStart, $periodEnd);
$periodTypeLabel = peso_period_type_label($periodType);
$isPastPeriod = peso_is_past_period($periodType, $periodStart);
$weekOptions = peso_recent_weeks($selectedWeekStart);
$monthOptions = peso_recent_months($selectedMonth);

$ALERT_THRESHOLD = 80;

// Overall budget for this period
$stmt = $pdo->prepare("SELECT amount FROM budgets WHERE user_id = ? AND category = '' AND period_start = ? AND period_end = ?");
$stmt->execute([$userId, $periodStart, $periodEnd]);
$overallBudget = (float) ($stmt->fetchColumn() ?: 0);

// Category budgets set for this period
$stmt = $pdo->prepare("SELECT category, amount FROM budgets WHERE user_id = ? AND category != '' AND period_start = ? AND period_end = ?");
$stmt->execute([$userId, $periodStart, $periodEnd]);
$categoryBudgets = [];
foreach ($stmt->fetchAll() as $row) {
    $categoryBudgets[$row['category']] = (float) $row['amount'];
}

// Spending per category within this period - only expenses explicitly
// tagged for this same period type count toward it (see "Applies To" on
// the expense form).
$stmt = $pdo->prepare(
    'SELECT category, SUM(amount) AS total FROM expenses
     WHERE user_id = ? AND expense_date BETWEEN ? AND ? AND period_type = ? GROUP BY category'
);
$stmt->execute([$userId, $periodStart, $periodEnd, $periodType]);
$categorySpent = [];
$totalSpent = 0;
foreach ($stmt->fetchAll() as $row) {
    $categorySpent[$row['category']] = (float) $row['total'];
    $totalSpent += (float) $row['total'];
}

$overallUsedPct = $overallBudget > 0 ? ($totalSpent / $overallBudget) * 100 : 0;
$overallRemaining = $overallBudget - $totalSpent;

$categoryBudgetSum = array_sum($categoryBudgets);
$unallocated = $overallBudget - $categoryBudgetSum;

$topbarIcon = 'bi-piggy-bank';
$topbarTitle = 'Budgets';
$topbarSubtitle = 'Set spending limits and track how close you are to them.';
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
      <div class="period-tabs">
        <a href="budgets.php?period=day&amp;day=<?php echo $selectedDay; ?>" class="period-tab <?php echo $periodType === 'day' ? 'active' : ''; ?>">Day</a>
        <a href="budgets.php?period=week&amp;week=<?php echo $selectedWeekStart; ?>" class="period-tab <?php echo $periodType === 'week' ? 'active' : ''; ?>">Week</a>
        <a href="budgets.php?period=month&amp;month=<?php echo $selectedMonth; ?>" class="period-tab <?php echo $periodType === 'month' ? 'active' : ''; ?>">Month</a>
      </div>
    </div>

    <div class="dash-card mb-3">
      <!-- Overall budget -->
      <div class="dash-card-header">
        <h2>Overall <?php echo $periodTypeLabel; ?> Budget &mdash; <?php echo $periodLabel; ?></h2>
        <?php if (!$isPastPeriod): ?>
          <button type="button" class="btn btn-forest" data-bs-toggle="modal" data-bs-target="#budgetModal" data-category="" data-amount="<?php echo $overallBudget ?: ''; ?>">
            <i class="bi bi-pencil me-1"></i> <?php echo $overallBudget > 0 ? 'Edit' : 'Set'; ?> Budget
          </button>
        <?php endif; ?>
      </div>

      <?php if ($overallBudget <= 0): ?>
        <div class="empty-state">
          <i class="bi bi-bullseye"></i>
          <?php echo $isPastPeriod
            ? 'No overall budget was set for this period.'
            : "You haven't set an overall budget for this period yet."; ?>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="summary-card-label">Budget</div>
            <div class="summary-card-value"><?php echo peso_format_currency($overallBudget); ?></div>
          </div>
          <div class="col-md-4">
            <div class="summary-card-label">Spent</div>
            <div class="summary-card-value"><?php echo peso_format_currency($totalSpent); ?></div>
          </div>
          <div class="col-md-4">
            <div class="summary-card-label">Remaining</div>
            <div class="summary-card-value <?php echo $overallRemaining < 0 ? '' : 'gold'; ?>" style="<?php echo $overallRemaining < 0 ? 'color:var(--muted-red);' : ''; ?>">
              <?php echo peso_format_currency($overallRemaining); ?>
            </div>
          </div>
        </div>
        <div class="summary-progress mt-3" style="height:8px;">
          <div class="summary-progress-bar" style="width:<?php echo min($overallUsedPct, 100); ?>%; background-color:<?php echo peso_progress_color($overallUsedPct, $ALERT_THRESHOLD); ?>;"></div>
        </div>
        <div class="summary-trend neutral mt-2"><?php echo number_format($overallUsedPct, 0); ?>% used &middot; alerts trigger at <?php echo $ALERT_THRESHOLD; ?>%</div>
        <div class="summary-trend neutral mt-1">
          <?php echo peso_format_currency($categoryBudgetSum); ?> of <?php echo peso_format_currency($overallBudget); ?> allocated
          &middot;
          <span style="<?php echo $unallocated < 0 ? 'color:var(--muted-red);' : ''; ?>"><?php echo peso_format_currency(abs($unallocated)); ?> <?php echo $unallocated < 0 ? 'over-allocated' : 'unallocated'; ?></span>
        </div>
      <?php endif; ?>

      <hr class="my-4" style="border-color:rgba(127, 175, 155, 0.25);">

      <!-- Category budgets -->
      <div class="dash-card-header">
        <h2 style="color:var(--forest-green);">Category Budgets</h2>
      </div>

      <div class="row g-3">
        <?php foreach ($CATEGORIES as $cat):
          $budget = $categoryBudgets[$cat] ?? 0;
          $spent = $categorySpent[$cat] ?? 0;
          $pct = $budget > 0 ? ($spent / $budget) * 100 : 0;
          $remaining = $budget - $spent;
          $barColor = peso_progress_color($pct, $ALERT_THRESHOLD);
        ?>
          <div class="col-md-6 col-xl-3">
            <?php
              $isOverBudget = $budget > 0 && $pct >= 100;
              $isNearBudget = $budget > 0 && !$isOverBudget && $pct >= $ALERT_THRESHOLD;
              $catCardClass = $isOverBudget ? 'is-over-budget' : ($isNearBudget ? 'is-near-budget' : '');
            ?>
            <div class="budget-cat-card h-100 js-card-link <?php echo $catCardClass; ?>" data-href="expenses.php?category=<?php echo urlencode($cat); ?>&amp;date_from=<?php echo urlencode($periodStart); ?>&amp;date_to=<?php echo urlencode($periodEnd); ?>&amp;period_type=<?php echo urlencode($periodType); ?>" title="View <?php echo htmlspecialchars($cat); ?> transactions for this period">
              <div class="budget-cat-card-header">
                <span class="budget-cat-name">
                  <span class="budget-cat-icon" style="background-color:<?php echo peso_category_color($cat); ?>;">
                    <i class="bi <?php echo peso_category_icon($cat); ?>"></i>
                  </span>
                  <?php echo htmlspecialchars($cat); ?>
                </span>
                <span class="d-flex align-items-center gap-2">
                  <?php if ($isOverBudget): ?>
                    <span class="budget-alert-icon danger" title="Over budget"><i class="bi bi-exclamation-triangle-fill"></i></span>
                  <?php elseif ($isNearBudget): ?>
                    <span class="budget-alert-icon warn" title="Near budget limit"><i class="bi bi-exclamation-triangle-fill"></i></span>
                  <?php endif; ?>
                  <?php if (!$isPastPeriod): ?>
                    <button type="button" class="row-action-btn" data-bs-toggle="modal" data-bs-target="#budgetModal" data-category="<?php echo htmlspecialchars($cat); ?>" data-amount="<?php echo $budget ?: ''; ?>" title="Set budget">
                      <i class="bi bi-pencil"></i>
                    </button>
                  <?php endif; ?>
                </span>
              </div>

              <?php if ($isOverBudget): ?>
                <div class="budget-alert-text danger"><i class="bi bi-exclamation-triangle-fill"></i> Over budget</div>
              <?php elseif ($isNearBudget): ?>
                <div class="budget-alert-text warn"><i class="bi bi-exclamation-triangle-fill"></i> Near budget limit</div>
              <?php endif; ?>

              <?php if ($budget > 0): ?>
                <div class="category-bar-track">
                  <div class="category-bar-fill" style="width:<?php echo min($pct, 100); ?>%; background-color:<?php echo $barColor; ?>;"></div>
                </div>
                <div class="budget-cat-amounts">
                  <span><?php echo peso_format_currency($spent); ?> spent</span>
                  <span><?php echo number_format($pct, 0); ?>%</span>
                </div>
                <div class="budget-cat-amounts">
                  <span>Budget: <?php echo peso_format_currency($budget); ?></span>
                  <span style="<?php echo $remaining < 0 ? 'color:var(--muted-red);' : ''; ?>"><?php echo peso_format_currency($remaining); ?> left</span>
                </div>
              <?php else: ?>
                <p class="text-muted small mb-0">No budget set for this period.</p>
                <?php if ($spent > 0): ?>
                  <p class="text-muted small mb-0 mt-1">Spent so far: <?php echo peso_format_currency($spent); ?></p>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/includes/budget_modal.php'; ?>
<?php include __DIR__ . '/includes/ui_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/loading.js"></script>
<script src="assets/js/dashboard.js"></script>
<script src="assets/js/ui-modals.js"></script>
<script src="assets/js/budget-modal.js"></script>
</body>
</html>
