<?php
/**
 * PESO - Dashboard
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
$pageTitle = "Dashboard - PESO";
$activePage = 'dashboard';

$budgetSnapshot = peso_budget_snapshot($pdo, $userId, $CATEGORIES);

$showWelcome = !empty($_SESSION['just_logged_in']);
$isNewSignup = !empty($_SESSION['just_registered']);
unset($_SESSION['just_logged_in'], $_SESSION['just_registered']);

$selectedMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$selectedDay = $_GET['day'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDay) || $selectedDay > date('Y-m-d')) {
    $selectedDay = date('Y-m-d');
}

$thisWeekStart = peso_monday_of(date('Y-m-d'));
$selectedWeekStart = $_GET['week'] ?? $thisWeekStart;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedWeekStart)) {
    $selectedWeekStart = $thisWeekStart;
} else {
    $selectedWeekStart = peso_monday_of($selectedWeekStart);
    if ($selectedWeekStart > $thisWeekStart) {
        $selectedWeekStart = $thisWeekStart;
    }
}
$selectedWeekEnd = date('Y-m-d', strtotime($selectedWeekStart . ' +6 days'));

// Last 12 weeks (including the current one) for the week picker
$weekOptions = [];
for ($i = 0; $i < 12; $i++) {
    $wStart = date('Y-m-d', strtotime($thisWeekStart . " -{$i} weeks"));
    $wEnd = date('Y-m-d', strtotime($wStart . ' +6 days'));
    $weekOptions[$wStart] = peso_period_label('week', $wStart, $wEnd);
}
if (!isset($weekOptions[$selectedWeekStart])) {
    $weekOptions[$selectedWeekStart] = peso_period_label('week', $selectedWeekStart, $selectedWeekEnd);
}
krsort($weekOptions);

$monthStart = $selectedMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$lastMonthStart = date('Y-m-01', strtotime($monthStart . ' -1 month'));
$lastMonthEnd = date('Y-m-t', strtotime($monthStart . ' -1 month'));

// Last 12 months (including the current one) for the month picker
$monthOptions = [];
for ($i = 0; $i < 12; $i++) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $monthOptions[$m] = date('F Y', strtotime($m . '-01'));
}
if (!isset($monthOptions[$selectedMonth])) {
    $monthOptions[$selectedMonth] = date('F Y', strtotime($monthStart));
}
krsort($monthOptions);

// ---- Category breakdown (selected month; drives the biggest-mover insight) ----
$stmt = $pdo->prepare(
    'SELECT category, SUM(amount) AS total FROM expenses
     WHERE user_id = ? AND expense_date BETWEEN ? AND ?
     GROUP BY category ORDER BY total DESC'
);
$stmt->execute([$userId, $monthStart, $monthEnd]);
$categoryBreakdown = $stmt->fetchAll();

// ---- Shared chart range: Day / Week / Month, drives the summary cards,
// Spending Breakdown, and Category Analysis together. Month reuses the
// month picker above (so you can browse past months); Day/Week are always
// the current day/week (same as the Budgets page). ----
$range = $_GET['range'] ?? 'month';
if (!in_array($range, ['day', 'week', 'month'], true)) {
    $range = 'month';
}

if ($range === 'month') {
    $chartPeriodStart = $monthStart;
    $chartPeriodEnd = $monthEnd;
} elseif ($range === 'day') {
    $chartPeriodStart = $chartPeriodEnd = $selectedDay;
} else {
    $chartPeriodStart = $selectedWeekStart;
    $chartPeriodEnd = $selectedWeekEnd;
}
$chartPeriodLabel = peso_period_label($range, $chartPeriodStart, $chartPeriodEnd);

$stmt->execute([$userId, $chartPeriodStart, $chartPeriodEnd]);
$chartCategoryBreakdown = $stmt->fetchAll();
$chartTotal = array_sum(array_column($chartCategoryBreakdown, 'total'));

// Previous equivalent period (for the "vs. last period" comparisons)
if ($range === 'day') {
    $prevStart = $prevEnd = date('Y-m-d', strtotime($chartPeriodStart . ' -1 day'));
} elseif ($range === 'week') {
    $prevEnd = date('Y-m-d', strtotime($chartPeriodStart . ' -1 day'));
    $prevStart = date('Y-m-d', strtotime($prevEnd . ' -6 days'));
} else {
    $prevStart = $lastMonthStart;
    $prevEnd = $lastMonthEnd;
}
$periodSumStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?');
$periodSumStmt->execute([$userId, $prevStart, $prevEnd]);
$chartPrevTotal = (float) $periodSumStmt->fetchColumn();

$periodTrendPct = $chartPrevTotal > 0 ? (($chartTotal - $chartPrevTotal) / $chartPrevTotal) * 100 : ($chartTotal > 0 ? 100 : 0);

// Overall budget for the selected period (day / week / month budgets are
// tracked separately, same as on the Budgets page)
$periodBudgetStmt = $pdo->prepare("SELECT amount FROM budgets WHERE user_id = ? AND category = '' AND period_type = ? AND period_start = ?");
$periodBudgetStmt->execute([$userId, $range, $chartPeriodStart]);
$periodBudget = (float) ($periodBudgetStmt->fetchColumn() ?: 0);
$periodRemaining = $periodBudget - $chartTotal;
$periodUsedPct = $periodBudget > 0 ? min(($chartTotal / $periodBudget) * 100, 999) : 0;

// "Savings" = money you didn't spend. If a budget is set, that's the most
// meaningful baseline (budget - spent). Otherwise fall back to comparing
// against the previous equivalent period.
if ($periodBudget > 0) {
    $savingsMode = 'budget';
    $periodSavings = max($periodBudget - $chartTotal, 0);
    $periodSavingsPct = ($periodBudget > 0 && $periodSavings > 0) ? ($periodSavings / $periodBudget) * 100 : 0;
} elseif ($chartPrevTotal > 0) {
    $savingsMode = 'compare';
    $periodSavings = max($chartPrevTotal - $chartTotal, 0);
    $periodSavingsPct = $periodSavings > 0 ? ($periodSavings / $chartPrevTotal) * 100 : 0;
} else {
    $savingsMode = 'none';
    $periodSavings = 0;
    $periodSavingsPct = 0;
}
$noCurrentSpendingYet = $savingsMode === 'compare' && $chartTotal <= 0 && $chartPrevTotal > 0;
// Nothing logged yet isn't real savings - don't show last period's whole total as "saved".
if ($noCurrentSpendingYet) {
    $periodSavings = 0;
    $periodSavingsPct = 0;
}

$rangeBudgetLabel = peso_period_type_label($range) . ' Budget';
$rangeCompareLabel = ['day' => 'vs. yesterday', 'week' => 'vs. last week', 'month' => 'vs. last month'][$range];
$rangeSavingsLabel = ['day' => "Today's Savings", 'week' => "This Week's Savings", 'month' => "This Month's Savings"][$range];
$rangeNoSpendLabel = ['day' => 'yet today', 'week' => 'yet this week', 'month' => 'yet this month'][$range];
$isPastPeriod = ($range === 'month' && $selectedMonth < date('Y-m'))
    || ($range === 'day' && $selectedDay < date('Y-m-d'))
    || ($range === 'week' && $selectedWeekStart < $thisWeekStart);
$rangePastNoun = ['day' => 'day', 'week' => 'week', 'month' => 'month'][$range];

// Category totals last month (for highest-category trend)
$stmt = $pdo->prepare(
    'SELECT category, SUM(amount) AS total FROM expenses
     WHERE user_id = ? AND expense_date BETWEEN ? AND ?
     GROUP BY category'
);
$stmt->execute([$userId, $lastMonthStart, $lastMonthEnd]);
$categoryLastMonth = [];
foreach ($stmt->fetchAll() as $row) {
    $categoryLastMonth[$row['category']] = (float) $row['total'];
}

// ---- Key Insights: an auto-generated text summary for the active period.
// Consolidates what used to be the separate Insights page (top category,
// spending change, average spend, budget status, unusual transactions, and
// rule-based recommendations) into one place, driven by the same Day /
// Week / Month range as the rest of the dashboard. ----
$rangeNowLabel = ['day' => 'today', 'week' => 'this week', 'month' => 'this month'][$range];
$rangeLowerLabel = strtolower(peso_period_type_label($range)); // daily / weekly / monthly
$keyInsights = [];

// Full expense list for the active period (drives the average metric and
// the unusual-spending check below).
$stmt = $pdo->prepare('SELECT amount, category, description, expense_date FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?');
$stmt->execute([$userId, $chartPeriodStart, $chartPeriodEnd]);
$periodExpenses = $stmt->fetchAll();
$avgTransaction = count($periodExpenses) > 0 ? $chartTotal / count($periodExpenses) : 0;

if ($range === 'day') {
    $txnCount = count($periodExpenses);
    if ($txnCount > 0) {
        $keyInsights[] = [
            'icon' => 'bi-speedometer2',
            'text' => 'You\'re spending about ' . peso_format_currency($avgTransaction) . ' per transaction'
                . ' — ' . $txnCount . ' transaction' . ($txnCount === 1 ? '' : 's') . ' ' . $rangeNowLabel . '.',
        ];
    }
} else {
    if ($range === 'week') {
        $daysElapsed = $isPastPeriod ? 7 : (int) date('N');
    } else {
        $daysElapsed = $isPastPeriod ? (int) date('t', strtotime($chartPeriodStart)) : min((int) date('j'), (int) date('t'));
    }
    if ($daysElapsed > 0 && $chartTotal > 0) {
        $keyInsights[] = [
            'icon' => 'bi-speedometer2',
            'text' => 'You\'re spending about ' . peso_format_currency($chartTotal / $daysElapsed) . ' per day ' . $rangeNowLabel
                . ', based on ' . $daysElapsed . ' day' . ($daysElapsed === 1 ? '' : 's') . ' ' . ($isPastPeriod ? 'in that ' . $range . '.' : 'so far.'),
        ];
    }
}

// Unusual spending: a single transaction notably above the period's average transaction size
$unusualExpense = null;
if ($avgTransaction > 0) {
    foreach ($periodExpenses as $exp) {
        if ($exp['amount'] >= $avgTransaction * 2.5 && (!$unusualExpense || $exp['amount'] > $unusualExpense['amount'])) {
            $unusualExpense = $exp;
        }
    }
}
if ($unusualExpense) {
    $keyInsights[] = [
        'icon' => 'bi-exclamation-triangle-fill',
        'text' => peso_format_currency($unusualExpense['amount']) . ' on ' . htmlspecialchars($unusualExpense['category'])
            . ($unusualExpense['description'] ? ' (' . htmlspecialchars($unusualExpense['description']) . ')' : '')
            . ' on ' . date('M j, Y', strtotime($unusualExpense['expense_date'])) . ' was notably higher than your usual transaction size.',
    ];
}

if ($chartTotal > 0 && !empty($chartCategoryBreakdown)) {
    $periodTopCategory = $chartCategoryBreakdown[0];
    $periodTopCategoryPct = ($periodTopCategory['total'] / $chartTotal) * 100;
    $keyInsights[] = [
        'icon' => 'bi-star-fill',
        'text' => htmlspecialchars($periodTopCategory['category']) . ' is your top spending category ' . $rangeNowLabel
            . ', making up ' . number_format($periodTopCategoryPct, 0) . '% of your total expenses.',
    ];
}

if ($chartPrevTotal > 0) {
    $trendDir = $periodTrendPct >= 0 ? 'increased' : 'decreased';
    $keyInsights[] = [
        'icon' => $periodTrendPct >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow',
        'text' => 'Your total spending has ' . $trendDir . ' by ' . number_format(abs($periodTrendPct), 0) . '% ' . $rangeCompareLabel . '.',
    ];
}

if ($periodBudget > 0) {
    if ($periodRemaining >= 0) {
        $keyInsights[] = [
            'icon' => 'bi-check-circle-fill',
            'text' => "You've used " . number_format($periodUsedPct, 0) . '% of your ' . $rangeBudgetLabel . ', with ' . peso_format_currency($periodRemaining) . ' left.',
        ];
    } else {
        $keyInsights[] = [
            'icon' => 'bi-exclamation-triangle-fill',
            'text' => "You're over your " . $rangeBudgetLabel . ' by ' . peso_format_currency(abs($periodRemaining)) . '.',
        ];
    }
}

// Biggest month-over-month mover, only meaningful when browsing by month
if ($range === 'month') {
    $insightThisMonth = [];
    foreach ($categoryBreakdown as $c) {
        $insightThisMonth[$c['category']] = (float) $c['total'];
    }
    $biggestMoverCategory = null;
    $biggestMoverDiff = 0;
    foreach ($CATEGORIES as $cat) {
        $diff = ($insightThisMonth[$cat] ?? 0) - ($categoryLastMonth[$cat] ?? 0);
        if (abs($diff) > abs($biggestMoverDiff)) {
            $biggestMoverDiff = $diff;
            $biggestMoverCategory = $cat;
        }
    }
    if ($biggestMoverCategory !== null && $biggestMoverDiff != 0) {
        $keyInsights[] = [
            'icon' => $biggestMoverDiff >= 0 ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle',
            'text' => htmlspecialchars($biggestMoverCategory) . ' spending ' . ($biggestMoverDiff >= 0 ? 'increased' : 'decreased')
                . ' by ' . peso_format_currency(abs($biggestMoverDiff)) . ' compared to last month.',
        ];
    }
}

// Rule-based recommendations (tagged so the UI can style them as tips)
if (isset($periodTopCategoryPct) && $periodTopCategoryPct >= 40) {
    $keyInsights[] = [
        'icon' => 'bi-lightbulb-fill',
        'tip' => true,
        'text' => 'Your "' . htmlspecialchars($periodTopCategory['category']) . '" spending makes up ' . number_format($periodTopCategoryPct, 0) . '% of your budget ' . $rangeNowLabel . ' — consider setting a category budget to keep it in check.',
    ];
}
if ($periodBudget > 0 && $periodUsedPct >= 100) {
    $keyInsights[] = [
        'icon' => 'bi-lightbulb-fill',
        'tip' => true,
        'text' => "You've gone over your {$rangeLowerLabel} budget. Try reviewing your recent expenses in the Expenses tab to see where you can cut back.",
    ];
} elseif ($periodBudget > 0 && $periodUsedPct >= 80) {
    $keyInsights[] = [
        'icon' => 'bi-lightbulb-fill',
        'tip' => true,
        'text' => "You're close to your {$rangeLowerLabel} budget limit. Consider slowing down on non-essential spending.",
    ];
}
if ($periodBudget <= 0 && !$isPastPeriod) {
    $keyInsights[] = [
        'icon' => 'bi-lightbulb-fill',
        'tip' => true,
        'text' => "You haven't set a {$rangeLowerLabel} budget yet. Setting one helps PESO warn you before you overspend.",
    ];
}
if ($chartPrevTotal > 0 && $periodTrendPct < -10) {
    $keyInsights[] = [
        'icon' => 'bi-lightbulb-fill',
        'tip' => true,
        'text' => 'Great job! Your spending is down ' . number_format(abs($periodTrendPct), 0) . '% ' . $rangeCompareLabel . ' — keep up the good habits.',
    ];
}

if (empty(array_filter($keyInsights, function ($i) { return !empty($i['tip']); })) && $chartTotal > 0) {
    $keyInsights[] = [
        'icon' => 'bi-lightbulb-fill',
        'tip' => true,
        'text' => "You're on track {$rangeNowLabel}. Keep logging your expenses to get more personalized tips.",
    ];
}

if (empty($keyInsights)) {
    $keyInsights[] = [
        'icon' => 'bi-info-circle',
        'text' => 'Log a few expenses to start seeing insights about your spending here.',
    ];
}

// ---- Budget alert: category with highest utilization vs its own budget.
// Always the current calendar month, regardless of the Day/Week/Month
// toggle or month picker (FR-18). ----
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');

$stmt = $pdo->prepare(
    'SELECT category, SUM(amount) AS total FROM expenses
     WHERE user_id = ? AND expense_date BETWEEN ? AND ?
     GROUP BY category'
);
$stmt->execute([$userId, $currentMonthStart, $currentMonthEnd]);
$currentMonthBreakdown = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT category, amount FROM budgets WHERE user_id = ? AND category != '' AND period_type = 'month' AND period_start = ?");
$stmt->execute([$userId, $currentMonthStart]);
$categoryBudgets = $stmt->fetchAll();

$budgetAlert = null;
foreach ($categoryBudgets as $cb) {
    $spent = 0;
    foreach ($currentMonthBreakdown as $c) {
        if ($c['category'] === $cb['category']) {
            $spent = (float) $c['total'];
            break;
        }
    }
    if ($cb['amount'] <= 0) continue;
    $pct = ($spent / $cb['amount']) * 100;
    if ($pct >= 80 && (!$budgetAlert || $pct > $budgetAlert['pct'])) {
        $budgetAlert = ['category' => $cb['category'], 'pct' => $pct, 'spent' => $spent, 'budget' => (float) $cb['amount']];
    }
}

// ---- Recent transactions ----
$stmt = $pdo->prepare(
    'SELECT id, amount, category, description, expense_date FROM expenses
     WHERE user_id = ? ORDER BY expense_date DESC, id DESC LIMIT 4'
);
$stmt->execute([$userId]);
$recentTransactions = $stmt->fetchAll();

// ---- Spending trend: same shared $range drives the history granularity ----
$trendLabels = [];
$trendData = [];
$trendSumStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?');

if ($range === 'day') {
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $trendSumStmt->execute([$userId, $d, $d]);
        $trendLabels[] = date('D', strtotime($d));
        $trendData[] = (float) $trendSumStmt->fetchColumn();
    }
    $trendRangeLabel = 'Last 7 Days';
} elseif ($range === 'week') {
    $trendThisWeekStart = new DateTime($thisWeekStart);
    for ($i = 7; $i >= 0; $i--) {
        $wStart = (clone $trendThisWeekStart)->modify('-' . ($i * 7) . ' days');
        $wEnd = (clone $wStart)->modify('+6 days');
        $trendSumStmt->execute([$userId, $wStart->format('Y-m-d'), $wEnd->format('Y-m-d')]);
        $trendLabels[] = $wStart->format('M j');
        $trendData[] = (float) $trendSumStmt->fetchColumn();
    }
    $trendRangeLabel = 'Last 8 Weeks';
} else {
    for ($i = 5; $i >= 0; $i--) {
        $label = date('M', strtotime("-{$i} months"));
        $mStart = date('Y-m-01', strtotime("-{$i} months"));
        $mEnd = date('Y-m-t', strtotime("-{$i} months"));
        $trendSumStmt->execute([$userId, $mStart, $mEnd]);
        $trendLabels[] = $label;
        $trendData[] = (float) $trendSumStmt->fetchColumn();
    }
    $trendRangeLabel = 'Last 6 Months';
}

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', trim($_SESSION['full_name'] ?? 'there'))[0];

$topbarIcon = 'bi-sun';
$topbarTitle = "{$greeting}, {$firstName}!";
if ($range === 'day' && $selectedDay !== date('Y-m-d')) {
    $topbarSubtitle = "Here's your spending overview for " . date('F j, Y', strtotime($selectedDay)) . '.';
} elseif ($range === 'week' && $selectedWeekStart !== $thisWeekStart) {
    $topbarSubtitle = "Here's your spending overview for {$weekOptions[$selectedWeekStart]}.";
} elseif ($range === 'month' && $selectedMonth !== date('Y-m')) {
    $topbarSubtitle = "Here's your spending overview for {$monthOptions[$selectedMonth]}.";
} else {
    $topbarSubtitle = "Here's your spending overview for this month.";
}
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
<body class="dash-body dashboard-page">

<div class="dash-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="dash-fold">

    <div class="dash-fold-controls d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="period-tabs">
          <a href="dashboard.php?range=day&amp;day=<?php echo $selectedDay; ?>" class="period-tab <?php echo $range === 'day' ? 'active' : ''; ?>">Day</a>
          <a href="dashboard.php?range=week&amp;week=<?php echo $selectedWeekStart; ?>" class="period-tab <?php echo $range === 'week' ? 'active' : ''; ?>">Week</a>
          <a href="dashboard.php?range=month&amp;month=<?php echo $selectedMonth; ?>" class="period-tab <?php echo $range === 'month' ? 'active' : ''; ?>">Month</a>
        </div>

        <?php if ($range === 'month'): ?>
          <select class="dash-select" onchange="location.href='dashboard.php?range=month&month=' + this.value">
            <?php foreach ($monthOptions as $value => $label): ?>
              <option value="<?php echo $value; ?>" <?php echo $value === $selectedMonth ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        <?php elseif ($range === 'day'): ?>
          <input type="date" class="dash-select" value="<?php echo $selectedDay; ?>" max="<?php echo date('Y-m-d'); ?>"
            onchange="location.href='dashboard.php?range=day&day=' + this.value">
        <?php elseif ($range === 'week'): ?>
          <select class="dash-select" onchange="location.href='dashboard.php?range=week&week=' + this.value">
            <?php foreach ($weekOptions as $value => $label): ?>
              <option value="<?php echo $value; ?>" <?php echo $value === $selectedWeekStart ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <a href="api/export_report.php?month=<?php echo $selectedMonth; ?>" class="btn btn-outline-forest">
        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
      </a>
    </div>

    <!-- Summary cards -->
    <div class="dash-fold-summary row g-3 mb-3">
      <div class="col-6 col-lg-3">
        <div class="dash-card summary-card h-100">
          <div>
            <div class="summary-card-label">Total Expenses</div>
            <div class="summary-card-value"><?php echo peso_format_currency($chartTotal); ?></div>
            <?php if ($chartPrevTotal > 0): ?>
              <div class="summary-trend <?php echo $periodTrendPct >= 0 ? 'down' : 'up'; ?>">
                <i class="bi bi-arrow-<?php echo $periodTrendPct >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo number_format(abs($periodTrendPct), 0); ?>% <?php echo $rangeCompareLabel; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="summary-card-icon"><i class="bi bi-receipt"></i></div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="dash-card summary-card h-100">
          <div style="width:100%;">
            <div class="summary-card-label"><?php echo $rangeBudgetLabel; ?></div>
            <?php if ($periodBudget > 0): ?>
              <div class="summary-card-value"><?php echo peso_format_currency($periodBudget); ?></div>
              <div class="summary-progress"><div class="summary-progress-bar" style="width:<?php echo min($periodUsedPct, 100); ?>%; background-color:<?php echo peso_progress_color($periodUsedPct); ?>;"></div></div>
              <div class="summary-trend neutral"><?php echo number_format($periodUsedPct, 0); ?>% used</div>
            <?php elseif ($isPastPeriod): ?>
              <div class="summary-card-value" style="font-size:1rem;">Not set</div>
              <div class="summary-trend neutral">No budget was set that <?php echo $rangePastNoun; ?></div>
            <?php else: ?>
              <div class="summary-card-value" style="font-size:1rem;">Not set</div>
              <a href="budgets.php?period=<?php echo $range; ?>" class="summary-trend neutral" style="text-decoration:underline;">Set a budget</a>
            <?php endif; ?>
          </div>
          <div class="summary-card-icon"><i class="bi bi-bullseye"></i></div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="dash-card summary-card h-100">
          <div>
            <div class="summary-card-label">Remaining Budget</div>
            <div class="summary-card-value gold"><?php echo peso_format_currency(max($periodRemaining, 0)); ?></div>
            <?php if ($periodBudget > 0 && $periodRemaining < 0): ?>
              <div class="summary-trend down"><i class="bi bi-exclamation-triangle"></i> Over budget</div>
            <?php endif; ?>
          </div>
          <div class="summary-card-icon gold"><i class="bi bi-wallet2"></i></div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="dash-card summary-card h-100">
          <div>
            <div class="summary-card-label"><?php echo $rangeSavingsLabel; ?></div>
            <div class="summary-card-value"><?php echo peso_format_currency($periodSavings); ?></div>
            <?php if ($savingsMode === 'budget' && $periodSavings > 0): ?>
              <div class="summary-trend up"><i class="bi bi-arrow-up"></i> <?php echo number_format($periodSavingsPct, 0); ?>% of budget unspent</div>
            <?php elseif ($savingsMode === 'budget'): ?>
              <div class="summary-trend neutral">Budget fully used</div>
            <?php elseif ($noCurrentSpendingYet): ?>
              <div class="summary-trend neutral">No expenses logged <?php echo $rangeNoSpendLabel; ?></div>
            <?php elseif ($savingsMode === 'compare' && $periodSavings > 0): ?>
              <div class="summary-trend up"><i class="bi bi-arrow-up"></i> <?php echo number_format($periodSavingsPct, 0); ?>% <?php echo $rangeCompareLabel; ?></div>
            <?php endif; ?>
          </div>
          <div class="summary-card-icon"><i class="bi bi-piggy-bank"></i></div>
        </div>
      </div>
    </div>

    <!-- Breakdown + insights -->
    <div class="dash-fold-row row g-3 mb-3">
      <div class="col-lg-7">
        <div class="d-flex flex-column gap-3 h-100">
          <?php if ($budgetAlert): ?>
            <div class="alert-card">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div class="flex-grow-1">
                <h3>Budget Alert</h3>
                <p>You're <?php echo number_format($budgetAlert['pct'], 0); ?>% towards your <?php echo htmlspecialchars($budgetAlert['category']); ?> budget.</p>
              </div>
              <a href="budgets.php" class="btn btn-forest">View Details</a>
            </div>
          <?php endif; ?>

          <div class="dash-card flex-grow-1">
            <div class="dash-card-header">
              <h2>Recent Transactions</h2>
              <a href="expenses.php">View All</a>
            </div>

            <?php if (empty($recentTransactions)): ?>
              <div class="empty-state">
                <i class="bi bi-receipt"></i>
                No transactions yet.
              </div>
            <?php else: ?>
              <?php foreach ($recentTransactions as $t): ?>
                <div class="transaction-item">
                  <span class="transaction-icon" style="background-color:<?php echo peso_category_color($t['category']); ?>;">
                    <i class="bi <?php echo peso_category_icon($t['category']); ?>"></i>
                  </span>
                  <div class="transaction-info">
                    <div class="transaction-name"><?php echo htmlspecialchars($t['description'] ?: $t['category']); ?></div>
                    <div class="transaction-meta"><?php echo htmlspecialchars($t['category']); ?> &middot; <?php echo date('M j, Y', strtotime($t['expense_date'])); ?></div>
                  </div>
                  <div class="transaction-amount"><?php echo peso_format_currency($t['amount']); ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="dash-card h-100">
          <div class="dash-card-header">
            <h2>Spending Breakdown</h2>
            <span class="d-flex align-items-center gap-2">
              <button type="button" id="donutResetBtn" class="dash-link" style="display:none; background:none; border:none; padding:0;">Show All</button>
              <span class="period-type-pill"><?php echo htmlspecialchars($chartPeriodLabel); ?></span>
            </span>
          </div>

          <?php if (empty($chartCategoryBreakdown)): ?>
            <div class="dash-card-fill empty-state">
              <i class="bi bi-pie-chart"></i>
              No expenses recorded for this period yet.
            </div>
          <?php else: ?>
            <div class="dash-card-fill">
            <div class="row align-items-center w-100">
              <div class="col-sm-5">
                <div class="donut-chart-wrap">
                  <canvas id="donutChart"></canvas>
                  <div class="donut-center-label">
                    <span class="amount" id="donutAmount"><?php echo peso_format_currency($chartTotal); ?></span>
                    <span class="label" id="donutLabel">Total Expenses</span>
                  </div>
                </div>
              </div>
              <div class="col-sm-7">
                <ul class="donut-legend" id="donutLegend">
                  <?php foreach ($chartCategoryBreakdown as $c):
                    $pct = $chartTotal > 0 ? ($c['total'] / $chartTotal) * 100 : 0;
                  ?>
                    <li data-category="<?php echo htmlspecialchars($c['category']); ?>">
                      <span class="donut-legend-label">
                        <span class="donut-legend-dot" style="background-color:<?php echo peso_category_color($c['category']); ?>;"></span>
                        <?php echo htmlspecialchars($c['category']); ?>
                      </span>
                      <span class="donut-legend-amount"><?php echo peso_format_currency($c['total']); ?></span>
                      <span class="donut-legend-percent"><?php echo number_format($pct, 0); ?>%</span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Trend + top categories -->
    <div class="dash-fold-row row g-3">
      <div class="col-lg-7">
        <div class="dash-card h-100">
          <div class="dash-card-header">
            <h2>Spending Trend</h2>
            <span class="period-type-pill"><?php echo htmlspecialchars($trendRangeLabel); ?></span>
          </div>
          <div class="chart-fill">
            <canvas id="trendChart"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="dash-card h-100">
          <div class="dash-card-header">
            <h2>Category Analysis</h2>
            <span class="period-type-pill"><?php echo htmlspecialchars($chartPeriodLabel); ?></span>
          </div>

          <?php if (empty($chartCategoryBreakdown)): ?>
            <div class="dash-card-fill empty-state">
              <i class="bi bi-bar-chart"></i>
              No data yet.
            </div>
          <?php else: ?>
            <div class="chart-fill">
              <canvas id="categoryBarChart"></canvas>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    </div><!-- /.dash-fold -->

    <!-- Key Insights -->
    <div class="row g-3 mt-1">
      <div class="col-12">
        <div class="dash-card">
          <div class="dash-card-header">
            <h2>Key Insights</h2>
            <span class="period-type-pill"><?php echo htmlspecialchars($chartPeriodLabel); ?></span>
          </div>

          <ul class="key-insight-list">
            <?php foreach ($keyInsights as $insight): ?>
              <li class="key-insight-item <?php echo !empty($insight['tip']) ? 'is-tip' : ''; ?>">
                <span class="key-insight-icon"><i class="bi <?php echo $insight['icon']; ?>"></i></span>
                <span class="key-insight-text"><?php echo $insight['text']; ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

  </main>
</div>

<?php include __DIR__ . '/includes/expense_modal.php'; ?>
<?php include __DIR__ . '/includes/ui_modals.php'; ?>

<!-- Welcome modal (shown once right after login/signup) -->
<div class="modal fade" id="welcomeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content ui-modal-content text-center">
      <div class="welcome-modal-icon"><i class="bi bi-emoji-smile-fill"></i></div>
      <h5><?php echo $isNewSignup ? 'Welcome to PESO' : 'Welcome back'; ?>, <?php echo htmlspecialchars($firstName); ?>!</h5>
      <p class="text-muted small mb-0">
        <?php echo $isNewSignup
          ? "Your account is all set. Let's log your first expense and start building better money habits."
          : "Here's a quick look at your spending overview for this month."; ?>
      </p>
      <button type="button" class="btn btn-forest mt-3" data-bs-dismiss="modal">Let's Go</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="assets/js/loading.js"></script>
<script src="assets/js/dashboard.js"></script>
<script src="assets/js/ui-modals.js"></script>
<script>window.PESO_BUDGET_SNAPSHOT = <?php echo json_encode($budgetSnapshot); ?>;</script>
<script src="assets/js/expense-modal.js"></script>
<script src="assets/js/onboarding-tour.js"></script>

<?php if ($showWelcome): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var welcomeModalEl = document.getElementById('welcomeModal');
  new bootstrap.Modal(welcomeModalEl).show();

  <?php if ($isNewSignup): ?>
  welcomeModalEl.addEventListener('hidden.bs.modal', function () {
    new PesoTour([
      {
        target: 'nav-dashboard',
        title: 'Dashboard',
        body: 'Your spending overview at a glance — totals, budget status, trends, and Key Insights with personalized tips. Switch between Day, Week, and Month anytime.'
      },
      {
        target: 'nav-expenses',
        title: 'Expenses',
        body: 'Log every purchase here. Search, filter by category or date, and edit or delete any transaction.'
      },
      {
        target: 'nav-budgets',
        title: 'Budgets',
        body: 'Set spending limits — overall or per category — for a day, week, or month, and track how close you are to them.'
      },
      {
        target: 'nav-profile',
        title: 'Profile',
        body: 'Update your name, email, profile photo, and password here.'
      }
    ]).start();
  }, { once: true });
  <?php endif; ?>
});
</script>
<?php endif; ?>

<?php if (!empty($chartCategoryBreakdown)): ?>
<script>
(function () {
  var categories = <?php echo json_encode(array_column($chartCategoryBreakdown, 'category')); ?>;
  var totals = <?php echo json_encode(array_map('floatval', array_column($chartCategoryBreakdown, 'total'))); ?>;
  var colors = <?php echo json_encode(array_map('peso_category_color', array_column($chartCategoryBreakdown, 'category'))); ?>;
  var grandTotal = <?php echo json_encode((float) $chartTotal); ?>;

  function pesoFmt(v) {
    return '₱' + v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  var donutChart = new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
      labels: categories,
      datasets: [{
        data: totals,
        backgroundColor: colors,
        borderWidth: 3,
        borderColor: '#ffffff'
      }]
    },
    options: {
      cutout: '68%',
      plugins: { legend: { display: false }, tooltip: { enabled: true } }
    }
  });

  var barChart = new Chart(document.getElementById('categoryBarChart'), {
    type: 'bar',
    data: {
      labels: categories,
      datasets: [{
        data: totals,
        backgroundColor: colors.slice(),
        borderRadius: 6
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: { callback: function (v) { return pesoFmt(v); } } } },
      onClick: function (evt) {
        var points = barChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!points.length) return;
        var category = categories[points[0].index];
        if (selectedCategory === category) {
          resetFilter();
        } else {
          applyFilter(category);
        }
      },
      onHover: function (evt, elements) {
        evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
      }
    }
  });

  var donutAmountEl = document.getElementById('donutAmount');
  var donutLabelEl = document.getElementById('donutLabel');
  var legendItems = document.querySelectorAll('#donutLegend li');
  var resetBtn = document.getElementById('donutResetBtn');
  var selectedCategory = null;

  function applyFilter(category) {
    selectedCategory = category;
    var idx = categories.indexOf(category);
    var amount = idx > -1 ? totals[idx] : 0;

    donutChart.data.datasets[0].data = categories.map(function (c, i) { return c === category ? totals[i] : 0; });
    donutChart.update();

    barChart.data.datasets[0].backgroundColor = colors.map(function (c, i) {
      return categories[i] === category ? c : 'rgba(0,0,0,0.12)';
    });
    barChart.update();

    donutAmountEl.textContent = pesoFmt(amount);
    donutLabelEl.textContent = category;

    legendItems.forEach(function (li) {
      li.style.display = li.getAttribute('data-category') === category ? '' : 'none';
    });

    resetBtn.style.display = 'inline';
  }

  function resetFilter() {
    selectedCategory = null;

    donutChart.data.datasets[0].data = totals;
    donutChart.update();

    barChart.data.datasets[0].backgroundColor = colors.slice();
    barChart.update();

    donutAmountEl.textContent = pesoFmt(grandTotal);
    donutLabelEl.textContent = 'Total Expenses';

    legendItems.forEach(function (li) { li.style.display = ''; });

    resetBtn.style.display = 'none';
  }

  resetBtn.addEventListener('click', resetFilter);
})();
</script>
<?php endif; ?>

<script>
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($trendLabels); ?>,
    datasets: [{
      data: <?php echo json_encode($trendData); ?>,
      borderColor: '#245C4A',
      backgroundColor: 'rgba(36, 92, 74, 0.08)',
      fill: true,
      tension: 0.35,
      pointBackgroundColor: '#245C4A',
      pointRadius: 4
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { callback: function (v) { return '₱' + v.toLocaleString(); } } }
    }
  }
});
</script>


</body>
</html>
