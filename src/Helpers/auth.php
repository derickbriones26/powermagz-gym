<?php

use App\Models\User;
use App\Models\Session;

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
        header('Location: /login');
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
    if ($token) Session::deleteByToken($token);
    setcookie('pmgz_token', '', time() - 3600, '/');
    unset($_SESSION['user_id']);
}
