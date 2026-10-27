<?php

/**
 * Admin — Programs Tab (2026 UI)
 *
 * Receives: $data['programs']  (from Program::allWithStats())
 */

use App\Models\Program;

$programs = $data['programs'] ?? [];

/* Tier meta — SVG paths replacing emoji */
$tierMeta = [
    'digital'  => [
        'svg'   => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
        'label' => 'Digital',
        'color' => '#00A6A6',
    ],
    'standard' => [
        'svg'   => 'M13 10V3L4 14h7v7l9-11h-7z',
        'label' => 'Standard',
        'color' => '#00A6A6',
    ],
    'premium'  => [
        'svg'   => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
        'label' => 'Premium',
        'color' => '#D50000',
    ],
    'b2b'      => [
        'svg'   => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'label' => 'B2B',
        'color' => '#7c3aed',
    ],
];

/* Derived stats */
$tierCounts   = ['digital' => 0, 'standard' => 0, 'premium' => 0, 'b2b' => 0];
$activeCount  = 0;
$portalCount  = 0;
$totalActive  = 0;
$totalPending = 0;
$totalRevenue = 0.0;

foreach ($programs as $p) {
    $t = $p['tier'] ?? 'standard';
    if (isset($tierCounts[$t])) $tierCounts[$t]++;

    if ((int)($p['active'] ?? 0) === 1) $activeCount++;
    if ((int)($p['portal_visible'] ?? 0) === 1 && (int)($p['active'] ?? 0) === 1) {
        $portalCount++;
    }

    $totalActive  += (int)($p['active_enrollments']  ?? 0);
    $totalPending += (int)($p['pending_enrollments'] ?? 0);
}

$seenTiers = [];
foreach ($programs as $p) {
    $t = $p['tier'] ?? '';
    if ($t !== '' && !isset($seenTiers[$t])) {
        $seenTiers[$t] = true;
        $totalRevenue += (float)($p['revenue'] ?? 0);
    }
}

/* Popularity ranking */
$most  = null;
$least = null;
if (!empty($programs)) {
    $ranked = $programs;
    usort(
        $ranked,
        fn($a, $b) =>
        (int)($b['active_enrollments'] ?? 0) <=> (int)($a['active_enrollments'] ?? 0)
    );
    $most  = $ranked[0] ?? null;
    $least = end($ranked) ?: null;

    if ($least && $most && $least['id'] === $most['id']) {
        $least = null;
    }
}

$operationalFloor = Program::operationalCostFloor();
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
                    PROGRAMS &amp; TIERS
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Manage membership tiers, pricing, and portal visibility.
                </p>
            </div>

            <button type="button" onclick="openProgramModal()"
                class="tap-effect group inline-flex flex-shrink-0 items-center gap-2 self-start rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)] sm:self-auto">
                <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Program</span>
            </button>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP — 5 cards
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <!-- Programs -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Programs</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= count($programs) ?></div>
                    <div class="mt-1 text-xs text-gray-400"><?= $activeCount ?> active</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Enrolls -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/20 bg-green-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <?php if ($totalActive > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <?php endif; ?>
                        Active Enrolls
                    </div>
                    <div class="mt-2 font-heading text-4xl text-green-500"><?= $totalActive ?></div>
                    <div class="mt-1 text-xs text-gray-400"><?= $totalPending ?> pending</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Portal-Visible -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Portal-Visible</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]"><?= $portalCount ?></div>
                    <div class="mt-1 text-xs text-gray-400">of <?= $activeCount ?> active</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Revenue</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($totalRevenue) ?></div>
                    <div class="mt-1 text-xs text-gray-400">All paid payments</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Cost Floor -->
        <div class="group relative overflow-hidden rounded-2xl border <?= $operationalFloor > 0 ? 'border-amber-500/20 bg-amber-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest <?= $operationalFloor > 0 ? 'text-amber-500' : 'text-gray-500' ?>">Cost Floor</div>
                    <div class="mt-2 font-heading text-3xl <?= $operationalFloor > 0 ? 'text-amber-500' : 'text-gray-600' ?>">
                        <?= $operationalFloor > 0 ? peso($operationalFloor) : '—' ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $operationalFloor > 0 ? 'Below this = warning' : 'Not configured' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         MOST / LEAST POPULAR CALLOUT
         ============================================================ -->
    <?php if ($most || $least): ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <?php if ($most): ?>
                <div class="relative overflow-hidden rounded-2xl border border-green-500/20 bg-gradient-to-br from-green-500/5 to-transparent p-6 shadow-sm">
                    <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-green-500/10 blur-3xl"></div>
                    <div class="relative flex items-center gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-green-500/10 text-green-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-green-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                Most Popular
                            </div>
                            <div class="mt-1 truncate font-heading text-2xl tracking-wide text-white">
                                <?= e($most['name'] ?? '—') ?>
                            </div>
                            <div class="mt-1 text-xs text-gray-400">
                                <strong class="text-white"><?= (int)($most['active_enrollments'] ?? 0) ?></strong> active ·
                                <strong class="text-white"><?= peso($most['revenue'] ?? 0) ?></strong> revenue
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($least): ?>
                <div class="relative overflow-hidden rounded-2xl border border-amber-500/20 bg-gradient-to-br from-amber-500/5 to-transparent p-6 shadow-sm">
                    <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-amber-500/10 blur-3xl"></div>
                    <div class="relative flex items-center gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-amber-500">
                                Needs Attention
                            </div>
                            <div class="mt-1 truncate font-heading text-2xl tracking-wide text-white">
                                <?= e($least['name'] ?? '—') ?>
                            </div>
                            <div class="mt-1 text-xs text-gray-400">
                                <strong class="text-white"><?= (int)($least['active_enrollments'] ?? 0) ?></strong> active ·
                                <strong class="text-white"><?= peso($least['revenue'] ?? 0) ?></strong> revenue
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         FILTER BAR
         ============================================================ -->
    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm">

        <?= a11y_filter_open('Filter programs by tier and search') ?>

        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Tier:</span>

        <?php
        $filters = [
            ['all',      'All',      count($programs),        'M4 6h16M4 10h16M4 14h16M4 18h16'],
            ['digital',  'Digital',  $tierCounts['digital'],  'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
            ['standard', 'Standard', $tierCounts['standard'], 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['premium',  'Premium',  $tierCounts['premium'],  'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
            ['b2b',      'B2B',      $tierCounts['b2b'],      'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        ];
        foreach ($filters as [$key, $label, $count, $icon]):
        ?>
            <button type="button"
                data-tier-filter="<?= $key ?>"
                onclick="setTierFilter('<?= $key ?>')"
                aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>"
                class="tier-filter filter-pill flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-gray-300 transition-all duration-300
                       hover:border-[#00A6A6]/50 hover:text-white
                       <?= $key === 'all' ? 'active' : '' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                </svg>
                <span><?= $label ?></span>
                <span class="rounded-full bg-black/30 px-2 py-0.5 text-[10px] font-bold text-gray-400"><?= $count ?></span>
            </button>
        <?php endforeach; ?>

        <div class="ml-auto flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-64">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input id="programSearch" type="search" placeholder="Search programs…"
                    aria-label="Search programs by name"
                    class="h-11 w-full rounded-full border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </div>
        </div>

        <?= a11y_filter_close() ?>
    </div>

    <!-- ============================================================
         PROGRAM CARDS
         ============================================================ -->
    <?php if (empty($programs)): ?>
        <div class="rounded-2xl border border-dashed border-white/10 bg-[#111] p-16 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h3 class="mt-6 font-heading text-2xl tracking-wide text-white">NO PROGRAMS YET</h3>
            <p class="mt-2 text-sm text-gray-400">Click "Add Program" to create your first membership tier.</p>
            <button type="button" onclick="openProgramModal()"
                class="tap-effect mt-6 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create First Program
            </button>
        </div>
    <?php else: ?>
        <div id="programGrid" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($programs as $idx => $p):
                $t         = $p['tier'] ?? 'standard';
                $meta      = $tierMeta[$t] ?? $tierMeta['standard'];
                $isActive  = (int)($p['active'] ?? 0) === 1;
                $isPortal  = (int)($p['portal_visible'] ?? 0) === 1;
                $isPopular = ($most && $most['id'] === $p['id'] && (int)($most['active_enrollments'] ?? 0) > 0);
                $isLeast   = ($least && $least['id'] === $p['id']);

                $customIcon   = $p['icon'] ?? '';
                $features     = Program::featuresList($p['features'] ?? null);
                $activeEnroll = (int)($p['active_enrollments'] ?? 0);
                $pendingEnroll = (int)($p['pending_enrollments'] ?? 0);
                $expiredEnroll = (int)($p['expired_enrollments'] ?? 0);
                $cancelEnroll = (int)($p['cancelled_enrollments'] ?? 0);
                $revenue      = (float)($p['revenue'] ?? 0);
                $opCost       = (float)($p['operational_cost'] ?? 0);
                $belowCost    = $opCost > 0 && (float)$p['price'] < $opCost;
            ?>
                <div class="program-card group relative overflow-hidden rounded-3xl border bg-[#111] p-6 shadow-sm
                      transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl
                      <?= $isPopular
                            ? 'border-green-500/40 ring-2 ring-green-500/20 hover:shadow-green-500/20'
                            : ($isActive
                                ? 'border-white/5 hover:border-[#00A6A6]/50 hover:shadow-[#00A6A6]/20'
                                : 'border-white/5 opacity-60 hover:opacity-100') ?>"
                    data-tier="<?= e($t) ?>"
                    data-search="<?= e(strtolower($p['name'] ?? '')) ?>"
                    data-id="<?= e($p['id']) ?>"
                    style="animation: card-enter 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) <?= min($idx * 60, 360) ?>ms backwards;">

                    <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full opacity-10 transition-transform duration-500 group-hover:scale-150" style="background-color: <?= $meta['color'] ?>;"></div>

                    <!-- Badges -->
                    <?php if ($isPopular): ?>
                        <span data-status-badge class="absolute right-4 top-4 z-10 flex items-center gap-1.5 rounded-full bg-green-500 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white shadow-md">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Popular
                        </span>
                    <?php elseif ($isLeast): ?>
                        <span data-status-badge class="absolute right-4 top-4 z-10 flex items-center gap-1.5 rounded-full bg-amber-500 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white shadow-md">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            Low
                        </span>
                    <?php endif; ?>

                    <!-- Status row -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span data-status-badge class="flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider border <?= $isActive ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-gray-500/10 text-gray-400 border-gray-500/20' ?>">
                            <span class="h-1.5 w-1.5 rounded-full <?= $isActive ? 'bg-green-500 animate-pulse' : 'bg-gray-500' ?>"></span>
                            <?= $isActive ? 'Active' : 'Inactive' ?>
                        </span>

                        <?php if ($isPortal): ?>
                            <span data-status-badge class="flex items-center gap-1 rounded-full bg-[#00A6A6]/10 border border-[#00A6A6]/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Portal
                            </span>
                        <?php else: ?>
                            <span data-status-badge class="flex items-center gap-1 rounded-full bg-gray-500/10 border border-gray-500/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                Hidden
                            </span>
                        <?php endif; ?>

                        <?php if ((int)($p['auto_renew'] ?? 0) === 1): ?>
                            <span data-status-badge class="flex items-center gap-1 rounded-full bg-blue-500/10 border border-blue-500/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Auto
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Icon + name -->
                    <div class="mt-6 flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl text-3xl transition-all duration-300 group-hover:rotate-6 group-hover:scale-110"
                            style="background-color: <?= $meta['color'] ?>1A; color: <?= $meta['color'] ?>;">
                            <?php if ($customIcon !== ''): ?>
                                <span aria-hidden="true"><?= e($customIcon) ?></span>
                            <?php else: ?>
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $meta['svg'] ?>" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-gray-500">
                                <?= e($meta['label']) ?> Tier
                            </div>
                            <h4 class="mt-0.5 truncate font-heading text-2xl tracking-wide text-white transition-colors duration-300 <?= $isActive ? 'group-hover:text-[#00A6A6]' : '' ?>">
                                <?= e($p['name'] ?? 'Untitled') ?>
                            </h4>
                        </div>
                    </div>

                    <?php if (!empty($p['description'])): ?>
                        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-gray-400">
                            <?= e($p['description']) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Price -->
                    <div class="mt-4 flex items-end justify-between">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Price</div>
                            <div class="mt-0.5 flex items-end gap-2">
                                <span class="font-heading text-4xl leading-none" style="color: <?= $meta['color'] ?>;">
                                    <?= peso($p['price'] ?? 0) ?>
                                </span>
                                <span class="mb-1 text-xs font-semibold text-gray-500">
                                    / <?= (int)($p['duration_days'] ?? 30) ?>d
                                </span>
                            </div>
                        </div>

                        <?php if ($belowCost): ?>
                            <span data-status-badge class="flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-500"
                                title="Price is below the configured operational cost (<?= peso($opCost) ?>)">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Below cost
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Features -->
                    <?php if (!empty($features)): ?>
                        <div class="mt-4">
                            <button type="button" onclick="toggleFeatures(this)" aria-expanded="false"
                                class="flex w-full items-center justify-between text-left text-[10px] font-bold uppercase tracking-widest text-gray-500 transition-colors hover:text-[#00A6A6]">
                                <span><?= count($features) ?> feature<?= count($features) !== 1 ? 's' : '' ?></span>
                                <svg class="features-toggle-icon w-3 h-3 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div class="features-list mt-2 hidden space-y-1.5 rounded-xl border border-white/5 bg-white/[0.02] p-3">
                                <?php foreach ($features as $feat): ?>
                                    <div class="flex items-start gap-2 text-xs text-gray-300">
                                        <span class="mt-0.5 inline-flex h-3.5 w-3.5 flex-shrink-0 items-center justify-center rounded-full bg-[#00A6A6]/15 text-[#00A6A6]">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span><?= e($feat) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-xl border border-dashed border-white/5 bg-white/[0.02] p-3 text-center text-[11px] text-gray-500">
                            No features listed
                        </div>
                    <?php endif; ?>

                    <!-- Stats row -->
                    <div class="mt-4 grid grid-cols-3 gap-2 border-t border-white/5 pt-4">
                        <div class="text-center">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Active</div>
                            <div class="mt-0.5 font-heading text-xl <?= $activeEnroll > 0 ? 'text-green-500' : 'text-gray-600' ?>">
                                <?= $activeEnroll ?>
                            </div>
                        </div>
                        <div class="border-x border-white/5 text-center">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Revenue</div>
                            <div class="mt-0.5 truncate font-heading text-xl <?= $revenue > 0 ? 'text-[#00A6A6]' : 'text-gray-600' ?>"
                                title="<?= peso($revenue) ?>">
                                <?= $revenue > 0 ? peso($revenue) : '—' ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Ended</div>
                            <div class="mt-0.5 font-heading text-xl <?= ($expiredEnroll + $cancelEnroll) > 0 ? 'text-amber-500' : 'text-gray-600' ?>"
                                title="<?= $expiredEnroll ?> expired · <?= $cancelEnroll ?> cancelled">
                                <?= $expiredEnroll + $cancelEnroll ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pending banner -->
                    <?php if ($pendingEnroll > 0): ?>
                        <div class="mt-3 flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/5 px-3 py-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <span class="text-xs font-semibold text-amber-500">
                                <?= $pendingEnroll ?> pending enrollment<?= $pendingEnroll !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Action buttons -->
                    <div class="mt-5 flex items-center gap-2 border-t border-white/5 pt-4">
                        <button type="button" onclick='openProgramModal(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>

                        <form method="post" action="<?= url_e('/admin-dashboard/programs/toggle') ?>" class="contents">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <input type="hidden" name="active" value="<?= $isActive ? '0' : '1' ?>">
                            <button type="submit"
                                title="<?= $isActive ? 'Deactivate' : 'Activate' ?>"
                                aria-label="<?= $isActive ? 'Deactivate' : 'Activate' ?> program <?= e($p['name'] ?? '') ?>"
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                <?php if ($isActive): ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                <?php endif; ?>
                            </button>
                        </form>

                        <form method="post" action="<?= url_e('/admin-dashboard/programs/preview') ?>" class="contents">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <input type="hidden" name="portal_visible" value="<?= $isPortal ? '0' : '1' ?>">
                            <button type="submit"
                                title="<?= $isPortal ? 'Hide from portal' : 'Show in portal' ?>"
                                aria-label="<?= $isPortal ? 'Hide' : 'Show' ?> <?= e($p['name'] ?? '') ?> in member portal"
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                <?php if ($isPortal): ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                <?php endif; ?>
                            </button>
                        </form>

                        <button type="button" onclick='openDuplicateProgramModal(<?= json_encode(["id" => $p["id"], "name" => $p["name"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            title="Duplicate"
                            aria-label="Duplicate program <?= e($p['name'] ?? '') ?>"
                            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>

                        <button type="button" onclick='openDeleteProgramModal(<?= json_encode(["id" => $p["id"], "name" => $p["name"]], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            title="Delete"
                            aria-label="Delete program <?= e($p['name'] ?? '') ?>"
                            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-red-500 hover:bg-red-500/10 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                    <!-- Reorder -->
                    <div class="mt-3 flex items-center justify-end gap-1 text-[10px] text-gray-600">
                        <span class="mr-1 font-bold uppercase tracking-wider">Order</span>
                        <button type="button" onclick="reorderMove('<?= e($p['id']) ?>', -1)"
                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-white/10 bg-white/5 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]"
                            aria-label="Move <?= e($p['name'] ?? 'program') ?> up" title="Move up">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>
                        <button type="button" onclick="reorderMove('<?= e($p['id']) ?>', 1)"
                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-white/10 bg-white/5 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]"
                            aria-label="Move <?= e($p['name'] ?? 'program') ?> down" title="Move down">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="noProgramResults" class="hidden rounded-2xl border border-dashed border-white/10 bg-[#111] p-12 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <p class="mt-4 text-sm text-gray-400">No programs match this filter.</p>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         ADD / EDIT MODAL — with ₱ prefix + min="0.01"
         ============================================================ -->
    <div id="programModal" role="dialog" aria-modal="true" aria-labelledby="programModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/5 bg-[#111] p-6">
                <div>
                    <h2 id="programModalTitle" class="font-heading text-2xl tracking-wide text-white">
                        <span id="pm-title">NEW PROGRAM</span>
                    </h2>
                    <p class="mt-1 text-xs text-gray-400">Configure membership tier details — fields marked * are required</p>
                </div>
                <button type="button" onclick="closeProgramModal()"
                    aria-label="Close program dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/programs') ?>" id="programForm" class="p-6 sm:p-8 space-y-5" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="pm-id">

                <!-- Program name -->
                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Program Name <span class="text-[#D50000]">*</span>
                    </span>
                    <input name="name" id="pm-name" required maxlength="190"
                        data-validate="required|min:2|max:190"
                        data-error-label="Program Name"
                        placeholder="e.g. Premium All-Inclusive"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <span id="pm-name-error" class="mt-1 hidden text-xs font-semibold text-[#D50000]"></span>
                    <span class="mt-2 block text-[11px] text-gray-500">Must be unique across all programs.</span>
                </label>

                <!-- Tier + Icon -->
                <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
                    <label class="block">
                        <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Tier <span class="text-[#D50000]">*</span>
                        </span>
                        <select name="tier" id="pm-tier" required
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                            <option value="digital">Digital</option>
                            <option value="standard" selected>Standard</option>
                            <option value="premium">Premium</option>
                            <option value="b2b">B2B</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Icon / Emoji</span>
                        <input name="icon" id="pm-icon" maxlength="8" placeholder="Auto"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-center text-xl text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    </label>
                </div>

                <!-- Price + Duration — PESO FIX APPLIED -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Price (₱) <span class="text-[#D50000]">*</span>
                        </span>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-500" aria-hidden="true">₱</span>
                            <input type="number" name="price" id="pm-price" min="0.01" step="0.01" required
                                inputmode="decimal"
                                data-validate="required|positive"
                                data-error-label="Price"
                                placeholder="1000.00"
                                class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-9 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </div>
                        <span id="pm-price-error" class="mt-1 hidden text-xs font-semibold text-[#D50000]"></span>
                    </label>

                    <label class="block">
                        <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Duration (days) <span class="text-[#D50000]">*</span>
                        </span>
                        <input type="number" name="duration_days" id="pm-duration" min="1" max="3650" value="30" required
                            data-validate="required|min:1|max:3650"
                            data-error-label="Duration"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    </label>
                </div>

                <!-- Operational cost + Grace -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Operational Cost (₱)
                        </span>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-500" aria-hidden="true">₱</span>
                            <input type="number" name="operational_cost" id="pm-opcost" min="0" step="0.01" value="0"
                                inputmode="decimal"
                                data-validate="nonnegative"
                                data-error-label="Operational Cost"
                                class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-9 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </div>
                        <span id="pm-opcost-warning" class="mt-1 hidden text-xs font-semibold text-amber-500">
                            Price is below the operational cost
                        </span>
                        <span class="mt-2 block text-[11px] text-gray-500">Used to warn if price is too low.</span>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Grace Period (days)
                        </span>
                        <input type="number" name="grace_period_days" id="pm-grace" min="0" max="90" value="3"
                            data-validate="min:0|max:90"
                            data-error-label="Grace Period"
                            class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <span class="mt-2 block text-[11px] text-gray-500">Days after expiry before lockout.</span>
                    </label>
                </div>

                <!-- Description -->
                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Description</span>
                    <textarea name="description" id="pm-desc" rows="2" maxlength="500"
                        placeholder="Short summary shown on the member portal."
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20"></textarea>
                </label>

                <!-- Features -->
                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Feature List</span>
                    <textarea name="features_text" id="pm-features" rows="4"
                        placeholder="One feature per line, e.g.:&#10;Full gym access&#10;QR check-in&#10;Attendance tracking&#10;Group classes"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20"></textarea>
                    <span class="mt-2 block text-[11px] text-gray-500">Each line becomes a feature chip.</span>
                </label>

                <!-- Visibility -->
                <fieldset class="rounded-2xl border border-white/5 bg-white/[0.02] p-5">
                    <legend class="px-2 text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">
                        Visibility &amp; Behavior
                    </legend>

                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-white/[0.02]">
                            <input type="checkbox" name="active" id="pm-active" value="1" checked
                                class="h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6]">
                            <div>
                                <div class="text-sm font-semibold text-white">Program is active</div>
                                <div class="text-[11px] text-gray-500">Inactive programs are hidden from the member portal.</div>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-white/[0.02]">
                            <input type="checkbox" name="portal_visible" id="pm-portal" value="1" checked
                                class="h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6]">
                            <div>
                                <div class="text-sm font-semibold text-white">Visible in member portal</div>
                                <div class="text-[11px] text-gray-500">Members can see and enroll in this program.</div>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 transition-colors hover:bg-white/[0.02]">
                            <input type="checkbox" name="auto_renew" id="pm-autorenew" value="1"
                                class="h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6]">
                            <div>
                                <div class="text-sm font-semibold text-white">Auto-renew by default</div>
                                <div class="text-[11px] text-gray-500">Applies to new enrollments unless overridden.</div>
                            </div>
                        </label>
                    </div>
                </fieldset>

                <!-- Actions -->
                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" onclick="closeProgramModal()"
                        class="rounded-full border border-white/10 bg-transparent px-6 py-3 text-sm font-bold text-gray-300 transition-all hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" id="pm-submit"
                        class="group/btn relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                            hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]
                            disabled:cursor-not-allowed disabled:opacity-60">
                        <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
                        <span class="relative" id="pm-submit-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                        </span>
                        <span class="relative" id="pm-submit-label">Save Program</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         DUPLICATE MODAL
         ============================================================ -->
    <div id="duplicateProgramModal" role="dialog" aria-modal="true" aria-labelledby="duplicateModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-sm rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 id="duplicateModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">DUPLICATE PROGRAM?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Create a copy of <strong id="dup-name" class="text-white">this program</strong>.
                    The copy will be inactive and hidden from the member portal — edit and activate when ready.
                </p>
            </div>
            <form method="post" action="<?= url_e('/admin-dashboard/programs/duplicate') ?>" class="mt-6">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="dup-id">
                <div class="flex gap-3">
                    <button type="button" onclick="closeDuplicateProgramModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-all hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Duplicate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         DELETE MODAL
         ============================================================ -->
    <div id="deleteProgramModal" role="dialog" aria-modal="true" aria-labelledby="deleteProgramModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-sm rounded-3xl border border-red-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 id="deleteProgramModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">DELETE PROGRAM?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    You are about to delete <strong id="delp-name" class="text-white">this program</strong>.
                    Members already enrolled will keep their access, but no one new can enroll.
                </p>
            </div>
            <form id="deleteProgramForm" method="post" action="<?= url_e('/admin-dashboard/programs/delete') ?>" class="mt-6">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="delp-id">
                <div class="flex gap-3">
                    <button type="button" onclick="closeDeleteProgramModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-all hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-red-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(213,0,0,0.3)] transition-all hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-[0_0_25px_rgba(213,0,0,0.5)]">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes card-enter {
        from {
            opacity: 0;
            transform: translateY(16px) scale(0.96);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
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

    .tier-filter.active {
        background-color: #00A6A6 !important;
        border-color: #00A6A6 !important;
        color: #ffffff !important;
        box-shadow: 0 0 15px rgba(0, 166, 166, 0.4);
    }

    .tier-filter.active span:last-child {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    @media (prefers-reduced-motion: reduce) {

        .program-card,
        #programModal>div,
        #duplicateProgramModal>div,
        #deleteProgramModal>div {
            animation: none !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        const PROGRAM_IDS = <?= json_encode(array_column($programs, 'id')) ?>;
        const CSRF = document.querySelector('meta[name="csrf"]')?.content || '';
        const BASE = document.querySelector('meta[name="base"]')?.content || '';

        /* ============ Filter + search ============ */
        let currentTierFilter = 'all';
        let currentSearch = '';

        window.setTierFilter = function(tier) {
            currentTierFilter = tier;
            document.querySelectorAll('[data-tier-filter]').forEach(btn => {
                const active = btn.dataset.tierFilter === tier;
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            applyFilters();
        };

        document.getElementById('programSearch')?.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase().trim();
            applyFilters();
        });

        function applyFilters() {
            const cards = document.querySelectorAll('#programGrid .program-card');
            let visible = 0;

            cards.forEach(card => {
                const tier = card.dataset.tier || 'standard';
                const search = card.dataset.search || '';

                const okTier = currentTierFilter === 'all' || tier === currentTierFilter;
                const okSearch = currentSearch === '' || search.includes(currentSearch);

                if (okTier && okSearch) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noResults = document.getElementById('noProgramResults');
            if (noResults) {
                noResults.classList.toggle('hidden', visible > 0 || cards.length === 0);
            }
        }

        /* ============ Feature toggle ============ */
        window.toggleFeatures = function(btn) {
            const list = btn.parentElement.querySelector('.features-list');
            const icon = btn.querySelector('.features-toggle-icon');
            if (!list) return;

            const isOpen = !list.classList.contains('hidden');
            list.classList.toggle('hidden', isOpen);
            if (icon) icon.style.transform = isOpen ? '' : 'rotate(180deg)';
            btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        };

        /* ============ Modal helpers ============ */
        function trackTrigger() {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
        }

        /* ============ Add / edit modal ============ */
        window.openProgramModal = function(p) {
            const isEdit = p && p.id;

            trackTrigger();

            document.getElementById('pm-title').textContent = isEdit ? 'EDIT PROGRAM' : 'NEW PROGRAM';
            document.getElementById('pm-id').value = p?.id ?? '';
            document.getElementById('pm-name').value = p?.name ?? '';
            document.getElementById('pm-tier').value = p?.tier ?? 'standard';
            document.getElementById('pm-icon').value = p?.icon ?? '';
            document.getElementById('pm-price').value = p?.price ?? '';
            document.getElementById('pm-duration').value = p?.duration_days ?? 30;
            document.getElementById('pm-opcost').value = p?.operational_cost ?? 0;
            document.getElementById('pm-grace').value = p?.grace_period_days ?? 3;
            document.getElementById('pm-desc').value = p?.description ?? '';

            let featureLines = '';
            if (p && p.features) {
                try {
                    const arr = typeof p.features === 'string' ? JSON.parse(p.features) : p.features;
                    if (Array.isArray(arr)) featureLines = arr.join('\n');
                } catch (_) {}
            }
            document.getElementById('pm-features').value = featureLines;

            document.getElementById('pm-active').checked = p ? (p.active == 1) : true;
            document.getElementById('pm-portal').checked = p ? (p.portal_visible == 1) : true;
            document.getElementById('pm-autorenew').checked = p ? (p.auto_renew == 1) : false;

            /* Clear stale validation */
            const form = document.getElementById('programForm');
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
            document.getElementById('pm-name-error')?.classList.add('hidden');
            document.getElementById('pm-price-error')?.classList.add('hidden');
            updateCostWarning();

            const m = document.getElementById('programModal');
            m.classList.replace('hidden', 'flex');
        };

        window.closeProgramModal = function() {
            document.getElementById('programModal').classList.replace('flex', 'hidden');
        };

        /* Cost floor warning */
        function updateCostWarning() {
            const price = parseFloat(document.getElementById('pm-price').value || '0');
            const cost = parseFloat(document.getElementById('pm-opcost').value || '0');
            const warn = document.getElementById('pm-opcost-warning');
            if (!warn) return;

            if (cost > 0 && price > 0 && price < cost) {
                warn.classList.remove('hidden');
            } else {
                warn.classList.add('hidden');
            }
        }

        document.getElementById('pm-price')?.addEventListener('input', updateCostWarning);
        document.getElementById('pm-opcost')?.addEventListener('input', updateCostWarning);

        /* Prevent double-submit */
        document.getElementById('programForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('pm-submit');
            const icon = document.getElementById('pm-submit-icon');
            const label = document.getElementById('pm-submit-label');

            if (btn.disabled) {
                e.preventDefault();
                return;
            }

            setTimeout(() => {
                if (!e.defaultPrevented && !btn.disabled) {
                    btn.disabled = true;
                    icon.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    label.textContent = 'Saving…';
                }
            }, 0);
        });

        /* ============ Duplicate modal ============ */
        window.openDuplicateProgramModal = function(p) {
            trackTrigger();
            document.getElementById('dup-id').value = p.id;
            document.getElementById('dup-name').textContent = p.name;
            document.getElementById('duplicateProgramModal').classList.replace('hidden', 'flex');
        };
        window.closeDuplicateProgramModal = function() {
            document.getElementById('duplicateProgramModal').classList.replace('flex', 'hidden');
        };

        /* ============ Delete modal ============ */
        window.openDeleteProgramModal = function(p) {
            trackTrigger();
            document.getElementById('delp-id').value = p.id;
            document.getElementById('delp-name').textContent = p.name;
            document.getElementById('deleteProgramModal').classList.replace('hidden', 'flex');
        };
        window.closeDeleteProgramModal = function() {
            document.getElementById('deleteProgramModal').classList.replace('flex', 'hidden');
        };

        /* ============ Reorder (arrow buttons) ============ */
        let reorderPending = false;

        window.reorderMove = function(programId, direction) {
            if (reorderPending) return;

            const idx = PROGRAM_IDS.indexOf(programId);
            if (idx === -1) return;

            const newIdx = idx + direction;
            if (newIdx < 0 || newIdx >= PROGRAM_IDS.length) return;

            [PROGRAM_IDS[idx], PROGRAM_IDS[newIdx]] = [PROGRAM_IDS[newIdx], PROGRAM_IDS[idx]];

            const grid = document.getElementById('programGrid');
            const cards = Array.from(grid.querySelectorAll('.program-card'));
            const current = cards.find(c => c.dataset.id === programId);
            if (current && grid) {
                if (direction === -1) {
                    const prev = current.previousElementSibling;
                    if (prev) grid.insertBefore(current, prev);
                } else {
                    const next = current.nextElementSibling;
                    if (next) grid.insertBefore(next, current);
                }
            }

            reorderPending = true;

            const fd = new FormData();
            fd.append('_csrf', CSRF);
            PROGRAM_IDS.forEach(id => fd.append('order[]', id));

            fetch(BASE + '/admin-dashboard/programs/reorder', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(() => {
                    if (window.PMGZ_A11Y && window.PMGZ_A11Y.announce) {
                        window.PMGZ_A11Y.announce('Program order saved');
                    }
                })
                .catch(err => console.warn('[reorder] failed:', err))
                .finally(() => {
                    reorderPending = false;
                });
        };

        /* ============ Backdrop close ============ */
        const MODALS = [
            ['programModal', 'closeProgramModal'],
            ['duplicateProgramModal', 'closeDuplicateProgramModal'],
            ['deleteProgramModal', 'closeDeleteProgramModal'],
        ];

        MODALS.forEach(([id, fn]) => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) window[fn]();
            });
        });
    })();
</script>