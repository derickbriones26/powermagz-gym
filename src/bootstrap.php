<?php

declare(strict_types=1);

/* ================================================================
 |  PATHS + ENVIRONMENT
 |  ================================================================ */

define('BASE_PATH', dirname(__DIR__));

// 🔐 CRITICAL FIX: Load config.php (.env parser) BEFORE constants.php
// Otherwise, constants that rely on getenv() will fail.
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/constants.php';

/* ================================================================
 |  AUTOLOADER  (PSR-4 for App\)
 |  ================================================================ */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;

    $relative = substr($class, strlen($prefix));
    $file     = BASE_PATH . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require_once $file;
});

/* ================================================================
 |  HELPERS  (order matters)
 |  functions.php = canonical helpers (url, csrf, format, auth, flash)
 |  audit.php     = audit_log() global logger (spec §2.1)
 |  mfa.php       = TOTP + recovery codes     (spec §2.1)
 |  session_security.php = idle timeout + forced re-auth middleware (§2.1)
 | ================================================================ */

require_once BASE_PATH . '/src/Helpers/functions.php';

/* Guard the following loads — if a helper isn't present yet, the app
   still boots. Once all files exist, they load on every request. */
foreach (['audit', 'crypto', 'mfa', 'session_security', 'a11y', 'backup', 'mailer'] as $helper) {
    $helperFile = BASE_PATH . '/src/Helpers/' . $helper . '.php';
    if (is_file($helperFile)) {
        require_once $helperFile;
    }
}

/* ================================================================
 |  ERROR REPORTING + LOGGING
 |  ================================================================ */

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'local' ? '1' : '0');
ini_set('log_errors', '1');

if (!is_dir(BASE_PATH . '/storage/logs')) {
    @mkdir(BASE_PATH . '/storage/logs', 0775, true);
}
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

date_default_timezone_set(APP_TIMEZONE);

/* ================================================================
 |  SESSION  (hardened — spec §2.1)
 |  ================================================================ */

if (session_status() === PHP_SESSION_NONE) {
    /* Secure cookie defaults. `secure` flips on automatically when
       HTTPS is detected — safe under local http:// development too. */
    $isSecure = !empty($_SERVER['HTTPS'])
        && strtolower((string)$_SERVER['HTTPS']) !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,            /* session cookie — dies on browser close */
        'path'     => '/',
        'domain'   => '',           /* default: current host */
        'secure'   => $isSecure,
        'httponly' => true,         /* blocks JS access — mitigates XSS cookie theft */
        'samesite' => 'Lax',        /* mitigates CSRF + top-level nav */
    ]);

    /* Cookie name: opaque, avoids leaking that this is PHP */
    session_name('pmgz_sid');

    session_start();

    /* Regenerate the session ID periodically (every 30 min) to
       reduce the window if a session ID leaks. Cheap and standard. */
    if (empty($_SESSION['__rotated_at']) || (time() - (int)$_SESSION['__rotated_at']) > 1800) {
        if (!empty($_SESSION)) {
            session_regenerate_id(true);
        }
        $_SESSION['__rotated_at'] = time();
    }
}

/* ================================================================
 |  SESSION SECURITY MIDDLEWARE  (spec §2.1)
 |
 |  Runs after session_start() so it can read $_SESSION.
 |  Enforces:
 |    - 30-min idle timeout (configurable per-user via users.idle_timeout_minutes)
 |    - 5-min "your session will expire soon" flag for the warning modal
 |    - forced re-auth gate for sensitive operations
 |  ================================================================ */

if (function_exists('session_security_tick')) {
    session_security_tick();
}
