<?php

/**
 * Admin — Payments Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['payments']         — Payment::listWithFilters() rows
 *   $data['paymentSummary']   — statusSummary()
 *   $data['paymentAging']     — agingReport()
 *   $data['paymentMethods']   — methodConfig()
 *   $data['paymentRevenue7d'] — revenueDaily(7)
 *   $data['paymentFilters']   — active filter state
 */

use App\Models\Payment;

$payments  = $data['payments']         ?? [];
$summary   = $data['paymentSummary']   ?? [];
$aging     = $data['paymentAging']     ?? ['buckets' => [], 'rows' => []];
$methodCfg = $data['paymentMethods']   ?? ['accepted_methods' => [], 'collection_frequency' => 'monthly', 'grace_days' => 7];
$revenue7d = $data['paymentRevenue7d'] ?? [];
$filters   = $data['paymentFilters']   ?? [
    'status' => 'all',
    'method' => 'all',
    'tier' => 'all',
    'source' => '',
    'from' => '',
    'to' => '',
    'search' => '',
];

/* Status meta — SVG paths */
$statusMeta = [
    'paid'           => [
        'bg' => 'bg-green-500/10',
        'text' => 'text-green-500',
        'border' => 'border-green-500/20',
        'label' => 'Paid',
        'svg' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
    'pending'        => [
        'bg' => 'bg-amber-500/10',
        'text' => 'text-amber-500',
        'border' => 'border-amber-500/20',
        'label' => 'Pending',
        'svg' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
    'failed'         => [
        'bg' => 'bg-red-500/10',
        'text' => 'text-red-500',
        'border' => 'border-red-500/20',
        'label' => 'Failed',
        'svg' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
    'refunded'       => [
        'bg' => 'bg-purple-500/10',
        'text' => 'text-purple-400',
        'border' => 'border-purple-500/20',
        'label' => 'Refunded',
        'svg' => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'
    ],
    'partial_refund' => [
        'bg' => 'bg-purple-500/10',
        'text' => 'text-purple-400',
        'border' => 'border-purple-500/20',
        'label' => 'Partial Refund',
        'svg' => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'
    ],
];

/* Method meta — SVG paths */
$methodMeta = [
    'gcash'         => ['svg' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'label' => 'GCash'],
    'cash'          => ['svg' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'label' => 'Cash'],
    'credit_card'   => ['svg' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'label' => 'Card'],
    'bank_transfer' => ['svg' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z', 'label' => 'Bank'],
];

/* Query-string builder preserving current filters */
$qs = function (array $overrides = []) use ($filters): string {
    $merged = array_merge([
        'tab'        => 'payments',
        'pay_status' => $filters['status'] === 'all' ? '' : $filters['status'],
        'pay_method' => $filters['method'] === 'all' ? '' : $filters['method'],
        'pay_tier'   => $filters['tier']   === 'all' ? '' : $filters['tier'],
        'pay_source' => $filters['source'],
        'pay_from'   => $filters['from'],
        'pay_to'     => $filters['to'],
        'pay_q'      => $filters['search'],
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== 'all');
    return $merged ? '?' . http_build_query($merged) : '?tab=payments';
};
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Admin Panel</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                PAYMENTS
            </h1>
            <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                Confirm, refund, or fail payments. Every action is logged with a full audit trail.
            </p>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <!-- Total Revenue -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Revenue</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($summary['revenue_paid'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($summary['paid'] ?? 0) ?> paid transaction<?= ($summary['paid'] ?? 0) !== 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Today</div>
                    <div class="mt-2 font-heading text-3xl text-white"><?= peso($summary['today'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        Week <span class="text-white"><?= peso($summary['this_week'] ?? 0) ?></span>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="group relative overflow-hidden rounded-2xl border <?= ($summary['pending'] ?? 0) > 0 ? 'border-amber-500/20 bg-amber-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest <?= ($summary['pending'] ?? 0) > 0 ? 'text-amber-500' : 'text-gray-500' ?>">
                        <?php if (($summary['pending'] ?? 0) > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <?php endif; ?>
                        Pending
                    </div>
                    <div class="mt-2 font-heading text-3xl <?= ($summary['pending'] ?? 0) > 0 ? 'text-amber-500' : 'text-gray-600' ?>">
                        <?= (int)($summary['pending'] ?? 0) ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= peso($summary['revenue_pending'] ?? 0) ?> awaiting
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl <?= ($summary['pending'] ?? 0) > 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-white/5 text-gray-500' ?> transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Success Rate</div>
                    <?php
                    $sr = (float)($summary['success_rate'] ?? 0);
                    $srColor = $sr >= 90 ? 'text-green-500' : ($sr >= 70 ? 'text-amber-500' : 'text-red-500');
                    ?>
                    <div class="mt-2 font-heading text-3xl <?= $srColor ?>"><?= number_format($sr, 1) ?>%</div>
                    <div class="mt-1 flex items-center gap-1 text-xs text-gray-400">
                        <span><?= (int)($summary['failed'] ?? 0) ?> failed</span>
                        <?php if (($summary['failed'] ?? 0) > 0): ?>
                            <span class="text-red-500">· <?= number_format($summary['failure_rate'] ?? 0, 1) ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Refunded -->
        <div class="group relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Refunded</div>
                    <div class="mt-2 font-heading text-3xl text-purple-400"><?= peso($summary['revenue_refunded'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($summary['refunded'] ?? 0) + (int)($summary['partial_refund'] ?? 0) ?>
                        refund<?= ((int)($summary['refunded'] ?? 0) + (int)($summary['partial_refund'] ?? 0)) !== 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         AGING REPORT
         ============================================================ -->
    <?php
    $agingHasData = false;
    foreach (($aging['buckets'] ?? []) as $b) {
        if (($b['count'] ?? 0) > 0) {
            $agingHasData = true;
            break;
        }
    }
    ?>

    <?php if ($agingHasData): ?>
        <div class="rounded-2xl border border-amber-500/20 bg-gradient-to-br from-amber-500/5 to-transparent p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">PENDING PAYMENT AGING</h3>
                    <p class="mt-1 text-xs text-gray-400">Outstanding balances bucketed by days overdue</p>
                </div>
                <a href="<?= url_e('/admin-dashboard/payments/aging') ?>"
                    class="tap-effect inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-xs font-bold text-amber-500 transition-all hover:-translate-y-0.5 hover:bg-amber-500/20"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export aging report
                </a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <?php
                $bucketMeta = [
                    'current' => [
                        'label' => 'Current',
                        'color' => 'green',
                        'svg' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                    ],
                    '1_7'     => [
                        'label' => '1–7 days',
                        'color' => 'lime',
                        'svg' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'
                    ],
                    '8_14'    => [
                        'label' => '8–14 days',
                        'color' => 'amber',
                        'svg' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
                    ],
                    '15_30'   => [
                        'label' => '15–30 days',
                        'color' => 'orange',
                        'svg' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                    ],
                    '30_plus' => [
                        'label' => '30+ days',
                        'color' => 'red',
                        'svg' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                    ],
                ];
                $colorClasses = [
                    'green'  => ['card' => 'border-green-500/20 bg-green-500/5',   'text' => 'text-green-500'],
                    'lime'   => ['card' => 'border-lime-500/20 bg-lime-500/5',     'text' => 'text-lime-500'],
                    'amber'  => ['card' => 'border-amber-500/20 bg-amber-500/5',   'text' => 'text-amber-500'],
                    'orange' => ['card' => 'border-orange-500/20 bg-orange-500/5', 'text' => 'text-orange-500'],
                    'red'    => ['card' => 'border-red-500/20 bg-red-500/5',       'text' => 'text-red-500'],
                ];
                foreach ($bucketMeta as $key => $meta):
                    $bucket = $aging['buckets'][$key] ?? ['count' => 0, 'total' => 0.0];
                    $count  = (int)($bucket['count'] ?? 0);
                    $total  = (float)($bucket['total'] ?? 0);
                    $active = $count > 0;
                    $cls    = $colorClasses[$meta['color']];
                ?>
                    <div class="group relative overflow-hidden rounded-xl border <?= $active ? $cls['card'] : 'border-white/5 bg-white/[0.02]' ?> p-4 transition-all hover:-translate-y-1 hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] font-bold uppercase tracking-widest <?= $active ? $cls['text'] : 'text-gray-500' ?>">
                                <?= e($meta['label']) ?>
                            </div>
                            <svg class="w-4 h-4 <?= $active ? $cls['text'] : 'text-gray-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $meta['svg'] ?>" />
                            </svg>
                        </div>
                        <div class="mt-2 font-heading text-3xl <?= $active ? $cls['text'] : 'text-gray-600' ?>"><?= $count ?></div>
                        <div class="mt-0.5 text-xs text-gray-400"><?= peso($total) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         7-DAY REVENUE CHART
         ============================================================ -->
    <?php if (!empty($revenue7d)): ?>
        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">REVENUE — LAST 7 DAYS</h3>
                    <p class="mt-1 text-xs text-gray-400">Daily paid totals</p>
                </div>
                <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    <?= peso(array_sum(array_column($revenue7d, 'total'))) ?>
                </span>
            </div>
            <div class="mt-4 h-44"><canvas id="payment7dChart" role="img" aria-label="Line chart of daily revenue for the last 7 days"></canvas></div>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         FILTER BAR
         ============================================================ -->
    <form method="get" action="<?= url_e('/admin-dashboard') ?>" id="paymentFilterForm"
        class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-4">

        <input type="hidden" name="tab" value="payments">

        <?= a11y_filter_open('Filter payments by status, method, tier, source, and date range') ?>

        <!-- Status pills -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status:</span>

            <?php
            $statusFilters = [
                ['all',            'All',            (int)($summary['total'] ?? 0),          'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['paid',           'Paid',           (int)($summary['paid'] ?? 0),           'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['pending',        'Pending',        (int)($summary['pending'] ?? 0),        'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['failed',         'Failed',         (int)($summary['failed'] ?? 0),         'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['refunded',       'Refunded',       (int)($summary['refunded'] ?? 0),       'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
                ['partial_refund', 'Partial Refund', (int)($summary['partial_refund'] ?? 0), 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
            ];
            foreach ($statusFilters as [$key, $label, $count, $icon]):
                $isActive = ($filters['status'] === $key || ($key === 'all' && $filters['status'] === 'all'));
            ?>
                <a href="<?= url_e('/admin-dashboard' . $qs(['pay_status' => $key === 'all' ? '' : $key])) ?>"
                    aria-current="<?= $isActive ? 'true' : 'false' ?>"
                    class="filter-pill flex items-center gap-2 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-4 py-2 text-sm font-semibold transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                    <span class="rounded-full <?= $isActive ? 'bg-black/25 text-white' : 'bg-black/30 text-gray-400' ?> px-2 py-0.5 text-[10px] font-bold"><?= $count ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Method + Tier + Source pills -->
        <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-white/5">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Method:</span>

            <?php
            $methodFilters = [
                ['all',           'All',    'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['gcash',         'GCash',  'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                ['cash',          'Cash',   'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['credit_card',   'Card',   'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['bank_transfer', 'Bank',   'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
            ];
            foreach ($methodFilters as [$key, $label, $icon]):
                $isActive = ($filters['method'] === $key || ($key === 'all' && $filters['method'] === 'all'));
            ?>
                <a href="<?= url_e('/admin-dashboard' . $qs(['pay_method' => $key === 'all' ? '' : $key])) ?>"
                    aria-current="<?= $isActive ? 'true' : 'false' ?>"
                    class="filter-pill flex items-center gap-1.5 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                </a>
            <?php endforeach; ?>

            <span class="ml-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Tier:</span>

            <?php
            $tierFilters = [
                ['all',      'All',      'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['digital',  'Digital',  'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                ['standard', 'Standard', 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['premium',  'Premium',  'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                ['b2b',      'B2B',      'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ];
            foreach ($tierFilters as [$key, $label, $icon]):
                $isActive = ($filters['tier'] === $key || ($key === 'all' && $filters['tier'] === 'all'));
            ?>
                <a href="<?= url_e('/admin-dashboard' . $qs(['pay_tier' => $key === 'all' ? '' : $key])) ?>"
                    aria-current="<?= $isActive ? 'true' : 'false' ?>"
                    class="filter-pill flex items-center gap-1.5 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                </a>
            <?php endforeach; ?>

            <span class="ml-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Source:</span>

            <?php
            $sourceFilters = [
                ['',     'All',  'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['b2c',  'B2C',  'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['b2b',  'B2B',  'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ];
            foreach ($sourceFilters as [$key, $label, $icon]):
                $active = ($key === '' && ($filters['source'] ?? '') === '')
                    || ($key !== '' && $filters['source'] === $key);
            ?>
                <a href="<?= url_e('/admin-dashboard' . $qs(['pay_source' => $key])) ?>"
                    aria-current="<?= $active ? 'true' : 'false' ?>"
                    class="filter-pill flex items-center gap-1.5 rounded-full border <?= $active ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Date + search + actions -->
        <div class="grid gap-3 pt-3 border-t border-white/5 lg:grid-cols-[1fr_1fr_2fr_auto_auto_auto]">

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">From</span>
                <input type="date" name="pay_from" value="<?= e($filters['from']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">To</span>
                <input type="date" name="pay_to" value="<?= e($filters['to']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Search</span>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="pay_q" value="<?= e($filters['search']) ?>"
                        placeholder="Member, description, reference…"
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>
            </label>

            <div class="flex items-end">
                <button type="submit"
                    class="tap-effect inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#00A6A6] px-6 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
            </div>

            <div class="flex items-end">
                <a href="<?= url_e('/admin-dashboard/payments/export' . $qs()) ?>"
                    class="tap-effect inline-flex h-11 items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </a>
            </div>

            <div class="flex items-end">
                <form method="post" action="<?= url_e('/admin-dashboard/payments/send-reminders') ?>" class="contents"
                    onsubmit="return confirm('Send payment reminders to all members with pending balances?')">
                    <?= csrf_field() ?>
                    <button type="submit"
                        class="tap-effect inline-flex h-11 items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 text-xs font-bold text-amber-500 transition-all hover:-translate-y-0.5 hover:bg-amber-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Reminders
                    </button>
                </form>
            </div>
        </div>

        <?= a11y_filter_close() ?>

        <?php if (
            $filters['search'] || $filters['from'] || $filters['to'] ||
            $filters['status'] !== 'all' || $filters['method'] !== 'all' ||
            $filters['tier'] !== 'all' || $filters['source'] !== ''
        ): ?>
            <div class="flex items-center justify-between pt-3 border-t border-white/5 text-xs">
                <span class="text-gray-400" <?= a11y_live_region('polite') ?>>
                    <strong class="text-white"><?= count($payments) ?></strong> result<?= count($payments) !== 1 ? 's' : '' ?> match the active filters
                </span>
                <a href="<?= url_e('/admin-dashboard?tab=payments') ?>"
                    class="font-bold text-[#00A6A6] transition-colors hover:text-white">
                    Clear filters
                </a>
            </div>
        <?php endif; ?>
    </form>

    <!-- ============================================================
         BULK BAR
         ============================================================ -->
    <div id="bulkBar"
        role="region" aria-label="Bulk payment actions"
        class="hidden items-center justify-between rounded-2xl border border-[#00A6A6]/30 bg-[#00A6A6]/5 p-4"
        style="animation: slide-down 0.3s ease-out;">
        <div class="flex items-center gap-3">
            <span id="bulkCount" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#00A6A6] text-sm font-bold text-white"
                <?= a11y_live_region('polite') ?>>0</span>
            <span class="text-sm font-semibold text-gray-300">pending payment(s) selected</span>
            <span id="bulkTotal" class="text-xs text-gray-400"></span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="clearSelection()"
                class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-colors hover:bg-white/10 hover:text-white">
                Clear
            </button>
            <form method="post" action="<?= url_e('/admin-dashboard/payments/bulk-confirm') ?>" id="bulkForm" class="contents"
                onsubmit="return confirm('Confirm all selected payments? This will also activate their matching enrollments.')">
                <?= csrf_field() ?>
                <div id="bulkInputs"></div>
                <button type="submit"
                    class="tap-effect inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-5 py-2 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Confirm Selected
                </button>
            </form>
        </div>
    </div>

    <!-- ============================================================
         TABLE
         ============================================================ -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <caption class="sr-only">Payment ledger with status, method, tier, and confirm/refund actions</caption>
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.02]">
                        <th scope="col" class="w-12 p-4">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                                class="h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6] cursor-pointer"
                                aria-label="Select all confirmable payments">
                        </th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Member</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden md:table-cell">Date</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Description</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Tier</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Method</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="paymentRows">
                    <?php foreach ($payments as $idx => $p):
                        $status = $p['status'] ?? 'pending';
                        $sm     = $statusMeta[$status] ?? $statusMeta['pending'];
                        $method = $p['method'] ?? 'gcash';
                        $mm     = $methodMeta[$method] ?? $methodMeta['gcash'];
                        $isPending = $status === 'pending';
                        $isFailed  = $status === 'failed';
                        $canRefund = in_array($status, ['paid', 'partial_refund'], true);
                        $refunded  = (float)($p['refund_amount'] ?? 0);
                        $agingDays = (int)($p['aging_days'] ?? 0);
                        $dueUrgency = $p['due_urgency'] ?? 'n/a';
                    ?>
                        <tr class="payment-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                            data-status="<?= e($status) ?>"
                            data-method="<?= e($method) ?>"
                            data-tier="<?= e($p['tier'] ?? '') ?>"
                            data-search="<?= e(strtolower(($p['user_name'] ?? '') . ' ' . ($p['description'] ?? '') . ' ' . ($p['reference_no'] ?? ''))) ?>"
                            style="animation: row-enter 0.35s ease-out <?= min($idx * 15, 400) ?>ms backwards;">

                            <!-- Checkbox -->
                            <td class="p-4">
                                <?php if ($isPending || $isFailed): ?>
                                    <input type="checkbox" class="row-check h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6] cursor-pointer"
                                        value="<?= e($p['id']) ?>"
                                        data-name="<?= e($p['user_name'] ?? 'Member') ?>"
                                        data-amount="<?= e($p['amount'] ?? 0) ?>"
                                        onchange="updateBulkBar()"
                                        aria-label="Select payment from <?= e($p['user_name'] ?? 'member') ?>">
                                <?php else: ?>
                                    <span class="block h-4 w-4"></span>
                                <?php endif; ?>
                            </td>

                            <!-- Member -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white shadow-md"
                                        aria-hidden="true">
                                        <?= e(initials($p['user_name'] ?? '?')) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-white"><?= e($p['user_name'] ?? 'Unknown') ?></div>
                                        <?php if (!empty($p['receipt_no'])): ?>
                                            <div class="truncate font-mono text-[10px] text-gray-500"><?= e($p['receipt_no']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Date -->
                            <td class="p-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-white"><?= e(short_date($p['paid_date'] ?? null)) ?></div>
                                <?php if ($agingDays > 0 && ($isPending || $isFailed)): ?>
                                    <div class="text-[10px] font-bold uppercase <?= $dueUrgency === 'overdue' ? 'text-red-500' : 'text-amber-500' ?>">
                                        <?= $agingDays ?>d overdue
                                    </div>
                                <?php elseif (!empty($p['due_date']) && ($isPending || $isFailed)): ?>
                                    <div class="text-[10px] text-gray-500">due <?= e(short_date($p['due_date'])) ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Description -->
                            <td class="p-4 max-w-xs hidden xl:table-cell">
                                <div class="truncate text-sm text-gray-300" title="<?= e($p['description'] ?? '') ?>">
                                    <?= e($p['description'] ?? '—') ?>
                                </div>
                                <?php if (!empty($p['reference_no'])): ?>
                                    <div class="truncate font-mono text-[10px] text-gray-500">ref: <?= e($p['reference_no']) ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Tier -->
                            <td class="p-4 hidden lg:table-cell">
                                <?php if (!empty($p['tier'])): ?>
                                    <span class="rounded-full bg-[#00A6A6]/10 border border-[#00A6A6]/20 px-2.5 py-0.5 text-[10px] font-bold uppercase text-[#00A6A6]">
                                        <?= e($p['tier']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Method -->
                            <td class="p-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-bold uppercase text-gray-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $mm['svg'] ?>" />
                                    </svg>
                                    <?= e(strtoupper($mm['label'])) ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="p-4">
                                <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $sm['bg'] ?> <?= $sm['text'] ?> <?= $sm['border'] ?> px-3 py-1 text-[10px] font-bold uppercase">
                                    <?php if ($isPending): ?>
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                    <?php else: ?>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $sm['svg'] ?>" />
                                        </svg>
                                    <?php endif; ?>
                                    <?= e($sm['label']) ?>
                                </span>
                                <?php if ($refunded > 0 && $status !== 'refunded'): ?>
                                    <div class="mt-1 text-[10px] font-bold text-purple-400">
                                        <?= peso($refunded) ?> refunded
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Amount -->
                            <td class="p-4 text-right whitespace-nowrap">
                                <div class="font-heading text-lg <?= $isPending ? 'text-amber-500' : ($isFailed ? 'text-red-500' : 'text-white') ?>">
                                    <?= peso($p['amount'] ?? 0) ?>
                                </div>
                                <?php if ($refunded > 0): ?>
                                    <div class="text-[10px] text-purple-400">
                                        net <?= peso((float)($p['amount'] ?? 0) - $refunded) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="p-4 text-right">
                                <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">

                                    <?php if ($isPending): ?>
                                        <form method="post" action="<?= url_e('/admin-dashboard/payments/confirm') ?>" class="inline"
                                            onsubmit="return confirm('Confirm this payment?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                            <button type="submit"
                                                aria-label="Confirm payment from <?= e($p['user_name'] ?? 'member') ?>"
                                                class="rounded-full bg-[#00A6A6] px-3 py-1.5 text-xs font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                                                <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Confirm
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($isPending || $isFailed): ?>
                                        <button type="button"
                                            onclick='openFailModal(<?= json_encode(["id" => $p["id"], "name" => $p["user_name"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Mark payment from <?= e($p['user_name'] ?? 'member') ?> as failed"
                                            title="Mark as failed"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-red-500 hover:text-red-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($canRefund): ?>
                                        <button type="button"
                                            onclick='openRefundModal(<?= json_encode(["id" => $p["id"], "name" => $p["user_name"], "amount" => $p["amount"], "refunded" => $p["refund_amount"] ?? 0], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Refund payment from <?= e($p['user_name'] ?? 'member') ?>"
                                            title="Refund"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-purple-500 hover:text-purple-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <a href="<?= url_e('/admin-dashboard/payments/receipt?id=' . urlencode($p['id'])) ?>"
                                        target="_blank" rel="noopener"
                                        aria-label="View receipt for <?= e($p['user_name'] ?? 'member') ?>"
                                        title="View receipt"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>

                                    <button type="button"
                                        onclick='openPaymentModal(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        aria-label="View payment details for <?= e($p['user_name'] ?? 'member') ?>"
                                        title="View details"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="9" class="p-16 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm text-gray-400">No payments match your filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="noPayResults" class="hidden p-12 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <p class="mt-4 text-sm text-gray-400">No payments match your filters.</p>
        </div>
    </div>

    <!-- ============================================================
         PAYMENT CONFIG INFO
         ============================================================ -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-white">Payment Configuration</div>
                <div class="mt-1 text-xs text-gray-400">
                    Accepted: <strong class="text-white"><?= implode(', ', array_map([Payment::class, 'methodLabel'], $methodCfg['accepted_methods'] ?? [])) ?: '—' ?></strong>
                    · Collection: <strong class="text-white"><?= e(ucfirst($methodCfg['collection_frequency'] ?? 'monthly')) ?></strong>
                    · Grace: <strong class="text-white"><?= (int)($methodCfg['grace_days'] ?? 7) ?>d</strong>
                </div>
            </div>
        </div>
        <a href="<?= url_e('/admin-dashboard?tab=settings') ?>"
            class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-xs font-bold text-white transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Configure
        </a>
    </div>

    <!-- ============================================================
         PAYMENT DETAIL MODAL
         ============================================================ -->
    <div id="paymentModal" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-lg max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="flex items-center justify-between border-b border-white/5 p-6">
                <div>
                    <h2 id="paymentModalTitle" class="font-heading text-2xl tracking-wide text-white">PAYMENT DETAILS</h2>
                    <p class="mt-1 text-xs text-gray-400">Full transaction info</p>
                </div>
                <button type="button" onclick="closePaymentModal()"
                    aria-label="Close payment details dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div id="pm-hero" class="rounded-2xl bg-gradient-to-br from-[#00A6A6] to-[#008c8c] p-6 text-center text-white shadow-lg">
                    <div class="text-[10px] font-bold uppercase tracking-widest opacity-80">Amount</div>
                    <div id="pm-amount" class="mt-1 font-heading text-5xl">₱0</div>
                    <div id="pm-status" class="mt-3 inline-block rounded-full bg-white/20 px-4 py-1 text-[10px] font-bold uppercase">
                        Pending
                    </div>
                    <div id="pm-refunded-block" class="mt-3 hidden rounded-lg bg-white/15 px-3 py-1.5 text-xs">
                        Refunded: <strong id="pm-refunded-amt">₱0</strong>
                    </div>
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Member</dt>
                        <dd id="pm-user" class="font-semibold text-white">—</dd>
                    </div>
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Description</dt>
                        <dd id="pm-desc" class="text-right font-medium text-gray-300">—</dd>
                    </div>
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Tier</dt>
                        <dd id="pm-tier" class="font-medium text-white">—</dd>
                    </div>
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Source</dt>
                        <dd id="pm-source" class="font-medium text-white">—</dd>
                    </div>
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Method</dt>
                        <dd id="pm-method" class="font-medium text-white">—</dd>
                    </div>
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Paid Date</dt>
                        <dd id="pm-date" class="font-medium text-white">—</dd>
                    </div>
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Due Date</dt>
                        <dd id="pm-due" class="font-medium text-white">—</dd>
                    </div>
                    <div class="flex justify-between border-b border-white/5 py-2">
                        <dt class="text-gray-400">Reference</dt>
                        <dd id="pm-ref" class="font-mono text-xs text-gray-300">—</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-400">Receipt No.</dt>
                        <dd id="pm-receipt" class="font-mono text-xs text-gray-300">—</dd>
                    </div>
                </dl>

                <div id="pm-refund-detail" class="hidden rounded-xl border border-purple-500/20 bg-purple-500/5 p-4">
                    <div class="text-xs font-bold uppercase tracking-widest text-purple-400">Refund Info</div>
                    <div id="pm-refund-info" class="mt-1.5 text-xs text-gray-300"></div>
                </div>

                <div id="pm-failed-detail" class="hidden rounded-xl border border-red-500/20 bg-red-500/5 p-4">
                    <div class="text-xs font-bold uppercase tracking-widest text-red-500">Failure Reason</div>
                    <div id="pm-failed-info" class="mt-1.5 text-xs text-gray-300"></div>
                </div>

                <div id="pm-actions" class="pt-2"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         REFUND MODAL
         ============================================================ -->
    <div id="refundModal" role="dialog" aria-modal="true" aria-labelledby="refundModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-purple-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-purple-500/10 text-purple-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </div>
                <h2 id="refundModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">ISSUE REFUND</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Refund <strong id="refund-name" class="text-white">this member</strong>'s payment.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/payments/refund') ?>" class="mt-6 space-y-4" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="refund-id">

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-3">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Original</div>
                        <div id="refund-original" class="mt-0.5 font-heading text-xl text-white">₱0</div>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-3">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Remaining</div>
                        <div id="refund-remaining" class="mt-0.5 font-heading text-xl text-[#00A6A6]">₱0</div>
                    </div>
                </div>

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Refund Amount <span class="text-[#D50000]">*</span>
                    </span>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-500" aria-hidden="true">₱</span>
                        <input type="number" name="refund_amount" id="refund-amount" min="0.01" step="0.01" required
                            inputmode="decimal"
                            data-validate="required|positive"
                            data-error-label="Refund amount"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-9 pr-16 text-base font-bold text-white outline-none transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20">
                        <button type="button" onclick="fillMaxRefund()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-purple-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-purple-400 transition-all hover:bg-purple-500 hover:text-white">
                            MAX
                        </button>
                    </div>
                </label>

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Reason <span class="text-[#D50000]">*</span>
                    </span>
                    <textarea name="refund_reason" id="refund-reason" rows="3" maxlength="255" required
                        data-validate="required"
                        data-error-label="Refund reason"
                        placeholder="e.g. Duplicate payment — refunding overpayment"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"></textarea>
                    <span class="mt-2 block text-[11px] text-gray-500">Recorded on the payment row and in the audit log.</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeRefundModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(147,51,234,0.4)] transition-all hover:-translate-y-0.5 hover:bg-purple-700">
                        Issue Refund
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         MARK-FAILED MODAL
         ============================================================ -->
    <div id="failModal" role="dialog" aria-modal="true" aria-labelledby="failModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-red-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 id="failModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">MARK AS FAILED</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Mark <strong id="fail-name" class="text-white">this payment</strong> as failed.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/payments/mark-failed') ?>" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="fail-id">

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Reason</span>
                    <textarea name="reason" id="fail-reason" rows="3" maxlength="255"
                        placeholder="e.g. GCash reference not found, invalid amount, duplicate"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-red-500 focus:ring-2 focus:ring-red-500/20"></textarea>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeFailModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-red-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all hover:-translate-y-0.5 hover:bg-red-600">
                        Mark Failed
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

        .payment-row,
        #paymentModal>div,
        #refundModal>div,
        #failModal>div {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        /* ============ 7-day revenue chart ============ */
        const REVENUE_7D = <?= json_encode($revenue7d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function initRevenueChart() {
            const canvas = document.getElementById('payment7dChart');
            if (!canvas || !REVENUE_7D || REVENUE_7D.length === 0) return;

            const isDark = document.documentElement.classList.contains('dark');
            const tickClr = isDark ? '#94a3b8' : '#4b5563';
            const gridClr = isDark ? '#334155' : '#e5e7eb';

            const ctx = canvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 200);
            grad.addColorStop(0, 'rgba(0,166,166,0.35)');
            grad.addColorStop(1, 'rgba(0,166,166,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: REVENUE_7D.map(d => {
                        const dt = new Date(d.date + 'T00:00:00');
                        return dt.toLocaleDateString('en', {
                            weekday: 'short',
                            month: 'short',
                            day: 'numeric'
                        });
                    }),
                    datasets: [{
                        data: REVENUE_7D.map(d => Number(d.total || 0)),
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
                        duration: 800,
                        easing: 'easeOutQuart'
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

        /* ============ Bulk selection ============ */
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
            const inputs = document.getElementById('bulkInputs');

            if (checks.length === 0) {
                bar.classList.add('hidden');
                bar.classList.remove('flex');
                return;
            }

            bar.classList.remove('hidden');
            bar.classList.add('flex');
            if (count) count.textContent = checks.length;

            let sum = 0;
            inputs.innerHTML = '';
            checks.forEach(cb => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = cb.value;
                inputs.appendChild(inp);
                sum += parseFloat(cb.dataset.amount || 0);
            });

            if (total) total.textContent = '· Total ₱' + sum.toLocaleString();
        };

        /* ============ Payment detail modal ============ */
        const STATUS_META = {
            paid: {
                bg: 'bg-green-500/10',
                text: 'text-green-500',
                label: 'Paid'
            },
            pending: {
                bg: 'bg-amber-500/10',
                text: 'text-amber-500',
                label: 'Pending'
            },
            failed: {
                bg: 'bg-red-500/10',
                text: 'text-red-500',
                label: 'Failed'
            },
            refunded: {
                bg: 'bg-purple-500/10',
                text: 'text-purple-400',
                label: 'Refunded'
            },
            partial_refund: {
                bg: 'bg-purple-500/10',
                text: 'text-purple-400',
                label: 'Partial Refund'
            }
        };

        window.openPaymentModal = function(p) {
            if (!p) return;

            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }

            document.getElementById('pm-amount').textContent = '₱' + Number(p.amount || 0).toLocaleString();
            document.getElementById('pm-user').textContent = p.user_name || '—';
            document.getElementById('pm-desc').textContent = p.description || '—';
            document.getElementById('pm-tier').textContent = (p.tier || '—').toUpperCase();
            document.getElementById('pm-source').textContent = (p.source || '—').toUpperCase();
            document.getElementById('pm-method').textContent = (p.method || '—').toUpperCase();
            document.getElementById('pm-date').textContent = p.paid_date || '—';
            document.getElementById('pm-due').textContent = p.due_date || '—';
            document.getElementById('pm-ref').textContent = p.reference_no || '—';
            document.getElementById('pm-receipt').textContent = p.receipt_no || '—';

            const meta = STATUS_META[p.status] || STATUS_META.pending;
            const statusEl = document.getElementById('pm-status');
            statusEl.textContent = meta.label;
            statusEl.className = 'mt-3 inline-block rounded-full px-4 py-1 text-[10px] font-bold uppercase ' + meta.bg + ' ' + meta.text;

            const refAmt = parseFloat(p.refund_amount || 0);
            const refBlock = document.getElementById('pm-refunded-block');
            if (refAmt > 0) {
                refBlock.classList.remove('hidden');
                document.getElementById('pm-refunded-amt').textContent = '₱' + refAmt.toLocaleString();
            } else {
                refBlock.classList.add('hidden');
            }

            const refDetail = document.getElementById('pm-refund-detail');
            if (refAmt > 0 && p.refund_reason) {
                refDetail.classList.remove('hidden');
                document.getElementById('pm-refund-info').textContent =
                    '₱' + refAmt.toLocaleString() + ' — ' + p.refund_reason +
                    (p.refund_date ? ' (' + p.refund_date + ')' : '');
            } else {
                refDetail.classList.add('hidden');
            }

            const failDetail = document.getElementById('pm-failed-detail');
            if (p.status === 'failed' && p.failed_reason) {
                failDetail.classList.remove('hidden');
                document.getElementById('pm-failed-info').textContent = p.failed_reason;
            } else {
                failDetail.classList.add('hidden');
            }

            const actionsEl = document.getElementById('pm-actions');
            const actions = [];
            if (p.status === 'pending') {
                actions.push(`
                <form method="post" action="<?= url_e('/admin-dashboard/payments/confirm') ?>" class="inline-block w-full">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="${p.id}">
                    <button type="submit" class="w-full rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
                        Confirm Payment
                    </button>
                </form>`);
            }
            if (p.status === 'paid' || p.status === 'partial_refund') {
                actions.push(`
                <button type="button" onclick='closePaymentModal(); openRefundModal(${JSON.stringify({id: p.id, name: p.user_name, amount: p.amount, refunded: p.refund_amount || 0}).replace(/'/g, "&#39;")})'
                    class="w-full rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(147,51,234,0.4)] transition-all hover:-translate-y-0.5 hover:bg-purple-700">
                    Issue Refund
                </button>`);
            }
            actions.push(`
            <a href="<?= url_e('/admin-dashboard/payments/receipt') ?>?id=${p.id}" target="_blank" rel="noopener"
                class="block w-full rounded-full border border-white/10 bg-white/5 px-5 py-3 text-center text-sm font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                Print Receipt
            </a>`);

            actionsEl.innerHTML = '<div class="space-y-2">' + actions.join('') + '</div>';

            document.getElementById('paymentModal').classList.replace('hidden', 'flex');
        };

        window.closePaymentModal = function() {
            document.getElementById('paymentModal').classList.replace('flex', 'hidden');
        };

        /* ============ Refund modal ============ */
        let _refundRemaining = 0;

        window.openRefundModal = function(p) {
            if (!p) return;

            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }

            const original = parseFloat(p.amount || 0);
            const refunded = parseFloat(p.refunded || 0);
            _refundRemaining = Math.max(0, original - refunded);

            document.getElementById('refund-id').value = p.id;
            document.getElementById('refund-name').textContent = p.name || 'this member';
            document.getElementById('refund-original').textContent = '₱' + original.toLocaleString();
            document.getElementById('refund-remaining').textContent = '₱' + _refundRemaining.toLocaleString();
            document.getElementById('refund-amount').value = '';
            document.getElementById('refund-amount').max = _refundRemaining;
            document.getElementById('refund-reason').value = '';

            document.getElementById('refundModal').classList.replace('hidden', 'flex');
        };

        window.closeRefundModal = function() {
            document.getElementById('refundModal').classList.replace('flex', 'hidden');
        };

        window.fillMaxRefund = function() {
            const input = document.getElementById('refund-amount');
            input.value = Math.floor(_refundRemaining * 100) / 100;
            input.focus();
        };

        /* ============ Mark-failed modal ============ */
        window.openFailModal = function(p) {
            if (!p) return;

            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }

            document.getElementById('fail-id').value = p.id;
            document.getElementById('fail-name').textContent = p.name || 'this payment';
            document.getElementById('fail-reason').value = '';
            document.getElementById('failModal').classList.replace('hidden', 'flex');
        };

        window.closeFailModal = function() {
            document.getElementById('failModal').classList.replace('flex', 'hidden');
        };

        /* ============ Backdrop close ============ */
        const MODALS = [
            ['paymentModal', 'closePaymentModal'],
            ['refundModal', 'closeRefundModal'],
            ['failModal', 'closeFailModal'],
        ];

        MODALS.forEach(([id, fn]) => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) window[fn]();
            });
        });

        /* ============ Boot ============ */
        document.addEventListener('DOMContentLoaded', () => {
            initRevenueChart();
        });
    })();
</script>