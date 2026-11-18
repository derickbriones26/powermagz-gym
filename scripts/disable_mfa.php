<?php

declare(strict_types=1);

/**
 * Break-glass MFA reset (CLI only).
 *
 * Use when a privileged account has lost its authenticator and recovery
 * codes. Disables MFA for that account; the next login is forced through
 * enrollment again when MFA_REQUIRED_FOR_ADMIN is enabled.
 *
 *   php scripts/disable_mfa.php admin@example.com
 *   php scripts/disable_mfa.php admin@example.com --revoke-sessions
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}

require __DIR__ . '/../src/bootstrap.php';

use App\Models\Session;
use App\Models\User;

$email = trim((string)($argv[1] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/disable_mfa.php <email> [--revoke-sessions]\n");
    exit(1);
}

$user = User::findByEmail($email);
if (!$user) {
    fwrite(STDERR, "No user found for {$email}\n");
    exit(1);
}

User::update((string)$user['id'], [
    'mfa_enabled'        => 0,
    'mfa_secret'         => null,
    'mfa_recovery_codes' => null,
    'mfa_verified_at'    => null,
]);

if (in_array('--revoke-sessions', $argv, true)) {
    $count = Session::deleteByUser((string)$user['id']);
    echo "[mfa] Revoked {$count} session(s) for {$email}.\n";
}

if (function_exists('audit_log')) {
    audit_log(
        'auth.mfa.disabled_cli',
        'user',
        $user['id'],
        null,
        null,
        'MFA disabled from the command line by an operator'
    );
}

echo "[mfa] MFA disabled for {$email}. Enroll again on next login.\n";
