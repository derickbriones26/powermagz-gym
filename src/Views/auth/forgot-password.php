<?php ob_start(); ?>

<form method="post" action="<?= url_e('/forgot-password') ?>" class="space-y-5" data-validate>
    <?= csrf_field() ?>

    <!-- Header -->
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] shadow-[0_0_20px_rgba(0,166,166,0.15)]">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
        <h2 class="mt-3 font-heading text-3xl tracking-wide text-white">
            RESET PASSWORD
        </h2>
        <p class="mt-2 text-sm text-gray-400">
            Enter your email address and we'll send you a link to reset your password.
        </p>
    </div>

    <!-- Flash Messages -->
    <?php if ($m = flash('error')): ?>
        <div role="alert" class="rounded-xl border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-400">
            <?= e($m) ?>
        </div>
    <?php endif; ?>
    <?php if ($m = flash('success')): ?>
        <div role="status" class="rounded-xl border border-green-500/20 bg-green-500/10 p-3 text-sm text-green-400">
            <?= e($m) ?>
        </div>
    <?php endif; ?>

    <!-- Email Input -->
    <label class="block group">
        <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
            </svg>
            Email address <span class="text-[#D50000]">*</span>
        </span>
        <div class="relative">
            <input id="email" type="email" name="email" required autofocus
                data-validate="required|email"
                data-error-label="Email address"
                placeholder="you@example.com"
                class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20 group-hover:border-[#00A6A6]/50">
        </div>
    </label>

    <!-- Submit Button -->
    <button type="submit"
        class="submit-btn mt-2 h-12 w-full flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all
            hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
        </svg>
        Send Reset Link
    </button>

    <!-- Back to Login -->
    <p class="text-center text-sm text-gray-400 mt-6">
        Remembered it?
        <a href="<?= url_e('/login') ?>" class="font-semibold text-[#00A6A6] hover:text-white transition-colors">
            Back to log in
        </a>
    </p>
</form>

<?php
$content = ob_get_clean();
$pageTitle = 'Reset Password';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
