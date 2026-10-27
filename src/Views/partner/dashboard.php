<?php

/**
 * Partner — Dashboard (2026 UI)
 * Welcome + 4 stat cards + earnings chart + quick actions
 */

$user    = current_user();
$profile = $profile       ?? [];
$clients = $clients       ?? [];
$sales   = $sales         ?? [];
$totalEarnings = $totalEarnings ?? 0;
$monthEarnings = $monthEarnings ?? 0;

// ---------- Derived stats ----------
$approvedSales = array_filter($sales, fn($s) => ($s['status'] ?? '') === 'approved');
$pendingSales  = array_filter($sales, fn($s) => ($s['status'] ?? '') === 'pending');

$approvedCount = count($approvedSales);
$pendingCount  = count($pendingSales);

// Pending amount
$pendingAmount = 0;
foreach ($pendingSales as $s) $pendingAmount += (float)($s['amount'] ?? 0);

// Greeting
$hour = (int)date('G');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 18)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';

$businessName = $profile['business_name'] ?? ($user['full_name'] ?: 'Partner');
$partnerType  = strtoupper($profile['partner_type'] ?? 'coach');
$firstName    = explode(' ', $businessName)[0];
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== 1. Welcome Header (Member Portal Style) ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <!-- Glowing Orb (Teal only) -->
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-[0.3em] text-gray-500">
                    <?= e($greeting) ?>
                </div>
                <h1 class="mt-2 font-heading text-4xl tracking-wide text-white sm:text-5xl lg:text-6xl">
                    WELCOME, <span class="text-[#00A6A6]"><?= e(strtoupper($firstName)) ?></span>
                </h1>
                <p class="mt-3 font-heading text-lg tracking-[0.2em] text-gray-400">
                    <?= e($partnerType) ?> PARTNER
                </p>
                <p class="mt-4 max-w-lg text-sm leading-relaxed text-gray-400">
                    Track your clients, log sales, and manage your earnings all from here.
                </p>
            </div>

            <!-- Quick actions (Right side) -->
            <div class="flex flex-shrink-0 flex-wrap gap-3 sm:flex-col">
                <a href="<?= url_e('/partner-dashboard/sales') ?>"
                    class="tap-effect group inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                    <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Log Sale</span>
                </a>
                <a href="<?= url_e('/partner-dashboard/clients') ?>"
                    class="tap-effect group inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-xs font-bold text-white backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-white/10">
                    <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Add Client</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ========== 2. Stat Cards Grid ========== -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total Clients -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Clients</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= count($clients) ?></div>
                    <div class="mt-1 text-xs text-gray-400">Linked to your account</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Earnings -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Earnings</div>
                    <div class="mt-2 font-heading text-3xl text-[#00A6A6]"><?= peso($totalEarnings) ?></div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= $approvedCount ?> approved sale<?= $approvedCount !== 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">This Month</div>
                    <div class="mt-2 font-heading text-3xl text-white"><?= peso($monthEarnings) ?></div>
                    <div class="mt-1 text-xs text-gray-400"><?= date('F Y') ?></div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6]/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Sales (or Referral Code if none) -->
        <?php if ($pendingCount > 0): ?>
            <div class="group relative overflow-hidden rounded-2xl border border-amber-500/30 bg-amber-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40">
                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-amber-500">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                            Pending Sales
                        </div>
                        <div class="mt-2 font-heading text-3xl text-amber-500"><?= peso($pendingAmount) ?></div>
                        <div class="mt-1 text-xs text-gray-400">
                            <?= $pendingCount ?> awaiting approval
                        </div>
                    </div>
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Referral Code</div>
                        <div class="mt-2 truncate font-heading text-xl tracking-widest text-[#00A6A6]">
                            <?= e($profile['referral_code'] ?? 'PMZ-XXXXXX') ?>
                        </div>
                        <div class="mt-1 text-xs text-gray-400">Share it to earn referrals</div>
                    </div>
                    <button type="button"
                        onclick="navigator.clipboard.writeText('<?= e($profile['referral_code'] ?? '') ?>').then(()=>{this.innerHTML='<svg class=\'w-5 h-5 text-green-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/></svg>';setTimeout(()=>this.innerHTML='<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'/></svg>',1500)})"
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-white/5 text-gray-400 transition-all duration-300 hover:bg-[#00A6A6] hover:text-white"
                        title="Copy Referral Code">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========== 3. Earnings chart (partial) ========== -->
    <?php require BASE_PATH . '/src/Views/partner/partials/earnings_chart.php'; ?>

    <!-- ========== 4. Quick Actions grid ========== -->
    <div>
        <h3 class="font-heading text-2xl tracking-wide text-white mb-4">QUICK ACTIONS</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">

            <a href="<?= url_e('/partner-dashboard/clients') ?>"
                class="tap-effect group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <span class="relative mt-4 block text-xs font-bold uppercase tracking-wider text-white">My Clients</span>
            </a>

            <a href="<?= url_e('/partner-dashboard/sales') ?>"
                class="tap-effect group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <span class="relative mt-4 block text-xs font-bold uppercase tracking-wider text-white">Sales</span>
            </a>

            <a href="<?= url_e('/partner-dashboard/wallet') ?>"
                class="tap-effect group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <span class="relative mt-4 block text-xs font-bold uppercase tracking-wider text-white">Wallet</span>
            </a>

            <a href="<?= url_e('/partner-dashboard/profile') ?>"
                class="tap-effect group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
                <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <span class="relative mt-4 block text-xs font-bold uppercase tracking-wider text-white">My Profile</span>
            </a>
        </div>
    </div>

    <!-- ========== 5. Recent clients preview ========== -->
    <?php if (!empty($clients)): ?>
        <div class="rounded-2xl border border-white/5 bg-[#111] shadow-sm">
            <div class="flex items-center justify-between border-b border-white/5 p-6">
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">RECENT CLIENTS</h3>
                    <p class="mt-1 text-xs text-gray-400">
                        Showing latest <?= min(5, count($clients)) ?> of <?= count($clients) ?>
                    </p>
                </div>
                <a href="<?= url_e('/partner-dashboard/clients') ?>"
                    class="text-xs font-bold text-[#00A6A6] transition-colors hover:text-white">
                    View all &rarr;
                </a>
            </div>

            <div class="divide-y divide-white/5">
                <?php
                $recentClients = array_slice($clients, 0, 5);
                foreach ($recentClients as $idx => $c):
                    $statusStyle = [
                        'linked'   => 'bg-green-500/10 text-green-500 border border-green-500/20',
                        'pending'  => 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
                        'inactive' => 'bg-gray-500/10 text-gray-400 border border-gray-500/20',
                    ][$c['membership_status'] ?? 'pending'] ?? 'bg-gray-500/10 text-gray-400 border border-gray-500/20';
                ?>
                    <div class="flex items-center gap-4 p-4 transition-colors hover:bg-white/[0.02]"
                        style="animation: slide-in 0.4s ease-out <?= $idx * 50 ?>ms backwards;">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white shadow-md">
                            <?= e(initials($c['client_name'] ?? '?')) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-white"><?= e($c['client_name'] ?? 'Unknown') ?></div>
                            <div class="truncate text-xs text-gray-400"><?= e($c['client_email'] ?? '') ?></div>
                        </div>
                        <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase <?= $statusStyle ?>">
                            <?= e($c['membership_status'] ?? 'pending') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
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
</style>