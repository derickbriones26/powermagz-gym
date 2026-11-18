<?php

/**
 * Member — Home Dashboard (2026 UI with Digital ID & Tap Effects)
 * Fully Responsive for Mobile
 */

$user = current_user();

// Scoped motion layer for this page only.
$pageStyles = ['/public/css/dashboard.css'];

// Safe data
$profile    = $profile   ?? [];
$active     = $active    ?? null;
$daysLeft   = $daysLeft  ?? null;
$balance    = $balance   ?? 0;
$firstName  = $firstName ?? explode(' ', $user['full_name'] ?: $user['email'])[0];
$caps       = member_capabilities();
$capMeta    = capability_meta();

// Membership status
$hasActive = $active && ($active['status'] ?? '') === 'active';

// Sessions Left only exists on coaching tiers; match the grid to the real count.
$statCount = 3 + (!empty($caps['personal_coaching']) ? 1 : 0);

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

<div class="member-dashboard space-y-6 sm:space-y-8">

    <!-- ========== 1. Welcome Banner ========== -->
    <section class="dash-hero dash-reveal relative w-full overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-5 sm:p-8 lg:p-10 border border-white/5 shadow-2xl">
        <div class="dash-hero-media pointer-events-none absolute inset-0" aria-hidden="true"
            style="background-image:url('<?= url_e('/public/images/dashboard-hero.jpg') ?>')"></div>
        <div class="dash-hero-shade pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between w-full">
            <div class="min-w-0 w-full">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                        <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Member Portal</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400 sm:text-xs">
                        <span><?= e($greeting) ?></span>
                        <span class="h-1 w-1 rounded-full bg-[#00A6A6]" aria-hidden="true"></span>
                        <time datetime="<?= date('Y-m-d') ?>" class="text-gray-500"><?= date('D, M j') ?></time>
                    </div>
                </div>
                <h1 class="mt-3 sm:mt-4 font-heading text-3xl sm:text-4xl lg:text-5xl tracking-wide text-white break-words">
                    WELCOME BACK, <br class="sm:hidden"> <span class="text-[#00A6A6]"><?= e(strtoupper($firstName)) ?></span>
                </h1>
                <div class="dash-beam" aria-hidden="true"></div>
                <p class="mt-3 sm:mt-4 font-heading text-sm sm:text-lg tracking-[0.2em] text-gray-400">
                    COMMIT TO BE HEALTHY
                </p>
            </div>

            <div class="flex-shrink-0 w-full sm:w-auto">
                <?php if ($hasActive): ?>
                    <div class="inline-block w-full sm:w-auto rounded-2xl border border-[#00A6A6]/30 bg-[#0a0a0a]/60 px-4 sm:px-5 py-2.5 sm:py-3 backdrop-blur-md">
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
                    <div class="inline-block w-full sm:w-auto rounded-2xl border border-amber-500/30 bg-[#0a0a0a]/60 px-4 sm:px-5 py-2.5 sm:py-3 backdrop-blur-md">
                        <div class="flex items-center gap-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-amber-500">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Not Enrolled
                        </div>
                        <a href="<?= url_e('/programs') ?>" class="dash-link mt-0.5 flex items-center gap-1.5 text-xs sm:text-sm font-bold text-white transition-colors hover:text-[#00A6A6]">
                            Choose a program <span class="dash-link-arrow" aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ========== 2. Digital Membership ID Banner ========== -->
    <?php if ($hasActive): ?>
        <div class="dash-card dash-reveal group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm" style="--reveal-delay: 70ms">
            <span class="dash-accent" aria-hidden="true"></span>
            <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="dash-icon flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0a2 2 0 104 0" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Digital Membership</div>
                        <div class="mt-0.5 text-xs sm:text-sm font-bold text-white">Your ID card is ready to scan.</div>
                    </div>
                </div>
                <button type="button" onclick="openDigitalId()" aria-haspopup="dialog" aria-controls="digitalIdModal" class="dash-btn flex-shrink-0 flex w-full sm:w-auto items-center justify-center gap-2 rounded-full bg-[#00A6A6]/10 px-6 py-3 text-xs font-bold text-[#00A6A6] transition-colors hover:bg-[#00A6A6] hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    View ID
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========== 3. Stat Cards Grid ========== -->
    <div class="grid gap-3 sm:gap-4 grid-cols-2 <?= $statCount === 4 ? 'xl:grid-cols-4' : 'xl:grid-cols-3' ?>" style="--reveal-delay: 130ms">
        <!-- Program Card -->
        <div class="dash-card dash-item group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm" style="--item-i: 0">
            <span class="dash-accent" aria-hidden="true"></span>
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Program</div>
                    <div class="mt-1 sm:mt-2 font-heading text-[17px] sm:text-[21px] leading-tight text-white">
                        <?= e($active['program_name'] ?? '—') ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 truncate text-[10px] sm:text-xs text-gray-400">
                        <?= $hasActive ? strtoupper($active['tier']) . ' tier' : 'Not enrolled yet' ?>
                    </div>
                </div>
                <div class="dash-icon flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Days Left Card -->
        <div class="dash-card dash-item group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm" style="--item-i: 1">
            <span class="dash-accent" aria-hidden="true"></span>
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Days Left</div>
                    <div class="mt-1 sm:mt-2 font-heading text-2xl sm:text-4xl tabular-nums <?= ($daysLeft !== null && $daysLeft <= 3) ? 'text-red-500' : 'text-white' ?>">
                        <?= $daysLeft ?? '—' ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">
                        <?= $hasActive && !empty($active['end_date']) ? 'Until ' . date('M j', strtotime($active['end_date'])) : 'Enroll to start' ?>
                    </div>
                </div>
                <div class="dash-icon flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Sessions Left Card (Premium only) -->
        <?php if (!empty($caps['personal_coaching'])): ?>
        <div class="dash-card dash-item group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 shadow-sm" style="--item-i: 2">
            <span class="dash-accent" aria-hidden="true"></span>
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Sessions Left</div>
                    <div class="mt-1 sm:mt-2 font-heading text-2xl sm:text-4xl tabular-nums text-white">
                        <?= (int)($profile['sessions_left'] ?? 0) ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">Personal coaching</div>
                </div>
                <div class="dash-icon flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Balance Card -->
        <div class="dash-card dash-item <?= $balance > 0 ? 'dash-card-alert' : '' ?> group relative overflow-hidden rounded-2xl border <?= $balance > 0 ? 'border-red-500/30 bg-red-500/5' : 'border-white/5 bg-[#111]' ?> p-4 sm:p-6 shadow-sm" style="--item-i: 3">
            <span class="dash-accent" aria-hidden="true"></span>
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-500">Balance</div>
                    <div class="mt-1 sm:mt-2 font-heading text-xl sm:text-3xl tabular-nums <?= $balance > 0 ? 'text-red-500' : 'text-green-500' ?>">
                        <?= peso($balance) ?>
                    </div>
                    <div class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">
                        <?= $balance > 0 ? 'Outstanding dues' : 'All paid' ?>
                    </div>
                </div>
                <div class="dash-icon-static flex h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 items-center justify-center rounded-xl <?= $balance > 0 ? 'bg-red-500/10 text-red-500' : 'bg-green-500/10 text-green-500' ?> transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 4. Membership Progress ========== -->
    <?php if ($hasActive && $progress !== null): ?>
        <div class="dash-card-static dash-reveal rounded-2xl border border-white/5 bg-[#111] p-4 sm:p-6 lg:p-8 shadow-sm" style="--reveal-delay: 210ms">
            <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h3 class="font-heading text-xl sm:text-2xl tracking-wide text-white">MEMBERSHIP PROGRESS</h3>
                    <p class="mt-0.5 sm:mt-1 text-[10px] sm:text-xs text-gray-400">
                        <?= date('M j, Y', strtotime($active['start_date'])) ?> &rarr; <?= date('M j, Y', strtotime($active['end_date'])) ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="font-heading text-2xl sm:text-4xl tabular-nums <?= $progress >= 90 ? 'text-red-500' : ($progress >= 60 ? 'text-amber-500' : 'text-[#00A6A6]') ?>">
                        <?= $progress ?>%
                    </div>
                    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-500">elapsed</div>
                </div>
            </div>

            <div class="mt-4 sm:mt-6 h-2 sm:h-3 overflow-hidden rounded-full bg-white/5"
                role="progressbar" aria-label="Membership elapsed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)$progress ?>">
                <div class="dash-progress h-full rounded-full <?= $progress >= 90 ? 'bg-gradient-to-r from-red-600 to-red-400' : ($progress >= 60 ? 'bg-gradient-to-r from-amber-600 to-amber-400' : 'bg-gradient-to-r from-[#00A6A6] to-[#4fdfdf]') ?>"
                    style="--progress: <?= (int)$progress ?>%;"></div>
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
                    <a href="<?= url_e('/programs') ?>" class="dash-btn w-full sm:w-auto text-center flex-shrink-0 rounded-full bg-amber-500 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-amber-600">
                        Renew &rarr;
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ========== 4b. Your tier capabilities ========== -->
    <?php
    $unlockedCaps = array_filter($caps, fn($on) => $on === true);
    $lockedCaps   = array_filter($caps, fn($on) => $on !== true);
    ?>
    <div class="dash-card-static dash-reveal rounded-2xl border border-white/5 bg-[#111] p-5 sm:p-6" style="--reveal-delay: 250ms">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-heading text-xl sm:text-2xl tracking-wide text-white">YOUR MEMBERSHIP</h3>
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <?= $hasActive ? e(strtoupper($active['tier'])) . ' tier' : 'No active plan' ?>
            </span>
        </div>

        <?php if (!$hasActive): ?>
            <p class="mt-2 text-sm text-gray-400">Enroll in any plan to unlock QR check-in, workouts, and your portal features.</p>
            <a href="<?= url_e('/programs') ?>"
                class="dash-btn mt-4 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-5 py-2.5 text-xs font-bold text-white transition-colors hover:bg-[#008c8c]">
                View plans
            </a>
        <?php else: ?>
            <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2.5 sm:flex sm:flex-wrap sm:gap-2">
                <?php foreach ($unlockedCaps as $key => $on): ?>
                    <span class="flex items-start gap-1.5 text-[10px] font-bold uppercase leading-snug tracking-wider text-[#00A6A6] sm:inline-flex sm:items-center sm:rounded-full sm:border sm:border-[#00A6A6]/30 sm:bg-[#00A6A6]/10 sm:px-3 sm:py-1.5">
                        <svg class="mt-0.5 h-3 w-3 flex-shrink-0 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <?= e($capMeta[$key]['label'] ?? $key) ?>
                    </span>
                <?php endforeach; ?>
                <?php foreach ($lockedCaps as $key => $on): ?>
                    <a href="<?= url_e('/programs') ?>"
                        class="dash-chip flex items-start gap-1.5 text-[10px] font-bold uppercase leading-snug tracking-wider text-gray-500 transition-colors hover:text-gray-300 sm:inline-flex sm:items-center sm:rounded-full sm:border sm:border-white/10 sm:bg-white/[0.02] sm:px-3 sm:py-1.5 sm:hover:border-[#00A6A6]/40"
                        title="<?= e($capMeta[$key]['upgrade'] ?? 'Upgrade to unlock.') ?>">
                        <svg class="mt-0.5 h-3 w-3 flex-shrink-0 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <?= e($capMeta[$key]['label'] ?? $key) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========== 5. Quick Actions ========== -->
    <div>
        <h3 class="dash-reveal font-heading text-xl sm:text-2xl tracking-wide text-white mb-3 sm:mb-4" style="--reveal-delay: 290ms">QUICK ACTIONS</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4 xl:grid-cols-7 xl:gap-3" style="--reveal-delay: 320ms">

            <!-- Time In -->
            <a href="<?= url_e('/time-in') ?>" class="quick-primary dash-action dash-item group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 xl:p-4 text-center" style="--item-i: 0">
                <span class="dash-accent" aria-hidden="true"></span>
                <div class="dash-action-icon mx-auto flex h-12 w-12 sm:h-14 sm:w-14 xl:h-12 xl:w-12 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="quick-primary-label mt-3 sm:mt-4 xl:mt-3 block text-[10px] sm:text-xs xl:text-[10px] font-bold text-white uppercase tracking-wider">Time In</span>
            </a>

            <!-- Programs -->
            <a href="<?= url_e('/programs') ?>" class="dash-action dash-item group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 xl:p-4 text-center" style="--item-i: 1">
                <span class="dash-accent" aria-hidden="true"></span>
                <div class="dash-action-icon mx-auto flex h-12 w-12 sm:h-14 sm:w-14 xl:h-12 xl:w-12 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 xl:mt-3 block text-[10px] sm:text-xs xl:text-[10px] font-bold text-white uppercase tracking-wider">Programs</span>
            </a>

            <!-- Payments -->
            <a href="<?= url_e('/payments') ?>" class="dash-action dash-item group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 xl:p-4 text-center" style="--item-i: 2">
                <span class="dash-accent" aria-hidden="true"></span>
                <div class="dash-action-icon mx-auto flex h-12 w-12 sm:h-14 sm:w-14 xl:h-12 xl:w-12 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 xl:mt-3 block text-[10px] sm:text-xs xl:text-[10px] font-bold text-white uppercase tracking-wider">Payments</span>
            </a>

            <!-- Attendance -->
            <a href="<?= url_e('/attendance') ?>" class="dash-action dash-item group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 xl:p-4 text-center" style="--item-i: 3">
                <span class="dash-accent" aria-hidden="true"></span>
                <div class="dash-action-icon mx-auto flex h-12 w-12 sm:h-14 sm:w-14 xl:h-12 xl:w-12 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 xl:mt-3 block text-[10px] sm:text-xs xl:text-[10px] font-bold text-white uppercase tracking-wider">Attendance</span>
            </a>

            <!-- Group Classes -->
            <a href="<?= url_e('/classes') ?>" class="dash-action dash-item group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 xl:p-4 text-center" style="--item-i: 4">
                <span class="dash-accent" aria-hidden="true"></span>
                <?php if (empty($caps['group_classes'])): ?>
                    <span class="absolute right-3 top-3 text-gray-600" title="Upgrade to unlock">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                <?php endif; ?>
                <div class="dash-action-icon mx-auto flex h-12 w-12 sm:h-14 sm:w-14 xl:h-12 xl:w-12 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 xl:mt-3 block text-[10px] sm:text-xs xl:text-[10px] font-bold text-white uppercase tracking-wider">Classes</span>
            </a>

            <!-- Workout Plan -->
            <a href="<?= url_e('/recommender') ?>" class="dash-action dash-item group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 xl:p-4 text-center" style="--item-i: 5">
                <span class="dash-accent" aria-hidden="true"></span>
                <?php if (empty($caps['ai_recommender'])): ?>
                    <span class="absolute right-3 top-3 text-gray-600" title="Upgrade to unlock">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                <?php endif; ?>
                <div class="dash-action-icon mx-auto flex h-12 w-12 sm:h-14 sm:w-14 xl:h-12 xl:w-12 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 xl:mt-3 block text-[10px] sm:text-xs xl:text-[10px] font-bold text-white uppercase tracking-wider">Workout Plan</span>
            </a>

            <!-- AI Coach -->
            <a href="<?= url_e('/ai-coach') ?>" class="dash-action dash-item group relative overflow-hidden rounded-2xl bg-[#111] border border-white/5 p-4 sm:p-6 xl:p-4 text-center" style="--item-i: 6">
                <span class="dash-accent" aria-hidden="true"></span>
                <?php if (empty($caps['ai_coach'])): ?>
                    <span class="absolute right-3 top-3 text-gray-600" title="Premium feature">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                <?php endif; ?>
                <div class="dash-action-icon mx-auto flex h-12 w-12 sm:h-14 sm:w-14 xl:h-12 xl:w-12 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6]">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <span class="mt-3 sm:mt-4 xl:mt-3 block text-[10px] sm:text-xs xl:text-[10px] font-bold text-white uppercase tracking-wider">AI Coach</span>
            </a>
        </div>
    </div>

    <!-- ========== 6. Live Occupancy Widget ========== -->
    <?php require BASE_PATH . '/src/Views/member/partials/occupancy_widget.php'; ?>

</div>

<!-- Load the Digital ID Modal -->
<?php require BASE_PATH . '/src/Views/member/partials/digital_id_modal.php'; ?>