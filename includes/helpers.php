<?php
declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config/config.php';
        if (!is_file($path)) {
            $path = dirname(__DIR__) . '/config/config.sample.php';
        }
        $config = require $path;
    }
    if ($key === null) {
        return $config;
    }
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function detected_base_path(): string
{
    $configured = app_config('app.base_path', null);
    if (is_string($configured) && $configured !== '') {
        return rtrim($configured, '/');
    }
    // Auto-detect when the app lives in a subdirectory (e.g. /ExpenseTrack).
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir === '/' || $dir === '.' || $dir === '') {
        return '';
    }
    return $dir;
}

function base_path(string $path = ''): string
{
    $base = detected_base_path();
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return $base === '' ? '/' : $base . '/';
    }
    return $base . $path;
}

function redirect(string $path): never
{
    header('Location: ' . base_path($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format BDT with Indian/Bangladeshi grouping: ৳1,25,000.00
 */
function format_bdt(float|string|null $amount, bool $withSymbol = true): string
{
    $num = (float) $amount;
    $negative = $num < 0;
    $num = abs($num);
    $formatted = number_format($num, 2, '.', '');
    [$int, $dec] = explode('.', $formatted);

    if (strlen($int) > 3) {
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $int = $rest . ',' . $last3;
    }

    $out = $int . '.' . $dec;
    if ($negative) {
        $out = '-' . $out;
    }
    if ($withSymbol) {
        $symbol = (string) app_config('app.currency_symbol', '৳');
        return $symbol . $out;
    }
    return $out;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function payment_methods(): array
{
    return ['Cash', 'Bank', 'bKash', 'Nagad', 'Rocket', 'Card', 'Other'];
}

function default_expense_categories(): array
{
    return [
        'Food', 'Transport', 'Education', 'Shopping', 'Health', 'Rent',
        'Electricity', 'Internet', 'Entertainment', 'Mobile Recharge', 'Personal', 'Others',
    ];
}

function default_income_categories(): array
{
    return [
        'Salary', 'Freelancing', 'Business', 'Scholarship', 'Gift', 'Other Income',
    ];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please log in to continue.');
        redirect('/login.php');
    }
    return $user;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function request_str(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $_GET[$key] ?? $default));
}

function request_float(string $key, float $default = 0.0): float
{
    $raw = str_replace(',', '', request_str($key, (string) $default));
    return is_numeric($raw) ? (float) $raw : $default;
}

function request_int(string $key, int $default = 0): int
{
    $raw = request_str($key, (string) $default);
    return is_numeric($raw) ? (int) $raw : $default;
}

function validate_date(?string $date): bool
{
    if (!$date) {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function page_title(string $title = ''): string
{
    $app = (string) app_config('app.name', 'ExpenseTrack');
    return $title === '' ? $app : $title . ' · ' . $app;
}
