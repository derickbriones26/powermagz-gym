<?php

declare(strict_types=1);

/**
 * Cross-cutting security helpers.
 *
 * Loaded once from bootstrap.php. Kept dependency-free so the app still
 * boots on shared hosting without extra extensions.
 */

/* ================================================================
 |  TRANSPORT / CLIENT
 |  ================================================================ */

/**
 * True when the request arrived over HTTPS, including when TLS is
 * terminated by a reverse proxy (Render, nginx, Cloudflare).
 */
function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }

    $proto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($proto !== '') {
        $first = trim(explode(',', $proto)[0]);
        if ($first === 'https') return true;
    }

    return false;
}

/**
 * Best-effort client IP. Forwarded headers are only trusted when the
 * direct peer is a private/loopback address (i.e. a local proxy).
 */
function client_ip(): string
{
    $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));

    $validRemote = filter_var($remote, FILTER_VALIDATE_IP) !== false;
    $isPrivate   = $validRemote
        && filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

    if ($isPrivate) {
        $forwarded = trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]);
        if (filter_var($forwarded, FILTER_VALIDATE_IP) !== false) {
            return $forwarded;
        }
    }

    return $validRemote ? $remote : '0.0.0.0';
}

/* ================================================================
 |  RATE LIMITING  (file-backed, works without Redis/memcached)
 |  ================================================================ */

function rate_limit_dir(): string
{
    return BASE_PATH . '/storage/ratelimit';
}

function rate_limit_file(string $bucket, string $subject = ''): string
{
    $subject = $subject !== '' ? strtolower(trim($subject)) : client_ip();
    return rate_limit_dir() . '/' . hash('sha256', $bucket . '|' . $subject) . '.json';
}

/**
 * Record an attempt. Returns true when the caller may proceed, false when
 * the bucket has exceeded $max attempts inside $window seconds.
 *
 * Fails open (allows the request) if storage cannot be written; rate
 * limiting is defence-in-depth, not the only control.
 */
function rate_limit_attempt(string $bucket, int $max = 5, int $window = 300, string $subject = ''): bool
{
    $max    = max(1, $max);
    $window = max(1, $window);
    $now    = time();
    $dir    = rate_limit_dir();

    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        error_log('[rate_limit] storage directory is not writable: ' . $dir);
        return true;
    }

    $file = rate_limit_file($bucket, $subject);
    $fp   = @fopen($file, 'c+');
    if (!$fp) {
        error_log('[rate_limit] cannot open ' . $file);
        return true;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return true;
    }

    $raw   = stream_get_contents($fp);
    $state = json_decode($raw !== false ? $raw : '', true);

    if (!is_array($state)
        || !isset($state['count'], $state['reset'])
        || (int)$state['reset'] <= $now
    ) {
        $state = ['count' => 0, 'reset' => $now + $window];
    }

    $state['count'] = (int)$state['count'] + 1;
    $allowed        = $state['count'] <= $max;

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, (string)json_encode($state));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    /* Occasionally remove expired buckets so the directory stays bounded. */
    if (random_int(1, 200) === 1) {
        rate_limit_gc();
    }

    return $allowed;
}

/** Seconds until the bucket resets (0 when no bucket exists). */
function rate_limit_retry_after(string $bucket, string $subject = ''): int
{
    $file = rate_limit_file($bucket, $subject);
    if (!is_file($file)) return 0;

    $state = json_decode((string)@file_get_contents($file), true);
    if (!is_array($state) || !isset($state['reset'])) return 0;

    return max(0, (int)$state['reset'] - time());
}

/** Forget a bucket after a successful action. */
function rate_limit_clear(string $bucket, string $subject = ''): void
{
    $file = rate_limit_file($bucket, $subject);
    if (is_file($file)) {
        @unlink($file);
    }
}

function rate_limit_gc(): void
{
    $files = glob(rate_limit_dir() . '/*.json');
    if (!$files) return;

    $cutoff = time() - 86400;
    foreach ($files as $file) {
        $mtime = @filemtime($file);
        if ($mtime !== false && $mtime < $cutoff) {
            @unlink($file);
        }
    }
}

/* ================================================================
 |  REDIRECTS
 |  ================================================================ */

/**
 * Keep only same-origin, path-absolute redirect targets.
 *
 * Blocks scheme-relative ("//evil.com"), backslash tricks ("/\evil.com"),
 * encoded separators ("/%2F%2Fevil.com"), control characters and any
 * value that does not begin with a single "/".
 */
function safe_redirect_path(string $url): string
{
    $url = trim($url);
    if ($url === '' || strlen($url) > 2048) return '/';
    if (preg_match('/[\x00-\x1F\x7F]/', $url)) return '/';
    if (str_contains($url, '\\')) return '/';
    if (!str_starts_with($url, '/')) return '/';
    if (str_starts_with($url, '//')) return '/';

    $parts = parse_url($url);
    if ($parts === false) return '/';

    $path  = (string)($parts['path'] ?? '/');
    $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

    $decodedPath = rawurldecode($path);
    if ($decodedPath === '' || !str_starts_with($decodedPath, '/')) return '/';
    if (str_starts_with($decodedPath, '//')) return '/';
    if (str_contains($decodedPath, '\\')) return '/';
    if (preg_match('/[\x00-\x1F\x7F]/', $decodedPath)) return '/';

    return $path . $query;
}

/**
 * Convert a request URI that may include the app's subfolder into an
 * app-relative path suitable for Controller::redirect().
 *
 * Example (served from /powermagz-gym):
 *   /powermagz-gym/admin-dashboard?tab=members
 *   -> /admin-dashboard?tab=members
 */
function app_relative_path(string $url): string
{
    $safe = safe_redirect_path($url);
    if ($safe === '/') return '/';

    $base = base_path();
    if ($base !== '' && ($safe === $base || str_starts_with($safe, $base . '/'))) {
        $stripped = substr($safe, strlen($base));
        return $stripped === '' ? '/' : $stripped;
    }

    return $safe;
}

/* ================================================================
 |  CSV EXPORT SAFETY
 |  ================================================================ */

/**
 * Neutralise spreadsheet formula injection without mangling real numbers.
 */
function csv_safe(mixed $value): string
{
    $s = str_replace("\0", '', (string)($value ?? ''));
    if ($s === '') return '';

    /* Keep ordinary signed numbers numeric. */
    if (preg_match('/^[+-]?\d+(?:\.\d+)?$/', trim($s))) {
        return $s;
    }

    /* =, +, -, @, tab or CR after optional whitespace starts a formula. */
    if (preg_match('/^\s*[=+\-@\t\r]/u', $s)) {
        return "'" . $s;
    }

    return $s;
}

/** fputcsv() wrapper that neutralises every cell. */
function csv_write($handle, array $row): void
{
    fputcsv($handle, array_map('csv_safe', $row));
}

/* ================================================================
 |  JSON IN HTML ATTRIBUTES / SCRIPTS
 |  ================================================================ */

/**
 * json_encode() safe for inline HTML attributes and <script> blocks.
 * HEX_AMP stops HTML entity decoding from turning "&quot;" into a real
 * quote after JSON encoding, which is the classic onclick XSS bypass.
 */
function json_attr(mixed $data): string
{
    $json = json_encode(
        $data,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    return $json !== false ? $json : 'null';
}

/* ================================================================
 |  RESPONSE HEADERS
 |  ================================================================ */

function security_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
    header("Cross-Origin-Resource-Policy: same-origin");

    if (request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
