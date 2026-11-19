<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];
$month = request_str('month', date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month) || !validate_date($month . '-01')) {
    $month = date('Y-m');
}

$errors = [];

if (is_post()) {
    verify_csrf();
    $action = request_str('action');
    $month = request_str('budget_month', $month);
    if (!preg_match('/^\d{4}-\d{2}$/', $month) || !validate_date($month . '-01')) {
        $errors[] = 'Invalid budget month.';
    }

    if (!$errors && $action === 'save') {
        $amount = request_float('budget_amount');
        $categoryIdRaw = request_str('category_id');
        $categoryId = $categoryIdRaw === '' || $categoryIdRaw === '0' ? null : (int) $categoryIdRaw;

        if ($amount <= 0) {
            $errors[] = 'Budget amount must be greater than zero.';
        }
        if ($categoryId !== null) {
            $cat = get_category_for_user($userId, $categoryId);
            if (!$cat || $cat['category_type'] !== 'expense') {
                $errors[] = 'Select a valid expense category, or Overall.';
            }
        }

        if (!$errors) {
            // Upsert: find existing row for scope
            if ($categoryId === null) {
                $find = db()->prepare(
                    'SELECT budget_id FROM budgets WHERE user_id = ? AND budget_month = ? AND category_id IS NULL LIMIT 1'
                );
                $find->execute([$userId, $month]);
            } else {
                $find = db()->prepare(
                    'SELECT budget_id FROM budgets WHERE user_id = ? AND budget_month = ? AND category_id = ? LIMIT 1'
                );
                $find->execute([$userId, $month, $categoryId]);
            }
            $existing = $find->fetch();
            if ($existing) {
                $upd = db()->prepare('UPDATE budgets SET budget_amount = ? WHERE budget_id = ? AND user_id = ?');
                $upd->execute([number_format($amount, 2, '.', ''), $existing['budget_id'], $userId]);
                flash('success', 'Budget updated.');
            } else {
                $ins = db()->prepare(
                    'INSERT INTO budgets (user_id, category_id, budget_amount, budget_month) VALUES (?, ?, ?, ?)'
                );
                $ins->execute([$userId, $categoryId, number_format($amount, 2, '.', ''), $month]);
                flash('success', 'Budget created.');
            }
            redirect('/budgets.php?month=' . urlencode($month));
        }
    }

    if (!$errors && $action === 'delete') {
        $budgetId = request_int('budget_id');
        $del = db()->prepare('DELETE FROM budgets WHERE budget_id = ? AND user_id = ?');
        $del->execute([$budgetId, $userId]);
        flash('success', 'Budget removed.');
        redirect('/budgets.php?month=' . urlencode($month));
    }
}

$status = budget_status($userId, $month);
$expenseCats = user_categories($userId, 'expense');

$pageTitle = 'Budgets';
$activeNav = 'budgets';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="panel mb-4">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label">Month</label>
            <input type="month" name="month" class="form-control" value="<?= e($month) ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary" type="submit">Show</button>
        </div>
    </form>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2>Set Budget</h2></div>
            <form method="post" class="vstack gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <div>
                    <label class="form-label">Month</label>
                    <input type="month" name="budget_month" class="form-control" value="<?= e($month) ?>" required>
                </div>
                <div>
                    <label class="form-label">Scope</label>
                    <select name="category_id" class="form-select">
                        <option value="">Overall monthly expense</option>
                        <?php foreach ($expenseCats as $c): ?>
                            <option value="<?= (int) $c['category_id'] ?>"><?= e($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Amount (BDT)</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" step="0.01" min="0.01" name="budget_amount" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Budget</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header">
                <h2>Spending vs Budgets</h2>
                <span class="text-muted small"><?= e(date('F Y', strtotime($month . '-01'))) ?></span>
            </div>
            <?php if (!$status): ?>
                <p class="text-muted mb-0">No budgets set for this month.</p>
            <?php else: foreach ($status as $row): ?>
                <div class="budget-row mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <strong><?= e($row['name']) ?></strong>
                            <div class="small text-muted">
                                Spent <?= e(format_bdt($row['spent'])) ?> of <?= e(format_bdt($row['amount'])) ?>
                                · Remaining <?= e(format_bdt($row['remaining'])) ?>
                            </div>
                        </div>
                        <form method="post" onsubmit="return confirm('Remove this budget?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="budget_month" value="<?= e($month) ?>">
                            <input type="hidden" name="budget_id" value="<?= (int) $row['budget']['budget_id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    <?php
                    $pct = min(100, max(0, $row['percent']));
                    $barClass = $row['status'] === 'exceeded' ? 'bg-danger' : ($row['status'] === 'warning' ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="progress mt-2" role="progressbar" aria-valuenow="<?= (int) $pct ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar <?= e($barClass) ?>" style="width: <?= number_format($pct, 1) ?>%">
                            <?= number_format($row['percent'], 0) ?>%
                        </div>
                    </div>
                    <?php if ($row['status'] === 'warning'): ?>
                        <div class="small text-warning mt-1"><i class="bi bi-exclamation-triangle"></i> Approaching budget limit</div>
                    <?php elseif ($row['status'] === 'exceeded'): ?>
                        <div class="small text-danger mt-1"><i class="bi bi-exclamation-octagon"></i> Budget exceeded</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
