<?php

/**
 * Admin — Overview Tab (2026 UI)
 *
 * The command centre: KPIs first, then the four graphs that answer
 * "what is happening right now?", then supporting operations data.
 *
 * Receives from AdminController::dashboard():
 *   $data['users']                 — User::listWithStats()
 *   $data['payments']              — Payment::listWithFilters()
 *   $data['paymentSummary']        — Payment::statusSummary()
 *   $data['enrollments']           — Enrollment rows
 *   $data['enrollSummary']         — Enrollment::statusSummary()
 *   $data['partnerSales']          — PartnerSale rows
 *   $data['attendanceStats']       — Attendance::todayStats()
 *   $data['attendanceCurrently']   — Attendance::currentlyIn()
 *   $data['attendanceToday']       — Attendance::todayAll()
 *   $data['attendanceStreaks']     — Attendance::topStreaks(10)
 *   $data['attendanceInactive']    — Attendance::inactiveMembers(7)
 */

$users          = $data['users']               ?? [];
$payments       = $data['payments']            ?? [];
$paymentSum     = $data['paymentSummary']      ?? [];
$enrollments    = $data['enrollments']         ?? [];
$enrollSum      = $data['enrollSummary']       ?? [];
$partnerSales   = $data['partnerSales']        ?? [];

$attStats        = $data['attendanceStats']     ?? [];
$currentlyIn     = $data['attendanceCurrently'] ?? [];
$todayAttendance = $data['attendanceToday']     ?? [];
$streaks         = $data['attendanceStreaks']   ?? [];
$inactive        = $data['attendanceInactive']  ?? [];

$today = date('Y-m-d');

/* ---------- Today's revenue ---------- */
$todayRev = 0;
$todayRevCount = 0;
foreach ($payments as $p) {
    if (($p['paid_date'] ?? '') === $today
        && in_array($p['status'] ?? '', ['paid', 'partial_refund'], true)
    ) {
        $todayRev += (float)($p['amount'] ?? 0);
        $todayRevCount++;
    }
}

/* ---------- Yesterday revenue (for trend) ---------- */
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yRev = 0;
foreach ($payments as $p) {
    if (($p['paid_date'] ?? '') === $yesterday
        && in_array($p['status'] ?? '', ['paid', 'partial_refund'], true)
    ) {
        $yRev += (float)($p['amount'] ?? 0);
    }
}
$revTrend = $yRev > 0
    ? round((($todayRev - $yRev) / $yRev) * 100, 1)
    : ($todayRev > 0 ? 100 : 0);

/* ---------- Membership KPIs ---------- */
$activeEnrolls    = (int)($enrollSum['active']        ?? 0);
$pendingEnrolls   = (int)($enrollSum['pending']       ?? 0);
$expiringSoon     = (int)($enrollSum['expiring_soon'] ?? 0);

$totalMembers = 0;
foreach ($users as $u) {
    if (($u['role'] ?? '') !== 'admin') $totalMembers++;
}

/* ---------- Live attendance ---------- */
$currentlyInCount = count($currentlyIn);
$capacity         = (int)($attStats['capacity']    ?? 50);
$inGym            = (int)($attStats['in_gym']      ?? $currentlyInCount);
$util             = (float)($attStats['utilization'] ?? 0);
$todayCheckins    = (int)($attStats['total']       ?? 0);
$todayHours       = (float)($attStats['hours']     ?? 0);
$avgMinutes       = (int)($attStats['avg_minutes'] ?? 0);

/* ---------- Success / failure ---------- */
$successRate = (float)($paymentSum['success_rate'] ?? 0);
$failedCount = (int)($paymentSum['failed']         ?? 0);

/* ---------- Payment mix ---------- */
$paymentMix = [
    ['label' => 'Paid',     'value' => (int)($paymentSum['paid'] ?? 0),                 'color' => '#22c55e'],
    ['label' => 'Pending',  'value' => (int)($paymentSum['pending'] ?? 0),              'color' => '#f59e0b'],
    ['label' => 'Failed',   'value' => (int)($paymentSum['failed'] ?? 0),                'color' => '#ef4444'],
    ['label' => 'Refunded', 'value' => (int)($paymentSum['refunded'] ?? 0)
        + (int)($paymentSum['partial_refund'] ?? 0),                                    'color' => '#a855f7'],
];
$paymentMixTotal = array_sum(array_column($paymentMix, 'value'));

/* ---------- Enrollment mix ---------- */
$enrollMix = [
    ['label' => 'Active',    'value' => (int)($enrollSum['active'] ?? 0),    'color' => '#22c55e'],
    ['label' => 'Pending',   'value' => (int)($enrollSum['pending'] ?? 0),   'color' => '#f59e0b'],
    ['label' => 'Paused',    'value' => (int)($enrollSum['paused'] ?? 0),    'color' => '#3b82f6'],
    ['label' => 'Expired',   'value' => (int)($enrollSum['expired'] ?? 0),   'color' => '#64748b'],
    ['label' => 'Cancelled', 'value' => (int)($enrollSum['cancelled'] ?? 0), 'color' => '#ef4444'],
];
$enrollMixTotal = array_sum(array_column($enrollMix, 'value'));

/* ---------- Occupancy hourly ---------- */
$hourly = [];
foreach ($todayAttendance as $a) {
    if (empty($a['time_in'])) continue;
    $h = (int)date('G', strtotime($a['time_in']));
    $hourly[$h] = ($hourly[$h] ?? 0) + 1;
}
$occupancy = [];
for ($i = 6; $i <= 20; $i++) {
    $occupancy[] = ['hour' => sprintf('%02d:00', $i), 'checkins' => $hourly[$i] ?? 0];
}

/* ---------- Revenue last 7 days ---------- */
$revenue = [];
for ($i = 6; $i >= 0; $i--) {
    $d   = date('Y-m-d', strtotime("-{$i} days"));
    $sum = 0;
    foreach ($payments as $p) {
        if (($p['paid_date'] ?? '') === $d
            && in_array($p['status'] ?? '', ['paid', 'partial_refund'], true)
        ) {
            $sum += (float)($p['amount'] ?? 0);
        }
    }
    $revenue[] = ['day' => date('D', strtotime($d)), 'date' => $d, 'amount' => $sum];
}

/* ---------- Recent activity feed ---------- */
$activity = [];
foreach ($payments as $p) {
    if (!in_array($p['status'] ?? '', ['paid', 'partial_refund'], true)) continue;
    $activity[] = [
        'time' => $p['paid_date'] ?? '',
        'type' => 'payment',
        'text' => ($p['user_name'] ?? 'Member') . ' paid ' . peso($p['amount'] ?? 0),
        'sub'  => $p['description'] ?? ($p['tier'] ?? ''),
    ];
}
foreach ($enrollments as $e) {
    $activity[] = [
        'time' => $e['start_date'] ?? $e['created_date'] ?? '',
        'type' => 'enrollment',
        'text' => ($e['user_name'] ?? 'Member') . ' enrolled in ' . ($e['tier'] ?? 'tier'),
        'sub'  => $e['program_name'] ?? '',
    ];
}
foreach ($partnerSales as $s) {
    if (($s['status'] ?? '') !== 'approved') continue;
    $activity[] = [
        'time' => $s['sale_date'] ?? '',
        'type' => 'sale',
        'text' => 'Partner sale approved: ' . peso($s['amount'] ?? 0),
        'sub'  => $s['description'] ?? '',
    ];
}
usort($activity, fn($a, $b) => strcmp($b['time'], $a['time']));
$activity = array_slice($activity, 0, 6);

/* ---------- Streak leader ---------- */
$topStreak = $streaks[0] ?? null;

/* ---------- Greeting ---------- */
$adminName  = current_user()['full_name'] ?? 'Admin';
$adminFirst = explode(' ', $adminName)[0];
$hour       = (int)date('G');
$greeting   = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$todayLabel = date('l, F j, Y');
$utilWidth  = max(0, min(100, $util));
?>

<div class="space-y-7 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Admin Panel</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    DASHBOARD <span class="text-[#00A6A6]">AT A GLANCE</span>
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    <?= e($greeting) ?> — memberships, revenue, and gym activity for <strong class="font-semibold text-gray-300"><?= e($todayLabel) ?></strong>.
                </p>
            </div>

            <div class="flex flex-wrap gap-3 xl:flex-shrink-0 xl:justify-end">
                <a href="<?= url_e('/admin-dashboard?tab=payments') ?>"
                    class="tap-effect group inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                    <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Review Payments
                </a>
                <a href="<?= url_e('/admin-dashboard?tab=analytics') ?>"
                    class="tap-effect group inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-xs font-bold text-white backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-white/10">
                    <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Open Analytics
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP — the four numbers that matter first
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Members -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Members</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= $totalMembers ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $activeEnrolls ?> active · <?= $pendingEnrolls ?> pending
                    </div>
                </div>
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Enrollments -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/20 bg-green-500/5 p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <?php if ($activeEnrolls > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 motion-safe:animate-pulse"></span>
                        <?php endif; ?>
                        Active Enrollments
                    </div>
                    <div class="mt-2 font-heading text-4xl text-green-500"><?= $activeEnrolls ?></div>
                    <div class="mt-1 text-xs">
                        <?php if ($expiringSoon > 0): ?>
                            <span class="font-bold text-amber-500"><?= $expiringSoon ?> expiring soon</span>
                        <?php else: ?>
                            <span class="text-gray-400">None expiring soon</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Revenue -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Today's Revenue</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]"><?= peso($todayRev) ?></div>
                    <div class="mt-1 flex flex-wrap items-center gap-1 text-xs">
                        <?php if ($revTrend > 0): ?>
                            <span class="font-bold text-green-500">▲ <?= $revTrend ?>%</span>
                        <?php elseif ($revTrend < 0): ?>
                            <span class="font-bold text-red-500">▼ <?= abs($revTrend) ?>%</span>
                        <?php else: ?>
                            <span class="text-gray-500">—</span>
                        <?php endif; ?>
                        <span class="text-gray-400">· <?= $todayRevCount ?> payment<?= $todayRevCount !== 1 ? 's' : '' ?></span>
                    </div>
                </div>
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- In Gym Now -->
        <div class="group relative overflow-hidden rounded-2xl border border-red-500/30 bg-red-500/5 p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-red-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-red-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-red-500">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-60 motion-safe:animate-ping"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                        </span>
                        In Gym Now
                    </div>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <div class="font-heading text-4xl text-red-500" id="ovInGym"><?= $inGym ?></div>
                        <div class="text-sm font-bold text-gray-400">/ <?= $capacity ?></div>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <span id="ovUtil"><?= $util ?></span>% of capacity
                    </div>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-red-500/10" role="img" aria-label="Gym capacity utilization <?= $util ?> percent">
                        <div id="ovUtilBar" class="h-full rounded-full bg-red-500 transition-all duration-500" style="width: <?= $utilWidth ?>%"></div>
                    </div>
                </div>
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         INACTIVE ALERT — surfaced before the graphs
         ============================================================ -->
    <?php if (!empty($inactive)): ?>
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-bold text-amber-500">
                        <?= count($inactive) ?> inactive member<?= count($inactive) !== 1 ? 's' : '' ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        No check-ins in 7+ days. Consider a check-in message.
                    </div>
                </div>
            </div>
            <a href="<?= url_e('/admin-dashboard?tab=live') ?>#inactive"
                class="tap-effect inline-flex flex-shrink-0 items-center gap-2 rounded-full bg-amber-500 px-5 py-2.5 text-xs font-bold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:bg-amber-600 hover:shadow-lg">
                Review
            </a>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         PERFORMANCE GRAPHS — the four views worth seeing daily
         ============================================================ -->
    <section aria-labelledby="overview-performance-heading">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="overview-performance-heading" class="font-heading text-3xl tracking-wide text-white">PERFORMANCE</h2>
                <p class="mt-1 text-sm text-gray-400">Revenue, foot traffic, payments, and membership mix.</p>
            </div>
            <a href="<?= url_e('/admin-dashboard?tab=analytics') ?>"
                class="text-xs font-bold text-[#00A6A6] transition-colors hover:text-white">
                Full analytics &rarr;
            </a>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">

            <!-- Revenue — last 7 days -->
            <div class="min-w-0 rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">REVENUE — LAST 7 DAYS</h3>
                        <p class="mt-1 text-xs text-gray-400">All payments (B2C + B2B)</p>
                    </div>
                    <span class="flex-shrink-0 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                        <?= peso(array_sum(array_column($revenue, 'amount'))) ?>
                    </span>
                </div>
                <div class="mt-4 h-56 sm:h-60">
                    <canvas id="revenueChart" role="img" aria-label="Line chart of daily revenue for the last 7 days"></canvas>
                </div>
            </div>

            <!-- Gym occupancy today -->
            <div class="min-w-0 rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">GYM OCCUPANCY TODAY</h3>
                        <p class="mt-1 text-xs text-gray-400">Check-ins per hour · 6 AM – 8 PM</p>
                    </div>
                    <span class="flex-shrink-0 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                        Peak: <?= max(array_column($occupancy, 'checkins') ?: [0]) ?>
                    </span>
                </div>
                <div class="mt-4 h-56 sm:h-60">
                    <canvas id="occupancyChart" role="img" aria-label="Bar chart of check-ins per hour today"></canvas>
                </div>
            </div>

            <!-- Payment mix -->
            <div class="min-w-0 rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">PAYMENT MIX</h3>
                        <p class="mt-1 text-xs text-gray-400">Every payment by status</p>
                    </div>
                    <a href="<?= url_e('/admin-dashboard?tab=payments') ?>"
                        class="flex-shrink-0 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6] transition-colors hover:bg-[#00A6A6]/20">
                        <?= $paymentMixTotal ?> total
                    </a>
                </div>

                <?php if ($paymentMixTotal === 0): ?>
                    <div class="mt-4 flex h-56 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 bg-white/[0.02] sm:h-60">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/5 text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">No payments yet</p>
                        <p class="mt-1 text-xs text-gray-500">Status breakdown will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="relative mt-4 h-56 sm:h-60">
                        <canvas id="paymentMixChart" role="img" aria-label="Doughnut chart of payments by status"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center pb-8" aria-hidden="true">
                            <span class="font-heading text-3xl text-white"><?= $paymentMixTotal ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Payments</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Enrollment mix -->
            <div class="min-w-0 rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">MEMBERSHIP MIX</h3>
                        <p class="mt-1 text-xs text-gray-400">Enrollments by current status</p>
                    </div>
                    <a href="<?= url_e('/admin-dashboard?tab=enrollments') ?>"
                        class="flex-shrink-0 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6] transition-colors hover:bg-[#00A6A6]/20">
                        <?= $enrollMixTotal ?> total
                    </a>
                </div>

                <?php if ($enrollMixTotal === 0): ?>
                    <div class="mt-4 flex h-56 flex-col items-center justify-center rounded-xl border border-dashed border-white/10 bg-white/[0.02] sm:h-60">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/5 text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">No enrollments yet</p>
                        <p class="mt-1 text-xs text-gray-500">Membership mix will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="relative mt-4 h-56 sm:h-60">
                        <canvas id="enrollmentMixChart" role="img" aria-label="Doughnut chart of enrollments by status"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center pb-8" aria-hidden="true">
                            <span class="font-heading text-3xl text-white"><?= $enrollMixTotal ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Enrollments</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================
         TODAY AT THE GYM — secondary numbers in one panel
         ============================================================ -->
    <section class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm sm:p-6" aria-labelledby="overview-today-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="overview-today-heading" class="font-heading text-2xl tracking-wide text-white">TODAY AT THE GYM</h2>
                <p class="mt-1 text-xs text-gray-400">Attendance and collection health in one place.</p>
            </div>
            <a href="<?= url_e('/admin-dashboard?tab=live') ?>"
                class="text-xs font-bold text-[#00A6A6] transition-colors hover:text-white">
                Live monitor &rarr;
            </a>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-5 sm:grid-cols-4">
            <div class="min-w-0">
                <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Today's Check-ins</dt>
                <dd class="mt-1 font-heading text-3xl text-white"><?= $todayCheckins ?></dd>
                <dd class="mt-1 text-xs text-gray-400"><span id="ovStillIn"><?= $currentlyInCount ?></span> still in</dd>
            </div>

            <div class="min-w-0">
                <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Hours Tracked</dt>
                <dd class="mt-1 font-heading text-3xl text-[#00A6A6]"><?= $todayHours ?>h</dd>
                <dd class="mt-1 text-xs text-gray-400">Avg <?= $avgMinutes ?>m / session</dd>
            </div>

            <div class="min-w-0">
                <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Payment Success</dt>
                <?php $srColor = $successRate >= 90 ? 'text-green-500' : ($successRate >= 70 ? 'text-amber-500' : 'text-red-500'); ?>
                <dd class="mt-1 font-heading text-3xl <?= $srColor ?>"><?= number_format($successRate, 1) ?>%</dd>
                <dd class="mt-1 text-xs <?= $failedCount > 0 ? 'text-red-500' : 'text-gray-400' ?>">
                    <?= $failedCount > 0 ? $failedCount . ' failed' : 'No failures' ?>
                </dd>
            </div>

            <div class="min-w-0">
                <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Top Streak</dt>
                <?php if ($topStreak): ?>
                    <dd class="mt-1 font-heading text-3xl text-orange-500"><?= (int)$topStreak['streak'] ?>d</dd>
                    <dd class="mt-1 truncate text-xs text-gray-400"><?= e($topStreak['user_name'] ?? '—') ?></dd>
                <?php else: ?>
                    <dd class="mt-1 font-heading text-3xl text-gray-600">—</dd>
                    <dd class="mt-1 text-xs text-gray-400">No streaks yet</dd>
                <?php endif; ?>
            </div>
        </dl>
    </section>

    <!-- ============================================================
         RECENT ACTIVITY
         ============================================================ -->
    <div class="rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="flex items-center justify-between border-b border-white/5 p-5 sm:p-6">
            <div>
                <h2 class="font-heading text-2xl tracking-wide text-white">RECENT ACTIVITY</h2>
                <p class="mt-1 text-xs text-gray-400">Latest events across the gym</p>
            </div>
            <a href="<?= url_e('/admin-dashboard?tab=payments') ?>"
                class="text-xs font-bold text-[#00A6A6] transition-colors hover:text-white">
                View all &rarr;
            </a>
        </div>

        <?php if (empty($activity)): ?>
            <div class="p-14 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No activity yet. Once members enroll and pay, events will appear here.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-white/5" role="list">
                <?php foreach ($activity as $a):
                    $tone = match ($a['type']) {
                        'payment' => [
                            'bg'   => 'bg-green-500/10',
                            'text' => 'text-green-500',
                            'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                        'enrollment' => [
                            'bg'   => 'bg-[#00A6A6]/10',
                            'text' => 'text-[#00A6A6]',
                            'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        ],
                        'sale' => [
                            'bg'   => 'bg-amber-500/10',
                            'text' => 'text-amber-500',
                            'path' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                        ],
                        default => [
                            'bg'   => 'bg-gray-500/10',
                            'text' => 'text-gray-400',
                            'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                    };
                ?>
                    <li class="group flex items-center gap-4 p-4 transition-colors duration-200 hover:bg-white/[0.02]">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full <?= $tone['bg'] ?> <?= $tone['text'] ?> transition-transform duration-300 group-hover:scale-110">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tone['path'] ?>" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-white"><?= e($a['text']) ?></div>
                            <?php if (!empty($a['sub'])): ?>
                                <div class="truncate text-xs text-gray-500"><?= e($a['sub']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-shrink-0 text-xs text-gray-500">
                            <?= e(short_date($a['time'])) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .animate-\[fade-in_0\.4s_ease-out\] {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        const BASE = document.querySelector('meta[name="base"]')?.content || '';
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? '#334155' : '#e5e7eb';
        const tickColor = isDark ? '#94a3b8' : '#4b5563';
        const tealColor = '#00A6A6';

        Chart.defaults.font.family = 'Montserrat, sans-serif';
        Chart.defaults.color = tickColor;

        const animation = reduceMotion ? {
            duration: 0
        } : {
            duration: 800,
            easing: 'easeOutQuart'
        };

        const tooltip = {
            backgroundColor: '#0f172a',
            titleColor: '#f1f5f9',
            bodyColor: '#cbd5e1',
            padding: 12,
            cornerRadius: 10,
            displayColors: false
        };

        const pieOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: tickColor,
                        font: {
                            size: 11
                        },
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14
                    }
                },
                tooltip: {
                    ...tooltip,
                    callbacks: {
                        label: (item) => item.label + ': ' + item.raw
                    }
                }
            }
        };

        /* ---------- Occupancy bar chart ---------- */
        const occCanvas = document.getElementById('occupancyChart');
        if (occCanvas) {
            const ctx = occCanvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 240);
            grad.addColorStop(0, tealColor);
            grad.addColorStop(1, 'rgba(0,166,166,0.3)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($occupancy, 'hour')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($occupancy, 'checkins')) ?>,
                        backgroundColor: grad,
                        hoverBackgroundColor: tealColor,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 28
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...tooltip,
                            callbacks: {
                                title: (items) => 'At ' + items[0].label,
                                label: (item) => item.raw + ' check-in' + (item.raw !== 1 ? 's' : '')
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickColor,
                                font: {
                                    size: 11
                                },
                                autoSkip: true,
                                maxTicksLimit: 8
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickColor,
                                font: {
                                    size: 11
                                },
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        /* ---------- Revenue line chart ---------- */
        const revCanvas = document.getElementById('revenueChart');
        if (revCanvas) {
            const ctx = revCanvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 240);
            grad.addColorStop(0, 'rgba(0,166,166,0.35)');
            grad.addColorStop(1, 'rgba(0,166,166,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($revenue, 'day')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($revenue, 'amount')) ?>,
                        borderColor: tealColor,
                        backgroundColor: grad,
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: tealColor,
                        pointBorderWidth: 3,
                        pointHoverBackgroundColor: tealColor,
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            ...tooltip,
                            callbacks: {
                                label: (item) => '₱' + Number(item.raw).toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: tickColor,
                                font: {
                                    size: 11
                                },
                                autoSkip: true,
                                maxTicksLimit: 8
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickColor,
                                font: {
                                    size: 11
                                },
                                precision: 0,
                                callback: (v) => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        /* ---------- Payment mix doughnut ---------- */
        const payCanvas = document.getElementById('paymentMixChart');
        if (payCanvas) {
            new Chart(payCanvas, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($paymentMix, 'label')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($paymentMix, 'value')) ?>,
                        backgroundColor: <?= json_encode(array_column($paymentMix, 'color')) ?>,
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: pieOptions
            });
        }

        /* ---------- Enrollment mix doughnut ---------- */
        const enrollCanvas = document.getElementById('enrollmentMixChart');
        if (enrollCanvas) {
            new Chart(enrollCanvas, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($enrollMix, 'label')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($enrollMix, 'value')) ?>,
                        backgroundColor: <?= json_encode(array_column($enrollMix, 'color')) ?>,
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: pieOptions
            });
        }

        /* ---------- Live occupancy refresh (no page reload) ---------- */
        async function refreshInGym() {
            try {
                const res = await fetch(BASE + '/api/admin/occupancy', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();

                const el = document.getElementById('ovInGym');
                if (el && typeof data.in_gym !== 'undefined') {
                    const prev = parseInt(el.textContent, 10);
                    el.textContent = data.in_gym;
                    if (prev !== data.in_gym && !reduceMotion) {
                        el.style.transform = 'scale(1.15)';
                        setTimeout(() => el.style.transform = '', 300);
                    }
                }

                const utilEl = document.getElementById('ovUtil');
                const barEl = document.getElementById('ovUtilBar');
                if (typeof data.utilization !== 'undefined') {
                    if (utilEl) utilEl.textContent = Math.round(data.utilization);
                    if (barEl) barEl.style.width = Math.max(0, Math.min(100, data.utilization)) + '%';
                }

                const stillIn = document.getElementById('ovStillIn');
                if (stillIn && typeof data.in_gym !== 'undefined') {
                    stillIn.textContent = data.in_gym;
                }
            } catch (_) {
                /* silent */
            }
        }

        setInterval(() => {
            if (!document.hidden) refreshInGym();
        }, 30000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshInGym();
        });
    })();
</script>
