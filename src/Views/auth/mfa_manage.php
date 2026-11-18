<?php ob_start(); ?>

<div class="space-y-5">

    <!-- Header -->
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-green-500/10 text-green-500 shadow-[0_0_20px_rgba(34,197,94,0.15)]">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <h2 class="mt-3 font-heading text-3xl tracking-wide text-white">
            TWO-FACTOR IS ON
        </h2>
        <p class="mt-2 text-sm text-gray-400">
            Your account is protected with an authenticator app.
        </p>
    </div>

    <!-- Flash messages -->
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
    <?php if ($m = flash('warning')): ?>
        <div role="alert" class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 text-sm text-amber-400">
            <?= e($m) ?>
        </div>
    <?php endif; ?>

    <!-- Status card -->
    <div class="rounded-2xl border border-green-500/20 bg-gradient-to-br from-green-500/5 to-[#111] p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-green-500">Enabled</span>
                    <span class="flex h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                </div>
                <div class="mt-1 text-xs text-gray-400">
                    Account: <strong class="text-white"><?= e($user['email'] ?? '') ?></strong>
                </div>
                <?php if (!empty($verifiedAt)): ?>
                    <div class="mt-0.5 text-xs text-gray-400">
                        Enabled on
                        <strong class="text-white"><?= e(date('M j, Y \a\t g:i A', strtotime($verifiedAt))) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recovery codes status -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-white">Recovery Codes</div>
                <div class="mt-1 text-xs text-gray-400">
                    <?php if ($recoveryCount > 0): ?>
                        You have <strong class="text-white"><?= (int)$recoveryCount ?></strong>
                        unused code<?= (int)$recoveryCount !== 1 ? 's' : '' ?>.
                        <?php if ($recoveryCount <= 2): ?>
                            <span class="font-bold text-[#D50000]">Regenerate soon!</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="font-semibold text-[#D50000]">
                            No recovery codes on file.
                        </span>
                        Regenerate them now.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form method="post" action="<?= url_e('/mfa/regenerate-codes') ?>"
            onsubmit="return confirm('Regenerate recovery codes? All existing codes will be invalidated immediately.')"
            class="mt-4">
            <?= csrf_field() ?>
            <button type="submit"
                class="w-full flex items-center justify-center gap-2 rounded-full border border-purple-500/20 bg-purple-500/10 px-5 py-2.5 text-xs font-bold text-purple-400 transition-all
                    hover:-translate-y-0.5 hover:bg-purple-500 hover:text-white hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Generate New Recovery Codes
            </button>
        </form>
    </div>

    <!-- Disable MFA -->
    <div class="rounded-2xl border border-red-500/20 bg-red-500/5 p-5">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-red-500">Disable Two-Factor</div>
                <div class="mt-1 text-xs text-gray-400">
                    Admin accounts are expected to keep MFA enabled.
                    Disabling reduces account security.
                </div>
            </div>
        </div>

        <form method="post" action="<?= url_e('/mfa/disable') ?>"
            onsubmit="return confirm('Disable two-factor authentication? You will only need your password to sign in.')"
            class="mt-4">
            <?= csrf_field() ?>
            <button type="submit"
                class="w-full flex items-center justify-center gap-2 rounded-full bg-red-500 px-5 py-2.5 text-xs font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all
                    hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-[0_0_25px_rgba(213,0,0,0.5)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                Disable MFA
            </button>
        </form>
    </div>

    <!-- Back to dashboard -->
    <div class="border-t border-white/10 pt-5 text-center">
        <a href="<?= url_e('/admin-dashboard') ?>"
            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-2.5 text-xs font-bold text-gray-300 transition-all
                hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white hover:shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>

</div>

<style>
    @media (prefers-reduced-motion: reduce) {
        * {
            transition-duration: 0.01ms !important;
        }
    }
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'Two-Factor Authentication';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
