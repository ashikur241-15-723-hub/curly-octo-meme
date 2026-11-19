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

    if ($action === 'profile') {
        $name = request_str('name');
        if ($name === '') {
            $errors[] = 'Name is required.';
        } else {
            $stmt = db()->prepare('UPDATE users SET name = ? WHERE user_id = ?');
            $stmt->execute([$name, $userId]);
            $_SESSION['user']['name'] = $name;
            flash('success', 'Profile updated.');
            redirect('/settings.php');
        }
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $stmt = db()->prepare('SELECT password FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $hash = (string) $stmt->fetchColumn();
        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $upd = db()->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $upd->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            flash('success', 'Password changed.');
            redirect('/settings.php');
        }
    }

    if ($action === 'backup') {
        $filters = build_transaction_filters(['per_page' => 100000]);
        $result = filter_transactions($userId, $filters, false);
        $cats = user_categories($userId);
        $payload = [
            'exported_at' => date('c'),
            'user' => ['name' => $user['name'], 'email' => $user['email']],
            'categories' => $cats,
            'transactions' => $result['rows'],
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="expensetrack-backup-' . date('Ymd-His') . '.json"');
        echo $json;
        exit;
    }

    if ($action === 'restore') {
        if (empty($_FILES['backup_file']['tmp_name'])) {
            $errors[] = 'Please choose a backup JSON file.';
        } else {
            $raw = file_get_contents($_FILES['backup_file']['tmp_name']);
            $data = json_decode((string) $raw, true);
            if (!is_array($data) || empty($data['transactions']) || !is_array($data['transactions'])) {
                $errors[] = 'Invalid backup file.';
            } else {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    // Map category names+types from backup to current user categories
                    $catMap = [];
                    foreach (user_categories($userId) as $c) {
                        $catMap[$c['category_type'] . '|' . mb_strtolower($c['category_name'])] = (int) $c['category_id'];
                    }
                    if (!empty($data['categories']) && is_array($data['categories'])) {
                        foreach ($data['categories'] as $c) {
                            $type = $c['category_type'] ?? '';
                            $name = trim((string) ($c['category_name'] ?? ''));
                            if (!in_array($type, ['income', 'expense'], true) || $name === '') {
                                continue;
                            }
                            $key = $type . '|' . mb_strtolower($name);
                            if (!isset($catMap[$key])) {
                                $ins = $pdo->prepare(
                                    'INSERT INTO categories (user_id, category_name, category_type) VALUES (?, ?, ?)'
                                );
                                $ins->execute([$userId, $name, $type]);
                                $catMap[$key] = (int) $pdo->lastInsertId();
                            }
                        }
                    }

                    $insTx = $pdo->prepare(
                        'INSERT INTO transactions
                         (user_id, category_id, transaction_type, amount, description, payment_method, transaction_date, note)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $imported = 0;
                    foreach ($data['transactions'] as $tx) {
                        $type = $tx['transaction_type'] ?? '';
                        $catName = trim((string) ($tx['category_name'] ?? ''));
                        $key = $type . '|' . mb_strtolower($catName);
                        if (!isset($catMap[$key]) || !in_array($type, ['income', 'expense'], true)) {
                            continue;
                        }
                        $amount = (float) ($tx['amount'] ?? 0);
                        $date = $tx['transaction_date'] ?? '';
                        if ($amount <= 0 || !validate_date($date)) {
                            continue;
                        }
                        $pm = $tx['payment_method'] ?? 'Cash';
                        if (!in_array($pm, payment_methods(), true)) {
                            $pm = 'Other';
                        }
                        $insTx->execute([
                            $userId,
                            $catMap[$key],
                            $type,
                            number_format($amount, 2, '.', ''),
                            (string) ($tx['description'] ?? ''),
                            $pm,
                            $date,
                            $tx['note'] ?? null,
                        ]);
                        $imported++;
                    }
                    $pdo->commit();
                    flash('success', "Restore complete. Imported {$imported} transaction(s).");
                    redirect('/settings.php');
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    $errors[] = 'Restore failed. Please check the backup file.';
                }
            }
        }
    }
}

$pageTitle = 'Settings';
$activeNav = 'settings';
require __DIR__ . '/includes/layout_header.php';
?>
<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel mb-4">
            <div class="panel-header"><h2>Profile</h2></div>
            <form method="post" class="vstack gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                <div>
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required maxlength="100">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                    <div class="form-text">Email cannot be changed.</div>
                </div>
                <button type="submit" class="btn btn-primary">Save profile</button>
            </form>
        </div>
        <div class="panel">
            <div class="panel-header"><h2>Change Password</h2></div>
            <form method="post" class="vstack gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <div>
                    <label class="form-label">Current password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">New password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="8">
                </div>
                <div>
                    <label class="form-label">Confirm new password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">Update password</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel mb-4">
            <div class="panel-header"><h2>Appearance</h2></div>
            <p class="text-muted">Use the moon/sun button in the top bar to toggle dark mode. Preference is saved in this browser.</p>
            <button type="button" class="btn btn-outline-secondary" id="themeToggleSettings">Toggle dark mode</button>
        </div>
        <div class="panel">
            <div class="panel-header"><h2>Data Backup &amp; Restore</h2></div>
            <form method="post" class="mb-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="backup">
                <p class="text-muted small">Download your categories and transactions as JSON.</p>
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-download"></i> Download backup</button>
            </form>
            <hr>
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="restore">
                <p class="text-muted small">Import transactions from a previous ExpenseTrack JSON backup. Existing data is kept; imports are appended.</p>
                <input type="file" name="backup_file" class="form-control mb-2" accept="application/json,.json" required>
                <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('Import transactions from this backup?');">
                    <i class="bi bi-upload"></i> Restore
                </button>
            </form>
        </div>
    </div>
</div>
<?php
$extraScripts = '<script>
document.getElementById("themeToggleSettings")?.addEventListener("click", () => {
  document.getElementById("themeToggle")?.click();
});
</script>';
require __DIR__ . '/includes/layout_footer.php';
