<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];
$filters = build_transaction_filters($_GET);
$result = filter_transactions($userId, $filters, false);
$appName = (string) app_config('app.name', 'ExpenseTrack');
$reportTitle = request_str('report_title', 'Transaction Statement');
$rangeLabel = trim(($filters['date_from'] ?: '…') . ' to ' . ($filters['date_to'] ?: '…'));
if ($filters['date_from'] === '' && $filters['date_to'] === '') {
    $rangeLabel = 'All dates';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($reportTitle) ?> · <?= e($appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(base_path('/assets/css/print.css')) ?>" rel="stylesheet">
</head>
<body class="print-body">
    <div class="print-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-outline-secondary" href="<?= e(base_path('/transactions.php?' . http_build_query(array_filter([
            'q' => $filters['q'],
            'type' => $filters['type'],
            'category_id' => $filters['category_id'] ?: null,
            'payment_method' => $filters['payment_method'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'sort' => $filters['sort'],
        ])))) ?>">Back</a>
    </div>

    <header class="statement-header">
        <div>
            <h1><?= e($appName) ?></h1>
            <p class="mb-0"><?= e($reportTitle) ?></p>
        </div>
        <div class="text-end small">
            <div>Prepared for: <?= e($user['name']) ?></div>
            <div>Date range: <?= e($rangeLabel) ?></div>
            <div>Generated: <?= e(date('Y-m-d H:i')) ?></div>
        </div>
    </header>

    <section class="totals row g-3 mb-4">
        <div class="col-4"><div class="total-box">Income<br><strong><?= e(format_bdt($result['income_total'])) ?></strong></div></div>
        <div class="col-4"><div class="total-box">Expense<br><strong><?= e(format_bdt($result['expense_total'])) ?></strong></div></div>
        <div class="col-4"><div class="total-box">Net Balance<br><strong><?= e(format_bdt($result['income_total'] - $result['expense_total'])) ?></strong></div></div>
    </section>

    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Description</th>
                <th>Payment</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$result['rows']): ?>
            <tr><td colspan="7">No transactions.</td></tr>
        <?php else: foreach ($result['rows'] as $tx): ?>
            <tr>
                <td>#<?= (int) $tx['transaction_id'] ?></td>
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

    <footer class="statement-footer">
        <span><?= (int) $result['total'] ?> transaction(s)</span>
        <span class="page-hint">Page numbers appear when printing (browser print footer).</span>
    </footer>
    <script>window.addEventListener('load', () => { /* ready for print */ });</script>
</body>
</html>
