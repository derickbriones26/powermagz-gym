<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class MfaController extends Controller
{
    /* =============================================================
     |  SETUP  (authenticated admin/staff)
     |  ============================================================= */

    /**
     * Show the setup page: QR code, manual secret, code entry form.
     * If MFA is already enabled, redirect to the manage page.
     */
    public function showSetup(): void
    {
        $user = current_user();
        if (!$user) {
            $this->redirect('/login');
        }

        /* Already enrolled → show manage view instead */
        if (!empty($user['mfa_enabled']) && !empty($user['mfa_secret'])) {
            $recoveryHashes = mfa_decode_recovery_hashes($user['mfa_recovery_codes'] ?? null);

            $this->view('auth/mfa_manage', [
                'pageTitle'       => 'Two-Factor Authentication',
                'user'            => $user,
                'recoveryCount'   => count($recoveryHashes),
                'verifiedAt'      => $user['mfa_verified_at'] ?? null,
            ]);
            return;
        }

        /* Park a fresh secret in session for the QR code */
        if (empty($_SESSION['mfa_setup_secret'])) {
            $_SESSION['mfa_setup_secret']     = mfa_generate_secret();
            $_SESSION['mfa_setup_started_at'] = time();
        }

        $secret       = $_SESSION['mfa_setup_secret'];
        $issuer       = defined('APP_NAME') ? APP_NAME : 'PowerMagz Gym';
        $accountLabel = $user['email'];
        $uri          = mfa_provisioning_uri($secret, $accountLabel, $issuer);
        $qrUrl        = mfa_qr_url($uri, 240);
        $formatted    = mfa_format_secret($secret);

        $this->view('auth/mfa_setup', [
            'pageTitle' => 'Set Up Two-Factor Authentication',
            'secret'    => $secret,
            'formatted' => $formatted,
            'qrUrl'     => $qrUrl,
            'uri'       => $uri,
        ]);
    }

    /**
     * Rotate the setup secret (in case the user wants a new QR code).
     */
    public function beginSetup(): void
    {
        csrf_verify();

        $user = current_user();
        if (!$user) {
            $this->redirect('/login');
        }

        if (!empty($user['mfa_enabled']) && !empty($user['mfa_secret'])) {
            flash('error', 'MFA is already enabled. Disable it first to re-enroll.');
            $this->redirect('/mfa/setup');
        }

        $_SESSION['mfa_setup_secret']     = mfa_generate_secret();
        $_SESSION['mfa_setup_started_at'] = time();

        if (function_exists('audit_log')) {
            audit_log(
                'auth.mfa.setup_rotated',
                'user',
                $user['id'],
                null,
                null,
                'MFA setup secret regenerated'
            );
        }

        flash('success', 'New secret generated. Scan the updated QR code.');
        $this->redirect('/mfa/setup');
    }

    /**
     * Verify the 6-digit code from the authenticator app.
     * On success: enable MFA, generate + display recovery codes once.
     */
    public function verify(): void
    {
        csrf_verify();

        $user = current_user();
        if (!$user) {
            $this->redirect('/login');
        }

        $code   = trim((string)$this->input('code'));
        $secret = $_SESSION['mfa_setup_secret'] ?? null;

        if (!$secret) {
            flash('error', 'Setup session expired. Please start again.');
            $this->redirect('/mfa/setup');
        }

        /* Reject codes that have obviously expired from an old window */
        $startedAt = (int)($_SESSION['mfa_setup_started_at'] ?? 0);
        if ($startedAt > 0 && (time() - $startedAt) > 3600) {
            unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_started_at']);
            flash('error', 'Setup took too long. Please start again.');
            $this->redirect('/mfa/setup');
        }

        if (!mfa_verify($secret, $code)) {
            if (function_exists('audit_log')) {
                audit_log(
                    'auth.mfa.setup.verify_failed',
                    'user',
                    $user['id'],
                    null,
                    null,
                    'Incorrect TOTP code during setup'
                );
            }
            flash('error', 'Invalid code. Make sure your phone time is accurate and try again.');
            $this->redirect('/mfa/setup');
        }

        /* Generate fresh recovery codes */
        $codes = mfa_generate_recovery_codes(10);

        User::update($user['id'], [
            'mfa_enabled'        => 1,
            'mfa_secret'         => $secret,
            'mfa_recovery_codes' => mfa_encode_recovery_hashes($codes['hashed']),
            'mfa_verified_at'    => date('Y-m-d H:i:s'),
        ]);

        if (function_exists('audit_log')) {
            audit_log(
                'auth.mfa.enabled',
                'user',
                $user['id'],
                null,
                null,
                'MFA enabled; ' . count($codes['plain']) . ' recovery codes generated'
            );
        }

        unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_started_at']);

        /* Render the recovery-codes view DIRECTLY so codes show exactly once.
           Refreshing this page re-submits the POST and would fail CSRF —
           user must re-enroll in that case, which is intentional. */
        $this->view('auth/mfa_recovery_codes', [
            'pageTitle' => 'Save Your Recovery Codes',
            'codes'     => $codes['plain'],
        ]);
    }

    /**
     * Disable MFA. Requires an active re-auth window (§2.1).
     */
    public function disable(): void
    {
        csrf_verify();

        $user = current_user();
        if (!$user) {
            $this->redirect('/login');
        }

        /* Sensitive operation → confirm password just before */
        if (function_exists('session_require_reauth')) {
            session_require_reauth('/mfa/setup');
        }

        if (empty($user['mfa_enabled'])) {
            flash('error', 'MFA is not currently enabled.');
            $this->redirect('/mfa/setup');
        }

        User::update($user['id'], [
            'mfa_enabled'        => 0,
            'mfa_secret'         => null,
            'mfa_recovery_codes' => null,
            'mfa_verified_at'    => null,
        ]);

        if (function_exists('audit_log')) {
            audit_log(
                'auth.mfa.disabled',
                'user',
                $user['id'],
                null,
                null,
                'MFA disabled by user'
            );
        }

        flash('success', 'Two-factor authentication has been disabled.');
        $this->redirect('/mfa/setup');
    }

    /**
     * Regenerate recovery codes (invalidates all previous ones).
     * Requires re-auth.
     */
    public function regenerateRecoveryCodes(): void
    {
        csrf_verify();

        $user = current_user();
        if (!$user) {
            $this->redirect('/login');
        }

        if (function_exists('session_require_reauth')) {
            session_require_reauth('/mfa/setup');
        }

        if (empty($user['mfa_enabled'])) {
            flash('error', 'Enable MFA first.');
            $this->redirect('/mfa/setup');
        }

        $codes = mfa_generate_recovery_codes(10);

        User::update($user['id'], [
            'mfa_recovery_codes' => mfa_encode_recovery_hashes($codes['hashed']),
        ]);

        if (function_exists('audit_log')) {
            audit_log(
                'auth.mfa.recovery_regenerated',
                'user',
                $user['id'],
                null,
                null,
                'Recovery codes regenerated'
            );
        }

        $this->view('auth/mfa_recovery_codes', [
            'pageTitle' => 'Save Your New Recovery Codes',
            'codes'     => $codes['plain'],
        ]);
    }

    /* =============================================================
     |  LOGIN CHALLENGE  (unauthenticated — user is only partially logged in)
     |  ============================================================= */

    /**
     * Show the MFA challenge page during login.
     */
    public function showChallenge(): void
    {
        $pendingId = $_SESSION['mfa_pending_user_id'] ?? null;

        if (!$pendingId) {
            flash('error', 'Please sign in to continue.');
            $this->redirect('/login');
        }

        /* Pending window: 10 minutes */
        $pendingAt = (int)($_SESSION['mfa_pending_at'] ?? 0);
        if ($pendingAt <= 0 || (time() - $pendingAt) > 600) {
            $this->clearPendingMfa();
            flash('error', 'Verification window expired. Please sign in again.');
            $this->redirect('/login');
        }

        $user = User::find($pendingId);
        if (!$user) {
            $this->clearPendingMfa();
            $this->redirect('/login');
        }

        $this->view('auth/mfa_challenge', [
            'pageTitle' => 'Verify Your Identity',
            'email'     => $user['email'],
        ]);
    }

    /**
     * Submit a 6-digit TOTP code.
     */
    public function submitChallenge(): void
    {
        csrf_verify();

        $pendingId = $_SESSION['mfa_pending_user_id'] ?? null;
        if (!$pendingId) {
            $this->redirect('/login');
        }

        $user = User::find($pendingId);
        if (!$user) {
            $this->clearPendingMfa();
            flash('error', 'Session expired. Please sign in again.');
            $this->redirect('/login');
        }

        $pendingAt = (int)($_SESSION['mfa_pending_at'] ?? 0);
        if ($pendingAt <= 0 || (time() - $pendingAt) > 600) {
            $this->clearPendingMfa();
            flash('error', 'Verification window expired. Please sign in again.');
            $this->redirect('/login');
        }

        $code = trim((string)$this->input('code'));

        if (!mfa_verify((string)$user['mfa_secret'], $code)) {
            if (function_exists('audit_log')) {
                audit_log(
                    'auth.mfa.challenge_failed',
                    'user',
                    $user['id'],
                    null,
                    null,
                    'Wrong TOTP code during login'
                );
            }
            flash('error', 'Invalid code. Please try again.');
            $this->redirect('/mfa/challenge');
        }

        /* Success — finish login */
        $intended = $_SESSION['mfa_pending_intended'] ?? null;

        login_user($user);
        $this->clearPendingMfa();

        if (function_exists('audit_log')) {
            audit_log(
                'auth.login',
                'user',
                $user['id'],
                null,
                ['method' => 'mfa_totp'],
                'Signed in via TOTP'
            );
        }

        $redirect = $intended
            ? (parse_url($intended, PHP_URL_PATH) ?: $this->homeFor($user['role']))
            : $this->homeFor($user['role']);

        $this->redirect($redirect);
    }

    /**
     * Submit a recovery code during login.
     */
    public function useRecoveryCode(): void
    {
        csrf_verify();

        $pendingId = $_SESSION['mfa_pending_user_id'] ?? null;
        if (!$pendingId) {
            $this->redirect('/login');
        }

        $user = User::find($pendingId);
        if (!$user) {
            $this->clearPendingMfa();
            flash('error', 'Session expired. Please sign in again.');
            $this->redirect('/login');
        }

        $pendingAt = (int)($_SESSION['mfa_pending_at'] ?? 0);
        if ($pendingAt <= 0 || (time() - $pendingAt) > 600) {
            $this->clearPendingMfa();
            flash('error', 'Verification window expired. Please sign in again.');
            $this->redirect('/login');
        }

        $input  = trim((string)$this->input('recovery_code'));
        $hashes = mfa_decode_recovery_hashes($user['mfa_recovery_codes'] ?? null);

        if (empty($hashes)) {
            flash('error', 'No recovery codes are available for this account.');
            $this->redirect('/mfa/challenge');
        }

        $idx = mfa_consume_recovery_code($input, $hashes);

        if ($idx === null || $idx < 0) {
            if (function_exists('audit_log')) {
                audit_log(
                    'auth.mfa.recovery_failed',
                    'user',
                    $user['id'],
                    null,
                    null,
                    'Invalid recovery code during login'
                );
            }
            flash('error', 'Invalid recovery code.');
            $this->redirect('/mfa/challenge');
        }

        /* Consume the matched code */
        unset($hashes[$idx]);
        $hashes = array_values($hashes);

        User::update($user['id'], [
            'mfa_recovery_codes' => mfa_encode_recovery_hashes($hashes),
        ]);

        if (function_exists('audit_log')) {
            audit_log(
                'auth.mfa.recovery_used',
                'user',
                $user['id'],
                null,
                ['remaining' => count($hashes)],
                'Recovery code used; ' . count($hashes) . ' remaining'
            );
        }

        /* Finish login */
        $intended = $_SESSION['mfa_pending_intended'] ?? null;

        login_user($user);
        $this->clearPendingMfa();

        $remaining = count($hashes);
        if ($remaining <= 2) {
            flash(
                'warning',
                "You've used a recovery code. Only {$remaining} remaining — regenerate them soon."
            );
        } else {
            flash('success', "Signed in. {$remaining} recovery codes remaining.");
        }

        $redirect = $intended
            ? (parse_url($intended, PHP_URL_PATH) ?: $this->homeFor($user['role']))
            : $this->homeFor($user['role']);

        $this->redirect($redirect);
    }

    /* =============================================================
     |  HELPERS
     |  ============================================================= */

    private function clearPendingMfa(): void
    {
        unset(
            $_SESSION['mfa_pending_user_id'],
            $_SESSION['mfa_pending_at'],
            $_SESSION['mfa_pending_intended']
        );
    }

    private function homeFor(string $role): string
    {
        return match ($role) {
            'admin', 'staff' => '/admin-dashboard',
            'partner'        => '/partner-dashboard',
            default          => '/home',
        };
    }
}
