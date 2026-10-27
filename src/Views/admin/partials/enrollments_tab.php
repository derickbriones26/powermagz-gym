<?php

/**
 * Admin — Enrollments Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['enrollments']      — Enrollment::allWithStats()
 *   $data['enrollSummary']    — ['active'=>N, 'pending'=>N, ..., 'expiring_soon'=>N, 'total'=>N]
 *   $data['enrollTierCounts'] — ['digital'=>N, 'standard'=>N, 'premium'=>N]
 *   $data['enrollTrends']     — 12-month [['month'=>'2026-09','expired'=>N,'cancelled'=>N], ...]
 */

use App\Models\Enrollment;

$enrollments  = $data['enrollments']      ?? [];
$summary      = $data['enrollSummary']    ?? [
    'active' => 0,
    'pending' => 0,
    'expired' => 0,
    'paused' => 0,
    'cancelled' => 0,
    'expiring_soon' => 0,
    'total' => 0,
];
$tierCounts   = $data['enrollTierCounts'] ?? ['digital' => 0, 'standard' => 0, 'premium' => 0];
$trends       = $data['enrollTrends']     ?? [];

/* Tier meta — SVG paths */
$tierMeta = [
    'digital'  => ['svg' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'label' => 'Digital',  'color' => '#00A6A6'],
    'standard' => ['svg' => 'M13 10V3L4 14h7v7l9-11h-7z', 'label' => 'Standard', 'color' => '#00A6A6'],
    'premium'  => ['svg' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'label' => 'Premium',  'color' => '#D50000'],
];

/* Status config */
$statusCfg = [
    'active'    => ['bg' => 'bg-green-500/10',  'text' => 'text-green-500',  'border' => 'border-green-500/20',  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    'pending'   => ['bg' => 'bg-amber-500/10',  'text' => 'text-amber-500',  'border' => 'border-amber-500/20',  'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    'expired'   => ['bg' => 'bg-red-500/10',    'text' => 'text-red-500',    'border' => 'border-red-500/20',    'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
    'paused'    => ['bg' => 'bg-blue-500/10',   'text' => 'text-blue-400',   'border' => 'border-blue-500/20',   'icon' => 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    'cancelled' => ['bg' => 'bg-gray-500/10',   'text' => 'text-gray-400',   'border' => 'border-gray-500/20',   'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
];
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
                ENROLLMENTS
            </h1>
            <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                Track and manage the full membership lifecycle: approve, extend, pause, cancel, or upgrade.
            </p>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP — 5 cards
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <!-- Total -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= (int)$summary['total'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">All enrollments</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
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
                    <div class="mt-2 font-heading text-4xl text-green-500"><?= (int)$summary['active'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">Currently enrolled</div>
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
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Pending
                    </div>
                    <div class="mt-2 font-heading text-4xl text-amber-500"><?= (int)$summary['pending'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">Awaiting payment</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Paused -->
        <div class="group relative overflow-hidden rounded-2xl border border-blue-500/20 bg-blue-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-blue-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-blue-400">Paused</div>
                    <div class="mt-2 font-heading text-4xl text-blue-400"><?= (int)$summary['paused'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">Clock frozen</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Expiring soon -->
        <div class="group relative overflow-hidden rounded-2xl border <?= $summary['expiring_soon'] > 0 ? 'border-red-500/30 bg-red-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-red-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-red-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest <?= $summary['expiring_soon'] > 0 ? 'text-red-500' : 'text-gray-500' ?>">
                        <?php if ($summary['expiring_soon'] > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                        <?php endif; ?>
                        Expiring Soon
                    </div>
                    <div class="mt-2 font-heading text-4xl <?= $summary['expiring_soon'] > 0 ? 'text-red-500' : 'text-gray-600' ?>">
                        <?= (int)$summary['expiring_soon'] ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">Within 3 days</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl <?= $summary['expiring_soon'] > 0 ? 'bg-red-500/10 text-red-500' : 'bg-white/5 text-gray-500' ?> transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         12-MONTH TREND CHART
         ============================================================ -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">12-MONTH TREND</h3>
                <p class="mt-1 text-xs text-gray-400">Expirations and cancellations per month</p>
            </div>
            <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                Rolling 12 months
            </span>
        </div>
        <div class="mt-4 h-56"><canvas id="enrollTrendChart" role="img" aria-label="Bar chart of monthly enrollment expirations and cancellations"></canvas></div>
    </div>

    <!-- ============================================================
         FILTER BAR
         ============================================================ -->
    <form method="get" action="<?= url_e('/admin-dashboard') ?>" id="enrollFilterForm"
        class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-4">

        <input type="hidden" name="tab" value="enrollments">

        <?= a11y_filter_open('Filter enrollments by status and tier') ?>

        <!-- Status pills -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Status:</span>

            <?php
            $statusFilters = [
                ['all',       'All',       (int)$summary['total'],     'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['active',    'Active',    (int)$summary['active'],    'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['pending',   'Pending',   (int)$summary['pending'],   'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['paused',    'Paused',    (int)$summary['paused'],    'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['expired',   'Expired',   (int)$summary['expired'],   'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['cancelled', 'Cancelled', (int)$summary['cancelled'], 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
            ];
            foreach ($statusFilters as [$key, $label, $count, $icon]):
            ?>
                <button type="button"
                    data-status-filter="<?= $key ?>"
                    onclick="setStatusFilter('<?= $key ?>')"
                    aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>"
                    class="status-filter filter-pill flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-300 transition-all duration-300 hover:border-[#00A6A6]/50 hover:text-white
                        <?= $key === 'all' ? 'active' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                    <span class="rounded-full bg-black/30 px-2 py-0.5 text-[10px] font-bold text-gray-400"><?= $count ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Tier pills + search -->
        <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-white/5">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Tier:</span>

            <?php
            $tierFilters = [
                ['all',      'All',      'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['digital',  'Digital',  'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                ['standard', 'Standard', 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['premium',  'Premium',  'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
            ];
            foreach ($tierFilters as [$key, $label, $icon]):
            ?>
                <button type="button"
                    data-tier-filter="<?= $key ?>"
                    onclick="setTierFilter('<?= $key ?>')"
                    aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>"
                    class="tier-filter filter-pill flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-gray-300 transition-all duration-300 hover:border-[#00A6A6]/50 hover:text-white
                        <?= $key === 'all' ? 'active' : '' ?>">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                </button>
            <?php endforeach; ?>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <div class="relative w-full sm:w-64">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="enrollSearch" type="search" placeholder="Search member…"
                        aria-label="Search enrollments by member name"
                        class="h-11 w-full rounded-full border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>

                <a href="<?= url_e('/admin-dashboard/enrollments/export') ?>"
                    class="tap-effect inline-flex h-11 items-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        <?= a11y_filter_close() ?>
    </form>

    <!-- ============================================================
         BULK ACTIONS BAR
         ============================================================ -->
    <div id="bulkBar"
        role="region" aria-label="Bulk enrollment actions"
        class="hidden items-center justify-between rounded-2xl border border-[#00A6A6]/30 bg-[#00A6A6]/5 p-4"
        style="animation: slide-down 0.3s ease-out;">
        <div class="flex items-center gap-3">
            <span id="bulkCount" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#00A6A6] text-sm font-bold text-white"
                <?= a11y_live_region('polite') ?>>0</span>
            <span class="text-sm font-semibold text-gray-300">enrollment(s) selected</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="clearSelection()"
                class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-colors hover:bg-white/10 hover:text-white">
                Clear
            </button>
            <button type="button" onclick="openBatchRenewModal()"
                class="tap-effect inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-5 py-2 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Batch Renew
            </button>
        </div>
    </div>

    <!-- ============================================================
         TABLE
         ============================================================ -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <caption class="sr-only">Enrollment lifecycle: member, program, status, period, progress, and balance</caption>
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.02]">
                        <th scope="col" class="w-12 p-4">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                                class="h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6] cursor-pointer"
                                aria-label="Select all visible enrollments">
                        </th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Member</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Program</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Period</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden md:table-cell">Progress</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Balance</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="enrollRows">
                    <?php foreach ($enrollments as $idx => $e):
                        $status = $e['status'] ?? 'pending';
                        $tier   = $e['tier']   ?? 'standard';
                        $meta   = $tierMeta[$tier] ?? $tierMeta['standard'];
                        $sc     = $statusCfg[$status] ?? $statusCfg['pending'];

                        $progress = $e['progress_percent'] ?? null;
                        $daysLeft = $e['days_remaining']   ?? null;
                        $urgency  = $e['urgency']          ?? 'safe';
                        $balance  = (float)($e['balance'] ?? 0);

                        $barColor = 'bg-[#00A6A6]';
                        if ($progress !== null) {
                            if ($urgency === 'urgent' || $urgency === 'expired') $barColor = 'bg-red-500';
                            elseif ($urgency === 'soon') $barColor = 'bg-amber-500';
                        }

                        $events = $data['enrollmentEvents'][$e['id']] ?? [];
                    ?>
                        <tr class="enroll-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                            data-status="<?= e($status) ?>"
                            data-tier="<?= e($tier) ?>"
                            data-search="<?= e(strtolower($e['user_name'] ?? '')) ?>"
                            style="animation: row-enter 0.35s ease-out <?= min($idx * 20, 500) ?>ms backwards;">

                            <!-- Checkbox -->
                            <td class="p-4">
                                <input type="checkbox"
                                    class="row-check h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6] cursor-pointer"
                                    value="<?= e($e['id']) ?>"
                                    data-name="<?= e($e['user_name'] ?? 'Member') ?>"
                                    onchange="updateBulkBar()"
                                    aria-label="Select enrollment for <?= e($e['user_name'] ?? 'member') ?>">
                            </td>

                            <!-- Member -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white shadow-md"
                                        aria-hidden="true">
                                        <?= e(initials($e['user_name'] ?? '?')) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-white"><?= e($e['user_name'] ?? 'Unknown') ?></div>
                                        <div class="truncate text-[10px] text-gray-500 font-mono"><?= e(substr($e['user_id'] ?? '', 0, 8)) ?>…</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Program -->
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg" style="background-color: <?= $meta['color'] ?>1A; color: <?= $meta['color'] ?>;">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $meta['svg'] ?>" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-white"><?= e($e['program_name'] ?? '—') ?></div>
                                        <div class="truncate text-[10px] font-bold uppercase" style="color: <?= $meta['color'] ?>">
                                            <?= e($tier) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="p-4">
                                <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $sc['bg'] ?> <?= $sc['text'] ?> <?= $sc['border'] ?> px-3 py-1 text-[10px] font-bold uppercase">
                                    <?php if ($status === 'pending'): ?>
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                    <?php else: ?>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $sc['icon'] ?>" />
                                        </svg>
                                    <?php endif; ?>
                                    <?= e(Enrollment::statusLabel($status)) ?>
                                </span>
                            </td>

                            <!-- Period -->
                            <td class="p-4 text-sm text-gray-300 whitespace-nowrap hidden lg:table-cell">
                                <div><?= e(short_date($e['start_date'] ?? null)) ?></div>
                                <div class="text-xs text-gray-500">&rarr; <?= e(short_date($e['end_date'] ?? null)) ?></div>
                            </td>

                            <!-- Progress -->
                            <td class="p-4 hidden md:table-cell">
                                <?php if ($progress !== null): ?>
                                    <div class="min-w-[140px]">
                                        <div class="flex items-center justify-between text-xs text-gray-400">
                                            <span class="font-bold text-white"><?= $progress ?>%</span>
                                            <span class="<?= ($daysLeft !== null && $daysLeft <= 3) ? 'font-bold text-red-500' : '' ?>">
                                                <?= max(0, (int)($daysLeft ?? 0)) ?>d left
                                            </span>
                                        </div>
                                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-white/5"
                                            role="progressbar"
                                            aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                                            <div class="h-full rounded-full transition-all duration-1000 ease-out <?= $barColor ?>"
                                                style="width: <?= $progress ?>%;"></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Balance -->
                            <td class="p-4 text-right">
                                <?php if ($balance > 0): ?>
                                    <span class="font-heading text-base text-[#D50000]"><?= peso($balance) ?></span>
                                    <span class="sr-only">outstanding</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-500">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Paid
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="p-4 text-right">
                                <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">

                                    <?php if ($status === 'pending'): ?>
                                        <form method="post" action="<?= url_e('/admin-dashboard/enrollments/approve') ?>" class="inline"
                                            onsubmit="return confirm('Activate this enrollment?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($e['id']) ?>">
                                            <button type="submit"
                                                aria-label="Approve enrollment for <?= e($e['user_name'] ?? 'member') ?>"
                                                class="rounded-full bg-[#00A6A6] px-3 py-1.5 text-xs font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                                                <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($status === 'active'): ?>
                                        <button type="button"
                                            onclick='openPauseModal(<?= json_encode(["id" => $e["id"], "name" => $e["user_name"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Pause enrollment for <?= e($e['user_name'] ?? 'member') ?>"
                                            title="Pause"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-blue-500 hover:text-blue-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($status === 'paused'): ?>
                                        <form method="post" action="<?= url_e('/admin-dashboard/enrollments/resume') ?>" class="inline"
                                            onsubmit="return confirm('Resume this enrollment? Paused days will be added back to the end date.')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($e['id']) ?>">
                                            <button type="submit"
                                                aria-label="Resume enrollment for <?= e($e['user_name'] ?? 'member') ?>"
                                                class="rounded-full bg-blue-500 px-3 py-1.5 text-xs font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-blue-600">
                                                <svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Resume
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($status, ['active', 'expired', 'paused'], true)): ?>
                                        <button type="button"
                                            onclick='openExtendModal(<?= json_encode(["id" => $e["id"], "name" => $e["user_name"], "end_date" => $e["end_date"] ?? ""], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Extend enrollment for <?= e($e['user_name'] ?? 'member') ?>"
                                            title="Extend"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (in_array($status, ['active', 'pending'], true)): ?>
                                        <button type="button"
                                            onclick='openTierChangeModal(<?= json_encode(["id" => $e["id"], "name" => $e["user_name"], "tier" => $tier], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Change tier for <?= e($e['user_name'] ?? 'member') ?>"
                                            title="Change tier"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-purple-500 hover:text-purple-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <button type="button"
                                        onclick='openDetailModal(<?= json_encode(array_merge($e, ["_events" => $events]), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        aria-label="View enrollment details for <?= e($e['user_name'] ?? 'member') ?>"
                                        title="View details"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <?php if ($status !== 'cancelled'): ?>
                                        <button type="button"
                                            onclick='openCancelModal(<?= json_encode(["id" => $e["id"], "name" => $e["user_name"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Cancel enrollment for <?= e($e['user_name'] ?? 'member') ?>"
                                            title="Cancel enrollment"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-red-500 hover:text-red-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($enrollments)): ?>
                        <tr>
                            <td colspan="8" class="p-16 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm text-gray-400">No enrollments match your filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="noEnrollResults" class="hidden p-12 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <p class="mt-4 text-sm text-gray-400">No enrollments match your filters.</p>
        </div>
    </div>

    <!-- ============================================================
         DETAIL / HISTORY MODAL
         ============================================================ -->
    <div id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/5 bg-[#111] p-6">
                <div>
                    <h2 id="detailModalTitle" class="font-heading text-2xl tracking-wide text-white">ENROLLMENT DETAILS</h2>
                    <p id="dm-subtitle" class="mt-1 text-xs text-gray-400">Full history and lifecycle</p>
                </div>
                <button type="button" onclick="closeDetailModal()"
                    aria-label="Close enrollment details dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-5">
                <div class="rounded-2xl bg-gradient-to-br from-[#00A6A6] to-[#008c8c] p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="text-xs font-bold uppercase tracking-widest opacity-80">Member</div>
                            <div id="dm-name" class="mt-1 truncate font-heading text-3xl">—</div>
                            <div id="dm-tags" class="mt-2 flex flex-wrap items-center gap-2"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-bold uppercase tracking-widest opacity-80">Balance</div>
                            <div id="dm-balance" class="mt-1 font-heading text-3xl">₱0</div>
                        </div>
                    </div>
                </div>

                <dl class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Program</dt>
                        <dd id="dm-program" class="mt-1 text-sm font-semibold text-white">—</dd>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Tier</dt>
                        <dd id="dm-tier" class="mt-1 text-sm font-semibold text-white">—</dd>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Start Date</dt>
                        <dd id="dm-start" class="mt-1 text-sm font-semibold text-white">—</dd>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">End Date</dt>
                        <dd id="dm-end" class="mt-1 text-sm font-semibold text-white">—</dd>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Days Left</dt>
                        <dd id="dm-days" class="mt-1 text-sm font-semibold">—</dd>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Check-ins (30d)</dt>
                        <dd id="dm-checkins" class="mt-1 text-sm font-semibold text-white">—</dd>
                    </div>
                </dl>

                <div id="dm-cancel-block" class="hidden rounded-xl border border-red-500/20 bg-red-500/5 p-4">
                    <div class="text-xs font-bold uppercase tracking-widest text-red-500">Cancellation Details</div>
                    <div id="dm-cancel-info" class="mt-2 text-sm text-gray-300"></div>
                </div>

                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#00A6A6]/10 text-[#00A6A6]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white">Lifecycle History</div>
                            <div class="text-xs text-gray-500">Every event recorded for this enrollment</div>
                        </div>
                    </div>

                    <div id="dm-events" class="max-h-72 overflow-y-auto rounded-xl border border-white/5 bg-[#0a0a0a]"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         EXTEND MODAL
         ============================================================ -->
    <div id="extendModal" role="dialog" aria-modal="true" aria-labelledby="extendModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h2 id="extendModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">EXTEND ENROLLMENT</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Extend <strong id="ext-name" class="text-white">this member</strong>'s enrollment.
                    Current end date: <span id="ext-current" class="font-semibold text-white">—</span>
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/enrollments/extend') ?>" class="mt-6 space-y-4" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="ext-id">

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Add Days <span class="text-[#D50000]">*</span>
                    </span>
                    <input type="number" name="days" id="ext-days" min="1" max="3650" value="30" required
                        data-validate="required|min:1|max:3650"
                        data-error-label="Extension days"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-center text-lg font-bold text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <div class="mt-2 flex flex-wrap justify-center gap-2">
                        <?php foreach ([7, 15, 30, 90, 180] as $d): ?>
                            <button type="button" onclick="document.getElementById('ext-days').value = <?= $d ?>"
                                class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-gray-300 transition-colors hover:border-[#00A6A6] hover:text-white">
                                +<?= $d ?>d
                            </button>
                        <?php endforeach; ?>
                    </div>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeExtendModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Extend
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         PAUSE MODAL
         ============================================================ -->
    <div id="pauseModal" role="dialog" aria-modal="true" aria-labelledby="pauseModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-sm rounded-3xl border border-blue-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-500/10 text-blue-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 id="pauseModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">PAUSE ENROLLMENT?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Pause <strong id="pause-name" class="text-white">this member</strong>'s clock.
                    Time spent paused will be added back when resumed — no access is lost.
                </p>
            </div>
            <form method="post" action="<?= url_e('/admin-dashboard/enrollments/pause') ?>" class="mt-6">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="pause-id">
                <div class="flex gap-3">
                    <button type="button" onclick="closePauseModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(59,130,246,0.4)] transition-all hover:-translate-y-0.5 hover:bg-blue-600">
                        Pause
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         TIER CHANGE MODAL
         ============================================================ -->
    <div id="tierModal" role="dialog" aria-modal="true" aria-labelledby="tierModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-purple-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-purple-500/10 text-purple-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <h2 id="tierModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">CHANGE TIER</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Switch <strong id="tier-name" class="text-white">this member</strong>'s tier.
                    A pro-rata balance adjustment will be applied automatically.
                </p>
            </div>

            <form id="tierForm" method="post" action="<?= url_e('/admin-dashboard/enrollments/upgrade') ?>" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="tier-id">

                <div>
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Current tier</span>
                    <div id="tier-current" class="rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3 text-sm font-semibold text-white">—</div>
                </div>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">New tier <span class="text-[#D50000]">*</span></span>
                    <select name="tier" id="tier-new" required
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <option value="digital">Digital (₱500)</option>
                        <option value="standard">Standard (₱1,000)</option>
                        <option value="premium">Premium (₱2,000)</option>
                    </select>
                </label>

                <div id="tier-direction-hint" class="rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-3.5 text-xs leading-relaxed text-gray-300">
                    The balance will be adjusted based on unused days of the current cycle.
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeTierChangeModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" id="tierSubmitBtn"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Apply Change
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         CANCEL MODAL
         ============================================================ -->
    <div id="cancelModal" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-red-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 id="cancelModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">CANCEL ENROLLMENT?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Cancel <strong id="cancel-name" class="text-white">this enrollment</strong>.
                    The member loses active access but the record is preserved for reporting.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/enrollments/cancel') ?>" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="cancel-id">

                <fieldset>
                    <legend class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Reason <span class="text-[#D50000]">*</span>
                    </legend>
                    <div class="space-y-2">
                        <?php
                        $reasons = [
                            'member_request' => 'Member request',
                            'non_payment'    => 'Non-payment',
                            'relocation'     => 'Relocation',
                            'other'          => 'Other',
                        ];
                        foreach ($reasons as $val => $label):
                        ?>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-white/10 bg-white/[0.02] p-3 transition-all hover:border-[#00A6A6] hover:bg-[#00A6A6]/5">
                                <input type="radio" name="reason" value="<?= $val ?>" required
                                    class="h-4 w-4 border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6]">
                                <span class="text-sm font-semibold text-white"><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCancelModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Keep
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-red-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all hover:-translate-y-0.5 hover:bg-red-600">
                        Cancel Enrollment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         BATCH RENEW MODAL
         ============================================================ -->
    <div id="batchRenewModal" role="dialog" aria-modal="true" aria-labelledby="batchRenewModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h2 id="batchRenewModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">BATCH RENEW</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Renew <strong id="batch-count" class="text-white">0</strong> selected enrollment(s).
                    Each gets a new pending enrollment chained to the old one.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/enrollments/batch-renew') ?>" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <div id="batch-ids-container"></div>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Extra days (optional)
                    </span>
                    <input type="number" name="extra_days" min="0" max="90" value="0"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <span class="mt-2 block text-[11px] text-gray-500">Added on top of each program's default duration.</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeBatchRenewModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Renew Selected
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

    .status-filter.active,
    .tier-filter.active {
        background-color: #00A6A6 !important;
        border-color: #00A6A6 !important;
        color: #ffffff !important;
        box-shadow: 0 0 15px rgba(0, 166, 166, 0.4);
    }

    .status-filter.active span,
    .tier-filter.active span {
        color: #ffffff;
    }

    .status-filter.active span:last-child {
        background-color: rgba(255, 255, 255, 0.2) !important;
    }

    @media (prefers-reduced-motion: reduce) {

        .enroll-row,
        .bulk-bar,
        #detailModal>div,
        #extendModal>div,
        #pauseModal>div,
        #tierModal>div,
        #cancelModal>div,
        #batchRenewModal>div {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        const TRENDS = <?= json_encode($trends, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        /* ========== Trend chart ========== */
        function initTrendChart() {
            const canvas = document.getElementById('enrollTrendChart');
            if (!canvas) return;

            if (!TRENDS || TRENDS.length === 0) {
                canvas.parentElement.innerHTML =
                    '<div class="flex h-full items-center justify-center text-sm text-gray-500">No trend data yet</div>';
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const tickColor = isDark ? '#94a3b8' : '#4b5563';
            const gridColor = isDark ? '#334155' : '#e5e7eb';

            const labels = TRENDS.map(t => {
                const [y, m] = t.month.split('-');
                return new Date(y, m - 1).toLocaleDateString('en', {
                    month: 'short',
                    year: '2-digit'
                });
            });
            const expired = TRENDS.map(t => t.expired);
            const cancelled = TRENDS.map(t => t.cancelled);

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Expired',
                            data: expired,
                            backgroundColor: '#D50000',
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 22
                        },
                        {
                            label: 'Cancelled',
                            data: cancelled,
                            backgroundColor: '#94a3b8',
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 22
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 900,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: tickColor,
                                font: {
                                    size: 11
                                },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 16
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            padding: 12,
                            cornerRadius: 10
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickColor,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickColor,
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        /* ========== Filter + search ========== */
        let currentStatus = 'all';
        let currentTier = 'all';
        let currentSearch = '';

        window.setStatusFilter = function(status) {
            currentStatus = status;
            document.querySelectorAll('[data-status-filter]').forEach(btn => {
                const active = btn.dataset.statusFilter === status;
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            applyFilters();
        };

        window.setTierFilter = function(tier) {
            currentTier = tier;
            document.querySelectorAll('[data-tier-filter]').forEach(btn => {
                const active = btn.dataset.tierFilter === tier;
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            applyFilters();
        };

        document.getElementById('enrollSearch')?.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase().trim();
            applyFilters();
        });

        function applyFilters() {
            const rows = document.querySelectorAll('#enrollRows .enroll-row');
            let visible = 0;

            rows.forEach(row => {
                const status = row.dataset.status || '';
                const tier = row.dataset.tier || '';
                const search = row.dataset.search || '';

                const okStatus = currentStatus === 'all' || status === currentStatus;
                const okTier = currentTier === 'all' || tier === currentTier;
                const okSearch = currentSearch === '' || search.includes(currentSearch);

                if (okStatus && okTier && okSearch) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noResults = document.getElementById('noEnrollResults');
            if (noResults) {
                noResults.classList.toggle('hidden', visible > 0 || rows.length === 0);
            }
        }

        /* ========== Bulk selection ========== */
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

            if (checks.length === 0) {
                bar.classList.add('hidden');
                bar.classList.remove('flex');
                return;
            }

            bar.classList.remove('hidden');
            bar.classList.add('flex');
            if (count) count.textContent = checks.length;
        };

        window.openBatchRenewModal = function() {
            const checks = Array.from(document.querySelectorAll('.row-check:checked'));
            if (checks.length === 0) return;

            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }

            const container = document.getElementById('batch-ids-container');
            container.innerHTML = '';
            checks.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.getElementById('batch-count').textContent = checks.length;
            document.getElementById('batchRenewModal').classList.replace('hidden', 'flex');
        };

        window.closeBatchRenewModal = function() {
            document.getElementById('batchRenewModal').classList.replace('flex', 'hidden');
        };

        /* ========== Detail modal ========== */
        const EVENT_ICONS = {
            created: 'M12 4v16m8-8H4',
            approved: 'M5 13l4 4L19 7',
            extended: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            paused: 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z',
            resumed: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z',
            upgraded: 'M5 10l7-7m0 0l7 7m-7-7v18',
            downgraded: 'M19 14l-7 7m0 0l-7-7m7 7V3',
            cancelled: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
            expired: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            renewed: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'
        };

        window.openDetailModal = function(e) {
            if (!e) return;

            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }

            document.getElementById('dm-subtitle').textContent = 'Enrollment ID: ' + (e.id || '').slice(0, 8) + '…';
            document.getElementById('dm-name').textContent = e.user_name || '—';

            const tierIcons = {
                digital: 'D',
                standard: 'S',
                premium: 'P'
            };

            const tags = [];
            tags.push('<span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase">' + (e.status || '') + '</span>');
            tags.push('<span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase">' + (tierIcons[e.tier] || '') + ' ' + (e.tier || '').toUpperCase() + '</span>');
            if (parseInt(e.auto_renew || 0) === 1) {
                tags.push('<span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase">AUTO-RENEW</span>');
            }
            document.getElementById('dm-tags').innerHTML = tags.join('');

            const bal = parseFloat(e.balance || 0);
            const balEl = document.getElementById('dm-balance');
            balEl.textContent = '₱' + bal.toLocaleString();
            balEl.style.opacity = bal > 0 ? '1' : '0.7';

            document.getElementById('dm-program').textContent = e.program_name || '—';
            document.getElementById('dm-tier').textContent = (e.tier || '—').toUpperCase();
            document.getElementById('dm-start').textContent = e.start_date || '—';
            document.getElementById('dm-end').textContent = e.end_date || '—';
            document.getElementById('dm-checkins').textContent = (e.attendance_30d || 0);

            const dl = e.days_remaining;
            const daysEl = document.getElementById('dm-days');
            if (dl === null || dl === undefined) {
                daysEl.textContent = '—';
                daysEl.style.color = '';
            } else {
                daysEl.textContent = dl + ' day' + (dl !== 1 ? 's' : '');
                if (dl < 0) daysEl.style.color = '#D50000';
                else if (dl <= 3) daysEl.style.color = '#D50000';
                else if (dl <= 7) daysEl.style.color = '#f59e0b';
                else daysEl.style.color = '#22c55e';
            }

            const cancelBlock = document.getElementById('dm-cancel-block');
            if (e.status === 'cancelled' && e.cancel_reason) {
                cancelBlock.classList.remove('hidden');
                const reasonLabels = {
                    member_request: 'Member request',
                    non_payment: 'Non-payment',
                    relocation: 'Relocation',
                    other: 'Other'
                };
                document.getElementById('dm-cancel-info').textContent =
                    (reasonLabels[e.cancel_reason] || e.cancel_reason) +
                    (e.cancelled_date ? ' · ' + e.cancelled_date : '');
            } else {
                cancelBlock.classList.add('hidden');
            }

            const events = e._events || [];
            const eventsBox = document.getElementById('dm-events');

            if (events.length === 0) {
                eventsBox.innerHTML = '<div class="p-8 text-center text-sm text-gray-500">No lifecycle events recorded.</div>';
            } else {
                eventsBox.innerHTML = events.map(ev => {
                    const iconPath = EVENT_ICONS[ev.event_type] || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                    const title = (ev.event_type || '').replace(/_/g, ' ');
                    let detail = '';
                    if (ev.from_status && ev.to_status && ev.from_status !== ev.to_status) {
                        detail += ev.from_status + ' → ' + ev.to_status;
                    }
                    if (ev.from_tier && ev.to_tier && ev.from_tier !== ev.to_tier) {
                        detail += (detail ? ' · ' : '') + ev.from_tier + ' → ' + ev.to_tier;
                    }
                    if (ev.reason) detail += (detail ? ' · ' : '') + ev.reason;
                    if (ev.notes) detail += (detail ? ' · ' : '') + ev.notes;

                    return `
                    <div class="flex gap-3 border-b border-white/5 p-3 last:border-0">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-white capitalize">${title}</div>
                            ${detail ? `<div class="mt-0.5 text-xs text-gray-400">${detail}</div>` : ''}
                            <div class="mt-0.5 text-[10px] text-gray-500">${ev.created_date || ''}</div>
                        </div>
                    </div>`;
                }).join('');
            }

            document.getElementById('detailModal').classList.replace('hidden', 'flex');
        };

        window.closeDetailModal = function() {
            document.getElementById('detailModal').classList.replace('flex', 'hidden');
        };

        /* ========== Extend modal ========== */
        window.openExtendModal = function(e) {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
            document.getElementById('ext-id').value = e.id;
            document.getElementById('ext-name').textContent = e.name;
            document.getElementById('ext-current').textContent = e.end_date || '—';
            document.getElementById('ext-days').value = 30;
            document.getElementById('extendModal').classList.replace('hidden', 'flex');
        };
        window.closeExtendModal = function() {
            document.getElementById('extendModal').classList.replace('flex', 'hidden');
        };

        /* ========== Pause modal ========== */
        window.openPauseModal = function(e) {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
            document.getElementById('pause-id').value = e.id;
            document.getElementById('pause-name').textContent = e.name;
            document.getElementById('pauseModal').classList.replace('hidden', 'flex');
        };
        window.closePauseModal = function() {
            document.getElementById('pauseModal').classList.replace('flex', 'hidden');
        };

        /* ========== Tier change modal ========== */
        window.openTierChangeModal = function(e) {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
            document.getElementById('tier-id').value = e.id;
            document.getElementById('tier-name').textContent = e.name;

            const tierLabels = {
                digital: 'Digital',
                standard: 'Standard',
                premium: 'Premium'
            };
            document.getElementById('tier-current').textContent = tierLabels[e.tier] || e.tier;

            const sel = document.getElementById('tier-new');
            const options = ['digital', 'standard', 'premium'].filter(t => t !== e.tier);
            sel.value = options[0] || 'standard';

            window._tierCurrentTier = e.tier;
            updateTierHint();

            sel.addEventListener('change', updateTierHint);

            document.getElementById('tierModal').classList.replace('hidden', 'flex');
        };

        function updateTierHint() {
            const sel = document.getElementById('tier-new');
            const newTier = sel.value;
            const curTier = window._tierCurrentTier || 'standard';
            const hint = document.getElementById('tier-direction-hint');
            const form = document.getElementById('tierForm');
            const btn = document.getElementById('tierSubmitBtn');

            const rank = {
                digital: 1,
                standard: 2,
                premium: 3
            };
            const dir = rank[newTier] > rank[curTier] ? 'upgrade' : 'downgrade';

            if (dir === 'upgrade') {
                hint.innerHTML = '<strong>Upgrade.</strong> The pro-rata difference will be added to the balance.';
                form.action = '<?= url_e('/admin-dashboard/enrollments/upgrade') ?>';
                btn.className = 'flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]';
            } else {
                hint.innerHTML = '<strong>Downgrade.</strong> The balance will be reduced (floor ₱0) by the pro-rata difference.';
                form.action = '<?= url_e('/admin-dashboard/enrollments/downgrade') ?>';
                btn.className = 'flex-1 rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(147,51,234,0.4)] transition-all hover:-translate-y-0.5 hover:bg-purple-700';
            }
        }

        window.closeTierChangeModal = function() {
            document.getElementById('tierModal').classList.replace('flex', 'hidden');
        };

        /* ========== Cancel modal ========== */
        window.openCancelModal = function(e) {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
            document.getElementById('cancel-id').value = e.id;
            document.getElementById('cancel-name').textContent = e.name;
            document.getElementById('cancelModal').classList.replace('hidden', 'flex');
        };
        window.closeCancelModal = function() {
            document.getElementById('cancelModal').classList.replace('flex', 'hidden');
        };

        /* ========== Backdrop close ========== */
        const MODALS = [
            ['detailModal', 'closeDetailModal'],
            ['extendModal', 'closeExtendModal'],
            ['pauseModal', 'closePauseModal'],
            ['tierModal', 'closeTierChangeModal'],
            ['cancelModal', 'closeCancelModal'],
            ['batchRenewModal', 'closeBatchRenewModal'],
        ];

        MODALS.forEach(([id, fn]) => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) window[fn]();
            });
        });

        /* ========== Boot ========== */
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-status-filter]').forEach(btn => {
                const active = btn.dataset.statusFilter === 'all';
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-tier-filter]').forEach(btn => {
                const active = btn.dataset.tierFilter === 'all';
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            initTrendChart();
        });
    })();
</script>