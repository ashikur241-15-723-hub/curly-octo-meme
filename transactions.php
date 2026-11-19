<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];
$filters = build_transaction_filters($_GET);
$result = filter_transactions($userId, $filters, true);
$categories = user_categories($userId);

$queryBase = $_GET;
unset($queryBase['page']);

function filter_query(array $base, array $extra = []): string
{
    return http_build_query(array_merge($base, $extra));
}

$pageTitle = 'Transaction History';
$activeNav = 'history';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="panel mb-4">
    <form method="get" class="row g-2 align-items-end filter-form">
        <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="q" class="form-control" value="<?= e($filters['q']) ?>" placeholder="Keyword…">
        </div>
        <div class="col-md-2">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="">All</option>
                <option value="income" <?= $filters['type'] === 'income' ? 'selected' : '' ?>>Income</option>
                <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="0">All</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['category_id'] ?>" <?= $filters['category_id'] === (int) $c['category_id'] ? 'selected' : '' ?>>
                        <?= e($c['category_name']) ?> (<?= e($c['category_type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Payment</label>
            <select name="payment_method" class="form-select">
                <option value="">All</option>
                <?php foreach (payment_methods() as $pm): ?>
                    <option value="<?= e($pm) ?>" <?= $filters['payment_method'] === $pm ? 'selected' : '' ?>><?= e($pm) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sort</label>
            <select name="sort" class="form-select">
                <?php
                $sorts = [
                    'date_desc' => 'Date (newest)',
                    'date_asc' => 'Date (oldest)',
                    'amount_desc' => 'Amount (high → low)',
                    'amount_asc' => 'Amount (low → high)',
                ];
                foreach ($sorts as $val => $label):
                ?>
                <option value="<?= e($val) ?>" <?= $filters['sort'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="col-md-8 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            <a href="<?= e(base_path('/transactions.php')) ?>" class="btn btn-outline-secondary">Reset</a>
            <a href="<?= e(base_path('/transaction_form.php')) ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add</a>
            <a class="btn btn-outline-secondary" href="<?= e(base_path('/print.php?' . filter_query($queryBase))) ?>" target="_blank"><i class="bi bi-printer"></i> Print</a>
            <a class="btn btn-outline-secondary" href="<?= e(base_path('/export_csv.php?' . filter_query($queryBase))) ?>"><i class="bi bi-filetype-csv"></i> CSV</a>
            <button type="button" class="btn btn-outline-secondary" id="pdfExportBtn"
                    data-title="Filtered Transactions"
                    data-table="#historyTable"
                    data-filename="expensetrack-transactions.pdf"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
        </div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="mini-stat">Income <strong class="text-income"><?= e(format_bdt($result['income_total'])) ?></strong></div></div>
    <div class="col-md-4"><div class="mini-stat">Expense <strong class="text-expense"><?= e(format_bdt($result['expense_total'])) ?></strong></div></div>
    <div class="col-md-4"><div class="mini-stat">Net <strong><?= e(format_bdt($result['income_total'] - $result['expense_total'])) ?></strong></div></div>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="historyTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Payment</th>
                    <th class="text-end">Amount</th>
                    <th class="print-hide">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="8" class="text-muted">No transactions match your filters.</td></tr>
            <?php else: foreach ($result['rows'] as $tx): ?>
                <tr>
                    <td>#<?= (int) $tx['transaction_id'] ?></td>
                    <td><?= e($tx['transaction_date']) ?></td>
                    <td><span class="badge type-<?= e($tx['transaction_type']) ?>"><?= e(ucfirst($tx['transaction_type'])) ?></span></td>
                    <td><?= e($tx['category_name']) ?></td>
                    <td><?= e($tx['description'] ?: '—') ?></td>
                    <td><?= e($tx['payment_method']) ?></td>
                    <td class="text-end fw-semibold <?= $tx['transaction_type'] === 'income' ? 'text-income' : 'text-expense' ?>">
                        <?= e(format_bdt($tx['amount'])) ?>
                    </td>
                    <td class="print-hide">
                        <div class="btn-group btn-group-sm">
                            <a class="btn btn-outline-primary" href="<?= e(base_path('/transaction_form.php?id=' . (int) $tx['transaction_id'])) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e(base_path('/transaction_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this transaction?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $tx['transaction_id'] ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($result['pages'] > 1): ?>
    <nav class="p-3 border-top">
        <ul class="pagination mb-0 flex-wrap">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <li class="page-item <?= $p === $filters['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= e(filter_query($queryBase, ['page' => $p])) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
        <div class="text-muted small mt-2"><?= (int) $result['total'] ?> transaction(s)</div>
    </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
