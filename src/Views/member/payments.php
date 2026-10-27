<?php

/**
 * Member — Payment History (2026 Dark UI with Tap Effects)
 */

$payments = $payments ?? [];

// ---------- Sort newest first ----------
usort($payments, fn($a, $b) => strcmp($b['paid_date'] ?? '', $a['paid_date'] ?? ''));

// ---------- Stats ----------
$totalPaid = 0;
$totalPending = 0;
$paidCount = 0;
$pendingCount = 0;
$lastPaidDate = null;

foreach ($payments as $p) {
    $amt = (float)($p['amount'] ?? 0);
    if (($p['status'] ?? '') === 'paid') {
        $totalPaid += $amt;
        $paidCount++;
        if (!$lastPaidDate || ($p['paid_date'] ?? '') > $lastPaidDate) {
            $lastPaidDate = $p['paid_date'] ?? null;
        }
    } elseif (($p['status'] ?? '') === 'pending') {
        $totalPending += $amt;
        $pendingCount++;
    }
}

$user = current_user();
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== Page Header ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Billing</span>
                </div>

                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    PAYMENT HISTORY
                </h1>

                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    All your GCash and cash payments, in one place. Confirmed payments activate your enrollment automatically.
                </p>
            </div>

            <!-- Added tap-effect -->
            <a href="<?= url_e('/programs') ?>" class="tap-effect group inline-flex flex-shrink-0 items-center gap-2 self-start rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)] sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Enroll in a Program
            </a>
        </div>
    </div>

    <!-- ========== Summary Stats ========== -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Paid -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40 hover:shadow-[0_10px_30px_-10px_rgba(34,197,94,0.2)]">
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Paid</div>
                    <div class="mt-2 font-heading text-3xl text-green-500"><?= peso($totalPaid) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $paidCount ?> payment<?= $paidCount !== 1 ? 's' : '' ?>
                        <?php if ($lastPaidDate): ?>
                            &middot; last <?= e(short_date($lastPaidDate)) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="group relative overflow-hidden rounded-2xl border <?= $totalPending > 0 ? 'border-amber-500/30 bg-amber-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        <?php if ($totalPending > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <?php endif; ?>
                        Pending
                    </div>
                    <div class="mt-2 font-heading text-3xl <?= $totalPending > 0 ? 'text-amber-500' : 'text-gray-500' ?>">
                        <?= peso($totalPending) ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $pendingCount ?> awaiting<?= $pendingCount === 1 ? ' confirmation' : ' confirmations' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl <?= $totalPending > 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-white/5 text-gray-500' ?> transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40">
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Payments</div>
                    <div class="mt-2 font-heading text-3xl text-white"><?= count($payments) ?></div>
                    <div class="mt-1 text-xs text-gray-400">All time</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Account -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40">
            <div class="relative flex items-center justify-between">
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Account</div>
                    <div class="mt-2 truncate font-heading text-xl text-white"><?= e($user['full_name'] ?: $user['email']) ?></div>
                    <div class="mt-1 truncate text-xs text-gray-400"><?= e($user['email']) ?></div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 font-heading text-lg text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <?= e(strtoupper(substr($user['full_name'] ?: $user['email'], 0, 1))) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== Filter Bar ========== -->
    <?php if (!empty($payments)): ?>
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/5 bg-[#111] p-4 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Filter:</span>

            <?php
            $filters = [
                ['all',     'All',     count($payments), 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['paid',    'Paid',    $paidCount,       'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['pending', 'Pending', $pendingCount,    'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
            foreach ($filters as [$key, $label, $count, $icon]):
            ?>
                <!-- Added tap-effect -->
                <button type="button"
                    data-filter="<?= $key ?>"
                    onclick="setPaymentFilter('<?= $key ?>')"
                    class="tap-effect payment-filter flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-300 transition-all duration-300 hover:border-[#00A6A6]/50 hover:text-white
                       <?= $key === 'all' ? 'active' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                    <span class="rounded-full bg-black/30 px-2 py-0.5 text-[10px] font-bold text-gray-400"><?= $count ?></span>
                </button>
            <?php endforeach; ?>

            <div class="relative ml-auto w-full sm:w-64">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input id="paymentSearch" type="search" placeholder="Search by description…"
                    class="h-10 w-full rounded-full border border-white/10 bg-[#0a0a0a] pl-10 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </div>
        </div>
    <?php endif; ?>

    <!-- ========== Payments Table ========== -->
    <?php if (empty($payments)): ?>
        <div class="rounded-2xl border border-white/5 bg-[#111] p-16 text-center shadow-sm">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
            <h3 class="mt-6 font-heading text-2xl tracking-wide text-white">NO PAYMENTS YET</h3>
            <p class="mt-2 text-sm text-gray-400">
                You haven't enrolled in a program yet. Once you do, your payments will appear here.
            </p>
            <!-- Added tap-effect -->
            <a href="<?= url_e('/programs') ?>" class="tap-effect mt-8 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                Browse Programs
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Date</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Description</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Method</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="paymentRows">
                        <?php foreach ($payments as $idx => $p):
                            $isPending = ($p['status'] ?? '') === 'pending';
                            $method = $p['method'] ?? 'gcash';

                            // Method Icons
                            $methodIcon = match ($method) {
                                'gcash' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
                                'cash'  => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                                default => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>'
                            };

                            $desc = $p['description'] ?? ($p['tier'] ?? 'Membership');
                        ?>
                            <tr class="payment-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                data-status="<?= e($p['status'] ?? 'pending') ?>"
                                data-search="<?= e(strtolower($desc)) ?>">

                                <td class="p-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-white"><?= e(short_date($p['paid_date'] ?? null)) ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?= e(date('D', strtotime($p['paid_date'] ?? 'now'))) ?>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="text-sm font-semibold text-white"><?= e($desc) ?></div>
                                    <?php if (!empty($p['tier'])): ?>
                                        <div class="mt-1">
                                            <span class="rounded-full bg-white/5 border border-white/10 px-2 py-0.5 text-[10px] font-bold uppercase text-gray-400">
                                                <?= e($p['tier']) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/5 border border-white/10 px-3 py-1 text-[10px] font-bold uppercase text-gray-300">
                                        <?= $methodIcon ?>
                                        <?= e(strtoupper($method)) ?>
                                    </span>
                                </td>

                                <td class="p-4">
                                    <?php if ($isPending): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 px-3 py-1 text-[10px] font-bold uppercase text-amber-500">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pending
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 border border-green-500/20 px-3 py-1 text-[10px] font-bold uppercase text-green-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Paid
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-right whitespace-nowrap">
                                    <div class="font-heading text-lg <?= $isPending ? 'text-amber-500' : 'text-white' ?>">
                                        <?= peso($p['amount'] ?? 0) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="noPaymentsResults" class="hidden p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No payments match your search.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] px-6 py-4 shadow-sm">
            <div class="text-xs text-gray-500">
                Showing <strong class="text-white" id="visibleCount"><?= count($payments) ?></strong>
                of <strong class="text-white"><?= count($payments) ?></strong> payments
            </div>
            <div class="flex flex-wrap items-center gap-6 text-xs">
                <div>
                    <span class="text-gray-500">Total paid:</span>
                    <strong class="ml-1 text-green-500"><?= peso($totalPaid) ?></strong>
                </div>
                <?php if ($totalPending > 0): ?>
                    <div>
                        <span class="text-gray-500">Pending:</span>
                        <strong class="ml-1 text-amber-500"><?= peso($totalPending) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Help box -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-white">Payment question?</div>
                <div class="mt-1 text-xs text-gray-400">
                    Contact the gym front desk — bring your GCash reference number.
                </div>
            </div>
        </div>
        <!-- Added tap-effect -->
        <a href="tel:09618041523" class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-xs font-bold text-white transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            Call (0961) 804-1523
        </a>
    </div>
</div>

<style>
    .payment-filter.active {
        background-color: #00A6A6 !important;
        border-color: #00A6A6 !important;
        color: #ffffff !important;
        box-shadow: 0 0 15px rgba(0, 166, 166, 0.4);
    }

    .payment-filter.active span:last-child {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    /* ========== Filter + search Logic ========== */
    let payFilter = 'all';
    let paySearch = '';

    function setPaymentFilter(status) {
        payFilter = status;
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === status);
        });
        applyPaymentFilters();
    }

    document.getElementById('paymentSearch')?.addEventListener('input', (e) => {
        paySearch = e.target.value.toLowerCase().trim();
        applyPaymentFilters();
    });

    function applyPaymentFilters() {
        const rows = document.querySelectorAll('#paymentRows .payment-row');
        let visible = 0;

        rows.forEach(row => {
            const status = row.dataset.status || '';
            const search = row.dataset.search || '';

            const okStatus = payFilter === 'all' || status === payFilter;
            const okSearch = paySearch === '' || search.includes(paySearch);

            if (okStatus && okSearch) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        const empty = document.getElementById('noPaymentsResults');
        if (empty) empty.classList.toggle('hidden', visible > 0 || rows.length === 0);

        const count = document.getElementById('visibleCount');
        if (count) count.textContent = visible;
    }
</script>