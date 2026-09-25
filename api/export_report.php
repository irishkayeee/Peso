<?php
/**
 * PESO - Export monthly report as PDF
 */
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Please log in again.');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/lib/fpdf.php';

$userId = (int) $_SESSION['user_id'];

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$monthStart = $month . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$monthLabel = date('F Y', strtotime($monthStart));

$stmt = $pdo->prepare("SELECT amount FROM budgets WHERE user_id = ? AND category = '' AND period_type = 'month' AND period_start = ?");
$stmt->execute([$userId, $monthStart]);
$monthlyBudget = (float) ($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare(
    'SELECT category, SUM(amount) AS total FROM expenses
     WHERE user_id = ? AND expense_date BETWEEN ? AND ?
     GROUP BY category ORDER BY total DESC'
);
$stmt->execute([$userId, $monthStart, $monthEnd]);
$categoryBreakdown = $stmt->fetchAll();
$totalExpenses = array_sum(array_column($categoryBreakdown, 'total'));

$stmt = $pdo->prepare(
    'SELECT amount, category, payment_method, description, expense_date FROM expenses
     WHERE user_id = ? AND expense_date BETWEEN ? AND ?
     ORDER BY expense_date ASC, id ASC'
);
$stmt->execute([$userId, $monthStart, $monthEnd]);
$transactions = $stmt->fetchAll();

/** FPDF's core fonts only support Windows-1252; transliterate free text into it. */
function pdf_txt(string $s): string
{
    return (string) @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
}

function pdf_money(float $n): string
{
    return 'PHP ' . number_format($n, 2);
}

class PesoReportPDF extends FPDF
{
    public string $periodLabel = '';

    function Header()
    {
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(36, 92, 74);
        $this->Cell(0, 10, 'PESO Expense Report', 0, 1, 'L');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, $this->periodLabel, 0, 1, 'L');

        $this->SetDrawColor(127, 175, 155);
        $this->SetLineWidth(0.4);
        $this->Line(10, $this->GetY() + 2, 200, $this->GetY() + 2);
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, pdf_txt('Generated ' . date('M j, Y g:i A') . ' PHT  |  Page ' . $this->PageNo()), 0, 0, 'C');
    }

    function SectionTitle(string $title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(36, 92, 74);
        $this->Cell(0, 8, $title, 0, 1, 'L');
        $this->Ln(1);
    }

    function TableHeader(array $cols)
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(245, 243, 235);
        $this->SetTextColor(60, 60, 60);
        foreach ($cols as $label => $width) {
            $this->Cell($width, 7, $label, 0, 0, 'L', true);
        }
        $this->Ln();
    }
}

$pdf = new PesoReportPDF();
$pdf->periodLabel = 'Period: ' . $monthLabel;
$pdf->AliasNbPages();
$pdf->AddPage();

// ---- Summary ----
$pdf->SectionTitle('Summary');
$summaryRows = [
    ['Total Expenses', pdf_money($totalExpenses)],
    ['Monthly Budget', $monthlyBudget > 0 ? pdf_money($monthlyBudget) : 'Not set'],
    ['Remaining Budget', $monthlyBudget > 0 ? pdf_money($monthlyBudget - $totalExpenses) : '-'],
];
foreach ($summaryRows as $row) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(60, 7, $row[0], 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(40, 40, 40);
    $pdf->Cell(0, 7, $row[1], 0, 1, 'L');
}
$pdf->Ln(6);

// ---- Category breakdown ----
$pdf->SectionTitle('Category Breakdown');
if (empty($categoryBreakdown)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 7, 'No expenses recorded this month.', 0, 1, 'L');
} else {
    $pdf->TableHeader(['Category' => 80, 'Amount' => 55, '% of Total' => 45]);
    $fill = false;
    foreach ($categoryBreakdown as $c) {
        $pct = $totalExpenses > 0 ? ((float) $c['total'] / $totalExpenses) * 100 : 0;
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFillColor(250, 249, 243);
        $pdf->Cell(80, 7, pdf_txt($c['category']), 0, 0, 'L', $fill);
        $pdf->Cell(55, 7, pdf_money((float) $c['total']), 0, 0, 'L', $fill);
        $pdf->Cell(45, 7, number_format($pct, 0) . '%', 0, 1, 'L', $fill);
        $fill = !$fill;
    }
}
$pdf->Ln(6);

// ---- Transactions ----
$pdf->SectionTitle('Transactions');
if (empty($transactions)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 7, 'No transactions recorded this month.', 0, 1, 'L');
} else {
    $pdf->TableHeader(['Date' => 25, 'Category' => 32, 'Description' => 53, 'Payment' => 35, 'Amount' => 35]);
    $fill = false;
    foreach ($transactions as $t) {
        $desc = $t['description'] ?: '-';
        if (strlen($desc) > 32) {
            $desc = substr($desc, 0, 29) . '...';
        }

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFillColor(250, 249, 243);
        $pdf->Cell(25, 6.5, date('M j, Y', strtotime($t['expense_date'])), 0, 0, 'L', $fill);
        $pdf->Cell(32, 6.5, pdf_txt($t['category']), 0, 0, 'L', $fill);
        $pdf->Cell(53, 6.5, pdf_txt($desc), 0, 0, 'L', $fill);
        $pdf->Cell(35, 6.5, pdf_txt($t['payment_method']), 0, 0, 'L', $fill);
        $pdf->Cell(35, 6.5, pdf_money((float) $t['amount']), 0, 1, 'L', $fill);
        $fill = !$fill;
    }
}

$pdf->Output('D', 'peso-report-' . $month . '.pdf');
