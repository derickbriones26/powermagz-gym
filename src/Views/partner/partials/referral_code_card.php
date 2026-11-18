<?php

/**
 * Partner — Referral Code Card (2026 UI)
 */

if (defined('PMGZ_REF_CARD_RENDERED')) {
    return;
}
define('PMGZ_REF_CARD_RENDERED', true);

$profile = (isset($profile) && is_array($profile)) ? $profile : [];

$code = 'PMZ-XXXXXX';
if (!empty($profile['referral_code']) && is_string($profile['referral_code'])) {
    $candidate = trim($profile['referral_code']);
    if ($candidate !== '' && strlen($candidate) <= 40 && preg_match('/^[A-Z0-9\-]+$/i', $candidate)) {
        $code = $candidate;
    }
}

$base = '';
if (!empty($_SERVER['SCRIPT_NAME'])) {
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if ($base === '/' || $base === '.') {
        $base = '';
    }
}

$link = $base . '/register?ref=' . urlencode($code);
$codeOut = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
$linkOut = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
?>

<div class="grid gap-4 sm:grid-cols-2">

    <!-- Code Card -->
    <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-gradient-to-br from-[#0a0a0a] via-[#111] to-[#00A6A6]/20 p-6 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-2xl">
        <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[#00A6A6]/20 blur-3xl"></div>

        <div class="relative">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/5 text-[#00A6A6]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </span>
                <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">Your Referral Code</div>
            </div>

            <div class="mt-4 break-all font-heading text-4xl tracking-widest text-[#00A6A6]"><?= $codeOut ?></div>

            <div class="mt-5 flex items-center justify-between gap-3">
                <div class="text-xs text-gray-400">Share this code with new members</div>
                <button type="button" data-copy="<?= $codeOut ?>" onclick="pmgzCopy(this)"
                    class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-2 text-xs font-bold text-white transition-all hover:bg-[#00A6A6] hover:border-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                    <svg class="pmgz-copy-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="pmgz-copy-label">Copy</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Link Card -->
    <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-xl">
        <div class="relative">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/5 text-[#00A6A6]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </span>
                <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">Your Referral Link</div>
            </div>

            <div class="mt-4 break-all rounded-xl border border-white/5 bg-[#0a0a0a] px-4 py-3 font-mono text-xs text-gray-300"><?= $linkOut ?></div>

            <div class="mt-5 flex items-center justify-between gap-3">
                <div class="text-xs text-gray-400">New signups via this link are linked to you</div>
                <button type="button" data-copy="<?= $linkOut ?>" onclick="pmgzCopy(this)"
                    class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-2 text-xs font-bold text-white transition-all hover:bg-[#00A6A6] hover:border-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                    <svg class="pmgz-copy-icon w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="pmgz-copy-label">Copy</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof window.pmgzCopy !== 'function') {
        window.pmgzCopy = function(btn) {
            var val = btn.getAttribute('data-copy') || '';
            if (!val) return;
            var iconEl = btn.querySelector('.pmgz-copy-icon');
            var labelEl = btn.querySelector('.pmgz-copy-label');
            var fn = function() {
                if (iconEl) {
                    iconEl.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
                    iconEl.classList.add('text-green-400');
                }
                if (labelEl) labelEl.textContent = 'Copied!';
                setTimeout(function() {
                    if (iconEl) {
                        iconEl.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>';
                        iconEl.classList.remove('text-green-400');
                    }
                    if (labelEl) labelEl.textContent = 'Copy';
                }, 1400);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(val).then(fn).catch(function() {
                    window.prompt('Copy this value:', val);
                });
            } else {
                window.prompt('Copy this value:', val);
            }
        };
    }
</script>