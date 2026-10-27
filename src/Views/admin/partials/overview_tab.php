<?php

/**
 * Admin — Overview Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['users']                 — User::listWithStats()
 *   $data['payments']              — Payment::listWithFilters()
 *   $data['paymentSummary']        — Payment::statusSummary()
 *   $data['enrollments']           — Enrollment rows
 *   $data['enrollSummary']         — Enrollment::statusSummary()
 *   $data['partnerSales']          — PartnerSale rows
 *   $data['attendanceStats']       — Attendance::todayStats()          [§1.4]
 *   $data['attendanceCurrently']   — Attendance::currentlyIn()         [§1.4]
 *   $data['attendanceToday']       — Attendance::todayAll()            [§1.4]
 *   $data['attendanceStreaks']     — Attendance::topStreaks(10)        [§1.4]
 *   $data['attendanceInactive']    — Attendance::inactiveMembers(7)    [§1.4]
 */

use App\Models\Attendance;
use App\Models\Enrollment;

$users         = $data['users']               ?? [];
$payments      = $data['payments']            ?? [];
$paymentSum    = $data['paymentSummary']      ?? [];
$enrollments   = $data['enrollments']         ?? [];
$enrollSum     = $data['enrollSummary']       ?? [];
$partnerSales  = $data['partnerSales']        ?? [];

/* §1.4 additions */
$attStats      = $data['attendanceStats']     ?? [];
$currentlyIn   = $data['attendanceCurrently'] ?? [];
$todayAttendance = $data['attendanceToday']   ?? [];
$streaks       = $data['attendanceStreaks']   ?? [];
$inactive      = $data['attendanceInactive']  ?? [];

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
$activeEnrolls = (int)($enrollSum['active']        ?? 0);
$pendingEnrolls = (int)($enrollSum['pending']      ?? 0);
$expiringSoon  = (int)($enrollSum['expiring_soon'] ?? 0);

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
$failureRate = (float)($paymentSum['failure_rate'] ?? 0);
$failedCount = (int)($paymentSum['failed']         ?? 0);

/* ---------- Occupancy hourly (for chart) ---------- */
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
        'time'  => $p['paid_date'] ?? '',
        'type'  => 'payment',
        'text'  => ($p['user_name'] ?? 'Member') . ' paid ' . peso($p['amount'] ?? 0),
        'sub'   => $p['description'] ?? ($p['tier'] ?? ''),
    ];
}
foreach ($enrollments as $e) {
    $activity[] = [
        'time'  => $e['start_date'] ?? $e['created_date'] ?? '',
        'type'  => 'enrollment',
        'text'  => ($e['user_name'] ?? 'Member') . ' enrolled in ' . ($e['tier'] ?? 'tier'),
        'sub'   => $e['program_name'] ?? '',
    ];
}
foreach ($partnerSales as $s) {
    if (($s['status'] ?? '') !== 'approved') continue;
    $activity[] = [
        'time'  => $s['sale_date'] ?? '',
        'type'  => 'sale',
        'text'  => 'Partner sale approved: ' . peso($s['amount'] ?? 0),
        'sub'   => $s['description'] ?? '',
    ];
}
usort($activity, fn($a, $b) => strcmp($b['time'], $a['time']));
$activity = array_slice($activity, 0, 6);

/* ---------- Gauge color ---------- */
$gaugeColor = '#22c55e';
if ($util >= 90)      $gaugeColor = '#D50000';
elseif ($util >= 70)  $gaugeColor = '#f97316';
elseif ($util >= 40)  $gaugeColor = '#f59e0b';

/* ---------- Streak leader ---------- */
$topStreak = $streaks[0] ?? null;

/* ---------- Greeting ---------- */
$adminName = current_user()['full_name'] ?? 'Admin';
$adminFirst = explode(' ', $adminName)[0];
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$todayLabel = date('l, F j, Y');
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         1. WELCOME HEADER (matches Member/Partner style)
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-[0.3em] text-gray-500">
                    <?= e($greeting) ?>
                </div>
                <h1 class="mt-2 font-heading text-4xl tracking-wide text-white sm:text-5xl lg:text-6xl">
                    WELCOME, <span class="text-[#00A6A6]"><?= e(strtoupper($adminFirst)) ?></span>
                </h1>
                <p class="mt-3 font-heading text-lg tracking-[0.2em] text-gray-400">
                    ADMIN DASHBOARD
                </p>
                <p class="mt-4 max-w-lg text-sm leading-relaxed text-gray-400">
                    Live snapshot of memberships, payments, and gym activity for <strong class="text-white"><?= e($todayLabel) ?></strong>.
                </p>
            </div>

            <div class="flex flex-shrink-0 flex-wrap gap-3 sm:flex-col">
                <a href="<?= url_e('/admin-dashboard?tab=payments') ?>"
                    class="tap-effect group inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                    <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Review Payments</span>
                </a>
                <a href="<?= url_e('/admin-dashboard?tab=live') ?>"
                    class="tap-effect group inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-xs font-bold text-white backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-white/10">
                    <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Live Monitor</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================================
         2. KPI STRIP — 4 hero cards
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Members -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Members</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= $totalMembers ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $activeEnrolls ?> active · <?= $pendingEnrolls ?> pending
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Enrollments -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/20 bg-green-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <?php if ($activeEnrolls > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
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
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Revenue -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Today's Revenue</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]"><?= peso($todayRev) ?></div>
                    <div class="mt-1 flex items-center gap-1 text-xs">
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
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- In Gym Now -->
        <div class="group relative overflow-hidden rounded-2xl border border-red-500/30 bg-red-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-red-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-red-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-red-500">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-60"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                        </span>
                        In Gym Now
                    </div>
                    <div class="mt-2 font-heading text-4xl text-red-500" id="ovInGym"><?= $inGym ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $util ?>% of <?= $capacity ?> capacity
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         3. CAPACITY GAUGE + QUICK KPIs
         ============================================================ -->
    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">

        <!-- Capacity gauge -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">CAPACITY</h3>
                    <p class="mt-1 text-xs text-gray-400">Live utilization</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-center">
                <div class="relative" role="img" aria-label="Capacity utilization <?= $util ?> percent, <?= $inGym ?> of <?= $capacity ?> members inside">
                    <svg width="200" height="200" viewBox="0 0 200 200" class="-rotate-90" aria-hidden="true">
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#1f2937" stroke-width="12" />
                        <circle cx="100" cy="100" r="80" fill="none"
                            stroke="<?= $gaugeColor ?>"
                            stroke-width="12"
                            stroke-linecap="round"
                            stroke-dasharray="<?= round(2 * pi() * 80 * ($util / 100), 2) ?> <?= round(2 * pi() * 80, 2) ?>"
                            class="transition-all duration-700 ease-out" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="font-heading text-5xl" style="color: <?= $gaugeColor ?>;"><?= $util ?>%</div>
                        <div class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Utilization</div>
                        <div class="mt-2 text-sm font-bold text-white"><?= $inGym ?> / <?= $capacity ?></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center text-[11px] text-gray-400">
                <?php if ($util >= 90): ?>
                    <span class="font-bold text-red-500">Almost at capacity</span>
                <?php elseif ($util >= 70): ?>
                    <span class="font-bold text-orange-500">Getting busy</span>
                <?php elseif ($util >= 40): ?>
                    <span class="font-bold text-amber-500">Moderately busy</span>
                <?php else: ?>
                    <span class="font-bold text-green-500">Plenty of space</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick KPI strip -->
        <div class="grid gap-4 sm:grid-cols-2">

            <div class="group rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="flex items-center justify-between">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Today's Check-ins</div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#00A6A6]/10 text-[#00A6A6]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 font-heading text-3xl text-white"><?= $todayCheckins ?></div>
                <div class="mt-1 text-xs text-gray-400"><?= $currentlyInCount ?> still in</div>
            </div>

            <div class="group rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="flex items-center justify-between">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Hours Tracked</div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#00A6A6]/10 text-[#00A6A6]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 font-heading text-3xl text-[#00A6A6]"><?= $todayHours ?>h</div>
                <div class="mt-1 text-xs text-gray-400">Avg <?= $avgMinutes ?>m / session</div>
            </div>

            <div class="group rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="flex items-center justify-between">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Payment Success</div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-500/10 text-green-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <?php
                $srColor = $successRate >= 90 ? 'text-green-500'
                    : ($successRate >= 70 ? 'text-amber-500' : 'text-red-500');
                ?>
                <div class="mt-3 font-heading text-3xl <?= $srColor ?>"><?= number_format($successRate, 1) ?>%</div>
                <div class="mt-1 text-xs text-gray-400">
                    <?php if ($failedCount > 0): ?>
                        <span class="text-red-500"><?= $failedCount ?> failed</span>
                    <?php else: ?>
                        <span class="text-green-500">No failures</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="group rounded-2xl border border-orange-500/20 bg-orange-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-orange-500/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-orange-500">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        </svg>
                        Top Streak
                    </div>
                </div>
                <?php if ($topStreak): ?>
                    <div class="mt-3 font-heading text-3xl text-orange-500"><?= (int)$topStreak['streak'] ?>d</div>
                    <div class="mt-1 truncate text-xs text-gray-400">
                        <?= e($topStreak['user_name'] ?? '—') ?>
                    </div>
                <?php else: ?>
                    <div class="mt-3 font-heading text-3xl text-gray-600">—</div>
                    <div class="mt-1 text-xs text-gray-400">No streaks yet</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================
         4. INACTIVE ALERT
         ============================================================ -->
    <?php if (!empty($inactive)): ?>
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-6 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="tap-effect inline-flex flex-shrink-0 items-center gap-2 rounded-full bg-amber-500 px-6 py-3 text-xs font-bold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:bg-amber-600 hover:shadow-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Review
            </a>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         5. CHARTS ROW
         ============================================================ -->
    <div class="grid gap-6 lg:grid-cols-2">

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">GYM OCCUPANCY TODAY</h3>
                    <p class="mt-1 text-xs text-gray-400">Check-ins per hour · 6 AM – 8 PM</p>
                </div>
                <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    Peak: <?= max(array_column($occupancy, 'checkins') ?: [0]) ?>
                </span>
            </div>
            <div class="mt-4 h-64"><canvas id="occupancyChart" role="img" aria-label="Bar chart of check-ins per hour today"></canvas></div>
        </div>

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">REVENUE — LAST 7 DAYS</h3>
                    <p class="mt-1 text-xs text-gray-400">All payments (B2C + B2B)</p>
                </div>
                <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    <?= peso(array_sum(array_column($revenue, 'amount'))) ?>
                </span>
            </div>
            <div class="mt-4 h-64"><canvas id="revenueChart" role="img" aria-label="Line chart of daily revenue for the last 7 days"></canvas></div>
        </div>
    </div>

    <!-- ============================================================
         6. RECENT ACTIVITY
         ============================================================ -->
    <div class="rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="flex items-center justify-between border-b border-white/5 p-6">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">RECENT ACTIVITY</h3>
                <p class="mt-1 text-xs text-gray-400">Latest events across the gym</p>
            </div>
            <a href="<?= url_e('/admin-dashboard?tab=payments') ?>"
                class="text-xs font-bold text-[#00A6A6] transition-colors hover:text-white">
                View all &rarr;
            </a>
        </div>

        <?php if (empty($activity)): ?>
            <div class="p-16 text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No activity yet. Once members enroll and pay, events will appear here.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-white/5" role="list">
                <?php foreach ($activity as $i => $a):
                    $tone = match ($a['type']) {
                        'payment'    => [
                            'bg' => 'bg-green-500/10',
                            'text' => 'text-green-500',
                            'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                        ],
                        'enrollment' => [
                            'bg' => 'bg-[#00A6A6]/10',
                            'text' => 'text-[#00A6A6]',
                            'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
                        ],
                        'sale'       => [
                            'bg' => 'bg-amber-500/10',
                            'text' => 'text-amber-500',
                            'path' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
                        ],
                        default      => [
                            'bg' => 'bg-gray-500/10',
                            'text' => 'text-gray-400',
                            'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                        ],
                    };
                ?>
                    <li class="group flex items-center gap-4 p-4 transition-colors duration-200 hover:bg-white/[0.02]"
                        style="animation: slide-in 0.4s ease-out <?= $i * 50 ?>ms backwards;">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full <?= $tone['bg'] ?> <?= $tone['text'] ?> transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    @keyframes slide-in {
        from {
            opacity: 0;
            transform: translateX(-8px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        [style*="slide-in"] {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        const BASE = document.querySelector('meta[name="base"]')?.content || '';

        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? '#334155' : '#e5e7eb';
        const tickColor = isDark ? '#94a3b8' : '#4b5563';
        const tealColor = '#00A6A6';

        /* ---------- Occupancy bar chart ---------- */
        const occCanvas = document.getElementById('occupancyChart');
        if (occCanvas) {
            const ctx = occCanvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 300);
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
                    animation: {
                        duration: 900,
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
                                }
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
            const grad = ctx.createLinearGradient(0, 0, 0, 300);
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
                    animation: {
                        duration: 1000,
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
                                }
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
                                callback: (v) => '₱' + Number(v).toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        /* ---------- Live In Gym refresh (no page reload) ---------- */
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
                    if (prev !== data.in_gym) {
                        el.style.transform = 'scale(1.15)';
                        setTimeout(() => el.style.transform = '', 300);
                    }
                }
            } catch (_) {
                /* silent */ }
        }

        setInterval(() => {
            if (!document.hidden) refreshInGym();
        }, 30000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshInGym();
        });
    })();
</script>