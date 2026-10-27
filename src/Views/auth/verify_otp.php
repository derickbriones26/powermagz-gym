<?php ob_start(); ?>

<div class="space-y-5">

    <!-- Header -->
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] shadow-[0_0_20px_rgba(0,166,166,0.15)]">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
        <h2 class="mt-3 font-heading text-3xl tracking-wide text-white">
            VERIFY EMAIL
        </h2>
        <p class="mt-2 text-sm text-gray-400">
            We've sent a 6-digit verification code to your email.
        </p>
    </div>

    <!-- Dev mode notice -->
    <div class="rounded-xl border border-blue-500/20 bg-blue-500/10 p-3 text-xs leading-relaxed text-blue-300">
        <div class="flex items-start gap-2">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>
                <strong class="text-blue-200">Dev mode:</strong> check
                <code class="rounded bg-black/40 px-1.5 py-0.5 font-mono text-[11px] text-white">storage/logs/php-error.log</code>
                for your OTP code.
            </span>
        </div>
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

    <!-- Form -->
    <form method="post" action="<?= url_e('/register/verify') ?>" class="space-y-5" data-validate>
        <?= csrf_field() ?>

        <!-- OTP Input -->
        <label class="block group">
            <span class="mb-2 flex items-center justify-center gap-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                Verification Code <span class="text-[#D50000]">*</span>
            </span>
            <input id="otp"
                type="text"
                name="otp"
                required
                maxlength="6"
                inputmode="numeric"
                pattern="\d{6}"
                placeholder="000000"
                autofocus
                data-validate="required"
                data-error-label="Verification code"
                class="mx-auto block h-16 w-56 rounded-xl border-2 border-white/10 bg-[#0a0a0a] text-center font-heading text-4xl tracking-[0.6em] text-white outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-4 focus:ring-[#00A6A6]/15 group-hover:border-[#00A6A6]/50">
        </label>

        <!-- Submit Button -->
        <button type="submit"
            class="submit-btn h-12 w-full flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all
                hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Verify Account
        </button>
    </form>

    <!-- Resend -->
    <div class="text-center pt-2">
        <p class="text-sm text-gray-500">
            Didn't get the code?
            <button type="button"
                onclick="resendOtp(this)"
                class="font-semibold text-[#00A6A6] hover:text-white transition-colors ml-1">
                Resend
            </button>
        </p>
    </div>

</div>

<script>
    (function() {
        'use strict';

        /* ============================================================
         *  Auto-format: strip non-digits as the user types
         *  ============================================================ */
        const otpInput = document.getElementById('otp');
        otpInput?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
        });

        /* ============================================================
         *  Resend OTP logic
         *  ============================================================ */
        window.resendOtp = function(btn) {
            const originalText = btn.textContent;
            btn.textContent = 'Sending…';
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');

            fetch('<?= url_e('/register/resend') ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= csrf_token() ?>',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.otp) {
                        alert('Dev mode OTP: ' + d.otp);
                    } else {
                        // Success feedback
                        btn.textContent = 'Sent!';
                        btn.classList.replace('text-[#00A6A6]', 'text-green-500');

                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.classList.replace('text-green-500', 'text-[#00A6A6]');
                            btn.disabled = false;
                            btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }, 3000);
                    }
                })
                .catch(() => {
                    alert('Could not resend the code. Please try again.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                });
        };
    })();
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Verify Email';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
