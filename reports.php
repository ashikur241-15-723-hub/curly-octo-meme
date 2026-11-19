<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];

$preset = request_str('preset', 'monthly');
$today = date('Y-m-d');

switch ($preset) {
    case 'daily':
        $from = $today;
        $to = $today;
        $title = 'Daily Report';
        break;
    case 'weekly':
        $from = date('Y-m-d', strtotime('monday this week'));
        $to = date('Y-m-d', strtotime('sunday this week'));
        $title = 'Weekly Report';
        break;
    case 'yearly':
        $from = date('Y-01-01');
        $to = date('Y-12-31');
        $title = 'Yearly Report';
        break;
    case 'custom':
        $from = validate_date(request_str('date_from')) ? request_str('date_from') : date('Y-m-01');
        $to = validate_date(request_str('date_to')) ? request_str('date_to') : $today;
        $title = 'Custom Range Report';
        break;
    case 'category':
        $from = validate_date(request_str('date_from')) ? request_str('date_from') : date('Y-m-01');
        $to = validate_date(request_str('date_to')) ? request_str('date_to') : date('Y-m-t');
        $title = 'Category-wise Expense Report';
        break;
    case 'income_vs_expense':
        $from = validate_date(request_str('date_from')) ? request_str('date_from') : date('Y-m-01');
        $to = validate_date(request_str('date_to')) ? request_str('date_to') : date('Y-m-t');
        $title = 'Income vs Expense Report';
        break;
    case 'monthly':
    default:
        $preset = 'monthly';
        $from = date('Y-m-01');
        $to = date('Y-m-t');
        $title = 'Monthly Report';
        break;
}

if ($from > $to) {
    [$from, $to] = [$to, $from];
}

$report = report_summary($userId, $from, $to);
$monthly = monthly_income_expense($userId, 6);

$pageTitle = 'Reports';
$activeNav = 'reports';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="panel mb-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php
        $presets = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'custom' => 'Custom Range',
            'category' => 'Category-wise',
            'income_vs_expense' => 'Income vs Expense',
        ];
        foreach ($presets as $key => $label):
        ?>
        <a class="btn btn-sm <?= $preset === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"
           href="<?= e(base_path('/reports.php?preset=' . $key)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="preset" value="<?= e($preset) ?>">
        <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($from) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($to) ?>">
        </div>
        <div class="col-md-6 d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn btn-outline-secondary" target="_blank"
               href="<?= e(base_path('/print.php?date_from=' . urlencode($from) . '&date_to=' . urlencode($to) . '&report_title=' . urlencode($title))) ?>">
                <i class="bi bi-printer"></i> Print
            </a>
            <a class="btn btn-outline-secondary"
               href="<?= e(base_path('/export_csv.php?date_from=' . urlencode($from) . '&date_to=' . urlencode($to))) ?>">
                <i class="bi bi-filetype-csv"></i> CSV
            </a>
            <button type="button" class="btn btn-outline-secondary" id="pdfExportBtn"
                    data-title="<?= e($title) ?>"
                    data-table="#reportTable"
                    data-filename="expensetrack-report.pdf">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="mini-stat">Total Income<strong class="text-income d-block"><?= e(format_bdt($report['income'])) ?></strong></div></div>
    <div class="col-md-3"><div class="mini-stat">Total Expense<strong class="text-expense d-block"><?= e(format_bdt($report['expense'])) ?></strong></div></div>
    <div class="col-md-3"><div class="mini-stat">Net Balance<strong class="d-block"><?= e(format_bdt($report['balance'])) ?></strong></div></div>
    <div class="col-md-3"><div class="mini-stat">Transactions<strong class="d-block"><?= (int) $report['count'] ?></strong></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>Category-wise Breakdown</h2></div>
            <?php if (!$report['breakdown']): ?>
                <p class="text-muted mb-0">No expense categories in this range.</p>
            <?php else: ?>
                <canvas id="reportCategoryChart" height="200"></canvas>
                <ul class="list-unstyled mt-3 mb-0">
                    <?php foreach ($report['breakdown'] as $row): ?>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span><?= e($row['category_name']) ?></span>
                            <strong><?= e(format_bdt($row['total'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>Income vs Expense Trend</h2></div>
            <canvas id="reportTrendChart" height="200"></canvas>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2><?= e($title) ?></h2>
        <span class="text-muted small"><?= e($from) ?> → <?= e($to) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="reportTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Payment</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$report['rows']): ?>
                <tr><td colspan="6" class="text-muted">No transactions in this range.</td></tr>
            <?php else: foreach ($report['rows'] as $tx): ?>
                <tr>
                    <td><?= e($tx['transaction_date']) ?></td>
                    <td><?= e(ucfirst($tx['transaction_type'])) ?></td>
                    <td><?= e($tx['category_name']) ?></td>
                    <td><?= e($tx['description'] ?: '—') ?></td>
                    <td><?= e($tx['payment_method']) ?></td>
                    <td class="text-end"><?= e(format_bdt($tx['amount'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$extraScripts = '<script>
window.ET_REPORTS = {
  category: ' . json_encode([
      'labels' => array_column($report['breakdown'], 'category_name'),
      'values' => array_map('floatval', array_column($report['breakdown'], 'total')),
  ], JSON_UNESCAPED_UNICODE) . ',
  monthly: ' . json_encode($monthly, JSON_UNESCAPED_UNICODE) . '
};
</script>
<script src="' . e(base_path('/assets/js/reports.js')) . '"></script>';
require __DIR__ . '/includes/layout_footer.php';
