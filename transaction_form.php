<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];
$id = request_int('id');
$tx = $id > 0 ? get_transaction_for_user($userId, $id) : null;
$editing = $tx !== null;

$errors = [];
$form = [
    'transaction_type' => $tx['transaction_type'] ?? 'expense',
    'amount' => $tx['amount'] ?? '',
    'category_id' => (string) ($tx['category_id'] ?? ''),
    'description' => $tx['description'] ?? '',
    'payment_method' => $tx['payment_method'] ?? 'Cash',
    'transaction_date' => $tx['transaction_date'] ?? date('Y-m-d'),
    'note' => $tx['note'] ?? '',
];

if (is_post()) {
    verify_csrf();
    $form['transaction_type'] = in_array(request_str('transaction_type'), ['income', 'expense'], true)
        ? request_str('transaction_type') : 'expense';
    $form['amount'] = request_str('amount');
    $form['category_id'] = request_str('category_id');
    $form['description'] = request_str('description');
    $form['payment_method'] = request_str('payment_method');
    $form['transaction_date'] = request_str('transaction_date');
    $form['note'] = request_str('note');

    $amount = (float) str_replace(',', '', (string) $form['amount']);
    $categoryId = (int) $form['category_id'];
    $category = $categoryId > 0 ? get_category_for_user($userId, $categoryId) : null;

    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero.';
    }
    if (!$category) {
        $errors[] = 'Please select a valid category.';
    } elseif ($category['category_type'] !== $form['transaction_type']) {
        $errors[] = 'Category type must match the transaction type.';
    }
    if (!in_array($form['payment_method'], payment_methods(), true)) {
        $errors[] = 'Invalid payment method.';
    }
    if (!validate_date($form['transaction_date'])) {
        $errors[] = 'Please enter a valid date.';
    }
    if (mb_strlen($form['description']) > 255) {
        $errors[] = 'Description is too long.';
    }

    if (!$errors) {
        if ($editing) {
            $stmt = db()->prepare(
                'UPDATE transactions SET category_id = ?, transaction_type = ?, amount = ?, description = ?,
                 payment_method = ?, transaction_date = ?, note = ? WHERE transaction_id = ? AND user_id = ?'
            );
            $stmt->execute([
                $categoryId, $form['transaction_type'], number_format($amount, 2, '.', ''),
                $form['description'], $form['payment_method'], $form['transaction_date'],
                $form['note'] !== '' ? $form['note'] : null,
                $id, $userId,
            ]);
            flash('success', 'Transaction updated.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO transactions
                 (user_id, category_id, transaction_type, amount, description, payment_method, transaction_date, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId, $categoryId, $form['transaction_type'], number_format($amount, 2, '.', ''),
                $form['description'], $form['payment_method'], $form['transaction_date'],
                $form['note'] !== '' ? $form['note'] : null,
            ]);
            flash('success', 'Transaction added.');
        }
        redirect('/transactions.php');
    }
}

$incomeCats = user_categories($userId, 'income');
$expenseCats = user_categories($userId, 'expense');

$pageTitle = $editing ? 'Edit Transaction' : 'Add Transaction';
$activeNav = $editing ? 'history' : 'add';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="panel form-panel">
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <label class="form-label">Transaction Type</label>
            <select name="transaction_type" id="transaction_type" class="form-select" required>
                <option value="expense" <?= $form['transaction_type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
                <option value="income" <?= $form['transaction_type'] === 'income' ? 'selected' : '' ?>>Income</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Amount (BDT)</label>
            <div class="input-group">
                <span class="input-group-text">৳</span>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= e((string) $form['amount']) ?>" required>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Category</label>
            <select name="category_id" id="category_id" class="form-select" required
                    data-income='<?= e(json_encode(array_map(fn($c) => ['id' => $c['category_id'], 'name' => $c['category_name']], $incomeCats), JSON_UNESCAPED_UNICODE)) ?>'
                    data-expense='<?= e(json_encode(array_map(fn($c) => ['id' => $c['category_id'], 'name' => $c['category_name']], $expenseCats), JSON_UNESCAPED_UNICODE)) ?>'
                    data-selected="<?= e($form['category_id']) ?>">
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" maxlength="255" value="<?= e($form['description']) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
                <?php foreach (payment_methods() as $pm): ?>
                    <option value="<?= e($pm) ?>" <?= $form['payment_method'] === $pm ? 'selected' : '' ?>><?= e($pm) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Transaction Date</label>
            <input type="date" name="transaction_date" class="form-control" value="<?= e($form['transaction_date']) ?>" required>
        </div>
        <div class="col-12">
            <label class="form-label">Additional Note</label>
            <textarea name="note" class="form-control" rows="3"><?= e($form['note']) ?></textarea>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Save' ?> Transaction</button>
            <a href="<?= e(base_path('/transactions.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php
$extraScripts = '<script src="' . e(base_path('/assets/js/transaction-form.js')) . '"></script>';
require __DIR__ . '/includes/layout_footer.php';
