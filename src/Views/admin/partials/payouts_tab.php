<?php

/**
 * Admin — Payouts Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['partnerSales']         — PartnerSale::listWithFilters()
 *   $data['saleSummary']          — PartnerSale::summary()
 *   $data['payouts']              — PayoutRequest::listWithFilters()
 *   $data['payoutSummary']        — PayoutRequest::summary()
 *   $data['payoutBatches']        — PayoutRequest::listBatches(50)
 *   $data['payoutReconciliation'] — PayoutRequest::monthlyReconciliation(12)
 *   $data['partners']             — PartnerProfile::listWithMetrics()
 *   $data['salesFilters']         — active filter state
 *   $data['payoutFilters']        — active filter state
 */

use App\Models\PartnerSale;
use App\Models\PayoutRequest;

$sales       = $data['partnerSales']         ?? [];
$saleSum     = $data['saleSummary']          ?? [];
$payouts     = $data['payouts']              ?? [];
$payoutSum   = $data['payoutSummary']        ?? [];
$batches     = $data['payoutBatches']        ?? [];
$recon       = $data['payoutReconciliation'] ?? [];
$partners    = $data['partners']             ?? [];
$sFilters    = $data['salesFilters']         ?? [
    'status' => 'all',
    'partner_user_id' => '',
    'type' => 'all',
    'from' => '',
    'to' => '',
    'search' => '',
];
$pFilters    = $data['payoutFilters']        ?? [
    'status' => 'all',
    'method' => 'all',
    'partner_user_id' => '',
    'batch_id' => '',
    'from' => '',
    'to' => '',
    'search' => '',
];

/* Status meta */
$saleStatusMeta = [
    'pending'  => [
        'bg' => 'bg-amber-500/10',
        'text' => 'text-amber-500',
        'border' => 'border-amber-500/20',
        'svg' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
    'approved' => [
        'bg' => 'bg-green-500/10',
        'text' => 'text-green-500',
        'border' => 'border-green-500/20',
        'svg' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
    'rejected' => [
        'bg' => 'bg-red-500/10',
        'text' => 'text-red-500',
        'border' => 'border-red-500/20',
        'svg' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
];
$payoutStatusMeta = $saleStatusMeta;

/* Query-string builders */
$qsSales = function (array $overrides = []) use ($sFilters): string {
    $merged = array_merge([
        'tab'       => 'payouts',
        's_status'  => $sFilters['status'] === 'all' ? '' : $sFilters['status'],
        's_partner' => $sFilters['partner_user_id'],
        's_type'    => $sFilters['type'] === 'all' ? '' : $sFilters['type'],
        's_from'    => $sFilters['from'],
        's_to'      => $sFilters['to'],
        's_q'       => $sFilters['search'],
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== 'all');
    return $merged ? '?' . http_build_query($merged) : '?tab=payouts';
};

$qsPayouts = function (array $overrides = []) use ($pFilters): string {
    $merged = array_merge([
        'tab'        => 'payouts',
        'po_status'  => $pFilters['status'] === 'all' ? '' : $pFilters['status'],
        'po_method'  => $pFilters['method'] === 'all' ? '' : $pFilters['method'],
        'po_partner' => $pFilters['partner_user_id'],
        'po_batch'   => $pFilters['batch_id'],
        'po_from'    => $pFilters['from'],
        'po_to'      => $pFilters['to'],
        'po_q'       => $pFilters['search'],
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== 'all');
    return $merged ? '?' . http_build_query($merged) : '?tab=payouts';
};

/* Method SVG */
$methodSvg = [
    'gcash'         => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    'bank_transfer' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
    'cash'          => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    'mixed'         => 'M4 6h16M4 10h16M4 14h16M4 18h16',
];
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Admin Panel</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    PARTNER PAYOUTS
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Approve sales, process payouts, and manage batches. Every action is logged.
                </p>
            </div>

            <button type="button" onclick="openLogSaleModal()"
                class="tap-effect group inline-flex flex-shrink-0 items-center gap-2 self-start rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)] sm:self-auto">
                <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Log Sale</span>
            </button>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <!-- Total Sales -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Sales</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($saleSum['total_amount'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($saleSum['total'] ?? 0) ?> sale<?= ($saleSum['total'] ?? 0) !== 1 ? 's' : '' ?> logged
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Sales to Review -->
        <div class="group relative overflow-hidden rounded-2xl border <?= ($saleSum['pending'] ?? 0) > 0 ? 'border-amber-500/20 bg-amber-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest <?= ($saleSum['pending'] ?? 0) > 0 ? 'text-amber-500' : 'text-gray-500' ?>">
                        <?php if (($saleSum['pending'] ?? 0) > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <?php endif; ?>
                        Sales to Review
                    </div>
                    <div class="mt-2 font-heading text-3xl <?= ($saleSum['pending'] ?? 0) > 0 ? 'text-amber-500' : 'text-gray-600' ?>">
                        <?= (int)($saleSum['pending'] ?? 0) ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= peso($saleSum['pending_amount'] ?? 0) ?> · commission <?= peso($saleSum['commission_pending'] ?? 0) ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Payouts to Review -->
        <div class="group relative overflow-hidden rounded-2xl border <?= ($payoutSum['pending'] ?? 0) > 0 ? 'border-amber-500/20 bg-amber-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest <?= ($payoutSum['pending'] ?? 0) > 0 ? 'text-amber-500' : 'text-gray-500' ?>">
                        <?php if (($payoutSum['pending'] ?? 0) > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <?php endif; ?>
                        Payouts to Review
                    </div>
                    <div class="mt-2 font-heading text-3xl <?= ($payoutSum['pending'] ?? 0) > 0 ? 'text-amber-500' : 'text-gray-600' ?>">
                        <?= (int)($payoutSum['pending'] ?? 0) ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= peso($payoutSum['pending_amount'] ?? 0) ?> awaiting
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Paid Out -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-red-500/40 hover:shadow-[0_10px_30px_-10px_rgba(213,0,0,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-red-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Paid Out</div>
                    <div class="mt-2 font-heading text-3xl text-[#D50000]"><?= peso($payoutSum['approved_amount'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($payoutSum['approved'] ?? 0) ?> payout<?= ($payoutSum['approved'] ?? 0) !== 1 ? 's' : '' ?> · avg <?= peso($payoutSum['avg_payout'] ?? 0) ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Commission Earned -->
        <div class="group relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Commission Earned</div>
                    <div class="mt-2 font-heading text-3xl text-purple-400"><?= peso($saleSum['commission_approved'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        From <?= (int)($saleSum['approved'] ?? 0) ?> approved sales
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         RECONCILIATION CHART
         ============================================================ -->
    <?php if (!empty($recon)): ?>
        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">PAYOUT RECONCILIATION</h3>
                    <p class="mt-1 text-xs text-gray-400">Approved payouts per month · last 12 months</p>
                </div>
                <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    <?= peso(array_sum(array_column($recon, 'total'))) ?>
                </span>
            </div>
            <div class="mt-4 h-56"><canvas id="reconChart" role="img" aria-label="Line chart of monthly approved payouts"></canvas></div>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         PARTNER SALES SECTION
         ============================================================ -->
    <section role="region" aria-label="Partner sales">

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="font-heading text-3xl tracking-wide text-white">PARTNER SALES</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Approve logged sales to credit commissions to partner wallets.
                </p>
            </div>
        </div>

        <!-- Sales filter bar -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-3">
            <?= a11y_filter_open('Filter partner sales by status and type') ?>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status:</span>

                <?php
                $saleStatusFilters = [
                    ['all',      'All',      (int)($saleSum['total']    ?? 0), 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['pending',  'Pending',  (int)($saleSum['pending']  ?? 0), 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['approved', 'Approved', (int)($saleSum['approved'] ?? 0), 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['rejected', 'Rejected', (int)($saleSum['rejected'] ?? 0), 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
                foreach ($saleStatusFilters as [$key, $label, $count, $icon]):
                    $isActive = ($sFilters['status'] === $key || ($key === 'all' && $sFilters['status'] === 'all'));
                ?>
                    <a href="<?= url_e('/admin-dashboard' . $qsSales(['s_status' => $key === 'all' ? '' : $key])) ?>"
                        aria-current="<?= $isActive ? 'true' : 'false' ?>"
                        class="filter-pill flex items-center gap-2 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-4 py-2 text-sm font-semibold transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                        </svg>
                        <span><?= $label ?></span>
                        <span class="rounded-full <?= $isActive ? 'bg-black/25 text-white' : 'bg-black/30 text-gray-400' ?> px-2 py-0.5 text-[10px] font-bold"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>

                <span class="ml-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Type:</span>

                <?php
                $saleTypeFilters = [
                    ['all',            'All',      'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['product',        'Products', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['client_payment', 'Client',   'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ];
                foreach ($saleTypeFilters as [$key, $label, $icon]):
                    $isActive = ($sFilters['type'] === $key || ($key === 'all' && $sFilters['type'] === 'all'));
                ?>
                    <a href="<?= url_e('/admin-dashboard' . $qsSales(['s_type' => $key === 'all' ? '' : $key])) ?>"
                        aria-current="<?= $isActive ? 'true' : 'false' ?>"
                        class="filter-pill flex items-center gap-1.5 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                        </svg>
                        <span><?= $label ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?= a11y_filter_close() ?>
        </div>

        <!-- Sales table -->
        <div class="mt-4 overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <caption class="sr-only">Partner sales with commission preview and approval actions</caption>
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Partner</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Date</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Description</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Type</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Commission</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="saleRows">
                        <?php foreach ($sales as $idx => $s):
                            $status = $s['status'] ?? 'pending';
                            $sm     = $saleStatusMeta[$status] ?? $saleStatusMeta['pending'];
                            $type   = $s['type'] ?? 'product';
                            $isPending = $status === 'pending';
                            $commissionAmt = (float)($s['commission_amount'] ?? 0);
                            $commissionRate = (float)($s['commission_rate'] ?? $s['partner_commission_rate'] ?? 10);
                        ?>
                            <tr class="sale-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                data-status="<?= e($status) ?>"
                                data-type="<?= e($type) ?>"
                                data-search="<?= e(strtolower(($s['business_name'] ?? '') . ' ' . ($s['description'] ?? ''))) ?>"
                                style="animation: row-enter 0.35s ease-out <?= min($idx * 20, 400) ?>ms backwards;">

                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white shadow-md">
                                            <?= e(initials($s['business_name'] ?? '?')) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-white">
                                                <?= e($s['business_name'] ?? 'Unknown Partner') ?>
                                            </div>
                                            <div class="truncate text-[10px] font-bold uppercase text-gray-500">
                                                <?= e($s['partner_type'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 text-sm text-gray-300 whitespace-nowrap hidden lg:table-cell">
                                    <?= e(short_date($s['sale_date'] ?? null)) ?>
                                </td>

                                <td class="p-4">
                                    <div class="max-w-xs truncate text-sm text-gray-300" title="<?= e($s['description'] ?? '') ?>">
                                        <?= e($s['description'] ?? '—') ?>
                                    </div>
                                </td>

                                <td class="p-4 hidden xl:table-cell">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-bold text-gray-300">
                                        <?php if ($type === 'client_payment'): ?>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Client
                                        <?php else: ?>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                            Product
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td class="p-4">
                                    <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $sm['bg'] ?> <?= $sm['text'] ?> <?= $sm['border'] ?> px-3 py-1 text-[10px] font-bold uppercase">
                                        <?php if ($isPending): ?>
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                        <?php else: ?>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $sm['svg'] ?>" />
                                            </svg>
                                        <?php endif; ?>
                                        <?= e($status) ?>
                                    </span>
                                    <?php if ($status === 'rejected' && !empty($s['rejected_reason'])): ?>
                                        <div class="mt-1 max-w-xs truncate text-[10px] text-gray-500" title="<?= e($s['rejected_reason']) ?>">
                                            <?= e($s['rejected_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-right font-heading text-base whitespace-nowrap <?= $isPending ? 'text-amber-500' : 'text-white' ?>">
                                    <?= peso($s['amount'] ?? 0) ?>
                                </td>

                                <td class="p-4 text-right whitespace-nowrap hidden lg:table-cell">
                                    <div class="font-heading text-base text-purple-400">
                                        <?= peso($commissionAmt ?: ((float)($s['amount'] ?? 0) * $commissionRate / 100)) ?>
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        <?= number_format($commissionRate, 1) ?>% rate
                                    </div>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">
                                        <?php if ($isPending): ?>
                                            <form method="post" action="<?= url_e('/admin-dashboard/sales/approve') ?>" class="inline"
                                                onsubmit="return confirm('Approve this sale and credit the commission?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                                                <button type="submit"
                                                    aria-label="Approve sale from <?= e($s['business_name'] ?? 'partner') ?>"
                                                    class="rounded-full bg-[#00A6A6] px-3 py-1.5 text-xs font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                                                    <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Approve
                                                </button>
                                            </form>

                                            <button type="button"
                                                onclick='openRejectSaleModal(<?= json_encode(["id" => $s["id"], "business" => $s["business_name"] ?? "Partner", "amount" => $s["amount"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                aria-label="Reject sale from <?= e($s['business_name'] ?? 'partner') ?>"
                                                title="Reject"
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-red-500 hover:text-red-500">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-600">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="8" class="p-16 text-center">
                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm text-gray-400">No sales match your filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ============================================================
         PAYOUT REQUESTS SECTION
         ============================================================ -->
    <section role="region" aria-label="Payout requests">

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="font-heading text-3xl tracking-wide text-white">PAYOUT REQUESTS</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Approve or reject partner payout requests · batch-process for efficiency.
                </p>
            </div>
        </div>

        <!-- Payout filter bar -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-3">
            <?= a11y_filter_open('Filter payout requests by status and method') ?>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status:</span>

                <?php
                $poStatusFilters = [
                    ['all',      'All',      (int)($payoutSum['total']    ?? 0), 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['pending',  'Pending',  (int)($payoutSum['pending']  ?? 0), 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['approved', 'Approved', (int)($payoutSum['approved'] ?? 0), 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['rejected', 'Rejected', (int)($payoutSum['rejected'] ?? 0), 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
                foreach ($poStatusFilters as [$key, $label, $count, $icon]):
                    $isActive = ($pFilters['status'] === $key || ($key === 'all' && $pFilters['status'] === 'all'));
                ?>
                    <a href="<?= url_e('/admin-dashboard' . $qsPayouts(['po_status' => $key === 'all' ? '' : $key])) ?>"
                        aria-current="<?= $isActive ? 'true' : 'false' ?>"
                        class="filter-pill flex items-center gap-2 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-4 py-2 text-sm font-semibold transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                        </svg>
                        <span><?= $label ?></span>
                        <span class="rounded-full <?= $isActive ? 'bg-black/25 text-white' : 'bg-black/30 text-gray-400' ?> px-2 py-0.5 text-[10px] font-bold"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>

                <span class="ml-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Method:</span>

                <?php
                $poMethodFilters = [
                    ['all',           'All',  'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['gcash',         'GCash', 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ['bank_transfer', 'Bank',  'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
                    ['cash',          'Cash',  'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ];
                foreach ($poMethodFilters as [$key, $label, $icon]):
                    $isActive = ($pFilters['method'] === $key || ($key === 'all' && $pFilters['method'] === 'all'));
                ?>
                    <a href="<?= url_e('/admin-dashboard' . $qsPayouts(['po_method' => $key === 'all' ? '' : $key])) ?>"
                        aria-current="<?= $isActive ? 'true' : 'false' ?>"
                        class="filter-pill flex items-center gap-1.5 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                        </svg>
                        <span><?= $label ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?= a11y_filter_close() ?>
        </div>

        <!-- Bulk bar -->
        <div id="bulkBar"
            role="region" aria-label="Bulk payout actions"
            class="mt-4 hidden items-center justify-between rounded-2xl border border-[#00A6A6]/30 bg-[#00A6A6]/5 p-4"
            style="animation: slide-down 0.3s ease-out;">
            <div class="flex items-center gap-3">
                <span id="bulkCount" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#00A6A6] text-sm font-bold text-white"
                    <?= a11y_live_region('polite') ?>>0</span>
                <span class="text-sm font-semibold text-gray-300">pending payout(s) selected</span>
                <span id="bulkTotal" class="text-xs text-gray-400"></span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="clearSelection()"
                    class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-colors hover:bg-white/10 hover:text-white">
                    Clear
                </button>
                <button type="button" onclick="openBatchModal()"
                    class="tap-effect inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-5 py-2 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Create Batch
                </button>
            </div>
        </div>

        <!-- Payout table -->
        <div class="mt-4 overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <caption class="sr-only">Payout requests with approval and batch actions</caption>
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th scope="col" class="w-12 p-4">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                                    class="h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6] cursor-pointer"
                                    aria-label="Select all pending payouts">
                            </th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Partner</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Requested</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Method</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Note</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="payoutRows">
                        <?php foreach ($payouts as $idx => $p):
                            $status = $p['status'] ?? 'pending';
                            $pm     = $payoutStatusMeta[$status] ?? $payoutStatusMeta['pending'];
                            $isPending = $status === 'pending';
                            $method = $p['method'] ?? 'gcash';
                            $methodIcon = $methodSvg[$method] ?? $methodSvg['gcash'];
                        ?>
                            <tr class="payout-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                data-status="<?= e($status) ?>"
                                data-method="<?= e($method) ?>"
                                data-search="<?= e(strtolower(($p['business_name'] ?? '') . ' ' . ($p['note'] ?? '') . ' ' . ($p['reference_no'] ?? ''))) ?>"
                                style="animation: row-enter 0.35s ease-out <?= min($idx * 20, 400) ?>ms backwards;">

                                <td class="p-4">
                                    <?php if ($isPending): ?>
                                        <input type="checkbox" class="row-check h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6] cursor-pointer"
                                            value="<?= e($p['id']) ?>"
                                            data-name="<?= e($p['business_name'] ?? 'Partner') ?>"
                                            data-amount="<?= e($p['amount'] ?? 0) ?>"
                                            onchange="updateBulkBar()"
                                            aria-label="Select payout from <?= e($p['business_name'] ?? 'partner') ?>">
                                    <?php else: ?>
                                        <span class="block h-4 w-4"></span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#D50000] to-[#b30000] font-heading text-sm text-white shadow-md">
                                            <?= e(initials($p['business_name'] ?? '?')) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-white">
                                                <?= e($p['business_name'] ?? 'Unknown Partner') ?>
                                            </div>
                                            <div class="truncate text-[10px] font-bold uppercase text-gray-500">
                                                <?= e($p['partner_type'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 text-sm text-gray-300 whitespace-nowrap hidden lg:table-cell">
                                    <?= e(short_date($p['requested_date'] ?? null)) ?>
                                </td>

                                <td class="p-4 hidden lg:table-cell">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-bold text-gray-300">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $methodIcon ?>" />
                                            </svg>
                                            <?= e(ucfirst(str_replace('_', ' ', $method))) ?>
                                        </span>
                                        <?php if (!empty($p['method_details'])): ?>
                                            <div class="max-w-xs truncate text-[10px] text-gray-500" title="<?= e($p['method_details']) ?>">
                                                <?= e($p['method_details']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="p-4 max-w-xs hidden xl:table-cell">
                                    <div class="truncate text-sm text-gray-400" title="<?= e($p['note'] ?? '') ?>">
                                        <?= e($p['note'] ?? '—') ?>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $pm['bg'] ?> <?= $pm['text'] ?> <?= $pm['border'] ?> px-3 py-1 text-[10px] font-bold uppercase">
                                        <?php if ($isPending): ?>
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                        <?php else: ?>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $pm['svg'] ?>" />
                                            </svg>
                                        <?php endif; ?>
                                        <?= e($status) ?>
                                    </span>
                                    <?php if ($status === 'approved' && !empty($p['reference_no'])): ?>
                                        <div class="mt-1 font-mono text-[10px] text-gray-500">
                                            ref: <?= e($p['reference_no']) ?>
                                        </div>
                                    <?php elseif ($status === 'rejected' && !empty($p['reject_reason'])): ?>
                                        <div class="mt-1 max-w-xs truncate text-[10px] text-gray-500" title="<?= e($p['reject_reason']) ?>">
                                            <?= e($p['reject_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="p-4 text-right font-heading text-base whitespace-nowrap <?= $isPending ? 'text-amber-500' : 'text-white' ?>">
                                    <?= peso($p['amount'] ?? 0) ?>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">
                                        <?php if ($isPending): ?>
                                            <button type="button"
                                                onclick='openMarkPaidModal(<?= json_encode(["id" => $p["id"], "business" => $p["business_name"] ?? "Partner", "amount" => $p["amount"], "method" => $method], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                aria-label="Approve payout for <?= e($p['business_name'] ?? 'partner') ?>"
                                                class="rounded-full bg-[#00A6A6] px-3 py-1.5 text-xs font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                                                <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Approve
                                            </button>

                                            <button type="button"
                                                onclick='openRejectPayoutModal(<?= json_encode(["id" => $p["id"], "business" => $p["business_name"] ?? "Partner", "amount" => $p["amount"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                aria-label="Reject payout for <?= e($p['business_name'] ?? 'partner') ?>"
                                                title="Reject"
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-red-500 hover:text-red-500">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-600">Processed</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($payouts)): ?>
                            <tr>
                                <td colspan="8" class="p-16 text-center">
                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm text-gray-400">No payout requests match your filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ============================================================
         BATCH HISTORY
         ============================================================ -->
    <?php if (!empty($batches)): ?>
        <section role="region" aria-label="Payout batch history">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-heading text-3xl tracking-wide text-white">PAYOUT BATCHES</h2>
                    <p class="mt-1 text-sm text-gray-400">Grouped payout processing for reconciliation.</p>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <caption class="sr-only">Payout batches with processing status</caption>
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Batch ID</th>
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Created</th>
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Method</th>
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                                <th scope="col" class="p-4 text-center text-[10px] font-bold uppercase tracking-widest text-gray-500">Payouts</th>
                                <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Total</th>
                                <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batches as $b):
                                $bStatus = $b['status'] ?? 'pending';
                                $bMeta = [
                                    'pending'    => [
                                        'bg' => 'bg-amber-500/10',
                                        'text' => 'text-amber-500',
                                        'border' => 'border-amber-500/20',
                                        'svg' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
                                    ],
                                    'processing' => [
                                        'bg' => 'bg-blue-500/10',
                                        'text' => 'text-blue-400',
                                        'border' => 'border-blue-500/20',
                                        'svg' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
                                    ],
                                    'completed'  => [
                                        'bg' => 'bg-green-500/10',
                                        'text' => 'text-green-500',
                                        'border' => 'border-green-500/20',
                                        'svg' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                                    ],
                                    'failed'     => [
                                        'bg' => 'bg-red-500/10',
                                        'text' => 'text-red-500',
                                        'border' => 'border-red-500/20',
                                        'svg' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
                                    ],
                                ][$bStatus] ?? [
                                    'bg' => 'bg-gray-500/10',
                                    'text' => 'text-gray-400',
                                    'border' => 'border-gray-500/20',
                                    'svg' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
                                ];
                                $isPending = in_array($bStatus, ['pending', 'processing'], true);
                            ?>
                                <tr class="group border-b border-white/5 transition-colors hover:bg-white/[0.02]">
                                    <td class="p-4">
                                        <button type="button"
                                            onclick='filterBatch("<?= e($b['id']) ?>")'
                                            class="font-mono text-xs font-bold text-[#00A6A6] transition-colors hover:text-white"
                                            aria-label="View payouts in batch <?= e(substr($b['id'], 0, 8)) ?>">
                                            <?= e(substr($b['id'], 0, 8)) ?>…
                                        </button>
                                    </td>
                                    <td class="p-4 text-sm text-gray-300 whitespace-nowrap hidden lg:table-cell">
                                        <?= e(short_date($b['created_date'] ?? null)) ?>
                                        <?php if (!empty($b['processed_date'])): ?>
                                            <div class="text-[10px] text-gray-500">
                                                Processed <?= e(short_date($b['processed_date'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm text-gray-300 hidden lg:table-cell">
                                        <?= e(ucfirst(str_replace('_', ' ', $b['method'] ?? 'mixed'))) ?>
                                    </td>
                                    <td class="p-4">
                                        <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $bMeta['bg'] ?> <?= $bMeta['text'] ?> <?= $bMeta['border'] ?> px-3 py-1 text-[10px] font-bold uppercase">
                                            <?php if ($bStatus === 'processing'): ?>
                                                <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                            <?php else: ?>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $bMeta['svg'] ?>" />
                                                </svg>
                                            <?php endif; ?>
                                            <?= e($bStatus) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-center font-heading text-lg text-white">
                                        <?= (int)($b['request_count'] ?? 0) ?>
                                    </td>
                                    <td class="p-4 text-right font-heading text-base text-[#D50000]">
                                        <?= peso($b['total_amount'] ?? 0) ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <?php if ($isPending): ?>
                                                <button type="button"
                                                    onclick='openProcessBatchModal(<?= json_encode(["id" => $b["id"], "count" => (int)($b["request_count"] ?? 0), "total" => (float)($b["total_amount"] ?? 0)], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                    class="rounded-full bg-[#00A6A6] px-3 py-1.5 text-xs font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                                                    <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Process
                                                </button>
                                            <?php else: ?>
                                                <button type="button" onclick='filterBatch("<?= e($b['id']) ?>")'
                                                    class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                                                    <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ============================================================
         LOG SALE MODAL
         ============================================================ -->
    <div id="logSaleModal" role="dialog" aria-modal="true" aria-labelledby="logSaleModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-lg rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="flex items-center justify-between border-b border-white/5 pb-4">
                <div>
                    <h2 id="logSaleModalTitle" class="font-heading text-2xl tracking-wide text-white">LOG PARTNER SALE</h2>
                    <p class="mt-1 text-xs text-gray-400">Sale will appear as pending until approved.</p>
                </div>
                <button type="button" onclick="closeLogSaleModal()"
                    aria-label="Close log sale dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/sales/log') ?>" class="mt-4 space-y-4" data-validate>
                <?= csrf_field() ?>

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Partner <span class="text-[#D50000]">*</span>
                    </span>
                    <select name="partner_user_id" required
                        data-validate="required"
                        data-error-label="Partner"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <option value="">— Select partner —</option>
                        <?php foreach ($partners as $p): ?>
                            <option value="<?= e($p['user_id']) ?>">
                                <?= e($p['business_name']) ?> — <?= e(ucfirst($p['partner_type'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Description <span class="text-[#D50000]">*</span>
                    </span>
                    <input name="description" required maxlength="255"
                        data-validate="required|max:255"
                        data-error-label="Description"
                        placeholder="e.g. 3× 1-month bundle referrals"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Amount (₱) <span class="text-[#D50000]">*</span>
                        </span>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-500" aria-hidden="true">₱</span>
                            <input type="number" name="amount" min="0.01" step="0.01" required
                                inputmode="decimal"
                                data-validate="required|positive"
                                data-error-label="Amount"
                                placeholder="0.00"
                                class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-9 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </div>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Type</span>
                        <select name="type"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                            <option value="product">Product</option>
                            <option value="client_payment">Client Payment</option>
                        </select>
                    </label>
                </div>

                <div class="rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-4">
                    <div class="text-xs leading-relaxed text-gray-300">
                        Commission is calculated at creation time from the partner's commission rate. You'll see it in the sale row before approving.
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" onclick="closeLogSaleModal()"
                        class="rounded-full border border-white/10 px-6 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Log Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         REJECT SALE MODAL
         ============================================================ -->
    <div id="rejectSaleModal" role="dialog" aria-modal="true" aria-labelledby="rejectSaleModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-red-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 id="rejectSaleModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">REJECT SALE?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Reject <strong id="rs-partner" class="text-white">this sale</strong> of
                    <strong id="rs-amount" class="text-red-500">₱0</strong>.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/sales/reject') ?>" class="mt-6 space-y-4" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="rs-id">

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Reason <span class="text-[#D50000]">*</span>
                    </span>
                    <textarea name="reason" required rows="3" maxlength="255"
                        data-validate="required|max:255"
                        data-error-label="Reason"
                        placeholder="e.g. Duplicate entry, unverified referral, incorrect amount"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-red-500 focus:ring-2 focus:ring-red-500/20"></textarea>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeRejectSaleModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-red-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all hover:-translate-y-0.5 hover:bg-red-600">
                        Reject Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         MARK PAID MODAL
         ============================================================ -->
    <div id="markPaidModal" role="dialog" aria-modal="true" aria-labelledby="markPaidModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-[#00A6A6]/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 id="markPaidModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">APPROVE PAYOUT</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Approve <strong id="mp-partner" class="text-white">this partner</strong>'s payout of
                    <strong id="mp-amount" class="text-[#00A6A6]">₱0</strong> via
                    <strong id="mp-method" class="text-white">GCash</strong>.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/payouts/mark-paid') ?>" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="mp-id">

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Reference No. <span class="text-gray-600">(optional)</span>
                    </span>
                    <input name="reference_no" id="mp-ref" maxlength="80"
                        placeholder="e.g. GCash ref, bank confirmation"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <span class="mt-2 block text-[11px] text-gray-500">Recorded on the payout row for reconciliation.</span>
                </label>

                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                    <div class="text-xs leading-relaxed text-amber-200/80">
                        This is the point of no return — approving commits the amount as paid. Make sure you've actually sent the money first.
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeMarkPaidModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Approve Payout
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         REJECT PAYOUT MODAL
         ============================================================ -->
    <div id="rejectPayoutModal" role="dialog" aria-modal="true" aria-labelledby="rejectPayoutModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-red-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 id="rejectPayoutModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">REJECT PAYOUT?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Reject <strong id="rp-partner" class="text-white">this payout</strong> of
                    <strong id="rp-amount" class="text-red-500">₱0</strong>.
                    The amount will be released back to the partner's available balance.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/payouts/set') ?>" class="mt-6 space-y-4" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="rp-id">
                <input type="hidden" name="status" value="rejected">

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Reason <span class="text-[#D50000]">*</span>
                    </span>
                    <textarea name="note" required rows="3" maxlength="255"
                        data-validate="required|max:255"
                        data-error-label="Reason"
                        placeholder="e.g. Insufficient available balance, invalid method details"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-red-500 focus:ring-2 focus:ring-red-500/20"></textarea>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeRejectPayoutModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-red-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all hover:-translate-y-0.5 hover:bg-red-600">
                        Reject Payout
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         BATCH CREATION MODAL
         ============================================================ -->
    <div id="batchModal" role="dialog" aria-modal="true" aria-labelledby="batchModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h2 id="batchModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">CREATE PAYOUT BATCH</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Batch <strong id="bm-count" class="text-white">0</strong> payout(s) totaling
                    <strong id="bm-total" class="text-[#00A6A6]">₱0</strong>.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/payouts/batch') ?>" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <div id="bm-inputs"></div>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Batch note <span class="text-gray-600">(optional)</span>
                    </span>
                    <input name="notes" maxlength="255"
                        placeholder="e.g. Weekly GCash batch — Friday cutoff"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <div class="rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-4 text-xs leading-relaxed text-gray-300">
                    Payouts are grouped but still need a separate Process step after you send the money.
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeBatchModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Create Batch
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         PROCESS BATCH MODAL
         ============================================================ -->
    <div id="processBatchModal" role="dialog" aria-modal="true" aria-labelledby="processBatchModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-green-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-500/10 text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 id="processBatchModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">PROCESS BATCH</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Process <strong id="pb-count" class="text-white">0</strong> payout(s) totaling
                    <strong id="pb-total" class="text-[#00A6A6]">₱0</strong>.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/payouts/batch/process') ?>" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="batch_id" id="pb-id">

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Batch reference <span class="text-gray-600">(optional)</span>
                    </span>
                    <input name="reference_no" maxlength="80"
                        placeholder="e.g. GCash batch ref #12345"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <span class="mt-2 block text-[11px] text-gray-500">Applied to all payouts in this batch.</span>
                </label>

                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs leading-relaxed text-amber-200/80">
                    All payouts in this batch will be marked as approved. Make sure the money has actually been sent.
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeProcessBatchModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Process Batch
                    </button>
                </div>
            </form>
        </div>
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

    @keyframes modal-pop {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes slide-down {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .sale-row,
        .payout-row,
        #logSaleModal>div,
        #markPaidModal>div,
        #rejectPayoutModal>div,
        #rejectSaleModal>div,
        #batchModal>div,
        #processBatchModal>div {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        const RECON = <?= json_encode($recon, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const BASE = document.querySelector('meta[name="base"]')?.content || '';

        /* ============ Reconciliation chart ============ */
        const reconEl = document.getElementById('reconChart');
        if (reconEl && RECON && RECON.length > 0) {
            const isDark = document.documentElement.classList.contains('dark');
            const gridClr = isDark ? '#334155' : '#e5e7eb';
            const tickClr = isDark ? '#94a3b8' : '#4b5563';

            const ctx = reconEl.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 220);
            grad.addColorStop(0, 'rgba(0,166,166,0.35)');
            grad.addColorStop(1, 'rgba(0,166,166,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: RECON.map(r => {
                        const [y, m] = r.month.split('-');
                        return new Date(y, m - 1).toLocaleDateString('en', {
                            month: 'short',
                            year: '2-digit'
                        });
                    }),
                    datasets: [{
                        data: RECON.map(r => Number(r.total || 0)),
                        borderColor: '#00A6A6',
                        backgroundColor: grad,
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#00A6A6',
                        pointBorderWidth: 3,
                        pointHoverBackgroundColor: '#00A6A6',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 900,
                        easing: 'easeOutQuart'
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: false,
                            callbacks: {
                                label: (i) => '₱' + Number(i.raw).toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                font: {
                                    size: 10
                                },
                                callback: (v) => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        /* ============ Bulk selection (payouts) ============ */
        window.toggleSelectAll = function(el) {
            document.querySelectorAll('.row-check').forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') cb.checked = el.checked;
            });
            updateBulkBar();
        };

        window.clearSelection = function() {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
            const sa = document.getElementById('selectAll');
            if (sa) sa.checked = false;
            updateBulkBar();
        };

        window.updateBulkBar = function() {
            const checks = Array.from(document.querySelectorAll('.row-check:checked'));
            const bar = document.getElementById('bulkBar');
            const count = document.getElementById('bulkCount');
            const total = document.getElementById('bulkTotal');

            if (checks.length === 0) {
                bar.classList.add('hidden');
                bar.classList.remove('flex');
                return;
            }

            bar.classList.remove('hidden');
            bar.classList.add('flex');
            if (count) count.textContent = checks.length;

            let sum = 0;
            checks.forEach(cb => sum += parseFloat(cb.dataset.amount || 0));
            if (total) total.textContent = '· Total ₱' + sum.toLocaleString();
        };

        /* ============ Modal helpers ============ */
        function trackTrigger() {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
        }

        /* ============ Log sale modal ============ */
        window.openLogSaleModal = function() {
            trackTrigger();
            document.getElementById('logSaleModal').classList.replace('hidden', 'flex');
        };
        window.closeLogSaleModal = function() {
            document.getElementById('logSaleModal').classList.replace('flex', 'hidden');
        };

        /* ============ Reject sale modal ============ */
        window.openRejectSaleModal = function(s) {
            trackTrigger();
            document.getElementById('rs-id').value = s.id;
            document.getElementById('rs-partner').textContent = s.business;
            document.getElementById('rs-amount').textContent = '₱' + Number(s.amount || 0).toLocaleString();
            document.getElementById('rejectSaleModal').classList.replace('hidden', 'flex');
        };
        window.closeRejectSaleModal = function() {
            document.getElementById('rejectSaleModal').classList.replace('flex', 'hidden');
        };

        /* ============ Mark paid modal ============ */
        window.openMarkPaidModal = function(p) {
            trackTrigger();
            document.getElementById('mp-id').value = p.id;
            document.getElementById('mp-partner').textContent = p.business;
            document.getElementById('mp-amount').textContent = '₱' + Number(p.amount || 0).toLocaleString();
            document.getElementById('mp-method').textContent = (p.method || 'gcash').replace('_', ' ');
            document.getElementById('mp-ref').value = '';
            document.getElementById('markPaidModal').classList.replace('hidden', 'flex');
        };
        window.closeMarkPaidModal = function() {
            document.getElementById('markPaidModal').classList.replace('flex', 'hidden');
        };

        /* ============ Reject payout modal ============ */
        window.openRejectPayoutModal = function(p) {
            trackTrigger();
            document.getElementById('rp-id').value = p.id;
            document.getElementById('rp-partner').textContent = p.business;
            document.getElementById('rp-amount').textContent = '₱' + Number(p.amount || 0).toLocaleString();
            document.getElementById('rejectPayoutModal').classList.replace('hidden', 'flex');
        };
        window.closeRejectPayoutModal = function() {
            document.getElementById('rejectPayoutModal').classList.replace('flex', 'hidden');
        };

        /* ============ Batch creation modal ============ */
        window.openBatchModal = function() {
            const checks = Array.from(document.querySelectorAll('.row-check:checked'));
            if (checks.length === 0) return;

            trackTrigger();

            let sum = 0;
            const inputs = document.getElementById('bm-inputs');
            inputs.innerHTML = '';
            checks.forEach(cb => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = cb.value;
                inputs.appendChild(inp);
                sum += parseFloat(cb.dataset.amount || 0);
            });

            document.getElementById('bm-count').textContent = checks.length;
            document.getElementById('bm-total').textContent = '₱' + sum.toLocaleString();
            document.getElementById('batchModal').classList.replace('hidden', 'flex');
        };
        window.closeBatchModal = function() {
            document.getElementById('batchModal').classList.replace('flex', 'hidden');
        };

        /* ============ Process batch modal ============ */
        window.openProcessBatchModal = function(b) {
            trackTrigger();
            document.getElementById('pb-id').value = b.id;
            document.getElementById('pb-count').textContent = b.count;
            document.getElementById('pb-total').textContent = '₱' + Number(b.total || 0).toLocaleString();
            document.getElementById('processBatchModal').classList.replace('hidden', 'flex');
        };
        window.closeProcessBatchModal = function() {
            document.getElementById('processBatchModal').classList.replace('flex', 'hidden');
        };

        /* ============ Filter by batch ============ */
        window.filterBatch = function(batchId) {
            const params = new URLSearchParams(window.location.search);
            params.set('tab', 'payouts');
            params.set('po_batch', batchId);
            window.location.search = params.toString();
        };

        /* ============ Backdrop close ============ */
        const MODALS = [
            ['logSaleModal', 'closeLogSaleModal'],
            ['rejectSaleModal', 'closeRejectSaleModal'],
            ['markPaidModal', 'closeMarkPaidModal'],
            ['rejectPayoutModal', 'closeRejectPayoutModal'],
            ['batchModal', 'closeBatchModal'],
            ['processBatchModal', 'closeProcessBatchModal'],
        ];

        MODALS.forEach(([id, fn]) => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) window[fn]();
            });
        });
    })();
</script>