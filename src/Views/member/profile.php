<?php

/**
 * Member — Profile (2026 Dark UI)
 */

$user = current_user();
$profile = $profile ?? [];
$enrollments = $enrollments ?? [];

// ---------- Find current active + pending ----------
$active = null;
foreach ($enrollments as $e) {
    if (($e['status'] ?? '') === 'active') {
        if (!$active || strtotime($e['end_date'] ?? '1970-01-01') > strtotime($active['end_date'] ?? '1970-01-01')) {
            $active = $e;
        }
    }
}
$pending = null;
foreach ($enrollments as $e) {
    if (($e['status'] ?? '') === 'pending') {
        $pending = $e;
        break;
    }
}

$status = $active ? 'active' : ($pending ? 'pending' : ($enrollments ? 'expired' : 'none'));

// Modern Status Badges (No Emojis)
$statusConfig = [
    'active'  => ['label' => 'ACTIVE MEMBER',   'bg' => 'bg-green-500/10',  'text' => 'text-green-500',  'border' => 'border-green-500/20', 'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
    'pending' => ['label' => 'PAYMENT PENDING', 'bg' => 'bg-amber-500/10',  'text' => 'text-amber-500',  'border' => 'border-amber-500/20', 'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
    'expired' => ['label' => 'EXPIRED',         'bg' => 'bg-red-500/10',    'text' => 'text-red-500',    'border' => 'border-red-500/20',   'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
    'none'    => ['label' => 'NO MEMBERSHIP',   'bg' => 'bg-gray-500/10',   'text' => 'text-gray-400',   'border' => 'border-gray-500/20',  'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>'],
][$status];

$daysLeft = $active && !empty($active['end_date'])
    ? max(0, (int)ceil((strtotime($active['end_date']) - time()) / 86400))
    : null;

$fullName = $user['full_name'] ?: 'Member';
$initials = strtoupper(substr($fullName, 0, 1));
$memberSince = !empty($user['created_date']) ? date('M Y', strtotime($user['created_date'])) : date('M Y');

// Sort enrollments newest first
usort($enrollments, fn($a, $b) => strcmp($b['created_date'] ?? '', $a['created_date'] ?? ''));
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== Page Header ========== -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-heading text-4xl tracking-wide text-white sm:text-5xl">MY PROFILE</h1>
            <p class="mt-1 text-sm text-gray-400">Your membership and personal details</p>
        </div>

        <!-- Status Badge -->
        <span class="inline-flex items-center gap-2 self-start rounded-full border <?= $statusConfig['border'] ?> <?= $statusConfig['bg'] ?> px-4 py-2 text-xs font-bold uppercase tracking-wider <?= $statusConfig['text'] ?>">
            <?= $statusConfig['icon'] ?>
            <?= e($statusConfig['label']) ?>
        </span>
    </div>

    <!-- ========== 1. Premium Membership Card ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 border border-white/10 p-8 sm:p-10 shadow-[0_20px_50px_-15px_rgba(0,166,166,0.15)]">
        <!-- Decorative Glow & Texture -->
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 20px 20px;"></div>

        <div class="relative">
            <!-- Top Row: Brand & Tier -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="PowerMagz" class="h-12 w-12 rounded-full ring-2 ring-white/20 shadow-lg">
                    <div class="leading-none">
                        <div class="font-heading text-2xl tracking-widest text-white">POWERMAGZ</div>
                        <div class="mt-1 text-[9px] font-bold tracking-[0.4em] text-[#00A6A6]">GYM MEMBER</div>
                    </div>
                </div>

                <!-- Tier Badge -->
                <div class="rounded-xl border border-white/20 bg-white/5 px-4 py-2 backdrop-blur-md text-right">
                    <div class="text-[9px] font-bold uppercase tracking-[0.3em] text-gray-400">Tier</div>
                    <div class="font-heading text-lg text-white">
                        <?= $active ? e(strtoupper($active['tier'])) : ($pending ? e(strtoupper($pending['tier'])) : '—') ?>
                    </div>
                </div>
            </div>

            <!-- Middle Row: Member Name -->
            <div class="mt-12">
                <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Member Name</div>
                <div class="mt-2 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    <?= e(strtoupper($fullName)) ?>
                </div>
                <div class="mt-1 text-sm text-gray-400"><?= e($user['email']) ?></div>
            </div>

            <!-- Bottom Row: Numbers -->
            <div class="mt-12 grid grid-cols-2 gap-6 sm:grid-cols-4 border-t border-white/10 pt-6">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Days Left</div>
                    <div class="mt-1 font-heading text-2xl <?= ($daysLeft !== null && $daysLeft <= 3) ? 'text-red-500' : 'text-[#00A6A6]' ?>">
                        <?= $daysLeft !== null ? $daysLeft . 'D' : '—' ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Program</div>
                    <div class="mt-1 truncate font-heading text-xl text-white">
                        <?= $active ? e(substr($active['program_name'] ?? '—', 0, 14)) : '—' ?>
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Member Since</div>
                    <div class="mt-1 font-heading text-xl text-white"><?= e($memberSince) ?></div>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Member ID</div>
                    <div class="mt-1 font-mono text-sm text-gray-400"><?= e(substr($user['id'] ?? '', 0, 8)) ?>…</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 2. Renewal Warning ========== -->
    <?php if ($active && $daysLeft !== null && $daysLeft <= 7): ?>
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-500 text-white shadow-[0_0_15px_rgba(245,158,11,0.4)]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-bold text-white">
                        <?= $daysLeft === 0 ? 'Your membership expires today!' : "Only {$daysLeft} day" . ($daysLeft > 1 ? 's' : '') . ' left on your membership' ?>
                    </div>
                    <div class="mt-0.5 text-xs text-gray-400">Renew now to keep your streak and access running.</div>
                </div>
            </div>
            <a href="<?= url_e('/programs') ?>" class="inline-flex flex-shrink-0 items-center gap-2 rounded-full bg-amber-500 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:bg-amber-600 hover:shadow-xl">
                Renew Now
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    <?php endif; ?>

    <!-- ========== 3. Quick Info Tiles ========== -->
    <div class="grid gap-4 sm:grid-cols-3">
        <!-- Total Enrollments -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Enrollments</div>
                    <div class="mt-1 font-heading text-3xl text-white"><?= count($enrollments) ?></div>
                </div>
            </div>
        </div>

        <!-- Sessions Left -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Sessions Left</div>
                    <div class="mt-1 font-heading text-3xl text-white"><?= (int)($profile['sessions_left'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Email Verified -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 <?= !empty($user['email_verified']) ? 'text-green-500' : 'text-gray-500' ?> transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Email Verified</div>
                    <div class="mt-1 font-heading text-xl <?= !empty($user['email_verified']) ? 'text-green-500' : 'text-gray-500' ?>">
                        <?= !empty($user['email_verified']) ? 'Yes' : 'No' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== 4. Contact Form (Partial) ========== -->
    <?php require BASE_PATH . '/src/Views/member/partials/contact_form.php'; ?>

    <!-- ========== 5. Enrollment History ========== -->
    <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111] shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/5 p-6 sm:p-8">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-heading text-2xl tracking-wide text-white">ENROLLMENT HISTORY</h3>
                    <p class="mt-0.5 text-xs text-gray-400"><?= count($enrollments) ?> enrollment<?= count($enrollments) !== 1 ? 's' : '' ?> total</p>
                </div>
            </div>

            <?php if (!empty($enrollments)): ?>
                <a href="<?= url_e('/programs') ?>" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-all hover:bg-white/10 hover:text-white">
                    Enroll in New Program
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($enrollments)): ?>
            <div class="p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">You haven't enrolled in a program yet.</p>
                <a href="<?= url_e('/programs') ?>" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:bg-[#008c8c] hover:shadow-xl">
                    Browse Programs
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.02]">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Program</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Tier</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Period</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th class="p-4 text-right text-[10px] font-bold uppercase tracking-widest text-gray-500">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $idx => $e):
                            $statusBadge = [
                                'active'  => 'bg-green-500/10 text-green-500 border border-green-500/20',
                                'pending' => 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
                                'expired' => 'bg-red-500/10 text-red-500 border border-red-500/20',
                            ][$e['status'] ?? 'pending'] ?? 'bg-gray-500/10 text-gray-400 border border-gray-500/20';

                            $bal = (float)($e['balance'] ?? 0);
                        ?>
                            <tr class="group border-b border-white/5 transition-colors hover:bg-white/[0.02]">
                                <td class="p-4">
                                    <div class="text-sm font-bold text-white"><?= e($e['program_name'] ?? 'Program') ?></div>
                                    <div class="mt-0.5 text-xs text-gray-500">Enrolled <?= e(short_date($e['created_date'] ?? null)) ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="rounded-full bg-white/5 px-2.5 py-1 text-[10px] font-bold uppercase text-gray-300">
                                        <?= e($e['tier'] ?? '—') ?>
                                    </span>
                                </td>
                                <td class="p-4 text-sm text-gray-400">
                                    <div><?= e(short_date($e['start_date'] ?? null)) ?></div>
                                    <div class="text-xs text-gray-500">&rarr; <?= e(short_date($e['end_date'] ?? null)) ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-bold uppercase <?= $statusBadge ?>">
                                        <?= e($e['status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <span class="font-heading text-lg <?= $bal > 0 ? 'text-red-500' : 'text-green-500' ?>">
                                        <?= peso($bal) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========== 6. Account Info ========== -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm">
        <div class="flex items-center gap-3 mb-6">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h3 class="font-heading text-xl tracking-wide text-white">ACCOUNT SECURITY</h3>
                <p class="mt-0.5 text-xs text-gray-400">Your account details and security</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Email</div>
                <div class="mt-1 truncate text-sm font-semibold text-white"><?= e($user['email']) ?></div>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Role</div>
                <div class="mt-1 text-sm font-semibold text-white capitalize"><?= e($user['role'] ?? 'member') ?></div>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Account Created</div>
                <div class="mt-1 text-sm font-semibold text-white"><?= e(short_date($user['created_date'] ?? null)) ?></div>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Last Updated</div>
                <div class="mt-1 text-sm font-semibold text-white"><?= e(short_date($user['updated_date'] ?? null)) ?></div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a href="<?= url_e('/forgot-password') ?>" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-all hover:bg-white/10 hover:text-white">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Change Password
            </a>
            <button type="button" onclick="openSignOutModal()" class="inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-2 text-xs font-bold text-red-500 transition-all hover:bg-red-500/20">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sign Out
            </button>
        </div>
    </div>
</div>