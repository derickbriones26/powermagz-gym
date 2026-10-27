<?php ob_start(); ?>
<form method="post" action="<?= url_e('/login') ?>" class="space-y-6">
    <?= csrf_field() ?>

    <!-- Email Input -->
    <div>
        <label for="email" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-300">Email</label>
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                </svg>
            </div>
            <input id="email" type="email" name="email" required autofocus
                placeholder="you@example.com"
                class="modern-input h-12 w-full rounded-xl pl-11 pr-4 text-sm">
        </div>
    </div>

    <!-- Password Input -->
    <div>
        <div class="mb-2 flex items-center justify-between">
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-300">Password</label>
            <a href="<?= url_e('/forgot-password') ?>" class="text-xs font-semibold text-[#00A6A6] hover:text-white transition-colors">Forgot?</a>
        </div>
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <input id="password" type="password" name="password" required
                placeholder="••••••••"
                class="modern-input h-12 w-full rounded-xl pl-11 pr-12 text-sm">

            <!-- Eye Toggle -->
            <button type="button" data-pw-toggle tabindex="-1" class="pw-toggle">
                <svg class="eye-open h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg class="eye-closed hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="glow-btn mt-2 h-12 w-full rounded-xl bg-[#00A6A6] font-bold text-white tracking-wide text-sm hover:bg-[#008c8c]">
        Log in
    </button>

    <!-- Register Link -->
    <p class="text-center text-sm text-gray-400 mt-6">
        Don't have an account?
        <a href="<?= url_e('/register') ?>" class="font-semibold text-[#00A6A6] hover:text-white transition-colors">Create one</a>
    </p>
</form>
<?php $content = ob_get_clean();
$pageTitle = 'Log In';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
