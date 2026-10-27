<?php

/**
 * Admin — Partners Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['partners']             — PartnerProfile::listWithMetrics()
 *   $data['partnerSummary']       — summary()
 *   $data['partnerAcquisition']   — acquisitionTrend(12)
 *   $data['partnerTypeBreak']     — typeBreakdown()
 *   $data['partnerTop']           — topPerformers(8)
 *   $data['partnerB2bClients']    — B2BClient::listWithFilters()
 *   $data['b2bSummary']           — B2BClient::summary()
 *   $data['b2bByPartner']         — countsByPartner()
 *   $data['partnerFilters']       — active filter state
 *   $data['clientFilters']        — active filter state
 */

use App\Models\PartnerProfile;
use App\Models\B2BClient;

$partners       = $data['partners']           ?? [];
$summary        = $data['partnerSummary']     ?? [];
$acquisition    = $data['partnerAcquisition'] ?? [];
$typeBreak      = $data['partnerTypeBreak']   ?? [];
$topPartners    = $data['partnerTop']         ?? [];
$clients        = $data['partnerB2bClients']  ?? [];
$b2bSummary     = $data['b2bSummary']         ?? ['total' => 0, 'linked' => 0, 'pending' => 0, 'inactive' => 0];
$b2bByPartner   = $data['b2bByPartner']       ?? [];
$pFilters       = $data['partnerFilters']     ?? ['status' => 'all', 'type' => 'all', 'search' => ''];
$cFilters       = $data['clientFilters']      ?? ['partner_user_id' => '', 'status' => 'all', 'search' => ''];

/* Chart data */
$partnerLabels   = [];
$partnerRevenues = [];
foreach ($topPartners as $t) {
    $partnerLabels[]   = $t['business_name'];
    $partnerRevenues[] = $t['revenue'];
}

$typeLabels   = [];
$typeCounts   = [];
$typeRevenues = [];
foreach ($typeBreak as $t) {
    $typeLabels[]   = $t['label'];
    $typeCounts[]   = $t['count'];
    $typeRevenues[] = $t['revenue'];
}

/* Query-string builders */
$qsP = function (array $overrides = []) use ($pFilters): string {
    $merged = array_merge([
        'tab'      => 'partners',
        'p_status' => $pFilters['status'] === 'all' ? '' : $pFilters['status'],
        'p_type'   => $pFilters['type']   === 'all' ? '' : $pFilters['type'],
        'p_q'      => $pFilters['search'],
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== 'all');
    return $merged ? '?' . http_build_query($merged) : '?tab=partners';
};

$qsC = function (array $overrides = []) use ($cFilters): string {
    $merged = array_merge([
        'tab'       => 'partners',
        'c_partner' => $cFilters['partner_user_id'],
        'c_status'  => $cFilters['status'] === 'all' ? '' : $cFilters['status'],
        'c_q'       => $cFilters['search'],
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== 'all');
    return $merged ? '?' . http_build_query($merged) : '?tab=partners';
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
                PARTNERS
            </h1>
            <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                Manage partner accounts, referral codes, commission rates, and B2B client relationships.
            </p>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <!-- Partners -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Partners</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]"><?= (int)($summary['total'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($summary['coaches'] ?? 0) ?> coaches · <?= (int)($summary['suppliers'] ?? 0) ?> suppliers
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/20 bg-green-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        Active
                    </div>
                    <div class="mt-2 font-heading text-4xl text-green-500"><?= (int)($summary['active'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400"><?= (int)($summary['inactive'] ?? 0) ?> inactive</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Churned -->
        <div class="group relative overflow-hidden rounded-2xl border <?= ($summary['churned'] ?? 0) > 0 ? 'border-red-500/20 bg-red-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-red-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-red-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest <?= ($summary['churned'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-500' ?>">Churned (60d)</div>
                    <div class="mt-2 font-heading text-4xl <?= ($summary['churned'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-600' ?>">
                        <?= (int)($summary['churned'] ?? 0) ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">No sale in 60+ days</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Partner Revenue -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Partner Revenue</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($summary['total_revenue'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($summary['total_referrals'] ?? 0) ?> referrals · <?= (int)($summary['linked_clients'] ?? 0) ?> linked
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Commission -->
        <div class="group relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Commission Earned</div>
                    <div class="mt-2 font-heading text-3xl text-purple-400"><?= peso($summary['total_commission'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400"><?= peso($summary['total_paid_out'] ?? 0) ?> paid out</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         CHARTS ROW 1
         ============================================================ -->
    <div class="grid gap-6 lg:grid-cols-2">

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">PARTNER ACQUISITION</h3>
                    <p class="mt-1 text-xs text-gray-400">New partners per month · last 12 months</p>
                </div>
                <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    12-month
                </span>
            </div>
            <div class="mt-4 h-64"><canvas id="acquisitionChart" role="img" aria-label="Partner acquisition chart"></canvas></div>
        </div>

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">PARTNER TYPE BREAKDOWN</h3>
                <p class="mt-1 text-xs text-gray-400">Coaches vs Suppliers — count and revenue</p>
            </div>
            <div class="mt-4 h-64"><canvas id="typeChart" role="img" aria-label="Partner type breakdown chart"></canvas></div>
        </div>
    </div>

    <!-- ============================================================
         CHARTS ROW 2
         ============================================================ -->
    <div class="grid gap-6 lg:grid-cols-2">

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">TOP PERFORMERS</h3>
                    <p class="mt-1 text-xs text-gray-400">Highest revenue contributors</p>
                </div>
                <?php if (!empty($topPartners)): ?>
                    <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                        Top <?= count($topPartners) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if (empty($topPartners)): ?>
                <div class="mt-4 flex h-64 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 bg-white/[0.02]">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/5 text-gray-500">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm text-gray-400">No approved sales yet.</p>
                </div>
            <?php else: ?>
                <div class="mt-4 h-64"><canvas id="topPartnersChart" role="img" aria-label="Top partners chart"></canvas></div>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">RETENTION HEALTH</h3>
                <p class="mt-1 text-xs text-gray-400">Active vs inactive vs churned partners</p>
            </div>
            <div class="mt-4 h-64"><canvas id="churnChart" role="img" aria-label="Partner retention health chart"></canvas></div>
        </div>
    </div>

    <!-- ============================================================
         PARTNERS SECTION
         ============================================================ -->
    <section role="region" aria-label="Partners list">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="font-heading text-3xl tracking-wide text-white">PARTNERS</h2>
                <p class="mt-1 text-sm text-gray-400">
                    All partners with business metrics, commission, and payout status.
                </p>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-3">
            <?= a11y_filter_open('Filter partners by status and type') ?>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status:</span>

                <?php
                $statusFilters = [
                    ['all',      'All',      (int)($summary['total']    ?? 0), 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['active',   'Active',   (int)($summary['active']   ?? 0), 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['inactive', 'Inactive', (int)($summary['inactive'] ?? 0), 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'],
                ];
                foreach ($statusFilters as [$key, $label, $count, $icon]):
                    $active = ($pFilters['status'] === $key || ($key === 'all' && $pFilters['status'] === 'all'));
                ?>
                    <a href="<?= url_e('/admin-dashboard' . $qsP(['p_status' => $key === 'all' ? '' : $key])) ?>"
                        aria-current="<?= $active ? 'true' : 'false' ?>"
                        class="filter-pill flex items-center gap-2 rounded-full border <?= $active ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-4 py-2 text-sm font-semibold transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                        </svg>
                        <span><?= $label ?></span>
                        <span class="rounded-full <?= $active ? 'bg-black/25 text-white' : 'bg-black/30 text-gray-400' ?> px-2 py-0.5 text-[10px] font-bold"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>

                <span class="ml-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Type:</span>

                <?php
                $typeFilters = [
                    ['all',      'All',       'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['coach',    'Coaches',   'M13 10V3L4 14h7v7l9-11h-7z'],
                    ['supplier', 'Suppliers', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ];
                foreach ($typeFilters as [$key, $label, $icon]):
                    $active = ($pFilters['type'] === $key || ($key === 'all' && $pFilters['type'] === 'all'));
                ?>
                    <a href="<?= url_e('/admin-dashboard' . $qsP(['p_type' => $key === 'all' ? '' : $key])) ?>"
                        aria-current="<?= $active ? 'true' : 'false' ?>"
                        class="filter-pill flex items-center gap-1.5 rounded-full border <?= $active ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                        </svg>
                        <span><?= $label ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-white/5">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="partnerSearch" type="search" placeholder="Search partners…"
                        value="<?= e($pFilters['search']) ?>"
                        class="h-11 w-full rounded-full border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>

                <a href="<?= url_e('/admin-dashboard/partners/export' . $qsP()) ?>"
                    class="tap-effect inline-flex h-11 items-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </a>
            </div>

            <?= a11y_filter_close() ?>
        </div>

        <!-- Partners table -->
        <div class="mt-4 overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <caption class="sr-only">Partners with business metrics, commission, and payout status</caption>
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Partner</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Type</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Referral</th>
                            <th scope="col" class="p-4 text-center text-[10px] font-bold uppercase tracking-widest text-gray-500">Clients</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Revenue</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Commission</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Available</th>
                            <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="partnerRows">
                        <?php foreach ($partners as $idx => $p):
                            $isActive  = ($p['status'] ?? '') === 'active';
                            $isChurned = !empty($p['is_churned']);
                            $partnerType = $p['partner_type'] ?? 'coach';
                            $typeSvg = $partnerType === 'supplier'
                                ? 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
                                : 'M13 10V3L4 14h7v7l9-11h-7z';

                            $commission = (float)($p['commission_earned'] ?? 0);
                            $available  = (float)($p['available_balance'] ?? 0);
                            $pending    = (float)($p['payouts_pending'] ?? 0);
                        ?>
                            <tr class="partner-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                data-status="<?= e($p['status']) ?>"
                                data-type="<?= e($partnerType) ?>"
                                data-search="<?= e(strtolower(($p['business_name'] ?? '') . ' ' . ($p['user_email'] ?? '') . ' ' . ($p['referral_code'] ?? ''))) ?>"
                                style="animation: row-enter 0.35s ease-out <?= min($idx * 20, 400) ?>ms backwards;">

                                <!-- Partner -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-lg text-white shadow-md">
                                            <?= e(initials($p['business_name'] ?? '?')) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <div class="truncate text-sm font-semibold text-white transition-colors group-hover:text-[#00A6A6]">
                                                    <?= e($p['business_name'] ?? 'Unnamed') ?>
                                                </div>
                                                <?php if ($isChurned): ?>
                                                    <span data-status-badge class="flex-shrink-0 rounded-full bg-red-500 px-1.5 py-0.5 text-[9px] font-bold uppercase text-white" title="No sale in 60+ days">
                                                        Churn
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="truncate text-xs text-gray-500">
                                                <?= e($p['contact_email'] ?? $p['user_email'] ?? '—') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="p-4 hidden lg:table-cell">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-bold text-gray-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $typeSvg ?>" />
                                        </svg>
                                        <?= e(ucfirst($partnerType)) ?>
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="p-4">
                                    <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $isActive ? 'border-green-500/20 bg-green-500/10 text-green-500' : 'border-gray-500/20 bg-gray-500/10 text-gray-400' ?> px-3 py-1 text-[10px] font-bold uppercase">
                                        <?php if ($isActive): ?>
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                            </svg>
                                        <?php endif; ?>
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>

                                <!-- Referral -->
                                <td class="p-4 hidden xl:table-cell">
                                    <button type="button"
                                        onclick="navigator.clipboard.writeText('<?= e($p['referral_code'] ?? '') ?>').then(()=>{this.querySelector('.copy-icon').innerHTML='<svg class=\'w-3 h-3 text-green-500\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/></svg>';setTimeout(()=>this.querySelector('.copy-icon').innerHTML='<svg class=\'w-3 h-3\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'/></svg>',1200)})"
                                        aria-label="Copy referral code <?= e($p['referral_code'] ?? '') ?>"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 font-mono text-[11px] font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                        <span class="copy-icon">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <?= e($p['referral_code'] ?? '—') ?>
                                    </button>
                                </td>

                                <!-- Clients -->
                                <td class="p-4 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="font-heading text-lg text-white"><?= (int)($p['total_clients'] ?? 0) ?></span>
                                        <span class="text-[10px] text-gray-500"><?= (int)($p['linked_clients'] ?? 0) ?> linked</span>
                                    </div>
                                </td>

                                <!-- Revenue -->
                                <td class="p-4 text-right">
                                    <div class="font-heading text-base <?= ($p['approved_amount'] ?? 0) > 0 ? 'text-[#00A6A6]' : 'text-gray-500' ?>">
                                        <?= peso($p['approved_amount'] ?? 0) ?>
                                    </div>
                                    <?php if (($p['pending_amount'] ?? 0) > 0): ?>
                                        <div class="text-[10px] font-bold text-amber-500">
                                            +<?= peso($p['pending_amount']) ?> pending
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Commission -->
                                <td class="p-4 text-right hidden lg:table-cell">
                                    <div class="font-heading text-base text-purple-400"><?= peso($commission) ?></div>
                                    <div class="text-[10px] text-gray-500">
                                        <?= number_format((float)($p['commission_rate'] ?? 10), 1) ?>% rate
                                    </div>
                                </td>

                                <!-- Available -->
                                <td class="p-4 text-right">
                                    <div class="font-heading text-base <?= $available > 0 ? 'text-green-500' : 'text-gray-500' ?>">
                                        <?= peso($available) ?>
                                    </div>
                                    <?php if ($pending > 0): ?>
                                        <div class="text-[10px] font-bold text-amber-500"><?= peso($pending) ?> pending</div>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">
                                        <button type="button"
                                            onclick='openPartnerModal(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Edit partner <?= e($p['business_name'] ?? '') ?>"
                                            title="Edit partner"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button type="button"
                                            onclick='openPartnerClientsModal(<?= json_encode(["user_id" => $p["user_id"], "business_name" => $p["business_name"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="View clients of <?= e($p['business_name'] ?? '') ?>"
                                            title="View clients"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </button>

                                        <form method="post" action="<?= url_e('/admin-dashboard/partners/toggle') ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                            <button type="submit"
                                                aria-label="<?= $isActive ? 'Deactivate' : 'Activate' ?> partner <?= e($p['business_name'] ?? '') ?>"
                                                title="<?= $isActive ? 'Deactivate' : 'Activate' ?>"
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 <?= $isActive ? 'hover:border-red-500 hover:text-red-500' : 'hover:border-green-500 hover:text-green-500' ?>">
                                                <?php if ($isActive): ?>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($partners)): ?>
                            <tr>
                                <td colspan="9" class="p-16 text-center">
                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm text-gray-400">No partners match your filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="noPartnerResults" class="hidden p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No partners match your filters.</p>
            </div>
        </div>
    </section>

    <!-- ============================================================
         B2B CLIENTS SECTION
         ============================================================ -->
    <section role="region" aria-label="B2B clients list">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="font-heading text-3xl tracking-wide text-white">B2B CLIENTS</h2>
                <p class="mt-1 text-sm text-gray-400">
                    Clients referred by partners · <?= (int)($b2bSummary['total'] ?? 0) ?> total · <?= (int)($b2bSummary['linked'] ?? 0) ?> linked
                </p>
            </div>
        </div>

        <!-- Client filter bar -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm">
            <?= a11y_filter_open('Filter B2B clients by status') ?>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status:</span>

                <?php
                $cStatusFilters = [
                    ['all',      'All',      (int)($b2bSummary['total']    ?? 0), 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['linked',   'Linked',   (int)($b2bSummary['linked']   ?? 0), 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['pending',  'Pending',  (int)($b2bSummary['pending']  ?? 0), 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['inactive', 'Inactive', (int)($b2bSummary['inactive'] ?? 0), 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'],
                ];
                foreach ($cStatusFilters as [$key, $label, $count, $icon]):
                    $active = ($cFilters['status'] === $key || ($key === 'all' && $cFilters['status'] === 'all'));
                ?>
                    <a href="<?= url_e('/admin-dashboard' . $qsC(['c_status' => $key === 'all' ? '' : $key])) ?>"
                        aria-current="<?= $active ? 'true' : 'false' ?>"
                        class="filter-pill flex items-center gap-2 rounded-full border <?= $active ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-4 py-2 text-sm font-semibold transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                        </svg>
                        <span><?= $label ?></span>
                        <span class="rounded-full <?= $active ? 'bg-black/25 text-white' : 'bg-black/30 text-gray-400' ?> px-2 py-0.5 text-[10px] font-bold"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>

                <a href="<?= url_e('/admin-dashboard/b2b-clients/export' . $qsC()) ?>"
                    class="ml-auto tap-effect inline-flex h-9 items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white"
                    download>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </a>
            </div>

            <?= a11y_filter_close() ?>
        </div>

        <!-- B2B clients table -->
        <?php if (empty($clients)): ?>
            <div class="mt-4 rounded-2xl border border-dashed border-white/10 bg-[#111] p-16 text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No B2B clients match your filters.</p>
            </div>
        <?php else: ?>
            <div class="mt-4 overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <caption class="sr-only">B2B clients referred by partners</caption>
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Client</th>
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Partner</th>
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Last Check-in</th>
                                <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Added</th>
                                <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $idx => $c):
                                $status = $c['membership_status'] ?? 'pending';
                                $statusStyle = [
                                    'linked'   => 'border-green-500/20 bg-green-500/10 text-green-500',
                                    'pending'  => 'border-amber-500/20 bg-amber-500/10 text-amber-500',
                                    'inactive' => 'border-gray-500/20 bg-gray-500/10 text-gray-400',
                                ][$status] ?? 'border-gray-500/20 bg-gray-500/10 text-gray-400';
                                $statusIcon = [
                                    'linked'   => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                                    'pending'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                                    'inactive' => 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
                                ][$status] ?? 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';

                                $lastCheckin = $c['recent_checkin'] ?? $c['last_check_in'] ?? null;
                            ?>
                                <tr class="group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                                    style="animation: row-enter 0.3s ease-out <?= min($idx * 20, 400) ?>ms backwards;">

                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white">
                                                <?= e(initials($c['client_name'] ?? '?')) ?>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-semibold text-white">
                                                    <?= e($c['client_name'] ?? 'Unknown') ?>
                                                </div>
                                                <div class="truncate text-xs text-gray-500">
                                                    <?= e($c['client_email'] ?? '') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4 hidden lg:table-cell">
                                        <?php if (!empty($c['business_name'])): ?>
                                            <div class="text-sm font-medium text-white"><?= e($c['business_name']) ?></div>
                                            <div class="text-[10px] font-bold uppercase text-gray-500">
                                                <?= e($c['partner_type'] ?? '') ?> · <?= e($c['referral_code'] ?? '') ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-600">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4">
                                        <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $statusStyle ?> px-3 py-1 text-[10px] font-bold uppercase">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $statusIcon ?>" />
                                            </svg>
                                            <?= e($status) ?>
                                        </span>
                                    </td>

                                    <td class="p-4 text-sm text-gray-300 whitespace-nowrap hidden lg:table-cell">
                                        <?php if ($lastCheckin): ?>
                                            <div class="flex items-center gap-1.5">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                                <?= e(short_date($lastCheckin)) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-600">Never</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-sm text-gray-400 whitespace-nowrap hidden xl:table-cell">
                                        <?= e(short_date($c['created_date'] ?? null)) ?>
                                    </td>

                                    <td class="p-4 text-right">
                                        <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">
                                            <button type="button"
                                                onclick='openClientModal(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                aria-label="Edit client <?= e($c['client_name'] ?? '') ?>"
                                                title="Edit client"
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <form method="post" action="<?= url_e('/admin-dashboard/b2b-clients/delete') ?>" class="inline"
                                                onsubmit="return confirm('Delete this client record? This cannot be undone.')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                                                <button type="submit"
                                                    aria-label="Delete client <?= e($c['client_name'] ?? '') ?>"
                                                    title="Delete"
                                                    class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-red-500 hover:text-red-500">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============================================================
         PARTNER EDIT MODAL
         ============================================================ -->
    <div id="partnerModal" role="dialog" aria-modal="true" aria-labelledby="partnerModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/5 bg-[#111] p-6">
                <div class="flex items-center gap-3">
                    <div id="pm-avatar" class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-xl text-white shadow-md">?</div>
                    <div class="min-w-0">
                        <h2 id="partnerModalTitle" class="truncate font-heading text-2xl tracking-wide text-white">
                            <span id="pm-title">EDIT PARTNER</span>
                        </h2>
                        <p id="pm-subtitle" class="truncate text-xs text-gray-400">—</p>
                    </div>
                </div>
                <button type="button" onclick="closePartnerModal()"
                    aria-label="Close edit partner dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/partners/update') ?>" id="partnerForm" class="p-6 sm:p-8 space-y-5" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="pm-id">

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Business Name <span class="text-[#D50000]">*</span>
                        </span>
                        <input name="business_name" id="pm-business-name" required maxlength="190"
                            data-validate="required|min:2|max:190"
                            data-error-label="Business Name"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Partner Type</span>
                        <select name="partner_type" id="pm-partner-type"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                            <option value="coach">Coach</option>
                            <option value="supplier">Supplier</option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Contact Email</span>
                        <input type="email" name="contact_email" id="pm-contact-email" maxlength="190"
                            data-validate="email|max:190"
                            data-error-label="Contact Email"
                            placeholder="business@example.com"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Phone</span>
                        <input type="tel" name="phone" id="pm-phone" maxlength="40"
                            data-validate="phone|max:40"
                            data-error-label="Phone"
                            placeholder="+63 917 123 4567"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Commission Rate (%) <span class="text-[#D50000]">*</span>
                        </span>
                        <input type="number" name="commission_rate" id="pm-commission" min="0" max="50" step="0.5" required
                            data-validate="required|min:0|max:50"
                            data-error-label="Commission Rate"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="mt-2 block text-[11px] text-gray-500">Spec recommends 10–15% for B2B.</span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</span>
                        <select name="status" id="pm-status"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>
                </div>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Address</span>
                    <input name="address" id="pm-address" maxlength="255"
                        placeholder="Street, Barangay, City"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Admin Notes</span>
                    <textarea name="notes" id="pm-notes" rows="2" maxlength="500"
                        placeholder="Internal notes — not visible to the partner"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20"></textarea>
                </label>

                <div id="pm-stats" class="rounded-2xl border border-white/5 bg-white/[0.02] p-4 text-xs text-gray-300 space-y-2"></div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" onclick="closePartnerModal()"
                        class="rounded-full border border-white/10 px-6 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         PARTNER CLIENTS QUICK-VIEW MODAL
         ============================================================ -->
    <div id="partnerClientsModal" role="dialog" aria-modal="true" aria-labelledby="partnerClientsModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/5 bg-[#111] p-6">
                <div>
                    <h2 id="partnerClientsModalTitle" class="font-heading text-2xl tracking-wide text-white">PARTNER CLIENTS</h2>
                    <p id="pcm-subtitle" class="mt-1 text-xs text-gray-400">—</p>
                </div>
                <button type="button" onclick="closePartnerClientsModal()"
                    aria-label="Close partner clients dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <div id="pcm-content" class="space-y-2"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         CLIENT EDIT MODAL
         ============================================================ -->
    <div id="clientModal" role="dialog" aria-modal="true" aria-labelledby="clientModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-lg rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="flex items-center justify-between border-b border-white/5 pb-4">
                <div>
                    <h2 id="clientModalTitle" class="font-heading text-2xl tracking-wide text-white">EDIT B2B CLIENT</h2>
                    <p class="mt-1 text-xs text-gray-400">Update client details and status</p>
                </div>
                <button type="button" onclick="closeClientModal()"
                    aria-label="Close edit client dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/b2b-clients/update') ?>" class="mt-4 space-y-4" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="cm-id">

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Client Name <span class="text-[#D50000]">*</span>
                    </span>
                    <input name="client_name" id="cm-name" required maxlength="190"
                        data-validate="required|min:2|max:190"
                        data-error-label="Client Name"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Email <span class="text-[#D50000]">*</span>
                    </span>
                    <input type="email" name="client_email" id="cm-email" required maxlength="190"
                        data-validate="required|email|max:190"
                        data-error-label="Email"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Contact Phone</span>
                    <input type="tel" name="contact_phone" id="cm-phone" maxlength="40"
                        data-validate="phone|max:40"
                        data-error-label="Contact Phone"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</span>
                    <select name="membership_status" id="cm-status"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <option value="linked">Linked</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Notes</span>
                    <textarea name="notes" id="cm-notes" rows="2" maxlength="500"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20"></textarea>
                </label>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" onclick="closeClientModal()"
                        class="rounded-full border border-white/10 px-6 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Save Client
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    @media (prefers-reduced-motion: reduce) {

        .partner-row,
        #partnerModal>div,
        #clientModal>div,
        #partnerClientsModal>div {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        const ACQUISITION = <?= json_encode($acquisition, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const TYPE_LABELS = <?= json_encode($typeLabels, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const TYPE_COUNTS = <?= json_encode($typeCounts, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const TYPE_REVENUES = <?= json_encode($typeRevenues, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const TOP_LABELS = <?= json_encode($partnerLabels, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const TOP_REVENUES = <?= json_encode($partnerRevenues, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const B2B_BY_PARTNER = <?= json_encode($b2bByPartner, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const SUMMARY = <?= json_encode([
                            'active'   => (int)($summary['active']   ?? 0),
                            'inactive' => (int)($summary['inactive'] ?? 0),
                            'churned'  => (int)($summary['churned']  ?? 0),
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        const isDark = document.documentElement.classList.contains('dark');
        const gridClr = isDark ? '#334155' : '#e5e7eb';
        const tickClr = isDark ? '#94a3b8' : '#4b5563';
        const TEAL = '#00A6A6';
        const PURPLE = '#9333ea';
        const RED = '#D50000';
        const AMBER = '#f59e0b';
        const GRAY = '#94a3b8';
        const GREEN = '#22c55e';

        Chart.defaults.font.family = 'Montserrat, sans-serif';
        Chart.defaults.color = tickClr;

        const baseOpts = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 900,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    labels: {
                        color: tickClr,
                        font: {
                            size: 11
                        },
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f1f5f9',
                    bodyColor: '#cbd5e1',
                    padding: 12,
                    cornerRadius: 10
                }
            }
        };

        const money = (v) => '₱' + Number(v).toLocaleString();

        /* --- Acquisition chart --- */
        const acqEl = document.getElementById('acquisitionChart');
        if (acqEl) {
            if (!ACQUISITION || ACQUISITION.length === 0) {
                acqEl.parentElement.innerHTML = '<div class="flex h-full items-center justify-center text-sm text-gray-500">No acquisition data yet</div>';
            } else {
                const ctx = acqEl.getContext('2d');
                const grad = ctx.createLinearGradient(0, 0, 0, 260);
                grad.addColorStop(0, TEAL);
                grad.addColorStop(1, 'rgba(0,166,166,0.3)');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ACQUISITION.map(r => {
                            const [y, m] = r.month.split('-');
                            return new Date(y, m - 1).toLocaleDateString('en', {
                                month: 'short',
                                year: '2-digit'
                            });
                        }),
                        datasets: [{
                            data: ACQUISITION.map(r => r.count),
                            backgroundColor: grad,
                            hoverBackgroundColor: TEAL,
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 36
                        }]
                    },
                    options: {
                        ...baseOpts,
                        plugins: {
                            ...baseOpts.plugins,
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...baseOpts.plugins.tooltip,
                                callbacks: {
                                    label: (i) => i.raw + ' partner' + (i.raw !== 1 ? 's' : '')
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
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        }

        /* --- Type chart --- */
        const typeEl = document.getElementById('typeChart');
        if (typeEl && TYPE_LABELS.length > 0) {
            new Chart(typeEl, {
                type: 'bar',
                data: {
                    labels: TYPE_LABELS,
                    datasets: [{
                            label: 'Partners',
                            data: TYPE_COUNTS,
                            backgroundColor: TEAL,
                            borderRadius: 8,
                            borderSkipped: false,
                            yAxisID: 'y',
                            maxBarThickness: 60
                        },
                        {
                            label: 'Revenue (₱)',
                            data: TYPE_REVENUES,
                            backgroundColor: PURPLE,
                            borderRadius: 8,
                            borderSkipped: false,
                            yAxisID: 'y1',
                            maxBarThickness: 60
                        }
                    ]
                },
                options: {
                    ...baseOpts,
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.datasetIndex === 0 ? i.raw + ' partner' + (i.raw !== 1 ? 's' : '') : money(i.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr
                            }
                        },
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                precision: 0
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => '₱' + Number(v).toLocaleString(),
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        /* --- Top partners chart --- */
        const topEl = document.getElementById('topPartnersChart');
        if (topEl && TOP_LABELS.length > 0) {
            new Chart(topEl, {
                type: 'bar',
                data: {
                    labels: TOP_LABELS,
                    datasets: [{
                        data: TOP_REVENUES,
                        backgroundColor: TEAL,
                        hoverBackgroundColor: '#008c8c',
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 32
                    }]
                },
                options: {
                    ...baseOpts,
                    indexAxis: 'y',
                    plugins: {
                        ...baseOpts.plugins,
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => money(i.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => '₱' + Number(v).toLocaleString(),
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickClr,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        /* --- Churn chart --- */
        const churnEl = document.getElementById('churnChart');
        if (churnEl) {
            new Chart(churnEl, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Inactive', 'Churned (60d)'],
                    datasets: [{
                        data: [SUMMARY.active, SUMMARY.inactive, SUMMARY.churned],
                        backgroundColor: [GREEN, GRAY, RED],
                        borderWidth: 0,
                        hoverOffset: 12
                    }]
                },
                options: {
                    ...baseOpts,
                    cutout: '68%',
                    plugins: {
                        ...baseOpts.plugins,
                        tooltip: {
                            ...baseOpts.plugins.tooltip,
                            callbacks: {
                                label: (i) => i.label + ': ' + i.raw
                            }
                        }
                    }
                }
            });
        }

        /* ============ Partner search (client-side) ============ */
        let currentSearch = '';
        document.getElementById('partnerSearch')?.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase().trim();
            applyClientFilter();
        });

        function applyClientFilter() {
            const rows = document.querySelectorAll('#partnerRows .partner-row');
            let visible = 0;
            rows.forEach(row => {
                const search = row.dataset.search || '';
                if (currentSearch === '' || search.includes(currentSearch)) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });
            const noResults = document.getElementById('noPartnerResults');
            if (noResults) noResults.classList.toggle('hidden', visible > 0 || rows.length === 0);
        }

        /* ============ Modal helpers ============ */
        function trackTrigger() {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
        }

        /* ============ Partner edit modal ============ */
        window.openPartnerModal = function(p) {
            if (!p) return;
            trackTrigger();

            document.getElementById('pm-id').value = p.id || '';
            document.getElementById('pm-avatar').textContent = (p.business_name || '?').charAt(0).toUpperCase();
            document.getElementById('pm-title').textContent = 'EDIT PARTNER';
            document.getElementById('pm-subtitle').textContent = p.user_email || '—';
            document.getElementById('pm-business-name').value = p.business_name || '';
            document.getElementById('pm-partner-type').value = p.partner_type || 'coach';
            document.getElementById('pm-contact-email').value = p.contact_email || '';
            document.getElementById('pm-phone').value = p.phone || '';
            document.getElementById('pm-commission').value = p.commission_rate || 10;
            document.getElementById('pm-status').value = p.status || 'active';
            document.getElementById('pm-address').value = p.address || '';
            document.getElementById('pm-notes').value = p.notes || '';

            const stats = document.getElementById('pm-stats');
            stats.innerHTML = `
            <div class="grid grid-cols-2 gap-3">
                <div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Referral code:</span> <code class="text-gray-300">${p.referral_code || '—'}</code></div>
                <div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Joined:</span> <span>${p.created_date || '—'}</span></div>
                <div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Clients:</span> <span class="font-semibold text-white">${(p.total_clients || 0)}</span> (${(p.linked_clients || 0)} linked)</div>
                <div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Sales:</span> <span class="font-semibold text-white">${(p.approved_sales_count || 0)}</span> approved</div>
                <div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Revenue:</span> <span class="font-semibold text-[#00A6A6]">₱${Number(p.approved_amount || 0).toLocaleString()}</span></div>
                <div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Commission:</span> <span class="font-semibold text-purple-400">₱${Number(p.commission_earned || 0).toLocaleString()}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Paid out:</span> <span class="font-semibold text-white">₱${Number(p.payouts_paid || 0).toLocaleString()}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Available:</span> <span class="font-semibold text-green-500">₱${Number(p.available_balance || 0).toLocaleString()}</span></div>
            </div>`;

            const form = document.getElementById('partnerForm');
            if (form) {
                form.querySelectorAll('[data-field-error]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                form.querySelector('[data-error-summary]')?.remove();
                form.querySelectorAll('[aria-invalid]').forEach(el => {
                    el.removeAttribute('aria-invalid');
                    el.classList.remove('border-[#D50000]', 'bg-red-50/50');
                    el.classList.add('border-white/10', 'bg-[#0a0a0a]');
                });
            }

            document.getElementById('partnerModal').classList.replace('hidden', 'flex');
        };

        window.closePartnerModal = function() {
            document.getElementById('partnerModal').classList.replace('flex', 'hidden');
        };

        /* ============ Partner clients quick-view ============ */
        window.openPartnerClientsModal = function(p) {
            if (!p) return;
            trackTrigger();

            document.getElementById('pcm-subtitle').textContent = p.business_name || '—';

            const container = document.getElementById('pcm-content');
            const allClients = <?= json_encode($clients, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const filtered = allClients.filter(c => c.partner_user_id === p.user_id);

            if (filtered.length === 0) {
                container.innerHTML = `
                <div class="rounded-xl border-2 border-dashed border-white/10 p-8 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="mt-3 text-sm text-gray-400">No clients linked to this partner yet.</p>
                </div>`;
            } else {
                container.innerHTML = filtered.map(c => {
                    const status = c.membership_status || 'pending';
                    const badge = {
                        linked: 'border-green-500/20 bg-green-500/10 text-green-500',
                        pending: 'border-amber-500/20 bg-amber-500/10 text-amber-500',
                        inactive: 'border-gray-500/20 bg-gray-500/10 text-gray-400'
                    } [status];
                    const icon = {
                        linked: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        pending: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                        inactive: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'
                    } [status];

                    return `
                    <div class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] p-3">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white">
                            ${(c.client_name || '?').charAt(0).toUpperCase()}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-white">${c.client_name || 'Client'}</div>
                            <div class="truncate text-xs text-gray-500">${c.client_email || ''}</div>
                        </div>
                        <span data-status-badge class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full border ${badge} px-3 py-1 text-[10px] font-bold uppercase">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icon}"/></svg>
                            ${status}
                        </span>
                    </div>`;
                }).join('');
            }

            document.getElementById('partnerClientsModal').classList.replace('hidden', 'flex');
        };

        window.closePartnerClientsModal = function() {
            document.getElementById('partnerClientsModal').classList.replace('flex', 'hidden');
        };

        /* ============ Client edit modal ============ */
        window.openClientModal = function(c) {
            if (!c) return;
            trackTrigger();

            document.getElementById('cm-id').value = c.id;
            document.getElementById('cm-name').value = c.client_name || '';
            document.getElementById('cm-email').value = c.client_email || '';
            document.getElementById('cm-phone').value = c.contact_phone || '';
            document.getElementById('cm-status').value = c.membership_status || 'pending';
            document.getElementById('cm-notes').value = c.notes || '';

            const form = document.querySelector('form[action*="/b2b-clients/update"]');
            if (form) {
                form.querySelectorAll('[data-field-error]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                form.querySelector('[data-error-summary]')?.remove();
                form.querySelectorAll('[aria-invalid]').forEach(el => {
                    el.removeAttribute('aria-invalid');
                    el.classList.remove('border-[#D50000]', 'bg-red-50/50');
                    el.classList.add('border-white/10', 'bg-[#0a0a0a]');
                });
            }

            document.getElementById('clientModal').classList.replace('hidden', 'flex');
        };

        window.closeClientModal = function() {
            document.getElementById('clientModal').classList.replace('flex', 'hidden');
        };

        /* ============ Backdrop close ============ */
        const MODALS = [
            ['partnerModal', 'closePartnerModal'],
            ['partnerClientsModal', 'closePartnerClientsModal'],
            ['clientModal', 'closeClientModal'],
        ];

        MODALS.forEach(([id, fn]) => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) window[fn]();
            });
        });
    })();
</script>