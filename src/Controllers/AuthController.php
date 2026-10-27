<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\MemberProfile;
use App\Models\PartnerProfile;
use App\Models\B2BClient;
use App\Models\PasswordReset;

class AuthController extends Controller
{
    /* =============================================================
     |  LOGIN
     ============================================================= */

    public function showLogin(): void
    {
        if (current_user()) {
            $this->redirect($this->homeFor(current_user()['role']));
        }
        $this->view('auth/login', ['pageTitle' => 'Log In']);
    }

    public function login(): void
    {
        csrf_verify();

        $email = trim((string)$this->input('email'));
        $pass  = (string)$this->input('password');

        $user = User::findByEmail($email);

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            if (function_exists('audit_log')) {
                audit_log(
                    'auth.login.failed',
                    'user',
                    null,
                    null,
                    ['email' => $email],
                    'Invalid credentials'
                );
            }
            flash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }

        /* Transparently upgrade legacy bcrypt cost ≤ 10 to cost 12 */
        $this->maybeRehashPassword($user, $pass);

        /* Record last_login_at */
        User::update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        /* MFA gate: admin/staff with MFA enabled must complete TOTP first */
        if ($this->userNeedsMfa($user)) {
            $_SESSION['mfa_pending_user_id']  = $user['id'];
            $_SESSION['mfa_pending_at']       = time();
            $_SESSION['mfa_pending_intended'] = $_SESSION['flash_intended'] ?? null;

            if (function_exists('audit_log')) {
                audit_log(
                    'auth.login.mfa_challenge',
                    'user',
                    $user['id'],
                    null,
                    null,
                    'Password OK — MFA challenge issued'
                );
            }

            $this->redirect('/mfa/challenge');
        }

        /* No MFA required — complete login directly */
        login_user($user);

        $intended = $_SESSION['flash_intended'] ?? null;
        unset($_SESSION['flash_intended']);

        if (function_exists('audit_log')) {
            audit_log('auth.login', 'user', $user['id'], null, null, 'User signed in');
        }

        $redirect = $intended
            ? (parse_url($intended, PHP_URL_PATH) ?: $this->homeFor($user['role']))
            : $this->homeFor($user['role']);

        $this->redirect($redirect);
    }

    /**
     * True when the user is admin/staff AND has MFA fully enrolled.
     */
    private function userNeedsMfa(array $user): bool
    {
        $role = $user['role'] ?? '';
        if (!in_array($role, ['admin', 'staff'], true)) {
            return false;
        }
        return !empty($user['mfa_enabled']) && !empty($user['mfa_secret']);
    }

    /**
     * Upgrade bcrypt cost ≤ 10 hashes to cost 12 on successful login.
     */
    private function maybeRehashPassword(array $user, string $plainPassword): void
    {
        $hash = $user['password_hash'] ?? '';
        if ($hash === '') return;

        $info = password_get_info($hash);
        $currentCost = (int)($info['options']['cost'] ?? 10);

        if ($currentCost < 12) {
            $newHash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            User::update($user['id'], ['password_hash' => $newHash]);
        }
    }

    /* =============================================================
     |  REGISTER
     ============================================================= */

    public function showRegister(): void
    {
        if (current_user()) {
            $this->redirect($this->homeFor(current_user()['role']));
        }
        $this->view('auth/register', ['pageTitle' => 'Create Account']);
    }

    public function register(): void
    {
        csrf_verify();

        $email = trim((string)$this->input('email'));
        $pass  = (string)$this->input('password');
        $conf  = (string)$this->input('confirm_password');
        $name  = trim((string)$this->input('full_name'));

        if ($name === '') {
            flash('error', 'Please enter your full name.');
            $this->redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.');
            $this->redirect('/register');
        }
        if (strlen($pass) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            $this->redirect('/register');
        }
        if ($pass !== $conf) {
            flash('error', 'Passwords do not match.');
            $this->redirect('/register');
        }
        if (User::findByEmail($email)) {
            flash('error', 'Email already exists.');
            $this->redirect('/register');
        }

        $otp = (string)random_int(100000, 999999);

        $user = User::create([
            'email'          => $email,
            'password_hash'  => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
            'full_name'      => $name,
            'role'           => 'user',
            'otp_code'       => $otp,
            'otp_expires_at' => date('Y-m-d H:i:s', time() + 600),
        ]);

        error_log("[PowerMagz OTP] {$email}: {$otp}");
        $_SESSION['pending_user'] = $user['id'];

        if (function_exists('audit_log')) {
            audit_log(
                'auth.register',
                'user',
                $user['id'],
                null,
                ['email' => $email],
                'New user registered'
            );
        }

        $this->redirect('/register/verify');
    }

    /* =============================================================
     |  OTP VERIFICATION
     ============================================================= */

    public function showVerify(): void
    {
        if (empty($_SESSION['pending_user'])) {
            $this->redirect('/register');
        }
        $this->view('auth/verify_otp', ['pageTitle' => 'Verify Email']);
    }

    public function verifyOtp(): void
    {
        csrf_verify();

        $id = $_SESSION['pending_user'] ?? null;
        if (!$id) {
            $this->redirect('/register');
        }

        $user = User::find($id);
        if (!$user) {
            unset($_SESSION['pending_user']);
            $this->redirect('/register');
        }

        $code = trim((string)$this->input('otp'));

        if (
            $user['otp_code'] !== $code
            || empty($user['otp_expires_at'])
            || strtotime($user['otp_expires_at']) < time()
        ) {
            flash('error', 'Invalid or expired code. Please try again.');
            $this->redirect('/register/verify');
        }

        User::update($user['id'], [
            'email_verified' => 1,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        /* Create the member profile if it doesn't exist yet */
        $profile = MemberProfile::first(['user_id' => $user['id']]);
        if (!$profile) {
            MemberProfile::create([
                'user_id'       => $user['id'],
                'sessions_left' => 12,
            ]);
        }

        /* Link to a partner if the user registered via a referral code */
        $ref = $_GET['ref'] ?? null;
        if ($ref) {
            $partner = PartnerProfile::first(['referral_code' => $ref]);
            if ($partner) {
                B2BClient::create([
                    'partner_user_id'   => $partner['user_id'],
                    'client_user_id'    => $user['id'],
                    'client_name'       => $user['full_name'] ?: $user['email'],
                    'client_email'      => $user['email'],
                    'membership_status' => 'linked',
                    'linked_at'         => date('Y-m-d H:i:s'),
                ]);
            }
        }

        login_user(User::find($user['id']));
        unset($_SESSION['pending_user']);

        if (function_exists('audit_log')) {
            audit_log(
                'auth.email_verified',
                'user',
                $user['id'],
                null,
                null,
                'Email verified'
            );
        }

        $this->redirect($this->homeFor('user'));
    }

    public function resendOtp(): void
    {
        csrf_verify();

        $id = $_SESSION['pending_user'] ?? null;
        if (!$id) {
            $this->json(['error' => 'No pending registration.'], 400);
        }

        $user = User::find($id);
        if (!$user) {
            $this->json(['error' => 'User not found.'], 404);
        }

        $otp = (string)random_int(100000, 999999);
        User::update($user['id'], [
            'otp_code'       => $otp,
            'otp_expires_at' => date('Y-m-d H:i:s', time() + 600),
        ]);

        error_log("[PowerMagz OTP resent] {$user['email']}: {$otp}");

        /* Dev hint only — dropped in production */
        $payload = ['status' => 'success'];
        if (APP_ENV === 'local') {
            $payload['otp'] = $otp;
        }
        $this->json($payload);
    }

    /* =============================================================
     |  FORGOT / RESET PASSWORD
     ============================================================= */

    public function showForgot(): void
    {
        if (current_user()) {
            $this->redirect($this->homeFor(current_user()['role']));
        }
        // 🔐 FIXED: Hyphen instead of underscore to match the file name
        $this->view('auth/forgot-password', ['pageTitle' => 'Reset Password']);
    }

    public function sendReset(): void
    {
        csrf_verify();

        $email = trim((string)$this->input('email'));
        $user  = User::findByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));

            PasswordReset::create([
                'user_id'    => $user['id'],
                'token'      => $token,
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'used'       => 0,
            ]);

            $url = function_exists('url')
                ? url('/reset-password?token=' . $token)
                : '/reset-password?token=' . $token;

            error_log('[PowerMagz Reset] ' . $url);

            if (function_exists('audit_log')) {
                audit_log(
                    'auth.password_reset_requested',
                    'user',
                    $user['id'],
                    null,
                    null,
                    'Password reset link generated'
                );
            }
        }

        flash('success', 'If that account exists, a reset link has been sent.');
        $this->redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $token = (string)($_GET['token'] ?? '');
        if ($token === '') {
            $this->redirect('/forgot-password');
        }
        // 🔐 FIXED: Hyphen instead of underscore to match the file name
        $this->view('auth/reset-password', [
            'pageTitle' => 'New Password',
            'token'     => $token,
        ]);
    }

    public function resetPassword(): void
    {
        csrf_verify();

        $token = (string)$this->input('token');
        $pass  = (string)$this->input('password');
        $conf  = (string)$this->input('confirm_password');

        if (strlen($pass) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            $this->redirect('/reset-password?token=' . urlencode($token));
        }
        if ($pass !== $conf) {
            flash('error', 'Passwords do not match.');
            $this->redirect('/reset-password?token=' . urlencode($token));
        }

        $reset = PasswordReset::first(['token' => $token, 'used' => 0]);
        if (!$reset || strtotime($reset['expires_at']) < time()) {
            flash('error', 'Reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
        }

        User::update($reset['user_id'], [
            'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        PasswordReset::update($reset['id'], ['used' => 1]);

        if (function_exists('audit_log')) {
            audit_log(
                'auth.password_reset',
                'user',
                $reset['user_id'],
                null,
                null,
                'Password reset completed'
            );
        }

        flash('success', 'Password updated — please log in.');
        $this->redirect('/login');
    }

    /* =============================================================
     |  LOGOUT
     ============================================================= */

    public function logout(): void
    {
        $user = current_user();

        if ($user && function_exists('audit_log')) {
            audit_log('auth.logout', 'user', $user['id'], null, null, 'User signed out');
        }

        logout_user();
        $this->redirect('/');
    }

    /* =============================================================
     |  FORCED RE-AUTH  (spec §2.1)
     ============================================================= */

    public function showReauth(): void
    {
        $user = current_user();
        if (!$user) {
            $this->redirect('/login');
        }

        $next = $this->sanitizeNextUrl((string)($this->input('next') ?? ''));

        $this->view('auth/reauth', [
            'pageTitle' => 'Confirm Password',
            'next'      => $next,
        ]);
    }

    public function reauth(): void
    {
        csrf_verify();

        $user = current_user();
        if (!$user) {
            $this->redirect('/login');
        }

        $pass = (string)$this->input('password');
        $next = $this->sanitizeNextUrl((string)$this->input('next', '/'));

        if (!password_verify($pass, $user['password_hash'])) {
            if (function_exists('audit_log')) {
                audit_log(
                    'auth.reauth.failed',
                    'user',
                    $user['id'],
                    null,
                    null,
                    'Wrong password on re-auth'
                );
            }
            flash('error', 'Incorrect password.');
            $this->redirect('/reauth?next=' . urlencode($next));
        }

        if (function_exists('session_grant_reauth')) {
            session_grant_reauth(5);
        }

        if (function_exists('audit_log')) {
            audit_log(
                'auth.reauth',
                'user',
                $user['id'],
                null,
                null,
                'Re-authenticated for sensitive action'
            );
        }

        flash('success', 'Password confirmed. You have 5 minutes to complete this action.');
        $this->redirect($next);
    }

    /**
     * Only allow relative URLs starting with a single "/".
     * Blocks "//evil.com" and "https://evil.com" open redirects.
     */
    private function sanitizeNextUrl(string $url): string
    {
        if ($url === '') return '/';
        if (!str_starts_with($url, '/')) return '/';
        if (str_starts_with($url, '//')) return '/';
        return $url;
    }

    /* =============================================================
     |  SESSION PING  (keepalive + idle reset)
     ============================================================= */

    public function ping(): void
    {
        $user = current_user();
        if (!$user) {
            $this->json(['status' => 'expired'], 401);
            return;
        }

        if (function_exists('session_security_touch')) {
            session_security_touch();
        }

        $this->json([
            'status'  => 'ok',
            'message' => 'Session refreshed.',
            'expires' => date('c', time() + ((int)($user['idle_timeout_minutes'] ?? 30) * 60)),
        ]);
    }

    /* =============================================================
     |  HELPERS
     ============================================================= */

    private function homeFor(string $role): string
    {
        return match ($role) {
            'admin', 'staff' => '/admin-dashboard',
            'partner'        => '/partner-dashboard',
            default          => '/home',
        };
    }
}
