<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$error = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $email = request_str('email');
    $password = (string) ($_POST['password'] ?? '');
    $result = attempt_login($email, $password);
    if ($result['ok']) {
        login_user($result['user']);
        flash('success', 'Welcome back, ' . $result['user']['name'] . '!');
        redirect('/dashboard.php');
    }
    $error = $result['error'];
}

$pageTitle = 'Login';
$bodyClass = 'auth-page';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="auth-card">
    <div class="auth-brand">
        <span class="brand-mark"><i class="bi bi-wallet2"></i></span>
        <h1><?= e((string) app_config('app.name', 'ExpenseTrack')) ?></h1>
        <p class="text-muted">Track every taka you earn and spend.</p>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign in</button>
    </form>
    <p class="mt-3 mb-0 text-center small">
        No account? <a href="<?= e(base_path('/register.php')) ?>">Create one</a>
    </p>
    <p class="mt-2 mb-0 text-center text-muted small">
        Demo: demo@expensetrack.test / Demo@1234
    </p>
</div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
