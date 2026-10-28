<?php

/**
 * Member — Home Dashboard (2026 UI with Digital ID & Tap Effects)
 * Fully Responsive for Mobile
 */

$user = current_user();

// Safe data
$profile    = $profile   ?? [];
$active     = $active    ?? null;
$daysLeft   = $daysLeft  ?? null;
$balance    = $balance   ?? 0;
$firstName  = $firstName ?? explode(' ', $user['full_name'] ?: $user['email'])[0];

// Membership status
$hasActive = $active && ($active['status'] ?? '') === 'active';

// Progress percentage
$progress = null;
if ($hasActive && !empty($active['start_date']) && !empty($active['end_date'])) {
    $start = strtotime($active['start_date']);
    $end   = strtotime($active['end_date']);
    $total = max(1, $end - $start);
    $used  = max(0, time() - $start);
    $progress = min(100, round(($used / $total) * 100));
}

// Greeting
$hour = (int)date('G');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 18)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';
?>

<div class="space-y-6 sm:space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== 1. Welcome Banner ========== -->
    <!-- ========== 1. Welcome Banner ========== -->
    <div class="relative w-full overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-5 sm:p-8 lg:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between w-full">
            <div class="min-w-0 w-full">
                <div class="text-[10px] sm:text-xs font-bold uppercase tracking-[0.3em] text-gray-500">
                    <?= e($greeting) ?>
                </div>
                <h1 class="mt-1 sm:mt-2 font-heading text-3xl sm:text-4xl lg:text-5xl tracking-wide text-white break-words">
                    WELCOME BACK, <br class="sm:hidden"> <span class="text-[#00A6A6]"><?= e(strtoupper($firstName)) ?></span>
                </h1>
                <p class="mt-2 sm:mt-3 font-heading text-sm sm:text-lg tracking-[0.2em] text-gray-400">
                    COMMIT TO BE HEALTHY
                </p>
            </div>

            <div class="flex-shrink-0 w-full sm:w-auto">
                <?php if ($hasActive): ?>
                    <div class="inline-block w-full sm:w-auto rounded-2xl border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-4 sm:px-5 py-2.5 sm:py-3 backdrop-blur-sm">
                        <div class="flex items-center gap-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-[#00A6A6]">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#00A6A6] opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-[#00A6A6]"></span>
                            </span>
                            Active Member
                        </div>
                        <div class="mt-0.5 text-xs sm:text-sm font-bold text-white">
                            <?= e(strtoupper($active['tier'] ?? 'standard')) ?> TIER
                        </div>
                    </div>
                <?php else: ?>
                    <div class="inline-block w-full sm:w-auto rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 sm:px-5 py-2.5 sm:py-3 backdrop-blur-sm">
                        <div class="flex items-center gap-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-amber-500">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Not Enrolled
                        </div>
                        <a href="<?= url_e('/programs') ?>" class="mt-0.5 block text-xs sm:text-sm font-bold text-white transition-colors hover:text-[#00A6A6]">
                            Choose a program &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========== 2. Digital Membership ID Banner ========== -->
    <?php if ($hasActive): ?>
        <div class="tap-effect group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm transition-all duration-300 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0a2 2 0 104 0" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Digital Membership</div>
                        <div class="mt-0.5 text-xs sm:text-sm font-bold text-white">Your ID card is ready to scan.</div>
                    </div>
                </div>
                <button type="button" onclick="openDigitalId()" class="flex-shrink-0 flex w-full sm:w-auto items-center justify-center gap-2 rounded-full bg-[#00A6A6]/10 px-6 py-3 text-xs font-bold text-[#00A6A6] transition-all duration-300 hover:bg-[#00A6A6] hover:text-white hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    View ID
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========== 3. Stat Cards Grid ========== -->
    <div class="grid gap-3 sm:gap-4 grid-cols-2 xl:grid-cols-4">
        <!-- Program Card -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Program</div>
                    <div class="mt-1 sm:mt-2 truncate font-heading text-lg sm:text-2xl text-white">
                        <?= e($active['program_name'] ?? '—') ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 truncate text-[10px] sm:text-xs text-gray-400">
                        <?= $hasActive ? strtoupper($active['tier']) . ' tier' : 'Not enrolled yet' ?>
                    </div>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Days Left Card -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Days Left</div>
                    <div class="mt-1 sm:mt-2 font-heading text-2xl sm:text-4xl <?= ($daysLeft !== null && $daysLeft <= 3) ? 'text-red-500' : 'text-white' ?>">
                        <?= $daysLeft ?? '—' ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">
                        <?= $hasActive && !empty($active['end_date']) ? 'Until ' . date('M j', strtotime($active['end_date'])) : 'Enroll to start' ?>
                    </div>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Sessions Left Card -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Sessions Left</div>
                    <div class="mt-1 sm:mt-2 font-heading text-2xl sm:text-4xl text-white">
                        <?= (int)($profile['sessions_left'] ?? 0) ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">This month</div>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="group relative overflow-hidden rounded-2xl border <?= $balance > 0 ? 'border-red-500/30 bg-red-500/5' : 'border-white/5 bg-[#111]' ?> p-4 sm:p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Balance</div>
                    <div class="mt-1 sm:mt-2 font-heading text-xl sm:text-3xl <?= $balance > 0 ? 'text-red-500' : 'text-green-500' ?>">
                        <?= peso($balance) ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">
                        <?= $balance > 0 ? 'Outstanding dues' : 'All paid' ?>
                    </div>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl <?= $balance > 0 ? 'bg-red-500/10 text-red-500' : 'bg-green-500/10 text-green-500' ?> transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 4. Membership Progress ========== -->
    <?php if ($hasActive && $progress !== null): ?>
        <div class="rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 lg:p-8 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h3 class="font-heading text-xl sm:text-2xl tracking-wide text-white">MEMBERSHIP PROGRESS</h3>
                    <p class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">
                        <?= date('M j, Y', strtotime($active['start_date'])) ?> &rarr; <?= date('M j, Y', strtotime($active['end_date'])) ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="font-heading text-2xl sm:text-4xl <?= $progress >= 90 ? 'text-red-500' : ($progress >= 60 ? 'text-amber-500' : 'text-[#00A6A6]') ?>">
                        <?= $progress ?>%
                    </div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-500">elapsed</div>
                </div>
            </div>

            <div class="mt-4 sm:mt-6 h-2 sm:h-3 overflow-hidden rounded-full bg-white/5">
                <div class="h-full rounded-full transition-all duration-1000 ease-out <?= $progress >= 90 ? 'bg-gradient-to-r from-red-600 to-red-400' : ($progress >= 60 ? 'bg-gradient-to-r from-amber-600 to-amber-400' : 'bg-gradient-to-r from-[#00A6A6] to-[#4fdfdf]') ?>"
                    style="width: <?= $progress ?>%;"></div>
            </div>

            <?php if ($daysLeft !== null && $daysLeft <= 7): ?>
                <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 sm:p-4">
                    <div class="flex h-8 w-8 sm:h-10 sm:w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-500 text-white">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="text-xs sm:text-sm font-bold text-amber-500">
                            <?= $daysLeft === 0 ? 'Membership expires today!' : "Only {$daysLeft} day" . ($daysLeft > 1 ? 's' : '') . " left" ?>
                        </div>
                        <div class="text-[10px] sm:text-xs text-gray-400">Renew now to avoid interruption.</div>
                    </div>
                    <a href="<?= url_e('/programs') ?>" class="w-full sm:w-auto text-center flex-shrink-0 rounded-full bg-amber-500 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-amber-600">
                        Renew &rarr;
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ========== 5. Quick Actions ========== -->
    <div>
        <h3 class="font-heading text-xl sm:text-2xl tracking-wide text-white mb-3 sm:mb-4">QUICK ACTIONS</h3>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-4">

            <!-- Time In -->
            <a href="<?= url_e('/time-in') ?>" class="tap-effect group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:bg-[#151515] hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.3)]">
                <div class="mx-auto flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 block text-[10px] sm:text-xs font-bold text-white uppercase tracking-wider">Time In</span>
            </a>

            <!-- Programs -->
            <a href="<?= url_e('/programs') ?>" class="tap-effect group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:bg-[#151515] hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.3)]">
                <div class="mx-auto flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 block text-[10px] sm:text-xs font-bold text-white uppercase tracking-wider">Programs</span>
            </a>

            <!-- Payments -->
            <a href="<?= url_e('/payments') ?>" class="tap-effect group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:bg-[#151515] hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.3)]">
                <div class="mx-auto flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 block text-[10px] sm:text-xs font-bold text-white uppercase tracking-wider">Payments</span>
            </a>

            <!-- Attendance -->
            <a href="<?= url_e('/attendance') ?>" class="tap-effect group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:bg-[#151515] hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.3)]">
                <div class="mx-auto flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 block text-[10px] sm:text-xs font-bold text-white uppercase tracking-wider">Attendance</span>
            </a>
        </div>
    </div>

    <!-- ========== 6. Live Occupancy Widget ========== -->
    <?php require BASE_PATH . '/src/Views/member/partials/occupancy_widget.php'; ?>

</div>

<!-- Load the Digital ID Modal -->
<?php require BASE_PATH . '/src/Views/member/partials/digital_id_modal.php'; ?>

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