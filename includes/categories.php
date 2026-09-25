<?php
/**
 * PESO - Shared expense categories, colors, and icons
 */

$CATEGORIES = ['Food', 'Transportation', 'Education', 'Bills', 'Shopping', 'Entertainment', 'Health', 'Others'];

$CATEGORY_COLORS = [
    'Food'           => '#245C4A',
    'Transportation' => '#7FAF9B',
    'Education'      => '#D9A441',
    'Bills'          => '#3E7C64',
    'Shopping'       => '#A9C9BB',
    'Entertainment'  => '#D96C6C',
    'Health'         => '#5B8A76',
    'Others'         => '#B8B2A0',
];

$CATEGORY_ICONS = [
    'Food'           => 'bi-cup-hot',
    'Transportation' => 'bi-bus-front',
    'Education'      => 'bi-mortarboard',
    'Bills'          => 'bi-receipt',
    'Shopping'       => 'bi-bag',
    'Entertainment'  => 'bi-film',
    'Health'         => 'bi-heart-pulse',
    'Others'         => 'bi-three-dots',
];

function peso_category_color(string $category): string
{
    global $CATEGORY_COLORS;
    return $CATEGORY_COLORS[$category] ?? '#B8B2A0';
}

function peso_category_icon(string $category): string
{
    global $CATEGORY_ICONS;
    return $CATEGORY_ICONS[$category] ?? 'bi-three-dots';
}

$PAYMENT_METHODS = ['Cash', 'GCash', 'Bank Transfer', 'Debit/Credit Card', 'Others'];

function peso_format_currency(float $amount): string
{
    return '&#8369;' . number_format($amount, 2);
}

/**
 * Green shade for an overall-budget progress bar that gets darker as usage
 * climbs toward the alert threshold - light green when barely used, dark
 * green when nearly at the limit. Past the thresholds it hands off to the
 * gold/red alert colors instead.
 */
function peso_progress_color(float $pct, float $alertThreshold = 80, float $dangerThreshold = 100): string
{
    if ($pct >= $dangerThreshold) {
        return '#D96C6C';
    }
    if ($pct >= $alertThreshold) {
        return '#D9A441';
    }

    $light = [0x7F, 0xAF, 0x9B]; // sage-green
    $dark = [0x1A, 0x44, 0x36];  // forest-green-dark
    $ratio = $alertThreshold > 0 ? max(0, min(1, $pct / $alertThreshold)) : 0;

    $rgb = [];
    foreach ([0, 1, 2] as $i) {
        $rgb[$i] = (int) round($light[$i] + ($dark[$i] - $light[$i]) * $ratio);
    }

    return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
}
