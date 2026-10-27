<?php

/**
 * Admin — Members Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['users']           — User::listWithStats() rows
 *   $data['userRoleCounts']  — ['all'=>N,'user'=>N,'partner'=>N,'admin'=>N,'staff'=>N]
 *   $data['memberFilters']   — active filter state
 */

use App\Models\User;
use App\Models\Enrollment;

require_once BASE_PATH . '/src/Views/admin/partials/_form_helpers.php';

$users       = $data['users']          ?? [];
$roleCounts  = $data['userRoleCounts'] ?? ['all' => 0, 'user' => 0, 'partner' => 0, 'admin' => 0, 'staff' => 0];
$filters     = $data['memberFilters']  ?? [
    'role' => 'all',
    'status' => 'all',
    'tier' => 'all',
    'joined_from' => '',
    'joined_to' => '',
    'search' => '',
];

/* Tier meta */
$tierMeta = [
    'digital'  => ['icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'label' => 'Digital',  'color' => '#00A6A6'],
    'standard' => ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'label' => 'Standard', 'color' => '#00A6A6'],
    'premium'  => ['icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'label' => 'Premium',  'color' => '#D50000'],
    'b2b'      => ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'label' => 'B2B',      'color' => '#7c3aed'],
];

/* Role badge config */
$roleBadgeCfg = [
    'admin'   => ['bg' => 'bg-red-500/10',    'text' => 'text-red-500',    'border' => 'border-red-500/20',    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
    'staff'   => ['bg' => 'bg-purple-500/10', 'text' => 'text-purple-400', 'border' => 'border-purple-500/20', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    'partner' => ['bg' => 'bg-[#00A6A6]/10',  'text' => 'text-[#00A6A6]',  'border' => 'border-[#00A6A6]/20',  'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
    'user'    => ['bg' => 'bg-gray-500/10',   'text' => 'text-gray-400',   'border' => 'border-gray-500/20',   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];

/* Urgency config */
$urgencyCfg = [
    'safe'      => ['key' => 'active',    'pulse' => false],
    'soon'      => ['key' => 'soon',      'pulse' => false],
    'urgent'    => ['key' => 'urgent',    'pulse' => true],
    'expired'   => ['key' => 'expired',   'pulse' => false],
    'pending'   => ['key' => 'pending',   'pulse' => true],
    'paused'    => ['key' => 'paused',    'pulse' => false],
    'cancelled' => ['key' => 'cancelled', 'pulse' => false],
];

/* Query-string builder preserving current filters */
$qs = function (array $overrides = []) use ($filters): string {
    $merged = array_merge([
        'tab'         => 'members',
        'role'        => $filters['role'],
        'status'      => $filters['status'],
        'tier'        => $filters['tier'],
        'joined_from' => $filters['joined_from'],
        'joined_to'   => $filters['joined_to'],
        'q'           => $filters['search'],
    ], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== 'all');
    return $merged ? '?' . http_build_query($merged) : '?tab=members';
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
                MEMBERS
            </h1>
            <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                Every account on the platform — filter by role, tier, enrollment status, or join date.
            </p>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Total -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total</div>
                    <div class="mt-2 font-heading text-4xl text-white"><?= (int)$roleCounts['all'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">All accounts</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-gray-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Members -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Members</div>
                    <div class="mt-2 font-heading text-4xl text-[#00A6A6]"><?= (int)$roleCounts['user'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">Regular users</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6] group-hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Partners -->
        <div class="group relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-purple-400">Partners</div>
                    <div class="mt-2 font-heading text-4xl text-purple-400"><?= (int)$roleCounts['partner'] ?></div>
                    <div class="mt-1 text-xs text-gray-400">B2B accounts</div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Admins + Staff -->
        <div class="group relative overflow-hidden rounded-2xl border border-red-500/20 bg-red-500/5 p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-red-500/40">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-red-500/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-red-500">Admins + Staff</div>
                    <div class="mt-2 font-heading text-4xl text-red-500">
                        <?= (int)$roleCounts['admin'] + (int)$roleCounts['staff'] ?>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        <?= (int)$roleCounts['admin'] ?> admin · <?= (int)$roleCounts['staff'] ?> staff
                    </div>
                </div>
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500 transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         FILTER BAR
         ============================================================ -->
    <form method="get" action="<?= url_e('/admin-dashboard') ?>" id="memberFilterForm"
        class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm space-y-4">

        <input type="hidden" name="tab" value="members">

        <?= a11y_filter_open('Filter members by role, tier, enrollment status, and join date') ?>

        <!-- Role pills -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 w-full sm:w-auto">Role:</span>

            <?php
            $roleFilters = [
                ['all',     'All',      (int)$roleCounts['all'],     'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['user',    'Members',  (int)$roleCounts['user'],    'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['partner', 'Partners', (int)$roleCounts['partner'], 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ['staff',   'Staff',    (int)$roleCounts['staff'],   'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['admin',   'Admins',   (int)$roleCounts['admin'],   'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ];
            foreach ($roleFilters as [$key, $label, $count, $icon]):
                $isActive = ($filters['role'] === $key || ($key === 'all' && $filters['role'] === 'all'));
            ?>
                <a href="<?= url_e('/admin-dashboard' . $qs(['role' => $key === 'all' ? '' : $key])) ?>"
                    aria-current="<?= $isActive ? 'true' : 'false' ?>"
                    class="role-filter filter-pill flex items-center gap-2 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-4 py-2 text-sm font-semibold transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                    <span class="rounded-full <?= $isActive ? 'bg-black/25 text-white' : 'bg-black/30 text-gray-400' ?> px-2 py-0.5 text-[10px] font-bold"><?= $count ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Tier + Status pills -->
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
                $isActive = ($filters['tier'] === $key || ($key === 'all' && $filters['tier'] === 'all'));
            ?>
                <a href="<?= url_e('/admin-dashboard' . $qs(['tier' => $key === 'all' ? '' : $key])) ?>"
                    aria-current="<?= $isActive ? 'true' : 'false' ?>"
                    class="tier-filter filter-pill flex items-center gap-1.5 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                </a>
            <?php endforeach; ?>

            <span class="ml-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status:</span>

            <?php
            $statusFilters = [
                ['all',       'All',       'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['active',    'Active',    'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['pending',   'Pending',   'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['paused',    'Paused',    'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['expired',   'Expired',   'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['cancelled', 'Cancelled', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
            ];
            foreach ($statusFilters as [$key, $label, $icon]):
                $isActive = ($filters['status'] === $key || ($key === 'all' && $filters['status'] === 'all'));
            ?>
                <a href="<?= url_e('/admin-dashboard' . $qs(['status' => $key === 'all' ? '' : $key])) ?>"
                    aria-current="<?= $isActive ? 'true' : 'false' ?>"
                    class="status-filter filter-pill flex items-center gap-1.5 rounded-full border <?= $isActive ? 'border-[#00A6A6] bg-[#00A6A6] text-white shadow-[0_0_15px_rgba(0,166,166,0.4)]' : 'border-white/10 bg-white/5 text-gray-300 hover:border-[#00A6A6]/50 hover:text-white' ?> px-3 py-1.5 text-xs font-semibold transition-all duration-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>" />
                    </svg>
                    <span><?= $label ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Date + search + export -->
        <div class="grid gap-3 pt-3 border-t border-white/5 lg:grid-cols-[1fr_1fr_2fr_auto_auto]">

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Joined from</span>
                <input type="date" name="joined_from" value="<?= e($filters['joined_from']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Joined to</span>
                <input type="date" name="joined_to" value="<?= e($filters['joined_to']) ?>"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Search</span>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="q" value="<?= e($filters['search']) ?>"
                        placeholder="Name or email…"
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>
            </label>

            <div class="flex items-end">
                <button type="submit"
                    class="tap-effect inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#00A6A6] px-6 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
            </div>

            <div class="flex items-end">
                <a href="<?= url_e('/admin-dashboard/members/export' . $qs()) ?>"
                    class="tap-effect inline-flex h-11 items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-5 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white hover:shadow-md"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </a>
            </div>
        </div>

        <?= a11y_filter_close() ?>

        <?php if (
            $filters['search'] || $filters['joined_from'] || $filters['joined_to']
            || $filters['role'] !== 'all' || $filters['tier'] !== 'all' || $filters['status'] !== 'all'
        ): ?>
            <div class="flex items-center justify-between pt-3 border-t border-white/5 text-xs">
                <span class="text-gray-400" <?= a11y_live_region('polite') ?>>
                    <strong class="text-white"><?= count($users) ?></strong> result<?= count($users) !== 1 ? 's' : '' ?> match the active filters
                </span>
                <a href="<?= url_e('/admin-dashboard?tab=members') ?>"
                    class="font-bold text-[#00A6A6] transition-colors hover:text-white">
                    Clear filters
                </a>
            </div>
        <?php endif; ?>
    </form>

    <!-- ============================================================
         TABLE
         ============================================================ -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="flex items-center justify-between border-b border-white/5 p-6">
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">ALL MEMBERS</h3>
                <p class="mt-1 text-xs text-gray-400">
                    <span <?= a11y_live_region('polite') ?>><?= count($users) ?> shown</span> · click any row to edit
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <caption class="sr-only">Member accounts with role, enrollment status, attendance, and actions</caption>
                <thead>
                    <tr class="border-b border-white/5 bg-white/[0.02]">
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Member</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">Email</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Role</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Enrollment</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Balance</th>
                        <th scope="col" class="p-4 text-center text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden lg:table-cell">30d Visits</th>
                        <th scope="col" class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500 hidden xl:table-cell">Joined</th>
                        <th scope="col" class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="memberRows">
                    <?php foreach ($users as $idx => $u):
                        $role     = $u['role'] ?? 'user';
                        $rc       = $roleBadgeCfg[$role] ?? $roleBadgeCfg['user'];

                        $enrollStatus = $u['enrollment_status'] ?? '';
                        $enrollTier   = $u['enrollment_tier'] ?? '';
                        $tierMeta_    = $tierMeta[$enrollTier] ?? null;

                        $urgency      = $u['enrollment_urgency'] ?? 'none';
                        $urgencyMap   = $urgencyCfg[$urgency] ?? null;

                        $daysLeft     = $u['enrollment_days_left'];
                        $balance      = (float)($u['enrollment_balance'] ?? 0);
                        $checkins30   = (int)($u['attendance_30d'] ?? 0);

                        $titlePrefix = (!empty($u['title']) && $u['title'] !== 'none')
                            ? User::titleLabel($u['title']) . ' '
                            : '';
                        $displayName = $titlePrefix . ($u['full_name'] ?: 'Unnamed');
                    ?>
                        <tr class="member-row group border-b border-white/5 transition-colors duration-200 hover:bg-white/[0.02]"
                            data-role="<?= e($role) ?>"
                            data-search="<?= e(strtolower(($u['full_name'] ?? '') . ' ' . $u['email'])) ?>"
                            style="animation: row-enter 0.35s ease-out <?= min($idx * 20, 500) ?>ms backwards;">

                            <!-- Member -->
                            <td class="p-4">
                                <button type="button"
                                    onclick='openMemberModal(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    class="flex items-center gap-3 text-left">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-lg text-white shadow-md transition-transform duration-300 group-hover:scale-110"
                                        aria-hidden="true">
                                        <?= e(initials($u['full_name'] ?: $u['email'])) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-white transition-colors group-hover:text-[#00A6A6]">
                                            <?= e($displayName) ?>
                                        </div>
                                        <div class="truncate text-xs text-gray-500">
                                            ID: <?= e(substr($u['id'], 0, 8)) ?>…
                                        </div>
                                    </div>
                                </button>
                            </td>

                            <!-- Email -->
                            <td class="p-4 hidden lg:table-cell">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate max-w-[200px] text-sm text-gray-300"><?= e($u['email']) ?></span>
                                    <?php if (!empty($u['email_verified'])): ?>
                                        <span class="flex-shrink-0 text-green-500" role="img" aria-label="Email verified">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="flex-shrink-0 text-amber-500" role="img" aria-label="Email not verified">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="p-4">
                                <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-bold uppercase <?= $rc['bg'] ?> <?= $rc['text'] ?> border <?= $rc['border'] ?>">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $rc['icon'] ?>" />
                                    </svg>
                                    <span><?= e(User::roleLabel($role)) ?></span>
                                </span>
                            </td>

                            <!-- Enrollment -->
                            <td class="p-4 hidden xl:table-cell">
                                <?php if ($enrollTier && $tierMeta_): ?>
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg" style="background-color: <?= $tierMeta_['color'] ?>1A; color: <?= $tierMeta_['color'] ?>;">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tierMeta_['icon'] ?>" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate text-xs font-bold uppercase" style="color: <?= $tierMeta_['color'] ?>">
                                                <?= e($tierMeta_['label']) ?>
                                            </div>
                                            <div class="truncate text-[11px] text-gray-500">
                                                <?= e($u['enrollment_program'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600" aria-label="No enrollment">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status / Days Left -->
                            <td class="p-4">
                                <?php if ($enrollStatus && $enrollStatus !== 'none' && $urgencyMap): ?>
                                    <?php
                                    $badgeLabel = $daysLeft !== null
                                        ? max(0, (int)$daysLeft) . 'd left'
                                        : Enrollment::statusLabel($enrollStatus);

                                    $badgeStyles = match ($urgencyMap['key']) {
                                        'active'    => [
                                            'bg' => 'bg-green-500/10',
                                            'text' => 'text-green-500',
                                            'border' => 'border-green-500/20',
                                            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                                        ],
                                        'soon'      => [
                                            'bg' => 'bg-amber-500/10',
                                            'text' => 'text-amber-500',
                                            'border' => 'border-amber-500/20',
                                            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
                                        ],
                                        'urgent'    => [
                                            'bg' => 'bg-red-500/10',
                                            'text' => 'text-red-500',
                                            'border' => 'border-red-500/20',
                                            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
                                        ],
                                        'expired'   => [
                                            'bg' => 'bg-red-500/10',
                                            'text' => 'text-red-500',
                                            'border' => 'border-red-500/20',
                                            'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
                                        ],
                                        'pending'   => [
                                            'bg' => 'bg-amber-500/10',
                                            'text' => 'text-amber-500',
                                            'border' => 'border-amber-500/20',
                                            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
                                        ],
                                        'paused'    => [
                                            'bg' => 'bg-blue-500/10',
                                            'text' => 'text-blue-400',
                                            'border' => 'border-blue-500/20',
                                            'icon' => 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z'
                                        ],
                                        'cancelled' => [
                                            'bg' => 'bg-gray-500/10',
                                            'text' => 'text-gray-400',
                                            'border' => 'border-gray-500/20',
                                            'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'
                                        ],
                                        default     => [
                                            'bg' => 'bg-gray-500/10',
                                            'text' => 'text-gray-400',
                                            'border' => 'border-gray-500/20',
                                            'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                                        ],
                                    };
                                    ?>
                                    <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full border <?= $badgeStyles['bg'] ?> <?= $badgeStyles['text'] ?> <?= $badgeStyles['border'] ?> px-3 py-1 text-[10px] font-bold uppercase">
                                        <?php if ($urgencyMap['pulse']): ?>
                                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                        <?php else: ?>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $badgeStyles['icon'] ?>" />
                                            </svg>
                                        <?php endif; ?>
                                        <?= e($badgeLabel) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600" aria-label="No enrollment">—</span>
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

                            <!-- 30-day check-ins -->
                            <td class="p-4 text-center hidden lg:table-cell">
                                <?php if ($checkins30 > 0): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-[#00A6A6]/10 border border-[#00A6A6]/20 px-2.5 py-1 text-xs font-bold text-[#00A6A6]"
                                        aria-label="<?= $checkins30 ?> check-ins in the last 30 days">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span><?= $checkins30 ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-600">0</span>
                                <?php endif; ?>
                            </td>

                            <!-- Joined -->
                            <td class="p-4 text-sm text-gray-400 whitespace-nowrap hidden xl:table-cell">
                                <?= e(short_date($u['created_date'] ?? null)) ?>
                            </td>

                            <!-- Actions -->
                            <td class="p-4 text-right">
                                <div class="inline-flex items-center gap-1 opacity-75 transition-opacity group-hover:opacity-100">
                                    <button type="button"
                                        onclick='openMemberModal(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        aria-label="Edit member <?= e($displayName) ?>"
                                        title="View / Edit"
                                        class="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-[#00A6A6] hover:shadow-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <?php if ($role !== 'admin' || (int)$roleCounts['admin'] > 1): ?>
                                        <button type="button"
                                            onclick='openDeleteMemberModal(<?= json_encode(["id" => $u["id"], "name" => $u["full_name"] ?: $u["email"], "role" => $role], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            aria-label="Delete member <?= e($displayName) ?>"
                                            title="Delete"
                                            class="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400 transition-all hover:-translate-y-0.5 hover:border-red-500 hover:bg-red-500/10 hover:text-red-500 hover:shadow-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" class="p-16 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm text-gray-400">No members match your filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================
         MEMBER DETAIL / EDIT MODAL
         ============================================================ -->
    <div id="memberModal" role="dialog" aria-modal="true" aria-labelledby="memberModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <!-- Modal header -->
            <div class="sticky top-0 z-10 flex items-center gap-4 border-b border-white/5 bg-[#111] p-6">
                <div id="mm-avatar" class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-2xl text-white shadow-md"
                    aria-hidden="true">
                    ?
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="memberModalTitle" class="truncate font-heading text-2xl tracking-wide text-white">
                        <span id="mm-title">EDIT MEMBER</span>
                    </h2>
                    <p id="mm-subtitle" class="truncate text-sm text-gray-400">email@example.com</p>
                </div>
                <button type="button" onclick="closeMemberModal()"
                    aria-label="Close edit member dialog"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal body -->
            <form id="memberForm" method="post" action="<?= url_e('/admin-dashboard/members/update') ?>"
                data-validate class="p-6 sm:p-8 space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="mm-id">

                <!-- ==== Section 1: Identity ==== -->
                <fieldset class="rounded-2xl border border-white/5 bg-white/[0.02] p-5">
                    <legend class="px-2 text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">
                        Identity
                    </legend>

                    <div class="grid gap-4 sm:grid-cols-[140px_1fr]">
                        <label class="block">
                            <?= form_label('Title', false, '', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z') ?>
                            <select name="title" id="mm-input-title"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                                <?php foreach (form_title_options() as $val => $label): ?>
                                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="block">
                            <?= form_label('Full Name', true, 'Single field — no need to split first/last.', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z') ?>
                            <input name="full_name" id="mm-input-name" required maxlength="190"
                                data-validate="required|min:2|max:190"
                                data-error-label="Full Name"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <?= form_label('Email', true, '', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z') ?>
                            <input type="email" name="email" id="mm-input-email" required maxlength="190"
                                data-validate="required|email|max:190"
                                data-error-label="Email"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </label>

                        <label class="block">
                            <?= form_label('Phone', false, 'Any format — international supported.', 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z') ?>
                            <input type="tel" name="phone" id="mm-input-phone" maxlength="40"
                                data-validate="phone|max:40"
                                data-error-label="Phone"
                                placeholder="+63 917 123 4567"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <?= form_label('Gender', false, '', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z') ?>
                            <select name="gender" id="mm-input-gender"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                                <?php foreach (form_gender_options() as $val => $label): ?>
                                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="block">
                            <?= form_label('Role', true, '', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z') ?>
                            <select name="role" id="mm-input-role" required
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                                <option value="user">Member</option>
                                <option value="partner">Partner</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </label>
                    </div>

                    <!-- Self-describe (conditional) -->
                    <label class="mt-4 block hidden" id="mm-self-describe-wrap">
                        <?= form_label('Self-described gender', true, 'How would you describe your gender?', 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z') ?>
                        <input name="gender_self_describe" id="mm-input-self-describe" maxlength="100"
                            data-validate="min:1|max:100"
                            data-error-label="Self-described gender"
                            placeholder="Your gender identity"
                            class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    </label>
                </fieldset>

                <!-- ==== Section 2: Emergency Contact ==== -->
                <fieldset class="rounded-2xl border border-white/5 bg-white/[0.02] p-5">
                    <legend class="px-2 text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">
                        Emergency Contact
                    </legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <?= form_label('Contact Name & Number', false, '', 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0a2 2 0 104 0') ?>
                            <input name="emergency_contact" id="mm-input-emergency" maxlength="190"
                                data-validate="max:190"
                                data-error-label="Emergency contact"
                                placeholder="e.g. Juan Dela Cruz — 09171234567"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </label>

                        <label class="block">
                            <?= form_label('Relationship', false, 'e.g. Mother, Partner, Friend', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z') ?>
                            <input name="emergency_relationship" id="mm-input-relationship" maxlength="80"
                                data-validate="max:80"
                                data-error-label="Relationship"
                                placeholder="Mother / Partner / Friend / Sibling"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </label>
                    </div>
                </fieldset>

                <!-- ==== Section 3: Account & Security ==== -->
                <fieldset class="rounded-2xl border border-white/5 bg-white/[0.02] p-5">
                    <legend class="px-2 text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">
                        Account & Security
                    </legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <?= form_label('Email Verified', false, '', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z') ?>
                            <select name="email_verified" id="mm-input-verified"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                                <option value="1">Verified</option>
                                <option value="0">Not verified</option>
                            </select>
                        </label>

                        <label class="block">
                            <?= form_label('New Password', false, 'Leave blank to keep current.', 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z') ?>
                            <input type="password" name="new_password" id="mm-input-password" minlength="8" maxlength="100"
                                data-validate="min:8|max:100"
                                data-error-label="New password"
                                placeholder="••••••••"
                                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        </label>
                    </div>
                </fieldset>

                <!-- Meta panel -->
                <div id="mm-meta" class="rounded-2xl border border-white/5 bg-white/[0.02] p-4 text-xs text-gray-300 space-y-2"></div>

                <!-- Actions -->
                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" onclick="closeMemberModal()"
                        class="rounded-full border border-white/10 bg-transparent px-6 py-3 text-sm font-bold text-gray-300 transition-all hover:bg-white/5 hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" id="mm-submit"
                        class="group/btn relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                            hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]
                            disabled:cursor-not-allowed disabled:opacity-60">
                        <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
                        <span class="relative" id="mm-submit-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                        </span>
                        <span class="relative" id="mm-submit-label">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         DELETE MODAL
         ============================================================ -->
    <div id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-sm rounded-3xl border border-red-500/20 bg-[#111] p-6 shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 id="deleteModalTitle" class="mt-4 font-heading text-2xl tracking-wide text-white">DELETE MEMBER?</h2>
                <p class="mt-2 text-sm text-gray-400">
                    You are about to permanently delete <strong id="del-name" class="text-white">this member</strong>.
                    All their enrollments, payments, and sessions will be removed.
                </p>
                <p class="mt-3 rounded-xl border border-red-500/20 bg-red-500/5 p-3 text-xs font-bold text-red-500">
                    This cannot be undone.
                </p>
            </div>
            <form id="deleteForm" method="post" action="<?= url_e('/admin-dashboard/members/delete') ?>" class="mt-6">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="del-id">
                <div class="flex gap-3">
                    <button type="button" onclick="closeDeleteModal()"
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

        .member-row,
        #memberModal>div,
        #deleteModal>div {
            animation: none !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        /* ============================================================
         *  OPEN MEMBER MODAL
         * ============================================================ */
        window.openMemberModal = function(u) {
            if (!u) return;

            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }

            document.getElementById('mm-id').value = u.id || '';
            document.getElementById('mm-avatar').textContent =
                (u.full_name || u.email || '?').charAt(0).toUpperCase();
            document.getElementById('mm-title').textContent = 'EDIT MEMBER';
            document.getElementById('mm-subtitle').textContent = u.email || '';

            document.getElementById('mm-input-name').value = u.full_name || '';
            document.getElementById('mm-input-email').value = u.email || '';
            document.getElementById('mm-input-role').value = u.role || 'user';
            document.getElementById('mm-input-verified').value = u.email_verified ? '1' : '0';
            document.getElementById('mm-input-title').value = u.title || 'none';
            document.getElementById('mm-input-gender').value = u.gender || 'prefer_not_to_say';
            document.getElementById('mm-input-phone').value = u.phone || '';
            document.getElementById('mm-input-password').value = '';

            const emergencyEl = document.getElementById('mm-input-emergency');
            const relationEl = document.getElementById('mm-input-relationship');
            if (emergencyEl) emergencyEl.value = u.emergency_contact || '';
            if (relationEl) relationEl.value = u.emergency_relationship || '';

            const sdWrap = document.getElementById('mm-self-describe-wrap');
            const sdInput = document.getElementById('mm-input-self-describe');
            if (u.gender === 'self_describe') {
                sdWrap.classList.remove('hidden');
                sdInput.value = u.gender_self_describe || '';
            } else {
                sdWrap.classList.add('hidden');
                sdInput.value = '';
            }

            /* Meta panel */
            const meta = document.getElementById('mm-meta');
            const statusLabels = {
                active: 'Active',
                pending: 'Pending',
                paused: 'Paused',
                expired: 'Expired',
                cancelled: 'Cancelled'
            };

            let html = '';
            html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">User ID</span><code class="text-gray-300 text-[11px]">' + (u.id || '—') + '</code></div>';
            html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Joined</span><span>' + (u.created_date || '—') + '</span></div>';
            if (u.last_login_at) {
                html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Last login</span><span>' + u.last_login_at + '</span></div>';
            }
            if (u.enrollment_tier) {
                html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Enrollment</span><span class="font-semibold text-white">' + u.enrollment_tier.toUpperCase() + '</span></div>';
            }
            if (u.enrollment_status) {
                html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Status</span><span class="font-semibold text-white">' + (statusLabels[u.enrollment_status] || u.enrollment_status) + '</span></div>';
            }
            if (u.enrollment_start && u.enrollment_end) {
                html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Period</span><span>' + u.enrollment_start + ' → ' + u.enrollment_end + '</span></div>';
            }
            if (u.enrollment_days_left !== null && u.enrollment_days_left !== undefined) {
                html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Days left</span><span class="font-semibold text-white">' + u.enrollment_days_left + ' day(s)</span></div>';
            }
            if (u.enrollment_balance !== undefined) {
                const bal = parseFloat(u.enrollment_balance || 0);
                html += '<div class="flex justify-between border-b border-white/5 pb-2"><span class="text-gray-500">Balance</span><span class="font-semibold ' + (bal > 0 ? 'text-[#D50000]' : 'text-green-500') + '">₱' + bal.toLocaleString() + '</span></div>';
            }
            if (u.attendance_30d !== undefined) {
                html += '<div class="flex justify-between"><span class="text-gray-500">Check-ins (30d)</span><span class="font-semibold text-white">' + (u.attendance_30d || 0) + '</span></div>';
            }

            meta.innerHTML = html;

            /* Clear stale validation */
            const form = document.getElementById('memberForm');
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

            const m = document.getElementById('memberModal');
            m.classList.replace('hidden', 'flex');
        };

        window.closeMemberModal = function() {
            document.getElementById('memberModal').classList.replace('flex', 'hidden');
        };

        /* ============================================================
         *  Self-describe toggle
         * ============================================================ */
        document.getElementById('mm-input-gender')?.addEventListener('change', function() {
            const wrap = document.getElementById('mm-self-describe-wrap');
            const input = document.getElementById('mm-input-self-describe');
            if (this.value === 'self_describe') {
                wrap.classList.remove('hidden');
                input.focus();
            } else {
                wrap.classList.add('hidden');
                input.value = '';
            }
        });

        /* ============================================================
         *  DELETE MODAL
         * ============================================================ */
        window.openDeleteMemberModal = function(u) {
            if (!u) return;
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
            document.getElementById('del-id').value = u.id;
            document.getElementById('del-name').textContent = u.name || 'this member';
            document.getElementById('deleteModal').classList.replace('hidden', 'flex');
        };

        window.closeDeleteModal = function() {
            document.getElementById('deleteModal').classList.replace('flex', 'hidden');
        };

        /* ============================================================
         *  Prevent double-submit
         * ============================================================ */
        document.getElementById('memberForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('mm-submit');
            const icon = document.getElementById('mm-submit-icon');
            const label = document.getElementById('mm-submit-label');

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

        /* ============================================================
         *  Esc + backdrop close
         * ============================================================ */
        ['memberModal', 'deleteModal'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', (e) => {
                if (e.target.id === id) {
                    if (id === 'memberModal') window.closeMemberModal();
                    if (id === 'deleteModal') window.closeDeleteModal();
                }
            });
        });
    })();
</script>