<?php

/* ============================================================
 |  ENVIRONMENT PARSER (.env) — Production Safe
 |  Only sets variables if they are NOT already set by the host
 | ============================================================ */
$envFile = __DIR__ . '/../.env';

if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);

        // Strip surrounding quotes
        if (strlen($v) >= 2) {
            $first = $v[0];
            $last = substr($v, -1);
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $v = substr($v, 1, -1);
            }
        }

        // 🔐 CRITICAL: Only set if NOT already set (Render's env vars take priority)
        if (getenv($k) === false) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
        }
    }
}

/* ============================================================
 |  APPLICATION CONSTANTS
 | ============================================================ */
return (function () {
    define('APP_NAME',     getenv('APP_NAME')     ?: 'PowerMagz Gym');
    define('APP_URL',      getenv('APP_URL')      ?: 'http://localhost/powermagz-gym');
    define('APP_ENV',      getenv('APP_ENV')      ?: 'production'); // Default to production
    define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Asia/Manila');
    define('APP_DEBUG',    APP_ENV === 'local');

    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'powermagz_gym');
    define('DB_USER', getenv('DB_USER') ?: 'powermagz');
    define('DB_PASS', getenv('DB_PASS') ?: 'secret');

    define('MAIL_FROM',    getenv('MAIL_FROM') ?: 'no-reply@powermagz.ph');
    define('GCASH_NUMBER', '0961 804 1523');
})();
