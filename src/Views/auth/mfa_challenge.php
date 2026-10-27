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
            TWO-FACTOR VERIFICATION
        </h2>
        <p class="mt-2 text-sm text-gray-400">
            Enter the 6-digit code from your authenticator app to continue.
        </p>
        <p class="mt-3 inline-block rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-gray-300">
            Signed in as <strong class="text-white"><?= e($email ?? '') ?></strong>
        </p>
    </div>

    <!-- Error / success flashes -->
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

    <!-- Tab strip -->
    <div class="flex rounded-full border border-white/10 bg-[#0a0a0a] p-1">
        <button type="button"
            id="tab-authenticator"
            onclick="showPanel('authenticator')"
            aria-selected="true"
            class="tab-btn flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 text-xs font-bold uppercase tracking-wider transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Authenticator
        </button>
        <button type="button"
            id="tab-recovery"
            onclick="showPanel('recovery')"
            aria-selected="false"
            class="tab-btn flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 text-xs font-bold uppercase tracking-wider transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            Recovery Code
        </button>
    </div>

    <!-- ============================================================
         PANEL 1: TOTP code
         ============================================================ -->
    <form method="post"
        action="<?= url_e('/mfa/challenge') ?>"
        id="panel-authenticator"
        data-validate
        class="space-y-4">

        <?= csrf_field() ?>

        <label class="block">
            <span class="mb-2 flex items-center justify-center gap-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                Verification code
            </span>
            <input type="text"
                name="code"
                id="mfa-code"
                required
                inputmode="numeric"
                pattern="\d{6}"
                maxlength="6"
                autocomplete="one-time-code"
                autofocus
                data-validate="required"
                data-error-label="Verification code"
                placeholder="000000"
                class="mx-auto block h-16 w-56 rounded-xl border-2 border-white/10 bg-[#0a0a0a] text-center font-heading text-4xl tracking-[0.6em] text-white outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-4 focus:ring-[#00A6A6]/15">
        </label>

        <!-- Live countdown to next window -->
        <div class="text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-semibold text-gray-400">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                <span>New code in <strong id="mfa-countdown" class="font-mono text-[#00A6A6]">--</strong>s</span>
            </div>
        </div>

        <button type="submit"
            class="submit-btn h-12 w-full flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all
                hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Verify & Sign In
        </button>
    </form>

    <!-- ============================================================
         PANEL 2: Recovery code
         ============================================================ -->
    <form method="post"
        action="<?= url_e('/mfa/recovery') ?>"
        id="panel-recovery"
        data-validate
        class="hidden space-y-4">

        <?= csrf_field() ?>

        <label class="block">
            <span class="mb-2 flex items-center justify-center gap-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                Recovery code
            </span>
            <input type="text"
                name="recovery_code"
                id="recovery-code"
                required
                maxlength="14"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                data-validate="required"
                data-error-label="Recovery code"
                placeholder="XXXX-XXXX-XX"
                class="mx-auto block h-14 w-72 rounded-xl border-2 border-white/10 bg-[#0a0a0a] text-center font-mono text-lg tracking-widest text-white outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-4 focus:ring-[#00A6A6]/15">
        </label>

        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3.5">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                <div class="text-xs leading-relaxed text-amber-200/80">
                    Each recovery code can only be used <strong class="text-amber-400">once</strong>.
                    If you don't have any left, contact another admin to reset your MFA.
                </div>
            </div>
        </div>

        <button type="submit"
            class="submit-btn h-12 w-full flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all
                hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
            </svg>
            Use Recovery Code
        </button>
    </form>

    <!-- Footer -->
    <div class="border-t border-white/10 pt-5 text-center">
        <p class="text-xs text-gray-500">
            Wrong account?
            <a href="<?= url_e('/login') ?>" class="font-semibold text-[#00A6A6] hover:text-white transition-colors">
                Sign in again
            </a>
        </p>
    </div>

</div>

<style>
    /* Tab states */
    .tab-btn {
        color: #9ca3af;
        /* text-gray-400 */
    }

    .tab-btn[aria-selected="true"] {
        background-color: #111111;
        color: #00A6A6;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            transition-duration: 0.01ms !important;
            animation: none !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        /* ============================================================
         *  Panel switcher
         *  ============================================================ */
        window.showPanel = function(which) {
            const authPanel = document.getElementById('panel-authenticator');
            const recPanel = document.getElementById('panel-recovery');
            const authTab = document.getElementById('tab-authenticator');
            const recTab = document.getElementById('tab-recovery');

            if (which === 'recovery') {
                authPanel.classList.add('hidden');
                recPanel.classList.remove('hidden');
                authTab.setAttribute('aria-selected', 'false');
                recTab.setAttribute('aria-selected', 'true');
                setTimeout(() => document.getElementById('recovery-code')?.focus(), 50);
            } else {
                recPanel.classList.add('hidden');
                authPanel.classList.remove('hidden');
                recTab.setAttribute('aria-selected', 'false');
                authTab.setAttribute('aria-selected', 'true');
                setTimeout(() => document.getElementById('mfa-code')?.focus(), 50);
            }
        };

        /* ============================================================
         *  Digit stripping on the TOTP field
         *  ============================================================ */
        const codeInput = document.getElementById('mfa-code');
        codeInput?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
        });

        /* ============================================================
         *  Uppercase + auto-format recovery codes as user types
         *  ============================================================ */
        const recInput = document.getElementById('recovery-code');
        recInput?.addEventListener('input', (e) => {
            let v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            v = v.slice(0, 10);

            /* Insert dashes every 4 then after 8 */
            let formatted = v;
            if (v.length > 4) formatted = v.slice(0, 4) + '-' + v.slice(4);
            if (v.length > 8) formatted = v.slice(0, 4) + '-' + v.slice(4, 8) + '-' + v.slice(8);

            e.target.value = formatted;
        });

        /* ============================================================
         *  Live countdown to next 30s window
         *  ============================================================ */
        function updateCountdown() {
            const el = document.getElementById('mfa-countdown');
            if (!el) return;
            const now = Math.floor(Date.now() / 1000);
            const remaining = 30 - (now % 30);
            el.textContent = String(remaining).padStart(2, '0');

            /* Color pulse as it gets short */
            if (remaining <= 5) {
                el.classList.add('text-amber-500');
                el.classList.remove('text-[#00A6A6]');
            } else {
                el.classList.remove('text-amber-500');
                el.classList.add('text-[#00A6A6]');
            }
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);

        /* ============================================================
         *  Auto-submit when 6 digits are entered
         *  ============================================================ */
        codeInput?.addEventListener('input', (e) => {
            const v = e.target.value;
            if (v.length === 6) {
                if (e.target.dataset.submitting === '1') return;
                e.target.dataset.submitting = '1';
                setTimeout(() => {
                    document.getElementById('panel-authenticator')?.requestSubmit();
                }, 120);
            }
        });

        /* ============================================================
         *  Auto-submit recovery code when full pattern reached
         *  ============================================================ */
        recInput?.addEventListener('input', (e) => {
            const v = e.target.value.replace(/-/g, '');
            if (v.length === 10) {
                if (e.target.dataset.submitting === '1') return;
                e.target.dataset.submitting = '1';
                setTimeout(() => {
                    document.getElementById('panel-recovery')?.requestSubmit();
                }, 120);
            }
        });

        /* ============================================================
         *  Focus handling on load
         *  ============================================================ */
        window.addEventListener('load', () => {
            codeInput?.focus();
        });
    })();
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Verify Your Identity';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
