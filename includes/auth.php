<?php
declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $name = (string) app_config('session.name', 'EXPENSETRACKSESSID');
    $lifetime = (int) app_config('session.lifetime', 86400);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_name($name);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'user_id' => (int) $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function seed_default_categories(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO categories (user_id, category_name, category_type) VALUES (?, ?, ?)'
    );
    foreach (default_expense_categories() as $name) {
        $stmt->execute([$userId, $name, 'expense']);
    }
    foreach (default_income_categories() as $name) {
        $stmt->execute([$userId, $name, 'income']);
    }
}

function register_user(string $name, string $email, string $password): array
{
    $pdo = db();
    $email = strtolower(trim($email));
    $name = trim($name);

    if ($name === '' || $email === '' || $password === '') {
        return ['ok' => false, 'error' => 'All fields are required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'An account with this email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $ins->execute([$name, $email, $hash]);
        $userId = (int) $pdo->lastInsertId();
        seed_default_categories($pdo, $userId);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['ok' => false, 'error' => 'Registration failed. Please try again.'];
    }

    return [
        'ok' => true,
        'user' => [
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
        ],
    ];
}

function attempt_login(string $email, string $password): array
{
    $pdo = db();
    $email = strtolower(trim($email));
    $stmt = $pdo->prepare('SELECT user_id, name, email, password FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
    unset($user['password']);
    return ['ok' => true, 'user' => $user];
}
