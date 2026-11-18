<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\MemberProfile;
use App\Models\PasswordReset;
use App\Models\Session;

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

        /* Throttle both the source IP and the targeted account. */
        $accountKey = strtolower($email);
        $ipOk       = rate_limit_attempt('login-ip', 30, 900);
        $accountOk  = rate_limit_attempt('login-account', 8, 900, $accountKey);

        if (!$ipOk || !$accountOk) {
            if (function_exists('audit_log')) {
                audit_log(
                    'auth.login.throttled',
                    'user',
                    null,
                    null,
                    ['email' => $email],
                    'Login attempt blocked by rate limit'
                );
            }
            flash('error', 'Too many sign-in attempts. Please wait a few minutes and try again.');
            $this->redirect('/login');
        }

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
        rate_limit_clear('login-account', $accountKey);

        $intended = $_SESSION['flash_intended'] ?? null;
        unset($_SESSION['flash_intended']);

        if (function_exists('audit_log')) {
            audit_log('auth.login', 'user', $user['id'], null, null, 'User signed in');
        }

        $redirect = $this->homeFor($user['role']);
        if ($intended) {
            $candidate = safe_redirect_path((string)$intended);
            if ($candidate !== '/') $redirect = $candidate;
        }

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

        if (!rate_limit_attempt('register-ip', 5, 3600)) {
            flash('error', 'Too many registration attempts. Please try again later.');
            $this->redirect('/register');
        }

        if (!$this->input('agree_terms')) {
            flash('error', 'Please agree to the Terms & Conditions and Privacy Policy to continue.');
            $this->redirect('/register');
        }

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

        if (!rate_limit_attempt('register-email', 3, 3600, strtolower($email))) {
            flash('error', 'Too many registration attempts. Please try again later.');
            $this->redirect('/register');
        }

        // No email verification step: accounts are usable immediately.
        $user = User::create([
            'email'          => $email,
            'password_hash'  => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
            'full_name'      => $name,
            'role'           => 'user',
            'email_verified' => 1,
        ]);

        MemberProfile::create([
            'user_id'       => $user['id'],
            'sessions_left' => 12,
        ]);

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

        login_user(User::find($user['id']));
        flash('success', 'Welcome to PowerMagz Gym!');
        $this->redirect($this->homeFor('user'));
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

        $ipOk = rate_limit_attempt('reset-ip', 5, 3600);
        $emailOk = $email === ''
            ? true
            : rate_limit_attempt('reset-email', 3, 3600, strtolower($email));

        if (!$ipOk || !$emailOk) {
            flash('success', 'If that account exists, a reset link has been sent.');
            $this->redirect('/forgot-password');
        }

        $user  = User::findByEmail($email);

        if ($user) {
            $token = PasswordReset::issue((string)$user['id']);

            $url = function_exists('url')
                ? url('/reset-password?token=' . urlencode($token))
                : '/reset-password?token=' . urlencode($token);

            if (function_exists('send_password_reset')) {
                send_password_reset((string)$user['email'], $url);
            }

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

        if (!rate_limit_attempt('reset-confirm', 10, 900)) {
            flash('error', 'Too many reset attempts. Please wait a few minutes.');
            $this->redirect('/forgot-password');
        }

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

        $reset = PasswordReset::findValidByRawToken($token);
        if (!$reset) {
            flash('error', 'Reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
        }

        User::update($reset['user_id'], [
            'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        PasswordReset::consume($reset);

        /* A reset is a recovery action: kill every existing session. */
        Session::deleteByUser((string)$reset['user_id']);

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
     |  SELF-SERVICE PASSWORD CHANGE
     ============================================================= */

    public function changePassword(): void
    {
        csrf_verify();

        $user = current_user();
        // Send the user back to the profile page of their own portal.
        $back = match ($user['role'] ?? 'user') {
            'admin', 'staff' => '/admin-dashboard?tab=settings',
            'partner'        => '/partner-dashboard/profile',
            default          => '/profile',
        };

        $current = (string)$this->input('current_password');
        $new     = (string)$this->input('new_password');
        $confirm = (string)$this->input('confirm_password');

        if (!rate_limit_attempt('change-password', 5, 900, (string)$user['id'])) {
            flash('error', 'Too many password change attempts. Please wait a few minutes.');
            $this->redirect($back);
        }

        if ($new !== $confirm) {
            flash('error', 'New passwords do not match.');
            $this->redirect($back);
        }

        $error = User::changePassword((string)$user['id'], $current, $new);
        if ($error !== null) {
            flash('error', $error);
            $this->redirect($back);
        }

        rate_limit_clear('change-password', (string)$user['id']);

        /* Revoke every other session and rotate the current one. */
        Session::deleteByUser(
            (string)$user['id'],
            (string)($_COOKIE['pmgz_token'] ?? '')
        );
        session_regenerate_id(true);
        unset($_SESSION['csrf']);

        if (function_exists('audit_log')) {
            audit_log('auth.password_changed', 'user', $user['id'], null, null, 'Password changed from profile');
        }

        flash('success', 'Password updated.');
        $this->redirect($back);
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

        if (!rate_limit_attempt('reauth', 5, 300, (string)$user['id'])) {
            flash('error', 'Too many password attempts. Please wait a few minutes.');
            $this->redirect('/reauth?next=' . urlencode($next));
        }

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
        rate_limit_clear('reauth', (string)$user['id']);

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
     * Only allow same-origin relative URLs. Delegates to the shared helper
     * so re-auth, login and MFA intended redirects behave identically.
     */
    private function sanitizeNextUrl(string $url): string
    {
        return safe_redirect_path($url);
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
