<?php

/**
 * Member — Attendance History (2026 Dark UI with Tap Effects)
 */

$records = $records ?? [];
$user = current_user();

// ---------- Sort newest first ----------
usort($records, fn($a, $b) => strcmp($b['time_in'] ?? '', $a['time_in'] ?? ''));

// ---------- Current month selected via ?month=YYYY-MM ----------
$monthParam = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}
$monthTs = strtotime($monthParam . '-01');
$monthLabel = date('F Y', $monthTs);
$daysInMonth = (int)date('t', $monthTs);
$firstDayOfWeek = (int)date('w', $monthTs); // 0 = Sunday
$prevMonth = date('Y-m', strtotime($monthParam . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($monthParam . '-01 +1 month'));
$todayStr = date('Y-m-d');

// ---------- Stats ----------
$totalVisits = count($records);
$totalHours = 0;
$monthVisits = 0;
$completedSessions = 0;

// Map dates for calendar
$visitDates = [];
$visitHours = [];

foreach ($records as $r) {
    $date = $r['date'] ?? '';
    if (!$date) continue;

    $visitDates[$date] = ($visitDates[$date] ?? 0) + 1;
    $hours = (float)($r['hours'] ?? 0);

    if (!empty($r['time_out'])) {
        $totalHours += $hours;
        $completedSessions++;
    } else {
        if (!empty($r['time_in'])) {
            $elapsed = max(0, (time() - strtotime($r['time_in'])) / 3600);
            $totalHours += $elapsed;
        }
    }

    $visitHours[$date] = ($visitHours[$date] ?? 0) + $hours;

    if (substr($date, 0, 7) === $monthParam) {
        $monthVisits++;
    }
}
$totalHours = round($totalHours, 1);

// ---------- Current streak ----------
$streak = 0;
$check = new DateTime($todayStr);
while (true) {
    $key = $check->format('Y-m-d');
    if (isset($visitDates[$key])) {
        $streak++;
        $check->modify('-1 day');
    } else {
        if ($streak === 0 && $key === $todayStr) {
            $check->modify('-1 day');
            continue;
        }
        break;
    }
}

// ---------- Longest streak ----------
$allDates = array_keys($visitDates);
sort($allDates);
$longestStreak = 0;
$currentRun = 0;
$prevDate = null;
foreach ($allDates as $d) {
    if ($prevDate === null) {
        $currentRun = 1;
    } else {
        $diff = (strtotime($d) - strtotime($prevDate)) / 86400;
        $currentRun = ($diff === 1.0) ? $currentRun + 1 : 1;
    }
    $longestStreak = max($longestStreak, $currentRun);
    $prevDate = $d;
}

// ---------- This month's records ----------
$monthRecords = array_values(array_filter($records, fn($r) => substr($r['date'] ?? '', 0, 7) === $monthParam));

// ---------- Most active day ----------
$dowCounts = [0, 0, 0, 0, 0, 0, 0];
foreach ($records as $r) {
    if (empty($r['date'])) continue;
    $dowCounts[(int)date('w', strtotime($r['date']))]++;
}
$mostActiveDow = array_search(max($dowCounts), $dowCounts, true);
$dowLabels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== Page Header ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Tracking</span>
                </div>
                <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    ATTENDANCE HISTORY
                </h1>
                <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                    Every check-in, your streaks, and total gym time.
                </p>
            </div>

            <!-- Added tap-effect -->
            <a href="<?= url_e('/time-in') ?>" class="tap-effect group inline-flex flex-shrink-0 items-center gap-2 self-start rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)] sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Time In Now
            </a>
        </div>
    </div>

    <!-- ========== Stats Grid ========== -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Visits -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Visits</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= $totalVisits ?></div>
                    <div class="mt-1 text-xs text-gray-400">All time</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">This Month</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= $monthVisits ?></div>
                    <div class="mt-1 text-xs text-gray-400"><?= e($monthLabel) ?></div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Hours -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Hours</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= $totalHours ?>h</div>
                    <div class="mt-1 text-xs text-gray-400"><?= $completedSessions ?> completed</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Current Streak -->
        <div class="group relative overflow-hidden rounded-2xl border <?= $streak > 0 ? 'border-orange-500/30 bg-orange-500/5' : 'border-white/5 bg-[#111]' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-orange-500/40">
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-orange-500">
                        <?php if ($streak > 0): ?>
                            <span class="h-1.5 w-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                        <?php endif; ?>
                        Streak
                    </div>
                    <div class="mt-2 font-heading text-4xl text-white">
                        <?= $streak ?><span class="text-2xl ml-1">🔥</span>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $streak === 0 ? 'Start today!' : ($streak === 1 ? 'day in a row' : 'days in a row') ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-500 transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($records)): ?>
        <!-- ========== Empty State ========== -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-16 text-center shadow-sm">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="mt-6 font-heading text-2xl tracking-wide text-white">NO CHECK-INS YET</h3>
            <p class="mt-2 text-sm text-gray-400">
                Your attendance history will appear here once you scan the QR code at the gym entrance.
            </p>
            <!-- Added tap-effect -->
            <a href="<?= url_e('/time-in') ?>" class="tap-effect mt-8 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                Time In Now
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>

    <?php else: ?>

        <!-- ========== Calendar + Sidebar ========== -->
        <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">

            <!-- Calendar -->
            <div class="rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm">
                <!-- Month header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-heading text-2xl tracking-wide text-white">CHECK-IN CALENDAR</h3>
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-gray-400">
                            <span class="h-2 w-2 rounded-full bg-[#00A6A6]"></span> Visited day
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Added tap-effect -->
                        <a href="<?= url_e('/attendance?month=' . $prevMonth) ?>" class="tap-effect flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:border-[#00A6A6] hover:text-[#00A6A6]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                        <div class="min-w-[110px] text-center font-heading text-lg tracking-wide text-white">
                            <?= e($monthLabel) ?>
                        </div>
                        <!-- Added tap-effect -->
                        <a href="<?= url_e('/attendance?month=' . $nextMonth) ?>" class="tap-effect flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:border-[#00A6A6] hover:text-[#00A6A6]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Day-of-week headers -->
                <div class="mt-6 grid grid-cols-7 gap-1 text-center">
                    <?php foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $d): ?>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500"><?= $d ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Calendar grid -->
                <div class="mt-2 grid grid-cols-7 gap-1">
                    <?php
                    // Empty leading cells
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        echo '<div class="aspect-square"></div>';
                    }

                    // Day cells
                    for ($day = 1; $day <= $daysInMonth; $day++):
                        $dateKey = $monthParam . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
                        $hasVisit = isset($visitDates[$dateKey]);
                        $isToday  = $dateKey === $todayStr;
                        $isFuture = $dateKey > $todayStr;
                    ?>
                        <div class="group relative aspect-square rounded-lg transition-all duration-300
                        <?= $hasVisit
                            ? 'bg-[#00A6A6] text-white shadow-[0_0_10px_rgba(0,166,166,0.4)] hover:scale-105 cursor-pointer tap-effect'
                            : ($isToday
                                ? 'border-2 border-[#00A6A6] bg-white/5 text-white font-bold'
                                : ($isFuture
                                    ? 'bg-white/[0.02] text-gray-600'
                                    : 'bg-white/5 text-gray-400 hover:bg-white/10')) ?>"
                            title="<?= $hasVisit ? $dateKey . ' — ' . number_format($visitHours[$dateKey], 1) . 'h' : $dateKey ?>">

                            <div class="flex h-full items-center justify-center text-xs font-semibold">
                                <?= $day ?>
                            </div>

                            <?php if ($hasVisit): ?>
                                <div class="absolute -bottom-0.5 left-1/2 flex -translate-x-1/2 gap-0.5">
                                    <span class="h-1 w-1 rounded-full bg-white/90"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>

                    <!-- Empty trailing cells -->
                    <?php
                    $totalCells = $firstDayOfWeek + $daysInMonth;
                    $trailing = (7 - ($totalCells % 7)) % 7;
                    for ($i = 0; $i < $trailing; $i++) {
                        echo '<div class="aspect-square"></div>';
                    }
                    ?>
                </div>

                <!-- Legend -->
                <div class="mt-6 flex flex-wrap items-center gap-6 border-t border-white/5 pt-4 text-xs text-gray-400">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-sm bg-[#00A6A6]"></span> Visited
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-sm border-2 border-[#00A6A6] bg-transparent"></span> Today
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-sm bg-white/[0.05]"></span> Upcoming
                    </div>
                </div>
            </div>

            <!-- Sidebar stats -->
            <div class="space-y-4">
                <!-- Longest streak -->
                <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-500/10 text-orange-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Longest Streak</div>
                            <div class="mt-1 font-heading text-2xl text-white"><?= $longestStreak ?> days</div>
                        </div>
                    </div>
                </div>

                <!-- Favorite day -->
                <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Favorite Day</div>
                            <div class="mt-1 font-heading text-2xl text-white"><?= $dowLabels[$mostActiveDow] ?></div>
                        </div>
                    </div>
                </div>

                <!-- Avg per week -->
                <?php
                $uniqueDays = count($visitDates);
                $avgPerWeek = 0;
                if ($uniqueDays > 0) {
                    $firstDate = min(array_keys($visitDates));
                    $weeks = max(1, (int)ceil((time() - strtotime($firstDate)) / (86400 * 7)));
                    $avgPerWeek = round($uniqueDays / $weeks, 1);
                }
                ?>
                <div class="rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10 text-blue-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Avg / Week</div>
                            <div class="mt-1 font-heading text-2xl text-white"><?= $avgPerWeek ?> visits</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== Check-in Table ========== -->
        <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/5 p-6 sm:p-8">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">
                        <?= e(strtoupper($monthLabel)) ?> CHECK-INS
                    </h3>
                    <p class="mt-1 text-xs text-gray-400">
                        <?= count($monthRecords) ?> check-in<?= count($monthRecords) !== 1 ? 's' : '' ?> this month
                    </p>
                </div>
            </div>

            <?php if (empty($monthRecords)): ?>
                <div class="p-12 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                    <p class="mt-4 text-sm text-gray-400">No check-ins in <?= e($monthLabel) ?>.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Date</th>
                                <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Time In</th>
                                <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Time Out</th>
                                <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Duration</th>
                                <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthRecords as $idx => $r):
                                $isIn = empty($r['time_out']);
                                $duration = '—';
                                if ($isIn && !empty($r['time_in'])) {
                                    $mins = max(0, (time() - strtotime($r['time_in'])) / 60);
                                    $h = floor($mins / 60);
                                    $m = (int)$mins % 60;
                                    $duration = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                                } elseif (!empty($r['hours'])) {
                                    $duration = $r['hours'] . 'h';
                                }
                            ?>
                                <tr class="group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]">
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-white">
                                            <?= e(date('D, M j', strtotime($r['date']))) ?>
                                        </div>
                                    </td>
                                    <td class="p-4 text-sm text-gray-400 whitespace-nowrap">
                                        <?= e(time_short($r['time_in'] ?? null)) ?>
                                    </td>
                                    <td class="p-4 text-sm whitespace-nowrap">
                                        <?php if ($isIn): ?>
                                            <span class="text-gray-500">&mdash;</span>
                                        <?php else: ?>
                                            <span class="text-white"><?= e(time_short($r['time_out'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-sm font-semibold whitespace-nowrap <?= $isIn ? 'text-green-500' : 'text-white' ?>">
                                        <?= e($duration) ?>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($isIn): ?>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 border border-green-500/20 px-3 py-1 text-[10px] font-bold uppercase text-green-500">
                                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-green-500"></span>
                                                In Gym
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/5 border border-white/10 px-3 py-1 text-[10px] font-bold uppercase text-gray-400">
                                                Done
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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
</style>