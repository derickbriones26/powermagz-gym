<?php

/**
 * Partner — Payout Request Modal (2026 UI)
 * Triggered from wallet.php via openPayoutModal()
 *
 * Expects (optional, set by parent view):
 *   $available — float, partner's currently available balance
 */
$available = $available ?? 0;
?>

<div id="payoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
        style="animation: payout-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

        <!-- Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#0a0a0a] to-[#00A6A6]/20 px-6 py-6 border-b border-white/5">
            <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[#00A6A6]/20 blur-3xl"></div>

            <div class="relative flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/5 text-[#00A6A6]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">
                            Partner Wallet
                        </div>
                        <h3 class="mt-1 font-heading text-2xl tracking-wide text-white">
                            REQUEST PAYOUT
                        </h3>
                    </div>
                </div>

                <button type="button" onclick="closePayoutModal()"
                    class="rounded-full bg-white/5 p-2 text-gray-400 transition-colors hover:bg-white/10 hover:text-white"
                    aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Available balance display -->
            <div class="mt-5 rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 px-4 py-3 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                        <svg class="w-3.5 h-3.5 text-[#00A6A6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Available Balance
                    </div>
                    <div class="font-heading text-xl text-[#00A6A6]" id="pm-available">
                        <?= peso($available) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <form method="post" action="<?= url_e('/partner-dashboard/payout') ?>" id="payoutForm" class="p-6 sm:p-8">
            <?= csrf_field() ?>

            <!-- Amount -->
            <label class="group block">
                <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Amount to Withdraw (₱) <span class="text-[#D50000]">*</span>
                </span>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-500" aria-hidden="true">₱</span>
                    <input type="number"
                        name="amount"
                        id="pm-amount"
                        min="1"
                        step="0.01"
                        max="<?= (float)$available ?>"
                        required
                        placeholder="5000"
                        inputmode="decimal"
                        class="h-14 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-9 pr-20 text-base font-bold text-white placeholder-gray-600 outline-none transition-all
                        focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20
                        group-hover:border-[#00A6A6]/50">
                    <button type="button" onclick="fillMaxPayout()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-[#00A6A6]/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-[#00A6A6] transition-all
                         hover:bg-[#00A6A6] hover:text-white hover:shadow-[0_0_10px_rgba(0,166,166,0.4)]">
                        MAX
                    </button>
                </div>
                <span class="mt-2 block text-[11px] text-gray-500">
                    Enter up to <?= peso($available) ?> — this is what you currently have available.
                </span>
            </label>

            <!-- Quick amount chips -->
            <?php if ($available >= 100): ?>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 self-center">Quick:</span>
                    <?php
                    $quickAmounts = [];
                    foreach ([100, 500, 1000, 5000] as $amt) {
                        if ($amt <= $available) $quickAmounts[] = $amt;
                    }
                    foreach ($quickAmounts as $amt):
                    ?>
                        <button type="button" onclick="setPayoutAmount(<?= $amt ?>)"
                            class="tap-effect rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-gray-300 transition-all
                           hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-white">
                            ₱<?= number_format($amt) ?>
                        </button>
                    <?php endforeach; ?>
                    <?php if (!in_array((int)$available, $quickAmounts, true) && $available > 0): ?>
                        <button type="button" onclick="fillMaxPayout()"
                            class="tap-effect rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1.5 text-xs font-semibold text-[#00A6A6] transition-all
                           hover:-translate-y-0.5 hover:bg-[#00A6A6] hover:text-white">
                            All (₱<?= number_format((float)$available) ?>)
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Note (optional) -->
            <label class="group mt-6 block">
                <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Note <span class="text-gray-600">(optional)</span>
                </span>
                <textarea name="note"
                    id="pm-note"
                    rows="3"
                    maxlength="255"
                    placeholder="e.g. GCash 09171234567 — Juan Dela Cruz"
                    class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all
                         focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20
                         group-hover:border-[#00A6A6]/50"></textarea>
                <span class="mt-2 block text-[11px] text-gray-500">
                    Add your GCash number or preferred payout method so the gym can process it faster.
                </span>
            </label>

            <!-- Info strip -->
            <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs leading-relaxed text-amber-200/80">
                    Your request will be <strong class="text-amber-400">pending</strong> until the gym admin reviews and approves it.
                    The amount is reserved from your available balance immediately.
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row">
                <button type="button" onclick="closePayoutModal()"
                    class="rounded-full border border-white/10 bg-transparent px-6 py-3.5 text-sm font-bold text-gray-300 transition-all
                       hover:bg-white/5 hover:text-white sm:flex-1">
                    Cancel
                </button>

                <button type="submit" id="payoutSubmitBtn"
                    class="group/btn relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-6 py-3.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                       hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]
                       disabled:cursor-not-allowed disabled:opacity-60 sm:flex-[2]">
                    <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
                    <span class="relative" id="payoutBtnIcon">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                    <span class="relative" id="payoutBtnLabel">Submit Request</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes payout-pop {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    #payoutModal.closing>div {
        animation: payout-out 0.2s ease-in forwards;
    }

    @keyframes payout-out {
        from {
            opacity: 1;
            transform: scale(1);
        }

        to {
            opacity: 0;
            transform: scale(0.96);
        }
    }

    #pm-amount {
        -moz-appearance: textfield;
    }

    #pm-amount::-webkit-outer-spin-button,
    #pm-amount::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    @media (prefers-reduced-motion: reduce) {
        #payoutModal>div {
            animation: none !important;
        }
    }
</style>

<script>
    /* ============ Available balance (from server) ============ */
    const PAYOUT_AVAILABLE = <?= json_encode((float)$available) ?>;

    /* ============ Open / Close ============ */
    function openPayoutModal() {
        const m = document.getElementById('payoutModal');
        m.classList.remove('closing');
        m.classList.replace('hidden', 'flex');

        const amt = document.getElementById('pm-amount');
        const note = document.getElementById('pm-note');
        if (amt) amt.value = '';
        if (note) note.value = '';

        if (amt) setTimeout(() => amt.focus(), 120);
    }

    function closePayoutModal() {
        const m = document.getElementById('payoutModal');
        m.classList.add('closing');
        setTimeout(() => {
            m.classList.replace('flex', 'hidden');
            m.classList.remove('closing');
        }, 200);
    }

    /* ============ Quick-set amount ============ */
    function setPayoutAmount(value) {
        const amt = document.getElementById('pm-amount');
        if (amt) {
            amt.value = value;
            amt.focus();
        }
    }

    function fillMaxPayout() {
        if (PAYOUT_AVAILABLE > 0) {
            // Floor to 2 decimals to avoid exceeding balance
            const floored = Math.floor(PAYOUT_AVAILABLE * 100) / 100;
            setPayoutAmount(floored);
        }
    }

    /* ============ Prevent double-submit + validate ============ */
    (function() {
        const form = document.getElementById('payoutForm');
        if (!form) return;
        const btn = document.getElementById('payoutSubmitBtn');
        const icon = document.getElementById('payoutBtnIcon');
        const label = document.getElementById('payoutBtnLabel');

        form.addEventListener('submit', function(e) {
            const amt = parseFloat(document.getElementById('pm-amount').value || '0');
            if (amt > PAYOUT_AVAILABLE) {
                e.preventDefault();
                alert('Amount exceeds available balance (' + '₱' + PAYOUT_AVAILABLE.toLocaleString() + ').');
                return;
            }
            if (amt <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount.');
                return;
            }

            if (btn.disabled) return;
            btn.disabled = true;

            // Swap cash icon for a spinner
            icon.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            label.textContent = 'Submitting…';
        });
    })();

    /* ============ Esc + backdrop close ============ */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const m = document.getElementById('payoutModal');
            if (m && m.classList.contains('flex')) closePayoutModal();
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'payoutModal') closePayoutModal();
    });

    /* Alias for backward compat */
    window.openPayout = openPayoutModal;
</script>