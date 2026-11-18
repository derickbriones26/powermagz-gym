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
            SET UP TWO-FACTOR
        </h2>
        <p class="mt-2 text-sm text-gray-400">
            Scan the QR code with your authenticator app to protect your admin account.
        </p>
    </div>

    <!-- Step indicator -->
    <ol class="flex items-center gap-2 text-xs">
        <li class="flex flex-1 items-center gap-2">
            <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-[#00A6A6] font-bold text-white shadow-[0_0_10px_rgba(0,166,166,0.4)]">1</span>
            <span class="font-semibold text-[#00A6A6]">Scan</span>
        </li>
        <li class="h-px flex-1 bg-white/10"></li>
        <li class="flex flex-1 items-center gap-2">
            <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-white/5 font-bold text-gray-500">2</span>
            <span class="text-gray-500">Verify</span>
        </li>
        <li class="h-px flex-1 bg-white/10"></li>
        <li class="flex flex-1 items-center gap-2">
            <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-white/5 font-bold text-gray-500">3</span>
            <span class="text-gray-500">Save codes</span>
        </li>
    </ol>

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

    <!-- QR card -->
    <div class="rounded-2xl border border-[#00A6A6]/20 bg-gradient-to-br from-[#00A6A6]/5 to-[#111] p-5 text-center">

        <div class="mx-auto w-fit rounded-2xl border-4 border-[#00A6A6] bg-white p-3 shadow-[0_0_30px_rgba(0,166,166,0.2)]">
            <img src="<?= e($qrUrl) ?>"
                alt="QR code for two-factor authentication setup"
                width="220" height="220"
                class="block">
        </div>

        <p class="mt-4 text-xs text-gray-400">
            Open <strong class="text-white">Google Authenticator</strong>,
            <strong class="text-white">Authy</strong>,
            <strong class="text-white">Microsoft Authenticator</strong>,
            or any TOTP app and scan the code above.
        </p>

        <!-- Manual entry fallback -->
        <details class="mt-4 text-left group">
            <summary class="cursor-pointer text-xs font-bold uppercase tracking-wider text-[#00A6A6] hover:text-white transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                Can't scan? Enter manually
            </summary>

            <div class="mt-3 rounded-xl border border-white/5 bg-[#0a0a0a] p-3">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                    Manual entry secret
                </div>

                <div class="mt-2 flex items-center gap-2">
                    <code id="mfa-secret"
                        class="min-w-0 flex-1 break-all rounded-lg border border-white/10 bg-[#111] px-3 py-2 font-mono text-xs font-bold tracking-widest text-[#00A6A6]">
                        <?= e($formatted) ?>
                    </code>
                    <button type="button"
                        onclick="copySecret(this)"
                        aria-label="Copy secret to clipboard"
                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-[#00A6A6] text-white shadow-md transition-all
                            hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-gray-500">
                    <div><span class="font-semibold text-gray-300">Type:</span> Time-based (TOTP)</div>
                    <div><span class="font-semibold text-gray-300">Digits:</span> 6</div>
                    <div><span class="font-semibold text-gray-300">Period:</span> 30 seconds</div>
                    <div><span class="font-semibold text-gray-300">Algorithm:</span> SHA-1</div>
                </div>
            </div>
        </details>
    </div>

    <!-- Code entry form -->
    <form method="post" action="<?= url_e('/mfa/verify') ?>" class="space-y-4" data-validate>
        <?= csrf_field() ?>

        <label class="block">
            <span class="mb-2 flex items-center justify-center gap-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                Enter the 6-digit code from your app
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

        <button type="submit"
            class="submit-btn h-12 w-full flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all
                hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Verify & Enable
        </button>
    </form>

    <!-- Regenerate option -->
    <form method="post" action="<?= url_e('/mfa/setup') ?>"
        onsubmit="return confirm('Generate a new secret? The current QR code will stop working.')">
        <?= csrf_field() ?>
        <button type="submit"
            class="w-full flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-2.5 text-xs font-bold text-gray-300 transition-all
                hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white hover:shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Generate New Secret
        </button>
    </form>

    <!-- Skip / cancel -->
    <p class="text-center text-xs text-gray-500">
        Changed your mind?
        <a href="<?= url_e('/admin-dashboard') ?>" class="font-semibold text-[#00A6A6] hover:text-white transition-colors">
            Return to dashboard
        </a>
    </p>

    <!-- Info box -->
    <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3.5">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-xs leading-relaxed text-amber-200/80">
                <strong class="text-amber-400">Admins must enable MFA</strong> to satisfy our security policy.
                If you lose access to your authenticator, you'll need to use a
                recovery code (issued in the next step) or contact another admin.
            </div>
        </div>
    </div>

</div>

<style>
    /* Remove the boxy default look on number inputs */
    #mfa-code::-webkit-outer-spin-button,
    #mfa-code::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            transition-duration: 0.01ms !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        /* ============================================================
         *  Auto-focus the code field (Safari sometimes skips autofocus)
         *  ============================================================ */
        window.addEventListener('load', () => {
            const input = document.getElementById('mfa-code');
            if (input) input.focus();
        });

        /* ============================================================
         *  Copy the manual secret to the clipboard
         *  ============================================================ */
        window.copySecret = function(btn) {
            const secretEl = document.getElementById('mfa-secret');
            if (!secretEl) return;

            const secret = secretEl.textContent.replace(/\s+/g, '');
            const originalHTML = btn.innerHTML;

            const done = () => {
                btn.innerHTML = '<svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                btn.classList.add('bg-green-500');
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-green-500');
                }, 1400);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(secret).then(done).catch(() => {
                    window.prompt('Copy this secret manually:', secret);
                });
            } else {
                window.prompt('Copy this secret manually:', secret);
            }
        };

        /* ============================================================
         *  Auto-format: strip non-digits as the user types
         *  ============================================================ */
        const codeInput = document.getElementById('mfa-code');
        codeInput?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
        });
    })();
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Set Up Two-Factor Authentication';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
