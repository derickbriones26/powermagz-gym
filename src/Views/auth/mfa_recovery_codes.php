<?php ob_start(); ?>

<div class="space-y-5">

    <!-- Header -->
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 shadow-[0_0_20px_rgba(245,158,11,0.15)]">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
        </div>
        <h2 class="mt-3 font-heading text-3xl tracking-wide text-white">
            SAVE YOUR RECOVERY CODES
        </h2>
        <p class="mt-2 text-sm text-gray-400">
            These codes let you sign in if you lose access to your authenticator app.
        </p>
    </div>

    <!-- Big warning -->
    <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-xs leading-relaxed text-amber-200/80">
                <strong class="text-amber-400">This is the only time you'll see these codes.</strong>
                Save them somewhere safe <em>right now</em>. Each code can only be used once.
                If you close this page without saving them, you'll need to regenerate a new set.
            </div>
        </div>
    </div>

    <!-- Codes grid -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <?php foreach ($codes as $idx => $code): ?>
                <div class="flex items-center gap-3 rounded-xl border border-white/5 bg-[#0a0a0a] px-3 py-2.5 transition-colors hover:border-[#00A6A6]/40 hover:bg-[#00A6A6]/5">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-white/5 text-[10px] font-bold text-gray-400">
                        <?= (int)$idx + 1 ?>
                    </span>
                    <code class="select-all font-mono text-sm font-bold tracking-wider text-[#00A6A6]">
                        <?= e($code) ?>
                    </code>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="grid gap-3 sm:grid-cols-2">

        <button type="button"
            onclick="copyAllCodes()"
            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-gray-300 transition-all
                hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white hover:shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Copy All
        </button>

        <button type="button"
            onclick="downloadCodes()"
            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-gray-300 transition-all
                hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white hover:shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Download .txt
        </button>

        <button type="button"
            onclick="window.print()"
            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-gray-300 transition-all
                hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white hover:shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print
        </button>

        <a href="<?= url_e('/mfa/setup') ?>"
            class="inline-flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all
                hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            I've Saved Them — Continue
        </a>
    </div>

    <!-- How to use them -->
    <details class="rounded-2xl border border-white/5 bg-[#0a0a0a] p-4 group">
        <summary class="cursor-pointer text-xs font-bold uppercase tracking-wider text-gray-400 hover:text-[#00A6A6] transition-colors flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
            How do I use a recovery code?
        </summary>

        <div class="mt-3 space-y-2 text-xs leading-relaxed text-gray-400">
            <p>
                On the login screen, after entering your email and password, you'll see the
                two-factor challenge. Click the <strong class="text-white">Recovery Code</strong> tab and enter one of
                the codes above.
            </p>
            <p>
                Each code works <strong class="text-white">only once</strong>. Once used, it's removed automatically.
                When you have 2 or fewer codes left, we'll remind you to regenerate.
            </p>
            <p class="text-amber-500 font-semibold">
                Never share these codes. Anyone who has them can sign in as you.
            </p>
        </div>
    </details>

    <!-- Footer -->
    <p class="text-center text-xs text-gray-500">
        Lost your device?
        <a href="<?= url_e('/login') ?>" class="font-semibold text-[#00A6A6] hover:text-white transition-colors">
            Sign in with a recovery code
        </a>
    </p>

</div>

<style>
    @media print {
        body {
            background: #fff !important;
        }

        .print\:hidden {
            display: none !important;
        }

        button,
        a[href] {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }
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

        const CODES = <?= json_encode($codes, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        /* ============================================================
         *  Copy all codes as newline-separated text
         *  ============================================================ */
        window.copyAllCodes = function() {
            const text = CODES.join('\n');

            const done = () => {
                showToast('All codes copied to clipboard');
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(() => {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        };

        /* ============================================================
         *  Fallback copy for insecure contexts (HTTP dev)
         *  ============================================================ */
        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                showToast('All codes copied to clipboard');
            } catch (e) {
                window.prompt('Copy these codes manually:', text);
            }
            document.body.removeChild(ta);
        }

        /* ============================================================
         *  Download as .txt file
         *  ============================================================ */
        window.downloadCodes = function() {
            const now = new Date();
            const dateStr = now.toISOString().slice(0, 10);

            const header = [
                'PowerMagz Gym — Two-Factor Recovery Codes',
                'Account: <?= e($user['email'] ?? '') ?>',
                'Generated: ' + now.toLocaleString(),
                '',
                'Each code can be used ONCE. Store in a safe place.',
                '===============================================',
                '',
            ].join('\n');

            const body = CODES.map((c, i) => (i + 1) + '. ' + c).join('\n');
            const footer = '\n\n===============================================\n' +
                'If you lose access to your account, use one of these codes at:\n' +
                '<?= e(APP_URL ?? '') ?>/login\n';

            const content = header + body + footer;
            const blob = new Blob([content], {
                type: 'text/plain;charset=utf-8'
            });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'powermagz-recovery-codes-' + dateStr + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            showToast('Downloaded recovery codes');
        };

        /* ============================================================
         *  Toast notifications
         *  ============================================================ */
        function showToast(msg) {
            const existing = document.getElementById('mfa-toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'mfa-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.className = 'fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 flex items-center gap-2 rounded-full border border-green-500/20 bg-[#111] px-5 py-2.5 text-xs font-bold text-white shadow-2xl';
            toast.style.animation = 'toast-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';

            toast.innerHTML = `
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                ${msg}
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 350);
            }, 2400);
        }

        /* ============================================================
         *  Warn before leaving without clicking "I've saved them"
         *  ============================================================ */
        let saved = false;

        document.querySelector('a[href*="/mfa/setup"]')?.addEventListener('click', () => {
            saved = true;
        });

        window.addEventListener('beforeunload', (e) => {
            if (!saved) {
                e.preventDefault();
                e.returnValue = 'Have you saved your recovery codes? They won\'t be shown again.';
                return e.returnValue;
            }
        });

        /* ============================================================
         *  Auto-focus for keyboard users
         *  ============================================================ */
        window.addEventListener('load', () => {
            const btn = document.querySelector('a[href*="/mfa/setup"]');
            if (btn) btn.focus();
        });
    })();
</script>

<style>
    @keyframes toast-in {
        from {
            opacity: 0;
            transform: translate(-50%, 20px);
        }

        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'Save Your Recovery Codes';
require BASE_PATH . '/src/Views/layouts/auth_layout.php';
