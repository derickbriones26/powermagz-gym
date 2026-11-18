<?php ob_start(); ?>
<form method="post" action="<?= url_e('/register') ?>" class="auth-form" data-auth-form>
    <?= csrf_field() ?>

    <header class="auth-head">
        <h1 class="auth-title" id="authTitle">Create your account</h1>
        <p class="auth-sub">Join PowerMagz and start training with a membership that tracks itself.</p>
    </header>

    <!-- Full name -->
    <div class="auth-field">
        <label for="full_name" class="auth-label">Full name</label>
        <div class="auth-field__control">
            <svg class="auth-field__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <input id="full_name" type="text" name="full_name" required autofocus
                autocomplete="name"
                placeholder="Juan Dela Cruz"
                class="auth-input">
        </div>
    </div>

    <!-- Email -->
    <div class="auth-field">
        <label for="email" class="auth-label">Email address</label>
        <div class="auth-field__control">
            <svg class="auth-field__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <input id="email" type="email" name="email" required
                autocomplete="email"
                placeholder="you@example.com"
                class="auth-input">
        </div>
    </div>

    <!-- Password -->
    <div class="auth-field">
        <label for="password" class="auth-label">Password</label>
        <div class="auth-field__control">
            <svg class="auth-field__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <input id="password" type="password" name="password" required minlength="8"
                autocomplete="new-password"
                aria-describedby="passwordHint"
                placeholder="At least 8 characters"
                class="auth-input auth-input--password">

            <button type="button" data-pw-toggle class="auth-eye" aria-label="Show password" aria-pressed="false">
                <svg class="eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg class="eye-closed" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" hidden>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
            </button>
        </div>
        <p class="auth-hint" id="passwordHint">Use 8 or more characters.</p>
    </div>

    <!-- Confirm password -->
    <div class="auth-field">
        <label for="confirm_password" class="auth-label">Confirm password</label>
        <div class="auth-field__control">
            <svg class="auth-field__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <input id="confirm_password" type="password" name="confirm_password" required minlength="8"
                autocomplete="new-password"
                placeholder="Repeat your password"
                class="auth-input auth-input--password">

            <button type="button" data-pw-toggle class="auth-eye" aria-label="Show password" aria-pressed="false">
                <svg class="eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg class="eye-closed" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" hidden>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
            </button>
        </div>
    </div>

    <label class="auth-consent auth-consent--check">
        <input type="checkbox" name="agree_terms" value="1" required>
        <span>
            By creating an account you agree to our
            <a href="<?= url_e('/terms') ?>" target="_blank" rel="noopener noreferrer">Terms &amp; Conditions</a> and
            <a href="<?= url_e('/privacy') ?>" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.
        </span>
    </label>

    <button type="submit" class="auth-submit" data-submit data-loading="Creating account…">
        <span class="btn-label">Create account</span>
        <svg class="auth-submit__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6" />
        </svg>
    </button>

    <div class="auth-divider">or quick sign up with</div>

    <div class="auth-social" role="group" aria-label="Quick sign up">
        <button type="button" class="social-btn" data-social="google" title="Google sign-in — coming soon">
            <svg viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.65 32.657 29.223 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z" />
                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z" />
                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z" />
                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z" />
            </svg>
            Google
        </button>

        <button type="button" class="social-btn" data-social="facebook" title="Facebook sign-in — coming soon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="12" fill="#1877F2" />
                <path fill="#ffffff" d="M13.5 21v-7h2.4l.4-2.8h-2.8V9.4c0-.8.2-1.4 1.4-1.4h1.5V5.5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2H8v2.8h2.5v7h3z" />
            </svg>
            Facebook
        </button>
    </div>
    <p class="auth-social__note" id="authSocialNote" role="status" aria-live="polite" hidden></p>

    <p class="auth-switch">
        Already have an account?
        <a href="<?= url_e('/login') ?>">Log in</a>
    </p>
</form>
<?php $content = ob_get_clean();
$pageTitle = 'Create Account';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
