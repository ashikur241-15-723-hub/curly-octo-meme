<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$error = '';
$name = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $name = request_str('name');
    $email = request_str('email');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');
    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = register_user($name, $email, $password);
        if ($result['ok']) {
            login_user($result['user']);
            flash('success', 'Account created. Default categories are ready for you.');
            redirect('/dashboard.php');
        }
        $error = $result['error'];
    }
}

$pageTitle = 'Register';
$bodyClass = 'auth-page';
require __DIR__ . '/includes/layout_header.php';
?>
<div class="auth-card">
    <div class="auth-brand">
        <span class="brand-mark"><i class="bi bi-wallet2"></i></span>
        <h1>Create account</h1>
        <p class="text-muted">Start tracking income and expenses in BDT.</p>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="name">Full name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="100">
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required maxlength="190">
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="8">
            <div class="form-text">At least 8 characters.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
    <p class="mt-3 mb-0 text-center small">
        Already have an account? <a href="<?= e(base_path('/login.php')) ?>">Sign in</a>
    </p>
</div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
