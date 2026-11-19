<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) app_config('db.host', '127.0.0.1');
    $port = (int) app_config('db.port', 3306);
    $name = (string) app_config('db.name', 'expense_tracker');
    $user = (string) app_config('db.user', 'root');
    $pass = (string) app_config('db.pass', '');
    $charset = (string) app_config('db.charset', 'utf8mb4');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $msg = 'Database connection failed. Import database/*.sql and copy config/config.sample.php to config/config.php.';
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $msg . PHP_EOL . $e->getMessage() . PHP_EOL);
            exit(1);
        }
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>DB Error</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            . '</head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger">'
            . htmlspecialchars($msg) . '</div><p class="text-muted small">'
            . htmlspecialchars($e->getMessage()) . '</p></div></body></html>';
        exit;
    }

    return $pdo;
}
