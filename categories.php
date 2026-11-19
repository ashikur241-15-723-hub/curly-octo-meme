<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = request_str('action');

    if ($action === 'create') {
        $name = request_str('category_name');
        $type = request_str('category_type');
        if ($name === '' || !in_array($type, ['income', 'expense'], true)) {
            $errors[] = 'Name and type are required.';
        } else {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO categories (user_id, category_name, category_type) VALUES (?, ?, ?)'
                );
                $stmt->execute([$userId, $name, $type]);
                flash('success', 'Category added.');
                redirect('/categories.php');
            } catch (PDOException $e) {
                $errors[] = 'Category already exists for this type.';
            }
        }
    }

    if ($action === 'update') {
        $id = request_int('category_id');
        $name = request_str('category_name');
        $cat = get_category_for_user($userId, $id);
        if (!$cat || $name === '') {
            $errors[] = 'Invalid category update.';
        } else {
            try {
                $stmt = db()->prepare(
                    'UPDATE categories SET category_name = ? WHERE category_id = ? AND user_id = ?'
                );
                $stmt->execute([$name, $id, $userId]);
                flash('success', 'Category updated.');
                redirect('/categories.php');
            } catch (PDOException $e) {
                $errors[] = 'Another category already uses that name.';
            }
        }
    }

    if ($action === 'delete') {
        $id = request_int('category_id');
        $cat = get_category_for_user($userId, $id);
        if (!$cat) {
            flash('danger', 'Category not found.');
            redirect('/categories.php');
        }
        $check = db()->prepare('SELECT COUNT(*) FROM transactions WHERE category_id = ? AND user_id = ?');
        $check->execute([$id, $userId]);
        if ((int) $check->fetchColumn() > 0) {
            flash('danger', 'Cannot delete a category that has transactions. Reassign or delete those first.');
            redirect('/categories.php');
        }
        $stmt = db()->prepare('DELETE FROM categories WHERE category_id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        flash('success', 'Category deleted.');
        redirect('/categories.php');
    }
}

$income = user_categories($userId, 'income');
$expense = user_categories($userId, 'expense');

$pageTitle = 'Categories';
$activeNav = 'categories';
require __DIR__ . '/includes/layout_header.php';
?>
<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2>Add Category</h2></div>
            <form method="post" class="vstack gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label">Name</label>
                    <input type="text" name="category_name" class="form-control" maxlength="100" required>
                </div>
                <div>
                    <label class="form-label">Type</label>
                    <select name="category_type" class="form-select" required>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2>Expense Categories</h2></div>
            <ul class="list-group list-group-flush category-list">
                <?php foreach ($expense as $c): ?>
                <li class="list-group-item">
                    <form method="post" class="d-flex gap-2 align-items-center">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                        <input type="text" name="category_name" class="form-control form-control-sm" value="<?= e($c['category_name']) ?>" required>
                        <button class="btn btn-sm btn-outline-primary" type="submit" title="Save"><i class="bi bi-check-lg"></i></button>
                    </form>
                    <form method="post" class="mt-1" onsubmit="return confirm('Delete this category?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                        <button class="btn btn-sm btn-link text-danger p-0" type="submit">Delete</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2>Income Categories</h2></div>
            <ul class="list-group list-group-flush category-list">
                <?php foreach ($income as $c): ?>
                <li class="list-group-item">
                    <form method="post" class="d-flex gap-2 align-items-center">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                        <input type="text" name="category_name" class="form-control form-control-sm" value="<?= e($c['category_name']) ?>" required>
                        <button class="btn btn-sm btn-outline-primary" type="submit" title="Save"><i class="bi bi-check-lg"></i></button>
                    </form>
                    <form method="post" class="mt-1" onsubmit="return confirm('Delete this category?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                        <button class="btn btn-sm btn-link text-danger p-0" type="submit">Delete</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
