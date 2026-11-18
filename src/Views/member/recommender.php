<?php

/**
 * Member — Workout Plan (deterministic recommender).
 */

$plan      = $plan ?? [];
$context   = $context ?? [];
$caps      = $caps ?? [];
$hasActive = $hasActive ?? false;

$unlocked = $hasActive && !empty($caps['ai_recommender']);
$tier     = (string)($context['tier'] ?? '');
$visits   = (int)($context['visits'] ?? 0);
?>

<div class="space-y-6 sm:space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>
        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Member Portal</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">WORKOUT PLAN</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-400">
                Your plan adapts to your tier, goal, and the last 30 days of check-ins.
            </p>

            <div class="mt-5 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-wider">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1.5 text-[#00A6A6]">
                    <?= $unlocked ? 'Plan ready' : 'Locked' ?>
                </span>
                <?php if ($tier !== ''): ?>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-gray-300"><?= e(ucfirst($tier)) ?> tier</span>
                <?php endif; ?>
                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-gray-300"><?= $visits ?> visits / 30d</span>
            </div>
        </div>
    </div>

    <?php if (!$unlocked): ?>
        <div class="rounded-3xl border border-dashed border-white/10 bg-[#111] p-10 text-center sm:p-14">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 text-gray-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="mt-5 font-heading text-2xl tracking-wide text-white">YOUR PLAN NEEDS AN ACTIVE MEMBERSHIP</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-gray-400">
                The workout recommender is included in every plan, including Digital. Enroll to generate your first week.
            </p>
            <a href="<?= url_e('/programs') ?>"
                class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                View plans
            </a>
        </div>
    <?php else: ?>
        <!-- Summary -->
        <div class="rounded-2xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-5 sm:p-6">
            <h2 class="font-heading text-2xl tracking-wide text-white"><?= e($plan['title'] ?? 'Your plan') ?></h2>
            <p class="mt-1 text-sm text-gray-300"><?= e($plan['summary'] ?? '') ?></p>
            <p class="mt-3 text-xs leading-relaxed text-gray-400"><?= e($plan['why'] ?? '') ?></p>
        </div>

        <!-- Days -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach (($plan['days'] ?? []) as $day): ?>
                <div class="group rounded-2xl border border-white/5 bg-[#111] p-5 transition-all duration-300 hover:-translate-y-0.5 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]"><?= e($day['day']) ?></span>
                        <span class="rounded-full bg-white/5 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-gray-500">
                            <?= count($day['exercises']) ?> moves
                        </span>
                    </div>
                    <h3 class="mt-2 font-heading text-xl tracking-wide text-white"><?= e($day['focus']) ?></h3>
                    <ul class="mt-3 space-y-1.5">
                        <?php foreach ($day['exercises'] as $exercise): ?>
                            <li class="flex items-start gap-2 text-sm text-gray-400">
                                <span class="mt-1.5 h-1 w-1 flex-shrink-0 rounded-full bg-[#00A6A6]"></span>
                                <?= e($exercise) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($caps['ai_coach'])): ?>
            <a href="<?= url_e('/ai-coach') ?>"
                class="flex items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] p-5 transition-all hover:border-[#00A6A6]/40 hover:bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-white">Ask the AI Coach</div>
                        <div class="text-xs text-gray-500">Schedule, nutrition, recovery, or this plan.</div>
                    </div>
                </div>
                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</div>
