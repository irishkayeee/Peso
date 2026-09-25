<?php
/**
 * PESO - Budget period helpers (Day / Week / Month)
 */

const PESO_PERIOD_TYPES = ['day', 'week', 'month'];

/**
 * Resolve a period type into a concrete [start, end] date range (Y-m-d).
 * Day/Week/Month are always "current" (today / this week / this month) -
 * there's no browsing past periods.
 */
function peso_period_range(string $type): array
{
    $today = new DateTime('today');

    if ($type === 'day') {
        $d = $today->format('Y-m-d');
        return ['start' => $d, 'end' => $d];
    }

    if ($type === 'week') {
        $dow = (int) $today->format('N'); // 1 (Mon) .. 7 (Sun)
        $start = (clone $today)->modify('-' . ($dow - 1) . ' days');
        $end = (clone $start)->modify('+6 days');
        return ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
    }

    // month (default)
    $start = $today->format('Y-m-01');
    $end = $today->format('Y-m-t');
    return ['start' => $start, 'end' => $end];
}

/**
 * Human-friendly label for a period range, e.g. "Sept 14, 2026",
 * "Sept 8 - 14, 2026", or "September 2026".
 */
function peso_period_label(string $type, string $start, string $end): string
{
    $startObj = new DateTime($start);
    $endObj = new DateTime($end);

    if ($type === 'day') {
        return $startObj->format('M j, Y');
    }

    if ($type === 'month' && $startObj->format('Y-m-d') === $startObj->format('Y-m-01')) {
        return $startObj->format('F Y');
    }

    if ($startObj->format('Y-m') === $endObj->format('Y-m')) {
        return $startObj->format('M j') . ' - ' . $endObj->format('j, Y');
    }

    return $startObj->format('M j, Y') . ' - ' . $endObj->format('M j, Y');
}

/**
 * Display name for a period type, e.g. "Daily", "Weekly".
 */
function peso_period_type_label(string $type): string
{
    switch ($type) {
        case 'day': return 'Daily';
        case 'week': return 'Weekly';
        default: return 'Monthly';
    }
}

/**
 * ---- Browsing past periods (Day picker / Week picker / Month picker) ----
 * The functions above always resolve to the CURRENT period. The helpers
 * below let a page (Dashboard, Budgets) offer a picker for past periods
 * while still defaulting to - and never going past - the current one.
 */

function peso_monday_of(string $dateStr): string
{
    $d = new DateTime($dateStr);
    $dow = (int) $d->format('N'); // 1 (Mon) .. 7 (Sun)
    $d->modify('-' . ($dow - 1) . ' days');
    return $d->format('Y-m-d');
}

function peso_resolve_day(?string $input): string
{
    $today = date('Y-m-d');
    if ($input === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input) || $input > $today) {
        return $today;
    }
    return $input;
}

function peso_resolve_week(?string $input): string
{
    $thisWeekStart = peso_monday_of(date('Y-m-d'));
    if ($input === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
        return $thisWeekStart;
    }
    $monday = peso_monday_of($input);
    return $monday > $thisWeekStart ? $thisWeekStart : $monday;
}

function peso_resolve_month(?string $input): string
{
    $thisMonth = date('Y-m');
    if ($input === null || !preg_match('/^\d{4}-\d{2}$/', $input) || $input > $thisMonth) {
        return $thisMonth;
    }
    return $input;
}

/** [weekStart (Y-m-d) => label], newest first, always including $selectedWeekStart. */
function peso_recent_weeks(string $selectedWeekStart, int $count = 12): array
{
    $thisWeekStart = peso_monday_of(date('Y-m-d'));
    $options = [];
    for ($i = 0; $i < $count; $i++) {
        $wStart = date('Y-m-d', strtotime("{$thisWeekStart} -{$i} weeks"));
        $wEnd = date('Y-m-d', strtotime("{$wStart} +6 days"));
        $options[$wStart] = peso_period_label('week', $wStart, $wEnd);
    }
    if (!isset($options[$selectedWeekStart])) {
        $wEnd = date('Y-m-d', strtotime("{$selectedWeekStart} +6 days"));
        $options[$selectedWeekStart] = peso_period_label('week', $selectedWeekStart, $wEnd);
    }
    krsort($options);
    return $options;
}

/** [YYYY-MM => "Month YYYY"], newest first, always including $selectedMonth. */
function peso_recent_months(string $selectedMonth, int $count = 12): array
{
    $options = [];
    for ($i = 0; $i < $count; $i++) {
        $m = date('Y-m', strtotime("-{$i} months"));
        $options[$m] = date('F Y', strtotime("{$m}-01"));
    }
    if (!isset($options[$selectedMonth])) {
        $options[$selectedMonth] = date('F Y', strtotime("{$selectedMonth}-01"));
    }
    krsort($options);
    return $options;
}

/**
 * Budget + spent snapshot for the CURRENT day/week/month periods, per
 * category and overall (category ''). Used by the Add/Edit Expense form
 * to warn about (and block) an expense once a set budget is used up,
 * before the user even submits.
 */
function peso_budget_snapshot(PDO $pdo, int $userId, array $categories): array
{
    $snapshot = [];

    foreach (PESO_PERIOD_TYPES as $type) {
        $range = peso_period_range($type);
        $start = $range['start'];
        $end = $range['end'];

        $budgetStmt = $pdo->prepare('SELECT category, amount FROM budgets WHERE user_id = ? AND period_start = ? AND period_end = ?');
        $budgetStmt->execute([$userId, $start, $end]);
        $budgets = [];
        foreach ($budgetStmt->fetchAll() as $row) {
            $budgets[$row['category']] = (float) $row['amount'];
        }

        $spentStmt = $pdo->prepare('SELECT category, SUM(amount) AS total FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ? AND period_type = ? GROUP BY category');
        $spentStmt->execute([$userId, $start, $end, $type]);
        $spentByCategory = [];
        $totalSpent = 0.0;
        foreach ($spentStmt->fetchAll() as $row) {
            $spentByCategory[$row['category']] = (float) $row['total'];
            $totalSpent += (float) $row['total'];
        }

        $entry = [
            'overall' => ['budget' => $budgets[''] ?? 0, 'spent' => $totalSpent],
            'categories' => [],
        ];
        foreach ($categories as $cat) {
            $entry['categories'][$cat] = [
                'budget' => $budgets[$cat] ?? 0,
                'spent' => $spentByCategory[$cat] ?? 0,
            ];
        }
        $snapshot[$type] = $entry;
    }

    return $snapshot;
}

/** Whether the given period (identified by its start date/type) is already over. */
function peso_is_past_period(string $type, string $periodStart): bool
{
    if ($type === 'day') {
        return $periodStart < date('Y-m-d');
    }
    if ($type === 'week') {
        return $periodStart < peso_monday_of(date('Y-m-d'));
    }
    if ($type === 'month') {
        return $periodStart < date('Y-m-01');
    }
    return false;
}
