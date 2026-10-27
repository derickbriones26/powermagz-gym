<?php

use App\Models\User;
use App\Models\Session;

/* ─────────── URL ─────────── */

function base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return $base;
}

function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function url_e(string $path = '/'): string
{
    return htmlspecialchars(url($path), ENT_QUOTES, 'UTF-8');
}

/* ─────────── Format ─────────── */

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function peso(float|int|string|null $n): string
{
    return '₱' . number_format((float)($n ?? 0));
}

function initials(?string $name): string
{
    $name = trim((string)$name);
    return $name ? strtoupper(mb_substr($name, 0, 1)) : '?';
}

function short_date(?string $date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : '—';
}

function time_short(?string $dt): string
{
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('g:i A', $ts) : '—';
}

/* ─────────── CSRF ─────────── */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_verify(): void
{
    $sessionToken = (string)($_SESSION['csrf'] ?? '');
    $requestToken = (string)($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

    // 🔐 CRITICAL FIX: Reject if either token is empty to prevent hash_equals('', '') bypass
    if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
        http_response_code(419);
        exit('CSRF token mismatch.');
    }
}

/* ─────────── Flash ─────────── */

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $m = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $m;
}

/* ─────────── Auth ─────────── */

function current_user(): ?array
{
    static $cached = null;
    if ($cached !== null) return $cached ?: null;

    $token = $_COOKIE['pmgz_token'] ?? null;
    if (!$token) return null;

    $session = Session::findValid($token);
    if (!$session) return null;

    $user = User::find($session['user_id']);
    $cached = $user ?: false;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['flash_intended'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . url('/login'));
        exit;
    }
}

function require_role(string ...$roles): void
{
    $u = current_user();
    if (!$u || !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        require BASE_PATH . '/src/Views/errors/403.php';
        exit;
    }
}

function login_user(array $user): void
{
    // 🔐 CRITICAL FIX: Prevent Session Fixation
    session_regenerate_id(true);
    unset($_SESSION['csrf']); // Force a new CSRF token for the authenticated session
    $_SESSION['__rotated_at'] = time();

    $token = bin2hex(random_bytes(32));

    Session::create([
        'user_id'    => $user['id'],
        'token'      => $token,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'expires_at' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30),
    ]);

    setcookie('pmgz_token', $token, [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    $token = $_COOKIE['pmgz_token'] ?? null;
    if ($token) {
        Session::deleteByToken($token);
    }

    setcookie('pmgz_token', '', time() - 3600, '/');

    // 🔐 CRITICAL FIX: Fully destroy the session state to prevent fixation
    unset($_SESSION['user_id'], $_SESSION['csrf']);
    session_regenerate_id(true);
}
