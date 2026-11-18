<?php ob_start(); ?>
<form method="post" action="<?= url_e('/reset-password') ?>" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-gray-700">New Password</label>
        <div class="pw-wrap">
            <input id="password" type="password" name="password" required minlength="8" autofocus
                placeholder="••••••••"
                class="h-12 w-full rounded-md border border-gray-300 px-4 text-sm outline-none">
            <button type="button" data-pw-toggle tabindex="-1" aria-label="Show password" class="pw-toggle">
                <span class="eye-icon">
                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L9.88 9.88" />
                    </svg>
                </span>
            </button>
        </div>
    </div>

    <div>
        <label for="confirm_password" class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
        <div class="pw-wrap">
            <input id="confirm_password" type="password" name="confirm_password" required minlength="8"
                placeholder="••••••••"
                class="h-12 w-full rounded-md border border-gray-300 px-4 text-sm outline-none">
            <button type="button" data-pw-toggle tabindex="-1" aria-label="Show password" class="pw-toggle">
                <span class="eye-icon">
                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L9.88 9.88" />
                    </svg>
                </span>
            </button>
        </div>
    </div>

    <button type="submit" class="submit-btn h-12 w-full rounded-md bg-[#00A6A6] font-medium text-white shadow-sm">
        Reset password
    </button>

    <p class="text-center text-sm text-gray-500">
        <a href="<?= url_e('/login') ?>" class="auth-link font-medium text-[#00A6A6]">← Back to log in</a>
    </p>
</form>
<?php $content = ob_get_clean();
$pageTitle = 'New Password';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
