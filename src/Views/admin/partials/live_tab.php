<?php

/**
 * Admin — Live Time In/Out Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['attendanceStats']     — Attendance::todayStats()
 *   $data['attendanceCurrently'] — Attendance::currentlyIn()
 *   $data['attendanceToday']     — Attendance::todayAll()
 *   $data['attendanceHeatmap']   — Attendance::heatmap(30)
 *   $data['attendanceStreaks']   — Attendance::topStreaks(10)
 *   $data['attendanceInactive']  — Attendance::inactiveMembers(7, 20)
 *   $data['attendanceSeasonal']  — Attendance::seasonalTrends(12)
 *   $data['attendanceRetention'] — Attendance::retentionCorrelation()
 *   $data['attendance']          — Attendance::listWithFilters() rows
 *   $data['attendanceFilters']   — active filter state
 *   $data['users']               — User::listWithStats() (for manual time-in)
 */

use App\Models\Attendance;
use App\Models\User;

$stats          = $data['attendanceStats']     ?? [];
$currentlyIn    = $data['attendanceCurrently'] ?? [];
$todayLog       = $data['attendanceToday']     ?? [];
$heatmap        = $data['attendanceHeatmap']   ?? ['grid' => [], 'days' => [], 'hours' => [], 'max' => 0];
$streaks        = $data['attendanceStreaks']   ?? [];
$inactive       = $data['attendanceInactive']  ?? [];
$seasonal       = $data['attendanceSeasonal']  ?? [];
$retention      = $data['attendanceRetention'] ?? [];
$logRows        = $data['attendance']          ?? [];
$filters        = $data['attendanceFilters']   ?? [
    'from' => date('Y-m-d'),
    'to' => date('Y-m-d'),
    'member' => '',
    'tier' => 'all',
    'source' => 'all',
    'open_only' => false,
];
$users          = $data['users']               ?? [];

/* Capacity gauge math */
$inGym    = (int)($stats['in_gym']      ?? 0);
$capacity = (int)($stats['capacity']    ?? 50);
$util     = (float)($stats['utilization'] ?? 0);

$gaugeColor = '#22c55e';
if ($util >= 90)      $gaugeColor = '#D50000';
elseif ($util >= 70)  $gaugeColor = '#f97316';
elseif ($util >= 40)  $gaugeColor = '#f59e0b';

/* Heatmap helpers */
$heatCellStyle = function (int $count, int $max): string {
    if ($count === 0) return 'background-color:rgba(255,255,255,0.03);';
    $ratio = $max > 0 ? $count / $max : 0;
    $alpha = 0.15 + ($ratio * 0.85);
    return sprintf('background-color: rgba(0,166,166,%.2f);', $alpha);
};

$heatCellText = function (int $count, int $max): string {
    if ($count === 0) return 'color:#4b5563;';
    $ratio = $max > 0 ? $count / $max : 0;
    return $ratio > 0.55 ? 'color:#ffffff;' : 'color:#0f172a;';
};

/* Query-string builder preserving current filters */
$qs = function (array $overrides = []) use ($filters): string {
    $merged = array_merge([
        'tab'        => 'live',
        'att_from'   => $filters['from'] !== date('Y-m-d') ? $filters['from'] : '',
        'att_to'     => $filters['to']   !== date('Y-m-d') ? $filters['to']   : '',
        'att_member' => $filters['member'],
        'att_tier'   => $filters['tier']   === 'all' ? '' : $filters['tier'],
        'att_source' => $filters['source'] === 'all' ? '' : $filters['source'],
        'att_open'   => $filters['open_only'] ? '1' : '',
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== 'all' && $v !== false);
    return $merged ? '?' . http_build_query($merged) : '?tab=live';
};

/* Tier icon SVG paths */
$tierSvg = [
    'digital'  => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    'standard' => 'M13 10V3L4 14h7v7l9-11h-7z',
    'premium'  => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
    'b2b'      => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
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
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-red-500">Live</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    LIVE TIME IN/OUT
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Real-time gym occupancy and attendance monitoring.
                </p>
            </div>

            <div class="flex flex-wrap gap-3 sm:flex-col">
                <button type="button" onclick="refreshLive()"
                    class="tap-effect group inline-flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Refresh Now</span>
                </button>
                <button type="button" onclick="openManualTimeInModal()"
                    class="tap-effect group inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-xs font-bold text-white backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Manual Time-In</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Live Now -->
        <div class="group relative overflow-hidden rounded-2xl border border-green-500/30 bg-green-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-green-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-green-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-green-500">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-60"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                        </span>
                        Live Now
                    </div>
                    <div class="mt-2 font-heading text-4xl text-green-500" id="liveCount"><?= $inGym ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $util ?>% of <?= $capacity ?> capacity
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-green-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Check-ins -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Today's Check-ins</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= (int)($stats['total'] ?? 0) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($stats['completed'] ?? 0) ?> completed · <?= $inGym ?> in gym
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Hours Tracked -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Hours Tracked</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]"><?= (float)($stats['hours'] ?? 0) ?>h</div>
                    <div class="mt-1 text-xs text-gray-400">
                        Avg <?= (int)($stats['avg_minutes'] ?? 0) ?>m per session
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Peak Hour -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Peak Hour</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]">
                        <?= $stats['peak_hour'] !== null ? sprintf('%02d:00', (int)$stats['peak_hour']) : '—' ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)($stats['peak_count'] ?? 0) ?> check-ins
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         LIVE BANNER
         ============================================================ -->
    <div class="flex items-center justify-between gap-4 rounded-2xl border border-green-500/20 bg-gradient-to-r from-green-500/10 to-[#00A6A6]/5 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="relative flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-500 opacity-60"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
            </span>
            <div>
                <div class="text-sm font-bold text-green-500">LIVE FEED</div>
                <div class="text-xs text-gray-400" id="liveUpdated" <?= a11y_live_region('polite') ?>>Polling every 30 seconds</div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         CAPACITY GAUGE + IN-GYM GRID
         ============================================================ -->
    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">

        <!-- Capacity ring -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">CAPACITY</h3>
                    <p class="mt-1 text-xs text-gray-400">Current utilization</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-center">
                <div class="relative" role="img" aria-label="Capacity utilization <?= $util ?> percent, <?= $inGym ?> of <?= $capacity ?> members inside">
                    <svg width="220" height="220" viewBox="0 0 220 220" class="-rotate-90" aria-hidden="true">
                        <circle cx="110" cy="110" r="90" fill="none" stroke="#1f2937" stroke-width="14" />
                        <circle cx="110" cy="110" r="90" fill="none"
                            stroke="<?= $gaugeColor ?>"
                            stroke-width="14"
                            stroke-linecap="round"
                            stroke-dasharray="<?= round(2 * pi() * 90 * ($util / 100), 2) ?> <?= round(2 * pi() * 90, 2) ?>"
                            class="transition-all duration-700 ease-out" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="font-heading text-5xl" style="color: <?= $gaugeColor ?>;"><?= $util ?>%</div>
                        <div class="mt-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Utilization</div>
                        <div class="mt-2 text-sm font-bold text-white"><?= $inGym ?> / <?= $capacity ?></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg bg-green-500/5 border border-green-500/20 p-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-green-500">Low</div>
                    <div class="text-[11px] text-gray-400">&lt;40%</div>
                </div>
                <div class="rounded-lg bg-amber-500/5 border border-amber-500/20 p-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Busy</div>
                    <div class="text-[11px] text-gray-400">40–70%</div>
                </div>
                <div class="rounded-lg bg-red-500/5 border border-red-500/20 p-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-red-500">Full</div>
                    <div class="text-[11px] text-gray-400">&gt;90%</div>
                </div>
            </div>
        </div>

        <!-- In-gym grid -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">
                        IN THE GYM NOW
                        <span class="ml-2 inline-flex items-center justify-center rounded-full bg-green-500 px-2.5 py-0.5 text-sm font-bold text-white" id="inGymBadge"><?= $inGym ?></span>
                    </h3>
                    <p class="mt-1 text-xs text-gray-400">Open sessions with live duration timers</p>
                </div>
                <button type="button" onclick="refreshLive()"
                    class="tap-effect inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>

            <div id="inGymGrid" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <?php if (empty($currentlyIn)): ?>
                    <div class="col-span-full rounded-2xl border border-dashed border-white/10 bg-white/[0.02] p-10 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm text-gray-400">No one is currently in the gym.</p>
                        <p class="mt-1 text-xs text-gray-500">Members appear here when they scan the entrance QR.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($currentlyIn as $idx => $a):
                        $tier = $a['tier'] ?? '';
                        $tierPath = $tierSvg[$tier] ?? 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z';
                    ?>
                        <div class="live-card group relative overflow-hidden rounded-2xl border border-green-500/30 bg-gradient-to-br from-green-500/10 to-[#111] p-4 shadow-sm transition-all duration-300
                            hover:-translate-y-1 hover:shadow-lg hover:shadow-green-500/20"
                            data-id="<?= e($a['id']) ?>"
                            style="animation: card-enter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) <?= min($idx * 40, 300) ?>ms backwards;">

                            <div class="flex items-start justify-between">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <div class="relative flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-green-600 font-heading text-sm text-white shadow-md">
                                            <?= e(initials($a['user_name'] ?? '?')) ?>
                                        </div>
                                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#111] bg-green-500"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-bold text-white">
                                            <?= e($a['user_name'] ?? 'Member') ?>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-green-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tierPath ?>" />
                                            </svg>
                                            <span>Since <?= e(time_short($a['time_in'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <span data-status-badge class="flex-shrink-0 rounded-full bg-green-500/20 px-2 py-0.5 text-[10px] font-bold uppercase text-green-500">
                                    IN
                                </span>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Duration</div>
                                    <div class="font-heading text-xl text-green-500 live-timer" data-start="<?= e($a['time_in']) ?>">
                                        <?= e($a['live_label'] ?? '0m') ?>
                                    </div>
                                </div>
                                <form method="post" action="<?= url_e('/admin-dashboard/attendance/out') ?>"
                                    onsubmit="return confirm('Time out <?= e(addslashes($a['user_name'] ?? 'this member')) ?>?')"
                                    class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                    <button type="submit"
                                        aria-label="Time out <?= e($a['user_name'] ?? 'member') ?>"
                                        class="tap-effect flex min-h-[40px] items-center gap-1 rounded-full bg-[#0a0a0a] border border-white/10 px-3 py-1.5 text-[11px] font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-red-500 hover:border-red-500 hover:shadow-[0_0_15px_rgba(213,0,0,0.4)]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Time Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================
         HEATMAP
         ============================================================ -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">PEAK OCCUPANCY HEATMAP</h3>
                <p class="mt-1 text-xs text-gray-400">Check-ins by day of week and hour · last 30 days</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    Peak: <strong><?= (int)($heatmap['max'] ?? 0) ?></strong> in one hour
                </span>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full border-separate" style="border-spacing: 3px;">
                <caption class="sr-only">Check-in heatmap: day of week vs hour</caption>
                <thead>
                    <tr>
                        <th scope="col" class="w-14"><span class="sr-only">Day</span></th>
                        <?php foreach (($heatmap['hours'] ?? []) as $hourLabel): ?>
                            <th scope="col" class="text-center text-[10px] font-bold text-gray-500"><?= e($hourLabel) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($heatmap['days'] ?? []) as $dow => $dayLabel):
                        $max = (int)($heatmap['max'] ?? 0);
                    ?>
                        <tr>
                            <th scope="row" class="pr-2 text-right text-[11px] font-bold text-gray-400"><?= e($dayLabel) ?></th>
                            <?php foreach (array_keys($heatmap['hours'] ?? []) as $hour):
                                $count = (int)($heatmap['grid'][$dow][$hour] ?? 0);
                                $style = $heatCellStyle($count, $max);
                                $textStyle = $heatCellText($count, $max);
                            ?>
                                <td class="text-center">
                                    <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-md text-[11px] font-bold transition-transform hover:scale-110"
                                        style="<?= $style ?><?= $textStyle ?>"
                                        title="<?= e($dayLabel) ?> at <?= sprintf('%02d:00', $hour) ?> — <?= $count ?> check-in<?= $count !== 1 ? 's' : '' ?>">
                                        <?= $count > 0 ? $count : '' ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 text-[11px] text-gray-400">
            <span class="font-bold uppercase tracking-wider">Intensity:</span>
            <div class="flex items-center gap-1">
                <span class="h-4 w-4 rounded" style="background:rgba(255,255,255,0.03);" aria-hidden="true"></span>
                <span>None</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="h-4 w-4 rounded" style="background:rgba(0,166,166,0.25);" aria-hidden="true"></span>
                <span>Low</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="h-4 w-4 rounded" style="background:rgba(0,166,166,0.55);" aria-hidden="true"></span>
                <span>Medium</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="h-4 w-4 rounded" style="background:rgba(0,166,166,0.9);" aria-hidden="true"></span>
                <span>High</span>
            </div>
        </div>
    </div>

    <!-- ============================================================
         STREAKS + INACTIVE
         ============================================================ -->
    <div class="grid gap-6 lg:grid-cols-2">

        <!-- Top streaks -->
        <div class="rounded-2xl border border-orange-500/20 bg-gradient-to-br from-orange-500/5 to-[#111] p-6 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">TOP STREAKS</h3>
                    <p class="mt-0.5 text-xs text-gray-400">Consecutive-day check-in runs</p>
                </div>
            </div>

            <?php if (empty($streaks)): ?>
                <div class="mt-5 rounded-xl border border-dashed border-white/10 bg-white/[0.02] p-8 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/5 text-gray-500">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm text-gray-400">No active streaks yet.</p>
                </div>
            <?php else: ?>
                <div class="mt-4 space-y-2">
                    <?php foreach ($streaks as $idx => $s):
                        $rankBg = [
                            'bg-gradient-to-br from-yellow-400 to-yellow-600 text-white shadow-[0_0_15px_rgba(234,179,8,0.4)]',
                            'bg-gradient-to-br from-slate-300 to-slate-500 text-white',
                            'bg-gradient-to-br from-amber-600 to-amber-800 text-white',
                        ][$idx] ?? 'bg-white/5 text-gray-400 border border-white/10';
                    ?>
                        <div class="group flex items-center gap-3 rounded-xl border border-white/5 bg-[#0a0a0a] p-3 transition-all hover:-translate-y-0.5 hover:border-orange-500/30 hover:shadow-md"
                            style="animation: row-enter 0.35s ease-out <?= min($idx * 40, 300) ?>ms backwards;">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full font-heading text-xs <?= $rankBg ?>">
                                <?= $idx + 1 ?>
                            </div>
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-orange-400 to-orange-500 font-heading text-sm text-white shadow-md">
                                <?= e(initials($s['user_name'] ?? '?')) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-bold text-white"><?= e($s['user_name'] ?? 'Member') ?></div>
                                <div class="truncate text-[10px] uppercase tracking-wider text-gray-500">
                                    <?= e($s['tier'] ?? '—') ?> tier
                                </div>
                            </div>
                            <div class="flex flex-shrink-0 items-center gap-1 font-heading text-2xl text-orange-500">
                                <?= (int)($s['streak'] ?? 0) ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                </svg>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Inactive members -->
        <div class="rounded-2xl border <?= !empty($inactive) ? 'border-red-500/30 bg-gradient-to-br from-red-500/5 to-[#111]' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">
                        INACTIVE MEMBERS
                        <?php if (!empty($inactive)): ?>
                            <span class="ml-1 rounded-full bg-red-500 px-2 py-0.5 text-sm text-white">
                                <?= count($inactive) ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                    <p class="mt-0.5 text-xs text-gray-400">No check-ins in 7+ days</p>
                </div>
            </div>

            <?php if (empty($inactive)): ?>
                <div class="mt-5 rounded-xl border border-dashed border-green-500/30 bg-green-500/5 p-8 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-500/10 text-green-500">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-green-500">All members are active!</p>
                    <p class="mt-1 text-xs text-gray-400">No one has been idle for more than 7 days.</p>
                </div>
            <?php else: ?>
                <div class="mt-4 max-h-80 space-y-2 overflow-y-auto pr-1">
                    <?php foreach ($inactive as $idx => $m):
                        $daysSince = $m['days_since'];
                        $urgencyColor = $daysSince === null ? 'text-gray-400'
                            : ($daysSince >= 30 ? 'text-red-500'
                                : ($daysSince >= 14 ? 'text-orange-500' : 'text-amber-500'));
                    ?>
                        <div class="group flex items-center gap-3 rounded-xl border border-white/5 bg-[#0a0a0a] p-3 transition-all hover:-translate-y-0.5 hover:border-red-500/30 hover:shadow-md"
                            style="animation: row-enter 0.35s ease-out <?= min($idx * 30, 300) ?>ms backwards;">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-gray-500 to-gray-600 font-heading text-sm text-white">
                                <?= e(initials($m['user_name'] ?? '?')) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-bold text-white"><?= e($m['user_name'] ?? 'Member') ?></div>
                                <div class="truncate text-[11px] text-gray-500"><?= e($m['email'] ?? '') ?></div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <?php if ($daysSince === null): ?>
                                    <div class="text-[10px] font-bold uppercase text-gray-500">Never</div>
                                    <div class="text-xs text-gray-500">checked in</div>
                                <?php else: ?>
                                    <div class="font-heading text-xl <?= $urgencyColor ?>">
                                        <?= $daysSince ?>d
                                    </div>
                                    <div class="text-[10px] uppercase <?= $urgencyColor ?>">since visit</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================
         SEASONAL + RETENTION CHARTS
         ============================================================ -->
    <div class="grid gap-6 lg:grid-cols-2">

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">SEASONAL TRENDS</h3>
                <p class="mt-1 text-xs text-gray-400">Monthly check-ins and unique members</p>
            </div>
            <div class="mt-4 h-64"><canvas id="seasonalChart" role="img" aria-label="Line chart of monthly check-ins and unique members for the last 12 months"></canvas></div>
        </div>

        <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">RETENTION BY ATTENDANCE</h3>
                <p class="mt-1 text-xs text-gray-400">Renewal rate vs. 30-day visit frequency</p>
            </div>
            <div class="mt-4 h-64"><canvas id="retentionChart" role="img" aria-label="Bar chart showing renewal rate by visit frequency bucket"></canvas></div>
        </div>
    </div>

    <!-- ============================================================
         FILTER BAR
         ============================================================ -->
    <form method="get" action="<?= url_e('/admin-dashboard') ?>" id="attendanceFilterForm"
        class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-4">

        <input type="hidden" name="tab" value="live">

        <?= a11y_filter_open('Filter attendance log by date range, member, tier, and source') ?>

        <div class="grid gap-3 lg:grid-cols-[1fr_1fr_2fr_1fr_1fr_auto_auto]">

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">From</span>
                <input type="date" name="att_from" value="<?= e($filters['from']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">To</span>
                <input type="date" name="att_to" value="<?= e($filters['to']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Member</span>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="att_member" value="<?= e($filters['member']) ?>"
                        placeholder="Name or member ID…"
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Tier</span>
                <select name="att_tier"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="all">All tiers</option>
                    <option value="digital" <?= $filters['tier'] === 'digital'  ? 'selected' : '' ?>>Digital</option>
                    <option value="standard" <?= $filters['tier'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                    <option value="premium" <?= $filters['tier'] === 'premium'  ? 'selected' : '' ?>>Premium</option>
                    <option value="b2b" <?= $filters['tier'] === 'b2b'      ? 'selected' : '' ?>>B2B</option>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Source</span>
                <select name="att_source"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="all">All sources</option>
                    <option value="qr" <?= $filters['source'] === 'qr'     ? 'selected' : '' ?>>QR Scan</option>
                    <option value="manual" <?= $filters['source'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                </select>
            </label>

            <div class="flex items-end">
                <label class="flex h-11 w-full items-center gap-2 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 cursor-pointer transition-colors hover:border-[#00A6A6]/50">
                    <input type="checkbox" name="att_open" value="1" <?= $filters['open_only'] ? 'checked' : '' ?>
                        class="h-4 w-4 rounded border-white/20 bg-[#0a0a0a] text-[#00A6A6] focus:ring-[#00A6A6]">
                    <span class="text-xs font-bold text-gray-300">Open only</span>
                </label>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="tap-effect inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#00A6A6] px-6 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
                <a href="<?= url_e('/admin-dashboard/attendance/export' . $qs()) ?>"
                    class="tap-effect inline-flex h-11 items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </a>
            </div>
        </div>

        <?= a11y_filter_close() ?>
    </form>

    <!-- ============================================================
         ATTENDANCE TABLE
         ============================================================ -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="flex items-center justify-between border-b border-white/5 p-6">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">ATTENDANCE LOG</h3>
                <p class="mt-1 text-xs text-gray-400" <?= a11y_live_region('polite') ?>>
                    <?= count($logRows) ?> record<?= count($logRows) !== 1 ? 's' : '' ?> match the current filters
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <caption class="sr-only">Attendance records with tier, source, and edit actions</caption>
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.02]">
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Member</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Date</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Time In</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Time Out</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Duration</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden md:table-cell">Tier</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Source</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="logRows">
                    <?php foreach ($logRows as $idx => $r):
                        $isOpen = !empty($r['is_open']);
                        $tier = $r['current_tier'] ?? $r['tier'] ?? null;
                        $tierPath = $tier ? ($tierSvg[$tier] ?? null) : null;
                        $source = $r['source'] ?? 'qr';
                        $sourceSvg = $source === 'manual'
                            ? 'M12 4v16m8-8H4'
                            : 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z';
                        $sourceLabel = $source === 'manual' ? 'Manual' : 'QR Scan';
                    ?>
                        <tr class="log-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                            data-search="<?= e(strtolower(($r['user_name'] ?? '') . ' ' . ($r['user_id'] ?? ''))) ?>"
                            style="animation: row-enter 0.3s ease-out <?= min($idx * 15, 400) ?>ms backwards;">

                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full font-heading text-sm text-white <?= $isOpen ? 'bg-gradient-to-br from-green-500 to-green-600' : 'bg-gradient-to-br from-gray-500 to-gray-600' ?>">
                                        <?= e(initials($r['user_name'] ?? '?')) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-white"><?= e($r['user_name'] ?? 'Member') ?></div>
                                        <div class="truncate font-mono text-[10px] text-gray-500"><?= e(substr($r['user_id'] ?? '', 0, 8)) ?>…</div>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4 text-sm text-gray-300 whitespace-nowrap hidden lg:table-cell">
                                <?= e(date('D, M j', strtotime($r['date']))) ?>
                            </td>

                            <td class="p-4 text-sm text-white whitespace-nowrap">
                                <?= e(time_short($r['time_in'])) ?>
                            </td>

                            <td class="p-4 text-sm whitespace-nowrap">
                                <?php if ($isOpen): ?>
                                    <span class="inline-flex items-center gap-1.5 text-green-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                        Live
                                    </span>
                                <?php else: ?>
                                    <span class="text-white"><?= e(time_short($r['time_out'])) ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 text-sm font-semibold whitespace-nowrap <?= $isOpen ? 'text-green-500' : 'text-white' ?>">
                                <?= e($r['session_label'] ?? ($r['hours'] ? $r['hours'] . 'h' : '—')) ?>
                            </td>

                            <td class="p-4 hidden md:table-cell">
                                <?php if ($tier && $tierPath): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-[#00A6A6]/10 border border-[#00A6A6]/20 px-2.5 py-0.5 text-[10px] font-bold uppercase text-[#00A6A6]">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tierPath ?>" />
                                        </svg>
                                        <?= e($tier) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 hidden lg:table-cell">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/5 border border-white/10 px-2.5 py-0.5 text-[10px] font-bold uppercase text-gray-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $sourceSvg ?>" />
                                    </svg>
                                    <?= e($sourceLabel) ?>
                                </span>
                            </td>

                            <td class="p-4 text-right">
                                <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">
                                    <?php if ($isOpen): ?>
                                        <form method="post" action="<?= url_e('/admin-dashboard/attendance/out') ?>" class="inline"
                                            onsubmit="return confirm('Time out <?= e(addslashes($r['user_name'] ?? 'this member')) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                            <button type="submit"
                                                aria-label="Time out <?= e($r['user_name'] ?? 'member') ?>"
                                                title="Time Out"
                                                class="rounded-full bg-[#0a0a0a] border border-white/10 px-3 py-1.5 text-[10px] font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-red-500 hover:border-red-500">
                                                Out
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button type="button"
                                        onclick='openEditAttendanceModal(<?= json_encode([
                                                                                "id"       => $r["id"],
                                                                                "user_name" => $r["user_name"] ?? "Member",
                                                                                "time_in"  => $r["time_in"],
                                                                                "time_out" => $r["time_out"] ?? "",
                                                                                "notes"    => $r["notes"] ?? "",
                                                                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        aria-label="Edit attendance record for <?= e($r['user_name'] ?? 'member') ?>"
                                        title="Edit record"
                                        class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-[#00A6A6]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <form method="post" action="<?= url_e('/admin-dashboard/attendance/delete') ?>" class="inline"
                                        onsubmit="return confirm('Delete this attendance record permanently?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                        <button type="submit"
                                            aria-label="Delete attendance record for <?= e($r['user_name'] ?? 'member') ?>"
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

                    <?php if (empty($logRows)): ?>
                        <tr>
                            <td colspan="8" class="p-16 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm text-gray-400">No records match the current filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="noLogResults" class="hidden p-12 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <p class="mt-4 text-sm text-gray-400">No records match the current filters.</p>
        </div>
    </div>

    <!-- ============================================================
         MANUAL TIME-IN MODAL
         ============================================================ -->
    <div id="manualTimeInModal" role="dialog" aria-modal="true" aria-labelledby="manualTimeInModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h2 id="manualTimeInModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">MANUAL TIME-IN</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Log a check-in when the QR scanner is unavailable.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/attendance/manual-in') ?>" class="mt-6 space-y-4" data-validate>
                <?= csrf_field() ?>

                <label class="block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Member <span class="text-[#D50000]">*</span>
                    </span>
                    <select name="user_id" required
                        data-validate="required"
                        data-error-label="Member"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <option value="">— Select member —</option>
                        <?php
                        $picker = $users;
                        usort($picker, fn($a, $b) => strcasecmp($a['full_name'] ?? $a['email'], $b['full_name'] ?? $b['email']));
                        foreach ($picker as $u):
                            if (($u['role'] ?? '') === 'admin') continue;
                        ?>
                            <option value="<?= e($u['id']) ?>">
                                <?= e($u['full_name'] ?: $u['email']) ?>
                                <?php if (!empty($u['enrollment_tier'])): ?>
                                    — <?= e(strtoupper($u['enrollment_tier'])) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Time In <span class="font-normal text-gray-600">(leave blank for now)</span>
                    </span>
                    <input type="datetime-local" name="time_in"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20 [color-scheme:dark]">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Note <span class="font-normal text-gray-600">(optional)</span>
                    </span>
                    <input name="notes" maxlength="255"
                        placeholder="e.g. Scanner offline"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeManualTimeInModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Clock In
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         EDIT ATTENDANCE MODAL
         ============================================================ -->
    <div id="editAttendanceModal" role="dialog" aria-modal="true" aria-labelledby="editAttendanceModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <h2 id="editAttendanceModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">EDIT RECORD</h2>
                <p class="mt-2 text-sm text-gray-400">
                    Fix timestamps for <strong id="edit-att-name" class="text-white">this member</strong>.
                </p>
            </div>

            <form method="post" action="<?= url_e('/admin-dashboard/attendance/update') ?>" class="mt-6 space-y-4" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit-att-id">

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Time In</span>
                    <input type="datetime-local" name="time_in" id="edit-att-timein" required
                        data-validate="required"
                        data-error-label="Time In"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20 [color-scheme:dark]">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Time Out <span class="font-normal text-gray-600">(blank = still in gym)</span>
                    </span>
                    <input type="datetime-local" name="time_out" id="edit-att-timeout"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20 [color-scheme:dark]">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Notes</span>
                    <textarea name="notes" id="edit-att-notes" rows="2" maxlength="255"
                        class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20"></textarea>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditAttendanceModal()"
                        class="flex-1 rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        Save
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

    @keyframes card-enter {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.96);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
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

        .live-card,
        .log-row,
        #manualTimeInModal>div,
        #editAttendanceModal>div {
            animation: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function() {
        'use strict';

        const BASE = document.querySelector('meta[name="base"]')?.content || '';
        const CSRF = document.querySelector('meta[name="csrf"]')?.content || '';

        /* ============ Seasonal trend chart ============ */
        const SEASONAL = <?= json_encode($seasonal, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function initSeasonalChart() {
            const canvas = document.getElementById('seasonalChart');
            if (!canvas) return;

            const isDark = document.documentElement.classList.contains('dark');
            const tickClr = isDark ? '#94a3b8' : '#4b5563';
            const gridClr = isDark ? '#334155' : '#e5e7eb';

            if (!SEASONAL || SEASONAL.length === 0) {
                canvas.parentElement.innerHTML =
                    '<div class="flex h-full items-center justify-center text-sm text-gray-500">No seasonal data yet</div>';
                return;
            }

            const ctx = canvas.getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 260);
            grad.addColorStop(0, 'rgba(0,166,166,0.35)');
            grad.addColorStop(1, 'rgba(0,166,166,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: SEASONAL.map(r => {
                        const [y, m] = r.month.split('-');
                        return new Date(y, m - 1).toLocaleDateString('en', {
                            month: 'short',
                            year: '2-digit'
                        });
                    }),
                    datasets: [{
                            label: 'Check-ins',
                            data: SEASONAL.map(r => r.count),
                            borderColor: '#00A6A6',
                            backgroundColor: grad,
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#00A6A6',
                            pointBorderWidth: 3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Unique members',
                            data: SEASONAL.map(r => r.unique_members),
                            borderColor: '#D50000',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#D50000',
                            pointBorderWidth: 3,
                            yAxisID: 'y1'
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
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: tickClr,
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
                            position: 'left',
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                precision: 0,
                                font: {
                                    size: 10
                                }
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
                                precision: 0,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        /* ============ Retention chart ============ */
        const RETENTION = <?= json_encode($retention, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function initRetentionChart() {
            const canvas = document.getElementById('retentionChart');
            if (!canvas) return;

            const isDark = document.documentElement.classList.contains('dark');
            const tickClr = isDark ? '#94a3b8' : '#4b5563';
            const gridClr = isDark ? '#334155' : '#e5e7eb';

            if (!RETENTION || RETENTION.length === 0) {
                canvas.parentElement.innerHTML =
                    '<div class="flex h-full items-center justify-center text-sm text-gray-500">No retention data yet</div>';
                return;
            }

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: RETENTION.map(r => r.bucket),
                    datasets: [{
                            label: 'Renewal rate %',
                            data: RETENTION.map(r => r.renewal_rate),
                            backgroundColor: '#00A6A6',
                            hoverBackgroundColor: '#008c8c',
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 48,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Members',
                            data: RETENTION.map(r => r.members),
                            backgroundColor: 'rgba(148,163,184,0.4)',
                            hoverBackgroundColor: '#94a3b8',
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 20,
                            yAxisID: 'y1'
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
                                color: tickClr,
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
                            cornerRadius: 10,
                            callbacks: {
                                label: (item) => {
                                    if (item.datasetIndex === 0) return 'Renewal: ' + item.raw + '%';
                                    return 'Members: ' + item.raw;
                                }
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
                            max: 100,
                            position: 'left',
                            grid: {
                                color: gridClr,
                                drawBorder: false
                            },
                            ticks: {
                                color: tickClr,
                                callback: (v) => v + '%',
                                font: {
                                    size: 10
                                }
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
                                precision: 0,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        /* ============ Live timers (15s) ============ */
        function formatElapsed(startIso) {
            const start = new Date(String(startIso).replace(' ', 'T')).getTime();
            if (isNaN(start)) return '0m';
            const mins = Math.max(0, Math.floor((Date.now() - start) / 60000));
            const h = Math.floor(mins / 60);
            const m = mins % 60;
            return h > 0 ? h + 'h ' + m + 'm' : m + 'm';
        }

        function updateTimers() {
            document.querySelectorAll('.live-timer').forEach(el => {
                const start = el.dataset.start;
                if (start) el.textContent = formatElapsed(start);
            });
        }

        /* ============ Live occupancy polling (30s) ============ */
        async function refreshLive() {
            const badge = document.getElementById('inGymBadge');
            const count = document.getElementById('liveCount');
            const label = document.getElementById('liveUpdated');

            try {
                const res = await fetch(BASE + '/api/admin/occupancy', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();

                if (count) count.textContent = data.in_gym;
                if (badge) badge.textContent = data.in_gym;

                const grid = document.getElementById('inGymGrid');
                if (grid && Array.isArray(data.currently)) {
                    if (data.currently.length === 0) {
                        grid.innerHTML = `
                        <div class="col-span-full rounded-2xl border border-dashed border-white/10 bg-white/[0.02] p-10 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                            <p class="mt-3 text-sm text-gray-400">No one is currently in the gym.</p>
                        </div>`;
                    } else {
                        const tierPaths = {
                            digital: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                            standard: 'M13 10V3L4 14h7v7l9-11h-7z',
                            premium: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                            b2b: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
                        };
                        grid.innerHTML = data.currently.map((a, i) => `
                        <div class="live-card group relative overflow-hidden rounded-2xl border border-green-500/30 bg-gradient-to-br from-green-500/10 to-[#111] p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                            data-id="${a.id}" style="animation: card-enter 0.4s cubic-bezier(0.34,1.56,0.64,1) ${Math.min(i*40,300)}ms backwards;">
                            <div class="flex items-start justify-between">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <div class="relative flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-green-600 font-heading text-sm text-white shadow-md">
                                            ${a.initials}
                                        </div>
                                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#111] bg-green-500"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-bold text-white">${a.user_name}</div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-green-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${tierPaths[a.tier] || 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'}"/></svg>
                                            <span>Since ${new Date(a.time_in.replace(' ', 'T')).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</span>
                                        </div>
                                    </div>
                                </div>
                                <span data-status-badge class="flex-shrink-0 rounded-full bg-green-500/20 px-2 py-0.5 text-[10px] font-bold uppercase text-green-500">IN</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Duration</div>
                                    <div class="font-heading text-xl text-green-500 live-timer" data-start="${a.time_in}">${formatElapsed(a.time_in)}</div>
                                </div>
                                <form method="post" action="${BASE}/admin-dashboard/attendance/out" onsubmit="return confirm('Time out this member?')" class="inline">
                                    <input type="hidden" name="_csrf" value="${CSRF}">
                                    <input type="hidden" name="id" value="${a.id}">
                                    <button type="submit" aria-label="Time out this member" class="flex min-h-[40px] items-center gap-1 rounded-full bg-[#0a0a0a] border border-white/10 px-3 py-1.5 text-[11px] font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-red-500 hover:border-red-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Time Out
                                    </button>
                                </form>
                            </div>
                        </div>`).join('');
                    }
                }

                if (label) {
                    label.textContent = 'Last updated ' + new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }
            } catch (err) {
                if (label) label.textContent = 'Live data unavailable — retrying…';
            }
        }

        window.refreshLive = refreshLive;

        /* ============ Modal helpers ============ */
        function trackTrigger() {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
        }

        /* ============ Manual time-in modal ============ */
        window.openManualTimeInModal = function() {
            trackTrigger();
            document.getElementById('manualTimeInModal').classList.replace('hidden', 'flex');
        };
        window.closeManualTimeInModal = function() {
            document.getElementById('manualTimeInModal').classList.replace('flex', 'hidden');
        };

        /* ============ Edit attendance modal ============ */
        window.openEditAttendanceModal = function(r) {
            if (!r) return;

            trackTrigger();

            document.getElementById('edit-att-id').value = r.id;
            document.getElementById('edit-att-name').textContent = r.user_name;

            const toLocal = (s) => s ? s.replace(' ', 'T').slice(0, 16) : '';

            document.getElementById('edit-att-timein').value = toLocal(r.time_in);
            document.getElementById('edit-att-timeout').value = toLocal(r.time_out);
            document.getElementById('edit-att-notes').value = r.notes || '';

            document.getElementById('editAttendanceModal').classList.replace('hidden', 'flex');
        };
        window.closeEditAttendanceModal = function() {
            document.getElementById('editAttendanceModal').classList.replace('flex', 'hidden');
        };

        /* ============ Backdrop close ============ */
        const MODALS = [
            ['manualTimeInModal', 'closeManualTimeInModal'],
            ['editAttendanceModal', 'closeEditAttendanceModal'],
        ];

        MODALS.forEach(([id, fn]) => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) window[fn]();
            });
        });

        /* ============ Boot ============ */
        document.addEventListener('DOMContentLoaded', () => {
            initSeasonalChart();
            initRetentionChart();
            updateTimers();

            setInterval(updateTimers, 15000);

            setInterval(() => {
                if (!document.hidden) refreshLive();
            }, 30000);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) refreshLive();
            });
        });
    })();
</script>