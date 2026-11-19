<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeNav */
$user = current_user();
$appName = (string) app_config('app.name', 'ExpenseTrack');
$pageTitle = $pageTitle ?? $appName;
$activeNav = $activeNav ?? '';
$flashes = get_flashes();
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(page_title($pageTitle === $appName ? '' : $pageTitle)) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(base_path('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="app-body <?= e($bodyClass) ?>">
<?php if ($user): ?>
<div class="app-shell">
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <a href="<?= e(base_path('/dashboard.php')) ?>" class="brand-link">
                <span class="brand-mark"><i class="bi bi-wallet2"></i></span>
                <span class="brand-text"><?= e($appName) ?></span>
            </a>
            <button type="button" class="btn btn-sm btn-link text-white d-lg-none sidebar-close" id="sidebarClose" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <?php
            $nav = [
                'dashboard' => ['Dashboard', 'bi-speedometer2', '/dashboard.php'],
                'add' => ['Add Transaction', 'bi-plus-circle', '/transaction_form.php'],
                'history' => ['Transaction History', 'bi-list-ul', '/transactions.php'],
                'categories' => ['Categories', 'bi-tags', '/categories.php'],
                'budgets' => ['Budgets', 'bi-piggy-bank', '/budgets.php'],
                'reports' => ['Reports', 'bi-bar-chart-line', '/reports.php'],
                'settings' => ['Settings', 'bi-gear', '/settings.php'],
            ];
            foreach ($nav as $key => [$label, $icon, $href]):
            ?>
            <a class="nav-link <?= $activeNav === $key ? 'active' : '' ?>" href="<?= e(base_path($href)) ?>">
                <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-chip">
                <i class="bi bi-person-circle"></i>
                <div>
                    <div class="user-name"><?= e($user['name']) ?></div>
                    <div class="user-email"><?= e($user['email']) ?></div>
                </div>
            </div>
            <a class="nav-link logout-link" href="<?= e(base_path('/logout.php')) ?>">
                <i class="bi bi-box-arrow-right"></i><span>Logout</span>
            </a>
        </div>
    </aside>
    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>
    <div class="app-main">
        <header class="app-topbar">
            <button type="button" class="btn btn-outline-secondary d-lg-none" id="sidebarToggle" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="page-heading mb-0"><?= e($pageTitle) ?></h1>
            <div class="topbar-actions">
                <button type="button" class="btn btn-outline-secondary theme-toggle" id="themeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                    <i class="bi bi-moon-stars"></i>
                </button>
            </div>
        </header>
        <main class="app-content">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
<?php else: ?>
<div class="auth-shell">
    <main class="auth-content">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
<?php endif; ?>
