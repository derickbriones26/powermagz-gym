<?php

/**
 * Partner — Sales & Referrals (2026 UI)
 * Referral code + log sale form + sales list + filters
 */

if (defined('PMGZ_SALE_FORM_RENDERED')) {
    return;
}
define('PMGZ_SALE_FORM_RENDERED', true);

$profile = $profile ?? [];
$sales   = $sales   ?? [];

// ---------- Sort newest first ----------
usort($sales, fn($a, $b) => strcmp($b['sale_date'] ?? '', $a['sale_date'] ?? ''));

// ---------- Stats ----------
$approvedSales = array_filter($sales, fn($s) => ($s['status'] ?? '') === 'approved');
$pendingSales  = array_filter($sales, fn($s) => ($s['status'] ?? '') === 'pending');

$totalApproved = 0;
foreach ($approvedSales as $s) $totalApproved += (float)($s['amount'] ?? 0);

$totalPending = 0;
foreach ($pendingSales as $s) $totalPending += (float)($s['amount'] ?? 0);

$approvedCount = count($approvedSales);
$pendingCount  = count($pendingSales);

$productCount = 0;
$clientPaymentCount = 0;
foreach ($sales as $s) {
    if (($s['type'] ?? '') === 'client_payment') $clientPaymentCount++;
    else $productCount++;
}

$totalSalesCount = count($sales);
$avgSale = $totalSalesCount > 0
    ? array_sum(array_map(fn($s) => (float)($s['amount'] ?? 0), $sales)) / $totalSalesCount
    : 0;
?>

<div class="space-y-8">

    <!-- ========== Page Header ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Partner Portal</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    SALES &amp; REFERRALS
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Share your referral code to grow your client base — and log sales to earn commissions.
                </p>
            </div>
        </div>
    </div>

    <!-- ========== Referral card (partial) ========== -->
    <?php require BASE_PATH . '/src/Views/partner/partials/referral_code_card.php'; ?>

    <!-- ========== Stats Grid ========== -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Sales -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Sales</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= $totalSalesCount ?></div>
                    <div class="mt-1 text-xs text-gray-400">All time</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Approved -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/20 bg-green-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <?php if ($approvedCount > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <?php endif; ?>
                        Approved
                    </div>
                    <div class="mt-2 font-heading text-3xl text-green-500"><?= peso($totalApproved) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $approvedCount ?> sale<?= $approvedCount !== 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="group relative overflow-hidden rounded-2xl border border-amber-500/20 bg-amber-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-amber-500">
                        <?php if ($pendingCount > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <?php endif; ?>
                        Pending
                    </div>
                    <div class="mt-2 font-heading text-3xl text-amber-500"><?= peso($totalPending) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $pendingCount ?> awaiting approval
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Avg Sale -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Avg Sale</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($avgSale) ?></div>
                    <div class="mt-1 text-xs text-gray-400">Per transaction</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== Log Sale form ========== -->
    <form method="post" action="<?= url_e('/partner-dashboard/sales') ?>" class="rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm">
        <?= csrf_field() ?>
        <div class="grid gap-4 lg:grid-cols-[1.2fr_0.6fr_0.6fr_auto]">

            <!-- Description -->
            <div>
                <label for="sale-description" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Description</label>
                <input id="sale-description" name="description" type="text" maxlength="120" required
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20"
                    placeholder="E.g. 1-month membership bundle">
            </div>

            <!-- Amount (₱ peso) -->
            <div>
                <label for="sale-amount" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount (₱)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-500" aria-hidden="true">₱</span>
                    <input id="sale-amount" name="amount" type="number" min="0.01" step="0.01" required
                        inputmode="decimal"
                        placeholder="0.00"
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-9 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>
            </div>

            <!-- Type -->
            <div>
                <label for="sale-type" class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Type</label>
                <select id="sale-type" name="type"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="product">Product</option>
                    <option value="client_payment">Client Payment</option>
                </select>
            </div>

            <!-- Submit -->
            <div class="flex items-end">
                <button type="submit" class="tap-effect h-11 w-full rounded-xl bg-[#00A6A6] px-6 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                    Log Sale
                </button>
            </div>
        </div>
    </form>

    <!-- ========== Sales list ========== -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/5 p-6">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">MY SALES</h3>
                <p class="mt-1 text-xs text-gray-400">
                    <?= $totalSalesCount ?> sale<?= $totalSalesCount !== 1 ? 's' : '' ?> logged
                </p>
            </div>

            <?php if (!empty($sales)): ?>
                <div class="flex flex-wrap items-center gap-2">
                    <?php
                    $typeFilters = [
                        ['all',            'All',            $totalSalesCount,           'M4 6h16M4 10h16M4 14h16M4 18h16'],
                        ['product',        'Products',       $productCount,              'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['client_payment', 'Client Payments', $clientPaymentCount,       'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                    ];
                    foreach ($typeFilters as [$key, $label, $count, $icon]):
                    ?>
                        <button type="button"
                            data-type-filter="<?= $key ?>"
                            onclick="setTypeFilter('<?= $key ?>')"
                            class="type-filter tap-effect flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-gray-300 transition-all duration-300 hover:border-[#00A6A6]/50 hover:text-white
                           <?= $key === 'all' ? 'active' : '' ?>">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                            </svg>
                            <span><?= $label ?></span>
                            <span class="rounded-full bg-black/30 px-1.5 py-0.5 text-[10px] font-bold text-gray-400"><?= $count ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($sales)): ?>
            <div class="p-16 text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <p class="mt-4 text-sm font-semibold text-gray-400">No sales logged yet</p>
                <p class="mt-1 max-w-md mx-auto text-xs text-gray-500">
                    Log your first sale above or share your referral link to earn commissions.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Date</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Description</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Type</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="saleRows">
                        <?php foreach ($sales as $idx => $s):
                            $status = $s['status'] ?? 'pending';
                            $type   = $s['type'] ?? 'product';
                            $isApproved = $status === 'approved';
                            $amount = (float)($s['amount'] ?? 0);
                        ?>
                            <tr class="sale-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                data-status="<?= e($status) ?>"
                                data-type="<?= e($type) ?>"
                                data-search="<?= e(strtolower($s['description'] ?? '')) ?>"
                                style="animation: row-enter 0.3s ease-out <?= min($idx * 30, 400) ?>ms backwards;">

                                <!-- Date -->
                                <td class="p-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-white">
                                        <?= e(short_date($s['sale_date'] ?? null)) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= e(date('D', strtotime($s['sale_date'] ?? 'now'))) ?>
                                    </div>
                                </td>

                                <!-- Description -->
                                <td class="p-4">
                                    <div class="max-w-xs truncate text-sm font-semibold text-white">
                                        <?= e($s['description'] ?? '—') ?>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="p-4 whitespace-nowrap">
                                    <?php if ($type === 'client_payment'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-500/10 border border-purple-500/20 px-3 py-1 text-[10px] font-bold uppercase text-purple-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Client
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#00A6A6]/10 border border-[#00A6A6]/20 px-3 py-1 text-[10px] font-bold uppercase text-[#00A6A6]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                            Product
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="p-4">
                                    <?php if ($isApproved): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 border border-green-500/20 px-3 py-1 text-[10px] font-bold uppercase text-green-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 px-3 py-1 text-[10px] font-bold uppercase text-amber-500">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Amount -->
                                <td class="p-4 text-right whitespace-nowrap">
                                    <div class="font-heading text-lg <?= $isApproved ? 'text-green-500' : 'text-amber-500' ?>">
                                        <?= peso($amount) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="noSaleResults" class="hidden p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No sales match your filter.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========== Help box ========== -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-white">How sales get credited</div>
                <div class="mt-1 text-xs text-gray-400">
                    The gym admin approves each sale before it's added to your wallet and earnings.
                </div>
            </div>
        </div>
        <a href="<?= url_e('/partner-dashboard/wallet') ?>"
            class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-xs font-bold text-white transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            View Wallet
        </a>
    </div>
</div>

<style>
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

    .type-filter.active {
        background-color: #00A6A6 !important;
        border-color: #00A6A6 !important;
        color: #ffffff !important;
        box-shadow: 0 0 15px rgba(0, 166, 166, 0.4);
    }

    .type-filter.active span:last-child {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .sale-row {
            animation: none !important;
        }
    }
</style>

<script>
    /* ========== Type filter ========== */
    let typeFilter = 'all';

    function setTypeFilter(type) {
        typeFilter = type;
        document.querySelectorAll('[data-type-filter]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.typeFilter === type);
        });
        applySaleFilters();
    }

    function applySaleFilters() {
        const rows = document.querySelectorAll('#saleRows .sale-row');
        let visible = 0;

        rows.forEach(row => {
            const type = row.dataset.type || '';
            const show = typeFilter === 'all' || type === typeFilter;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const empty = document.getElementById('noSaleResults');
        if (empty) empty.classList.toggle('hidden', visible > 0 || rows.length === 0);
    }
</script>