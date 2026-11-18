<?php ob_start(); ?>

<div class="space-y-5">

    <!-- Header -->
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] shadow-[0_0_20px_rgba(0,166,166,0.15)]">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h2 class="mt-3 font-heading text-3xl tracking-wide text-white">
            CONFIRM YOUR PASSWORD
        </h2>
        <p class="mt-2 text-sm text-gray-400">
            For your security, please re-enter your password to continue.
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

    <!-- Info box -->
    <div class="rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-3.5">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-[#00A6A6] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <div class="text-xs leading-relaxed text-gray-300">
                You're about to perform a <strong class="text-white">sensitive action</strong>.
                After confirming, you'll have <strong class="text-[#00A6A6]">5 minutes</strong> to complete it
                without being asked again.
            </div>
        </div>
    </div>

    <!-- Re-auth form -->
    <form method="post" action="<?= url_e('/reauth') ?>" data-validate class="space-y-4">

        <?= csrf_field() ?>

        <!-- Hidden next URL -->
        <input type="hidden" name="next" value="<?= e($next ?? '/') ?>">

        <!-- Email (read-only display) -->
        <div class="rounded-xl border border-white/5 bg-[#0a0a0a] px-4 py-3">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                Account
            </div>
            <div class="mt-0.5 truncate text-sm font-semibold text-white">
                <?= e(current_user()['email'] ?? '') ?>
            </div>
        </div>

        <!-- Password field -->
        <label class="block group">
            <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Password <span class="text-[#D50000]">*</span>
            </span>

            <div class="pw-wrap relative">
                <input id="reauth-password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    maxlength="200"
                    data-validate="required|min:8"
                    data-error-label="Password"
                    placeholder="••••••••"
                    class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 pr-12 text-sm text-white placeholder-gray-600 outline-none transition-all
                        focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20 group-hover:border-[#00A6A6]/50">

                <button type="button"
                    data-pw-toggle
                    tabindex="-1"
                    aria-label="Show password"
                    class="pw-toggle absolute right-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition-colors
                        hover:bg-[#00A6A6]/10 hover:text-[#00A6A6]">
                    <span class="eye-icon relative inline-block h-5 w-5">
                        <svg class="eye-open absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg class="eye-closed absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L9.88 9.88" />
                        </svg>
                    </span>
                </button>
            </div>
        </label>

        <!-- Submit -->
        <button type="submit"
            id="reauth-submit"
            class="submit-btn h-12 w-full flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all
                hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)] disabled:cursor-not-allowed disabled:opacity-60">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
            </svg>
            Confirm & Continue
        </button>
    </form>

    <!-- Cancel link -->
    <div class="border-t border-white/10 pt-5 text-center">
        <a href="<?= url_e('/admin-dashboard') ?>"
            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-2.5 text-xs font-bold text-gray-300 transition-all
                hover:-translate-y-0.5 hover:border-[#D50000] hover:text-[#D50000] hover:shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Cancel
        </a>
    </div>

    <!-- Forgot password link -->
    <p class="text-center text-xs text-gray-500">
        Forgot your password?
        <a href="<?= url_e('/forgot-password') ?>" class="font-semibold text-[#00A6A6] hover:text-white transition-colors">
            Reset it here
        </a>
    </p>

</div>

<style>
    /* ============================================================
       Password eye toggle — same pattern as login / register
       ============================================================ */
    .pw-toggle .eye-icon svg {
        transition: opacity 0.25s ease, transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform-origin: center;
    }

    .pw-toggle .eye-open {
        opacity: 1;
        transform: rotate(0deg) scale(1);
    }

    .pw-toggle .eye-closed {
        opacity: 0;
        transform: rotate(-90deg) scale(0.6);
    }

    .pw-toggle.is-visible .eye-open {
        opacity: 0;
        transform: rotate(90deg) scale(0.6);
    }

    .pw-toggle.is-visible .eye-closed {
        opacity: 1;
        transform: rotate(0deg) scale(1);
    }

    @media (prefers-reduced-motion: reduce) {

        .pw-toggle .eye-icon svg,
        * {
            transition: none !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        /* ============================================================
         *  Prevent double-submit
         *  ============================================================ */
        document.querySelector('form[action*="/reauth"]')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('reauth-submit');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Confirming…';
            }
        });

        /* ============================================================
         *  Focus the password field on load
         *  (Safari skips autofocus when JS is heavy)
         *  ============================================================ */
        window.addEventListener('load', () => {
            document.getElementById('reauth-password')?.focus();
        });

        /* ============================================================
         *  Clear stale errors when the user starts typing again
         *  ============================================================ */
        const pwdInput = document.getElementById('reauth-password');
        pwdInput?.addEventListener('input', () => {
            const err = pwdInput.parentElement.querySelector('[data-field-error]');
            if (err) {
                err.textContent = '';
                err.classList.add('hidden');
            }
            pwdInput.classList.remove('border-[#D50000]', 'bg-red-500/5');
            pwdInput.classList.add('border-white/10');
        });
    })();
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Confirm Password';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
