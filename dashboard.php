<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];
$stats = dashboard_stats($userId);
$recent = recent_transactions($userId, 8);
$monthly = monthly_income_expense($userId, 6);
$pie = category_expense_breakdown($userId, date('Y-m-01'), date('Y-m-t'));
$daily = daily_expense_overview($userId, 14);
$budgets = budget_status($userId, date('Y-m'));

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="stat-grid">
    <?php
    $cards = [
        ['Total Income', $stats['total_income'], 'income', 'bi-arrow-down-left'],
        ['Total Expense', $stats['total_expense'], 'expense', 'bi-arrow-up-right'],
        ['Current Balance', $stats['balance'], $stats['balance'] >= 0 ? 'balance' : 'expense', 'bi-wallet2'],
        ["Today's Expense", $stats['today_expense'], 'muted', 'bi-calendar-day'],
        ["This Month's Expense", $stats['month_expense'], 'muted', 'bi-calendar-month'],
        ["This Month's Income", $stats['month_income'], 'muted', 'bi-cash-stack'],
    ];
    foreach ($cards as [$label, $value, $tone, $icon]):
    ?>
    <div class="stat-card tone-<?= e($tone) ?>">
        <div class="stat-icon"><i class="bi <?= e($icon) ?>"></i></div>
        <div>
            <div class="stat-label"><?= e($label) ?></div>
            <div class="stat-value"><?= e(format_bdt($value)) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
$alerts = array_filter($budgets, fn($b) => in_array($b['status'], ['warning', 'exceeded'], true));
if ($alerts):
?>
<div class="mb-4">
    <?php foreach ($alerts as $a): ?>
        <div class="alert alert-<?= $a['status'] === 'exceeded' ? 'danger' : 'warning' ?> py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Budget alert — <strong><?= e($a['name']) ?></strong>:
            spent <?= e(format_bdt($a['spent'])) ?> of <?= e(format_bdt($a['amount'])) ?>
            (<?= number_format($a['percent'], 0) ?>%)
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <h2>Income vs Expense</h2>
                <span class="text-muted small">Last 6 months</span>
            </div>
            <canvas id="monthlyChart" height="160"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <h2>Category Expenses</h2>
                <span class="text-muted small">This month</span>
            </div>
            <?php if ($pie): ?>
                <canvas id="categoryChart" height="200"></canvas>
            <?php else: ?>
                <p class="text-muted mb-0">No expenses recorded this month.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <h2>Daily Expense Overview</h2>
                <span class="text-muted small">Last 14 days</span>
            </div>
            <canvas id="dailyChart" height="160"></canvas>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <h2>Monthly Summary</h2>
                <span class="text-muted small"><?= e(date('F Y')) ?></span>
            </div>
            <div class="summary-list">
                <div><span>Income</span><strong class="text-income"><?= e(format_bdt($stats['month_income'])) ?></strong></div>
                <div><span>Expense</span><strong class="text-expense"><?= e(format_bdt($stats['month_expense'])) ?></strong></div>
                <div><span>Net</span><strong><?= e(format_bdt($stats['month_income'] - $stats['month_expense'])) ?></strong></div>
            </div>
            <a class="btn btn-sm btn-outline-primary mt-3" href="<?= e(base_path('/reports.php?preset=monthly')) ?>">View full report</a>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Recent Transactions</h2>
        <a href="<?= e(base_path('/transactions.php')) ?>" class="btn btn-sm btn-outline-secondary">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
            <?php if (!$recent): ?>
                <tr><td colspan="6" class="text-muted">No transactions yet. <a href="<?= e(base_path('/transaction_form.php')) ?>">Add one</a>.</td></tr>
            <?php else: foreach ($recent as $tx): ?>
                <tr>
                    <td><?= e($tx['transaction_date']) ?></td>
                    <td><span class="badge type-<?= e($tx['transaction_type']) ?>"><?= e(ucfirst($tx['transaction_type'])) ?></span></td>
                    <td><?= e($tx['category_name']) ?></td>
                    <td><?= e($tx['description'] ?: '—') ?></td>
                    <td><?= e($tx['payment_method']) ?></td>
                    <td class="text-end fw-semibold <?= $tx['transaction_type'] === 'income' ? 'text-income' : 'text-expense' ?>">
                        <?= e(format_bdt($tx['amount'])) ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraScripts = '<script>
window.ET_DASHBOARD = {
  monthly: ' . json_encode($monthly, JSON_UNESCAPED_UNICODE) . ',
  category: ' . json_encode([
      'labels' => array_column($pie, 'category_name'),
      'values' => array_map('floatval', array_column($pie, 'total')),
  ], JSON_UNESCAPED_UNICODE) . ',
  daily: ' . json_encode($daily, JSON_UNESCAPED_UNICODE) . '
};
</script>
<script src="' . e(base_path('/assets/js/dashboard.js')) . '"></script>';
require __DIR__ . '/includes/layout_footer.php';
