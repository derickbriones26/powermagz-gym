<?php

/**
 * Member — Programs & Tiers (2026 Dark UI)
 */

$programs = $programs ?? [];

// ---------- Tier presentation meta (SVG Icons) ----------
$tierMeta = [
    'digital'  => [
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
        'label' => 'DIGITAL TIER',
        'color' => '#00A6A6'
    ],
    'standard' => [
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
        'label' => 'STANDARD TIER',
        'color' => '#00A6A6'
    ],
    'premium'  => [
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>',
        'label' => 'PREMIUM TIER',
        'color' => '#D50000'
    ],
    'b2b'      => [
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        'label' => 'B2B TIER',
        'color' => '#7c3aed'
    ],
];

// ---------- Sort: digital → standard → premium → b2b ----------
$order = ['digital' => 1, 'standard' => 2, 'premium' => 3, 'b2b' => 4];
usort($programs, fn($a, $b) => ($order[$a['tier'] ?? ''] ?? 99) <=> ($order[$b['tier'] ?? ''] ?? 99));
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== Page Header ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Membership</span>
            </div>

            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl lg:text-6xl">
                PROGRAMS &amp; TIERS
            </h1>

            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-gray-400 sm:text-base">
                Choose your membership. Enroll and pay via GCash in seconds.
                Your enrollment activates as soon as the gym confirms your payment.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-6 text-xs font-semibold text-gray-500">
                <span class="inline-flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-[#00A6A6]"></span>
                    GCash supported
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-[#00A6A6]"></span>
                    Instant enrollment request
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="flex h-2 w-2 rounded-full bg-[#00A6A6]"></span>
                    Admin-approved activation
                </span>
            </div>
        </div>
    </div>

    <!-- ========== Programs Grid ========== -->
    <?php if (empty($programs)): ?>
        <div class="rounded-2xl border border-white/5 bg-[#111] p-16 text-center shadow-sm">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h3 class="mt-6 font-heading text-2xl tracking-wide text-white">NO PROGRAMS AVAILABLE</h3>
            <p class="mt-2 text-sm text-gray-400">Check back soon — new membership options are on the way.</p>
        </div>
    <?php else: ?>

        <div class="grid gap-6 lg:grid-cols-3 items-stretch">
            <?php foreach ($programs as $idx => $p):
                $tier = $p['tier'] ?? 'standard';
                $meta = $tierMeta[$tier] ?? $tierMeta['standard'];
                $isPopular = ($tier === 'premium');
                $price = (float)($p['price'] ?? 0);
                $duration = (int)($p['duration_days'] ?? 30);

                // Encode program for the modal
                $programJson = json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT);
            ?>
                <div class="group relative flex flex-col overflow-hidden rounded-3xl border <?= $isPopular ? 'border-[#D50000]/40 ring-2 ring-[#D50000]/20 bg-[#111]' : 'border-white/5 bg-[#111]' ?> transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(0,166,166,0.2)] hover:border-[#00A6A6]/50">

                    <!-- Most Popular Badge -->
                    <?php if ($isPopular): ?>
                        <div class="absolute right-6 top-6 z-10 flex items-center gap-1.5 rounded-full bg-[#D50000] px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white shadow-[0_0_15px_rgba(213,0,0,0.4)] transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            Most Popular
                        </div>
                    <?php endif; ?>

                    <!-- Decorative Blob -->
                    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full opacity-[0.05] transition-transform duration-700 group-hover:scale-150" style="background-color: <?= $meta['color'] ?>;"></div>

                    <div class="relative flex flex-1 flex-col p-8">

                        <!-- Tier Icon -->
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-white/5 bg-white/[0.02] shadow-sm transition-all duration-500 group-hover:rotate-6 group-hover:scale-110 group-hover:bg-[#00A6A6]/10 group-hover:text-[#00A6A6]"
                            style="color: <?= $meta['color'] ?>;">
                            <?= $meta['icon'] ?>
                        </div>

                        <!-- Tier label -->
                        <div class="mt-6 text-[10px] font-bold uppercase tracking-[0.25em] text-gray-500">
                            <?= e($meta['label']) ?>
                        </div>

                        <!-- Name -->
                        <h3 class="mt-1 font-heading text-3xl tracking-wide text-white transition-colors duration-300 <?= $isPopular ? 'group-hover:text-[#D50000]' : 'group-hover:text-[#00A6A6]' ?>">
                            <?= e($p['name'] ?? 'Untitled Program') ?>
                        </h3>

                        <!-- Description -->
                        <p class="mt-3 text-sm leading-relaxed text-gray-400 line-clamp-2">
                            <?= e($p['description'] ?? 'No description available.') ?>
                        </p>

                        <!-- Meta chips -->
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-wider">
                            <span class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-gray-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?= $duration ?> days
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full border border-green-500/20 bg-green-500/10 px-2.5 py-1 text-green-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                GCash
                            </span>
                        </div>

                        <!-- Price (spacer pushes footer down) -->
                        <div class="mt-6 flex-1"></div>

                        <div class="mt-4 border-t border-white/5 pt-6">
                            <div class="flex items-end justify-between">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500">
                                        <?= $tier === 'standard' ? 'Initial Payment' : 'Price' ?>
                                    </div>
                                    <div class="mt-1 flex items-end gap-1">
                                        <span class="font-heading text-5xl leading-none" style="color: <?= $meta['color'] ?>;">
                                            ₱<?= number_format($price) ?>
                                        </span>
                                        <?php if ($tier !== 'standard'): ?>
                                            <span class="mb-1.5 text-xs font-semibold text-gray-500">/ month</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($tier === 'standard'): ?>
                                        <div class="mt-1 text-xs font-semibold text-gray-500">
                                            ₱500 membership fee + ₱500 monthly
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Enroll button -->
                        <button type="button"
                            onclick='openEnroll(<?= $programJson ?>)'
                            class="group/btn relative mt-6 flex w-full items-center justify-center gap-2 overflow-hidden rounded-full px-6 py-3.5 text-sm font-bold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:gap-3 hover:shadow-lg
                           <?= $isPopular ? 'bg-[#D50000] hover:bg-[#b30000] hover:shadow-[#D50000]/30' : 'bg-[#00A6A6] hover:bg-[#008c8c] hover:shadow-[#00A6A6]/30' ?>">
                            <span class="relative">ENROLL NOW</span>
                            <svg class="relative h-4 w-4 transition-transform duration-300 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ========== FAQ / How it works ========== -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm">
        <div class="flex items-center gap-3 mb-8">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">HOW ENROLLMENT WORKS</h3>
                <p class="mt-0.5 text-xs text-gray-400">3 simple steps to get started</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <!-- Step 1 -->
            <div class="group rounded-2xl border border-white/5 bg-[#0a0a0a] p-6 transition-all duration-300 hover:border-[#00A6A6]/40 hover:bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#00A6A6] text-xs font-bold text-white shadow-[0_0_10px_rgba(0,166,166,0.4)] transition-transform duration-300 group-hover:scale-110">1</div>
                    <div class="font-heading text-lg tracking-wide text-white">CHOOSE</div>
                </div>
                <p class="mt-4 text-xs leading-relaxed text-gray-400">
                    Pick the tier that fits your goals and click <strong class="text-white">ENROLL NOW</strong>.
                </p>
            </div>
            <!-- Step 2 -->
            <div class="group rounded-2xl border border-white/5 bg-[#0a0a0a] p-6 transition-all duration-300 hover:border-[#00A6A6]/40 hover:bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#00A6A6] text-xs font-bold text-white shadow-[0_0_10px_rgba(0,166,166,0.4)] transition-transform duration-300 group-hover:scale-110">2</div>
                    <div class="font-heading text-lg tracking-wide text-white">PAY</div>
                </div>
                <p class="mt-4 text-xs leading-relaxed text-gray-400">
                    Send the payment through <strong class="text-white">GCash</strong> using the number in the modal.
                </p>
            </div>
            <!-- Step 3 -->
            <div class="group rounded-2xl border border-white/5 bg-[#0a0a0a] p-6 transition-all duration-300 hover:border-[#00A6A6]/40 hover:bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#00A6A6] text-xs font-bold text-white shadow-[0_0_10px_rgba(0,166,166,0.4)] transition-transform duration-300 group-hover:scale-110">3</div>
                    <div class="font-heading text-lg tracking-wide text-white">ACTIVATE</div>
                </div>
                <p class="mt-4 text-xs leading-relaxed text-gray-400">
                    Once the gym confirms your payment, your account activates instantly.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Load the Enrollment Modal Partial -->
<?php require BASE_PATH . '/src/Views/member/partials/enroll_modal.php'; ?>

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