<?php
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        if (!isset($_ENV[$key]) && getenv($key) === false) {
            $_ENV[$key] = trim($val);
        }
    }
}

loadEnv(__DIR__ . '/../../.env');

function env(string $key, string $default = ''): string {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // JawsDB (Heroku) fournit une URL complète : mysql://user:pass@host:port/dbname
    $jawsUrl = env('JAWSDB_URL');
    if ($jawsUrl !== '') {
        $p = parse_url($jawsUrl);
        $host = $p['host'];
        $name = ltrim($p['path'], '/');
        $user = $p['user'];
        $pass = $p['pass'] ?? '';
        $port = $p['port'] ?? 3306;
    } else {
        $host = env('DB_HOST', 'localhost');
        $name = env('DB_NAME', 'ecoride');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');
        $port = 3306;
    }

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}
