<?php

/**
 * Partner — Wallet (2026 UI)
 * Balance + lifetime + transaction history + payout modal
 */

$sales     = $sales     ?? [];
$payouts   = $payouts   ?? [];
$lifetime  = $lifetime  ?? 0;
$available = $available ?? 0;

// ---------- Derived stats ----------
$approvedSales = array_filter($sales, fn($s) => ($s['status'] ?? '') === 'approved');
$pendingSales  = array_filter($sales, fn($s) => ($s['status'] ?? '') === 'pending');

$approvedPayouts = array_filter($payouts, fn($p) => ($p['status'] ?? '') === 'approved');
$pendingPayouts  = array_filter($payouts, fn($p) => ($p['status'] ?? '') === 'pending');

$totalPaidOut = 0;
foreach ($approvedPayouts as $p) $totalPaidOut += (float)($p['amount'] ?? 0);

$pendingPayoutAmount = 0;
foreach ($pendingPayouts as $p) $pendingPayoutAmount += (float)($p['amount'] ?? 0);

$pendingSalesAmount = 0;
foreach ($pendingSales as $s) $pendingSalesAmount += (float)($s['amount'] ?? 0);

// ---------- Build unified transaction list ----------
$tx = [];

foreach ($sales as $s) {
    $tx[] = [
        'id'     => 'sale-' . ($s['id'] ?? uniqid()),
        'date'   => $s['sale_date'] ?? '',
        'label'  => $s['description'] ?? 'Sale',
        'kind'   => 'Sale',
        'type'   => $s['type'] ?? 'product',
        'amount' => (float)($s['amount'] ?? 0),
        'status' => $s['status'] ?? 'pending',
    ];
}

foreach ($payouts as $p) {
    $tx[] = [
        'id'     => 'payout-' . ($p['id'] ?? uniqid()),
        'date'   => $p['requested_date'] ?? '',
        'label'  => $p['note'] ?: 'Payout request',
        'kind'   => 'Payout',
        'type'   => null,
        'amount' => -(float)($p['amount'] ?? 0),
        'status' => $p['status'] ?? 'pending',
    ];
}

usort($tx, fn($a, $b) => strcmp($b['date'], $a['date']));

$totalTx = count($tx);
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== Page Header ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Partner Portal</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    WALLET
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Your earnings, payouts, and transaction history — all in one place.
                </p>
            </div>

            <button type="button" onclick="openPayoutModal()"
                class="tap-effect group inline-flex flex-shrink-0 items-center gap-2 self-start rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)] sm:self-auto
                <?= $available <= 0 ? 'opacity-60' : '' ?>">
                <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Request Payout</span>
            </button>
        </div>
    </div>

    <!-- ========== Balance Cards ========== -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Available Balance (Hero) -->
        <div class="group relative col-span-1 overflow-hidden rounded-2xl border border-[#00A6A6]/30 bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-6 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#00A6A6]/30 sm:col-span-2">
            <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[#00A6A6]/25 blur-3xl transition-transform duration-700 group-hover:scale-150"></div>
            <div class="relative">
                <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-[#00A6A6]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </span>
                    Available Balance
                </div>
                <div class="mt-3 font-heading text-5xl tracking-wide text-[#00A6A6] sm:text-6xl">
                    <?= peso($available) ?>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-400">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        Ready to withdraw
                    </span>
                    <?php if ($pendingPayoutAmount > 0): ?>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            <?= peso($pendingPayoutAmount) ?> in pending payouts
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lifetime Earnings -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Lifetime Earnings</div>
                    <div class="mt-2 font-heading text-3xl text-white"><?= peso($lifetime) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= count($approvedSales) ?> approved sale<?= count($approvedSales) !== 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full origin-left scale-x-0 bg-gradient-to-r from-[#00A6A6] to-transparent transition-transform duration-500 group-hover:scale-x-100"></div>
        </div>

        <!-- Paid Out -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-red-500/40 hover:shadow-[0_10px_30px_-10px_rgba(213,0,0,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-red-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Paid Out</div>
                    <div class="mt-2 font-heading text-3xl text-[#D50000]"><?= peso($totalPaidOut) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= count($approvedPayouts) ?> payout<?= count($approvedPayouts) !== 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full origin-left scale-x-0 bg-gradient-to-r from-red-500 to-transparent transition-transform duration-500 group-hover:scale-x-100"></div>
        </div>
    </div>

    <!-- ========== Pending summary ========== -->
    <?php if ($pendingSalesAmount > 0 || $pendingPayoutAmount > 0): ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <?php if ($pendingSalesAmount > 0): ?>
                <div class="flex items-start gap-4 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-5">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-amber-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-amber-500">
                            <?= peso($pendingSalesAmount) ?> in pending sales
                        </div>
                        <div class="mt-0.5 text-xs text-gray-400">
                            <?= count($pendingSales) ?> sale<?= count($pendingSales) !== 1 ? 's' : '' ?> awaiting gym approval
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($pendingPayoutAmount > 0): ?>
                <div class="flex items-start gap-4 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-5">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-amber-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-amber-500">
                            <?= peso($pendingPayoutAmount) ?> payout in review
                        </div>
                        <div class="mt-0.5 text-xs text-gray-400">
                            <?= count($pendingPayouts) ?> request<?= count($pendingPayouts) !== 1 ? 's' : '' ?> awaiting approval
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ========== Filter Bar ========== -->
    <?php if (!empty($tx)): ?>
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/5 bg-[#111] p-4 shadow-sm">
            <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Filter:</span>

            <?php
            $kindFilters = [
                ['all',    'All',     $totalTx,        'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['Sale',   'Sales',   count($sales),   'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                ['Payout', 'Payouts', count($payouts), 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ];
            foreach ($kindFilters as [$key, $label, $count, $icon]):
            ?>
                <button type="button"
                    data-kind-filter="<?= $key ?>"
                    onclick="setKindFilter('<?= $key ?>')"
                    class="kind-filter tap-effect flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-300 transition-all duration-300 hover:border-[#00A6A6]/50 hover:text-white
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
                <input id="txSearch" type="search" placeholder="Search transactions…"
                    class="h-10 w-full rounded-full border border-white/10 bg-[#0a0a0a] pl-10 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </div>
        </div>
    <?php endif; ?>

    <!-- ========== Transaction history ========== -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/5 p-6">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">TRANSACTION HISTORY</h3>
                <p class="mt-1 text-xs text-gray-400">
                    <?= $totalTx ?> transaction<?= $totalTx !== 1 ? 's' : '' ?> total
                </p>
            </div>
        </div>

        <?php if (empty($tx)): ?>
            <div class="p-16 text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm font-semibold text-gray-400">No transactions yet</p>
                <p class="mt-1 max-w-md mx-auto text-xs text-gray-500">
                    Log your first sale or request a payout — transactions will appear here.
                </p>
                <div class="mt-6">
                    <a href="<?= url_e('/partner-dashboard/sales') ?>"
                        class="tap-effect inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Log a Sale
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Date</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Details</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Kind</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="txRows">
                        <?php foreach ($tx as $idx => $t):
                            $isPayout = $t['kind'] === 'Payout';
                            $isApproved = $t['status'] === 'approved';
                            $isRejected = $t['status'] === 'rejected';
                            $isPending = $t['status'] === 'pending';

                            $statusStyle = $isApproved
                                ? 'bg-green-500/10 text-green-500 border border-green-500/20'
                                : ($isRejected
                                    ? 'bg-red-500/10 text-red-500 border border-red-500/20'
                                    : 'bg-amber-500/10 text-amber-500 border border-amber-500/20');

                            $statusIcon = $isApproved
                                ? '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                                : ($isRejected
                                    ? '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
                                    : '');

                            $amount = (float)$t['amount'];
                            $isNegative = $amount < 0;
                        ?>
                            <tr class="tx-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                data-kind="<?= e($t['kind']) ?>"
                                data-status="<?= e($t['status']) ?>"
                                data-search="<?= e(strtolower($t['label'])) ?>"
                                style="animation: row-enter 0.3s ease-out <?= min($idx * 25, 400) ?>ms backwards;">

                                <!-- Date -->
                                <td class="p-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-white">
                                        <?= e(short_date($t['date'] ?? null)) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= e(date('D', strtotime($t['date'] ?: 'now'))) ?>
                                    </div>
                                </td>

                                <!-- Details -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full
                                            <?= $isPayout ? 'bg-red-500/10 text-red-500' : 'bg-[#00A6A6]/10 text-[#00A6A6]' ?>">
                                            <?php if ($isPayout): ?>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="max-w-xs truncate text-sm font-semibold text-white">
                                                <?= e($t['label']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kind -->
                                <td class="p-4 whitespace-nowrap">
                                    <?php if ($isPayout): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 border border-red-500/20 px-3 py-1 text-[10px] font-bold uppercase text-red-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Payout
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#00A6A6]/10 border border-[#00A6A6]/20 px-3 py-1 text-[10px] font-bold uppercase text-[#00A6A6]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                            Sale
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-bold uppercase <?= $statusStyle ?>">
                                        <?php if ($isPending): ?>
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <?php endif; ?>
                                        <?= $statusIcon ?> <?= e($t['status']) ?>
                                    </span>
                                </td>

                                <!-- Amount -->
                                <td class="p-4 text-right whitespace-nowrap">
                                    <div class="font-heading text-lg <?= $isNegative ? 'text-[#D50000]' : ($isApproved ? 'text-green-500' : 'text-amber-500') ?>">
                                        <?= $isNegative ? '-' : '+' ?><?= peso(abs($amount)) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="noTxResults" class="hidden p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No transactions match your filter.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========== Help box ========== -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-white">About payouts</div>
                <div class="mt-1 text-xs text-gray-400">
                    Payouts are processed within 3–5 business days after approval. Provide your GCash number in the note.
                </div>
            </div>
        </div>
        <a href="tel:09618041523"
            class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-xs font-bold text-white transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            Contact Gym
        </a>
    </div>
</div>

<!-- ========== Payout modal (partial) ========== -->
<?php require BASE_PATH . '/src/Views/partner/partials/payout_modal.php'; ?>

<style>
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

    @keyframes row-enter {
        from {
            opacity: 0;
            transform: translateX(-6px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .kind-filter.active {
        background-color: #00A6A6 !important;
        border-color: #00A6A6 !important;
        color: #ffffff !important;
        box-shadow: 0 0 15px rgba(0, 166, 166, 0.4);
    }

    .kind-filter.active span:last-child {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .tx-row {
            animation: none !important;
        }
    }
</style>

<script>
    /* ========== Kind filter ========== */
    let kindFilter = 'all';
    let txSearch = '';

    function setKindFilter(kind) {
        kindFilter = kind;
        document.querySelectorAll('[data-kind-filter]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.kindFilter === kind);
        });
        applyTxFilters();
    }

    document.getElementById('txSearch')?.addEventListener('input', (e) => {
        txSearch = e.target.value.toLowerCase().trim();
        applyTxFilters();
    });

    function applyTxFilters() {
        const rows = document.querySelectorAll('#txRows .tx-row');
        let visible = 0;

        rows.forEach(row => {
            const kind = row.dataset.kind || '';
            const search = row.dataset.search || '';

            const okKind = kindFilter === 'all' || kind === kindFilter;
            const okSearch = txSearch === '' || search.includes(txSearch);

            if (okKind && okSearch) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        const empty = document.getElementById('noTxResults');
        if (empty) empty.classList.toggle('hidden', visible > 0 || rows.length === 0);
    }
</script>