<?php

/**
 * PowerMagz — Session security middleware  (spec §2.1)
 *
 * Loaded by bootstrap.php on every request (guarded by function_exists).
 *
 * Responsibilities:
 *   1. Enforce per-user idle timeout (default 30 min)
 *   2. Set a warning flag in the last 5 minutes before expiry
 *   3. Force logout with AJAX-safe JSON or HTML redirect
 *   4. Gate sensitive operations behind a password re-auth window
 *   5. Provide session_touch() for the /session/ping heartbeat
 */

use App\Config\Database;

/* ================================================================
 |  MAIN ENTRY POINT
 |  Called by bootstrap.php after session_start().
 |  ================================================================ */

if (!function_exists('session_security_tick')) {

    function session_security_tick(): void
    {
        /* Only meaningful when a user is actually signed in. */
        $user = function_exists('current_user') ? current_user() : null;
        if (!$user) return;

        /* Login / register / MFA-challenge / ping pages must always be
           reachable regardless of idle state — otherwise the user can't
           even recover from a timeout. */
        if (session_security_route_is_exempt()) return;

        $token = $_COOKIE['pmgz_token'] ?? null;
        if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) return;

        /* Look up the current session row */
        $session = session_security_current_row($token);
        if (!$session) {
            /* Cookie points to a deleted session — force logout */
            session_security_force_logout(
                'Your session is no longer valid. Please sign in again.'
            );
            return;
        }

        /* Session must not have hit its absolute expiry */
        if (!empty($session['expires_at']) && strtotime($session['expires_at']) < time()) {
            session_security_force_logout(
                'Your session has expired. Please sign in again.'
            );
            return;
        }

        /* Determine the idle timeout for this user */
        $idleMinutes = (int)($user['idle_timeout_minutes'] ?? 30);
        if ($idleMinutes < 5)   $idleMinutes = 5;
        if ($idleMinutes > 480) $idleMinutes = 480;

        $idleSeconds = $idleMinutes * 60;

        $lastActivity = !empty($session['last_activity_at'])
            ? (int)strtotime($session['last_activity_at'])
            : time();

        $elapsed = max(0, time() - $lastActivity);

        /* ---- Hard timeout ---- */
        if ($elapsed >= $idleSeconds) {
            session_security_force_logout(
                'You were signed out after ' . $idleMinutes . ' minutes of inactivity.'
            );
            return;
        }

        /* ---- Warning window (last 5 min before expiry) ---- */
        $warnWindow = 5 * 60;
        if ($elapsed >= ($idleSeconds - $warnWindow)) {
            $_SESSION['__idle_warning']     = true;
            $_SESSION['__idle_expires_at']  = $lastActivity + $idleSeconds;
            $_SESSION['__idle_timeout_min'] = $idleMinutes;
        } else {
            unset(
                $_SESSION['__idle_warning'],
                $_SESSION['__idle_expires_at'],
                $_SESSION['__idle_timeout_min']
            );
        }

        /* ---- Throttled DB write (once per minute max) ---- */
        if ($elapsed >= 60) {
            session_security_touch($token);
        }
    }

    /* ================================================================
     |  ROUTE EXEMPTIONS
     |  ================================================================ */

    function session_security_route_is_exempt(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        /* Strip subfolder base path (e.g. /powermagz-gym) */
        $base = function_exists('base_path') ? base_path() : '';
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        static $exact = [
            '/',
            '/login',
            '/register',
            '/register/verify',
            '/register/resend',
            '/forgot-password',
            '/reset-password',
            '/logout',
            '/mfa/challenge',
            '/mfa/recovery',
            '/mfa/verify',
            '/reauth',
            '/session/ping',
            '/api/occupancy',
        ];

        if (in_array($path, $exact, true)) return true;

        /* Prefix matches */
        foreach (['/public/', '/api/public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) return true;
        }

        return false;
    }

    /* ================================================================
     |  SESSION ROW HELPERS
     |  ================================================================ */

    function session_security_current_row(string $token): ?array
    {
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT * FROM `sessions` WHERE `token` = ? LIMIT 1'
            );
            $stmt->execute([$token]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            error_log('[session_security_current_row] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Bump last_activity_at on the current (or provided) session row.
     * Called from:
     *   - session_security_tick() (throttled, once/min)
     *   - /session/ping controller (immediate)
     *   - session_grant_reauth() after successful re-auth
     */
    function session_security_touch(?string $token = null): void
    {
        $token = $token ?? ($_COOKIE['pmgz_token'] ?? null);
        if (!$token) return;
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return;

        try {
            Database::pdo()->prepare(
                'UPDATE `sessions` SET `last_activity_at` = NOW() WHERE `token` = ?'
            )->execute([$token]);
        } catch (\Throwable $e) {
            error_log('[session_security_touch] ' . $e->getMessage());
        }
    }

    /* ================================================================
     |  FORCE LOGOUT
     |  ================================================================ */

    function session_security_force_logout(string $message): void
    {
        $user = function_exists('current_user') ? current_user() : null;

        if (function_exists('audit_log')) {
            try {
                audit_log(
                    'auth.session_expired',
                    'user',
                    $user['id'] ?? null,
                    null,
                    null,
                    $message
                );
            } catch (\Throwable $e) { /* silent */
            }
        }

        /* Delete the session row from the DB */
        $token = $_COOKIE['pmgz_token'] ?? null;
        if ($token) {
            try {
                Database::pdo()->prepare(
                    'DELETE FROM `sessions` WHERE `token` = ?'
                )->execute([$token]);
            } catch (\Throwable $e) { /* silent */
            }
        }

        /* Clear the cookie */
        setcookie('pmgz_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        /* Clear server-side session */
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        /* AJAX → JSON; else → redirect */
        if (session_security_is_ajax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'   => 'session_expired',
                'message'  => $message,
                'redirect' => function_exists('url') ? url('/login') : '/login',
            ]);
            exit;
        }

        if (function_exists('flash')) {
            flash('error', $message);
        }

        $loginUrl = function_exists('url') ? url('/login') : '/login';
        header('Location: ' . $loginUrl);
        exit;
    }

    /* ================================================================
     |  REQUEST TYPE DETECTION
     |  ================================================================ */

    function session_security_is_ajax(): bool
    {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            return true;
        }
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        if (str_contains($accept, 'application/json')) return true;
        if (str_contains($accept, 'application/javascript')) return true;
        return false;
    }

    /* ================================================================
     |  FORCED RE-AUTH FOR SENSITIVE OPERATIONS
     |  ================================================================ */

    /**
     * Grant a re-auth window. Call this after the user has confirmed
     * their password on /reauth.
     */
    function session_grant_reauth(int $minutes = 5): void
    {
        $token = $_COOKIE['pmgz_token'] ?? null;
        if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) return;

        $until = date('Y-m-d H:i:s', time() + ($minutes * 60));

        try {
            Database::pdo()->prepare(
                'UPDATE `sessions` SET `reauth_until` = ?, `last_activity_at` = NOW()
                 WHERE `token` = ?'
            )->execute([$until, $token]);

            $_SESSION['__reauth_granted_at'] = time();
        } catch (\Throwable $e) {
            error_log('[session_grant_reauth] ' . $e->getMessage());
        }
    }

    /**
     * Whether the current session has a live re-auth window.
     */
    function session_has_valid_reauth(): bool
    {
        $token = $_COOKIE['pmgz_token'] ?? null;
        if (!$token) return false;

        $session = session_security_current_row($token);
        if (!$session || empty($session['reauth_until'])) return false;

        return strtotime($session['reauth_until']) > time();
    }

    /**
     * Guard a sensitive operation. Call this at the top of any controller
     * action that needs a fresh password confirmation:
     *   session_require_reauth();
     *
     * Pass an explicit $next URL when you want the user redirected to a
     * specific page after re-auth (defaults to the current URI).
     */
    function session_require_reauth(?string $next = null): void
    {
        if (session_has_valid_reauth()) return;

        if ($next === null) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $base = function_exists('base_path') ? base_path() : '';
            if ($base !== '' && str_starts_with($path, $base)) {
                $path = substr($path, strlen($base));
            }
            $next = '/' . ltrim($path, '/');
        }

        $redirectUrl = function_exists('url')
            ? url('/reauth?next=' . urlencode($next))
            : '/reauth?next=' . urlencode($next);

        if (session_security_is_ajax()) {
            http_response_code(428); /* Precondition Required */
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'   => 'reauth_required',
                'message'  => 'Please confirm your password to continue.',
                'redirect' => $redirectUrl,
            ]);
            exit;
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    /* ================================================================
     |  VIEW HELPERS
     |  ================================================================ */

    /**
     * Data block the warning modal + JS countdown need.
     * Returns null when no warning is active.
     *
     * @return array{expires_at:int, seconds_left:int, timeout_minutes:int}|null
     */
    function session_security_warning_data(): ?array
    {
        if (empty($_SESSION['__idle_warning'])) return null;

        $expiresAt = (int)($_SESSION['__idle_expires_at'] ?? 0);
        if ($expiresAt <= 0) return null;

        return [
            'expires_at'      => $expiresAt,
            'seconds_left'    => max(0, $expiresAt - time()),
            'timeout_minutes' => (int)($_SESSION['__idle_timeout_min'] ?? 30),
        ];
    }
}
