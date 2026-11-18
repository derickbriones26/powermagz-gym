<?php

/**
 * Member — Group Classes schedule.
 * Shows the next 7 days (14 for Premium priority) with book/cancel actions.
 */

$slots     = $slots ?? [];
$caps      = $caps ?? [];
$tier      = $tier ?? '';
$horizon   = $horizon ?? 7;
$hasActive = $hasActive ?? false;

$canBook = $hasActive && !empty($caps['group_classes']);

$grouped = [];
foreach ($slots as $slot) {
    $grouped[$slot['date']][] = $slot;
}
?>

<div class="space-y-6 sm:space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>
        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Member Portal</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">GROUP CLASSES</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-400">
                Yoga, HIIT, strength, and boxing run every week. Booking opens
                <span class="font-semibold text-white"><?= (int)$horizon ?> days</span> ahead<?= !empty($caps['class_priority_booking']) ? ' — Premium priority access' : '' ?>.
            </p>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <?= $canBook ? 'Booking open' : 'Locked' ?>
                </span>
                <?php if ($tier !== ''): ?>
                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-300">
                        <?= e(ucfirst($tier)) ?> tier
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$canBook): ?>
        <!-- Locked state -->
        <div class="rounded-3xl border border-dashed border-white/10 bg-[#111] p-10 text-center sm:p-14">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 text-gray-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="mt-5 font-heading text-2xl tracking-wide text-white">CLASSES ARE TIER-LOCKED</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-gray-400">
                <?php if (!$hasActive): ?>
                    You need an active membership to book group classes. Choose a plan to get started.
                <?php else: ?>
                    Group classes are included in Standard and Premium. Upgrade to unlock the weekly schedule.
                <?php endif; ?>
            </p>
            <a href="<?= url_e('/programs') ?>"
                class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                <?= $hasActive ? 'Upgrade plan' : 'View plans' ?>
            </a>
        </div>
    <?php elseif (!$grouped): ?>
        <div class="rounded-3xl border border-dashed border-white/10 bg-[#111] p-10 text-center">
            <h2 class="font-heading text-2xl tracking-wide text-white">NO CLASSES THIS WEEK</h2>
            <p class="mt-2 text-sm text-gray-400">The schedule has not been posted yet. Check back soon.</p>
        </div>
    <?php else: ?>
        <!-- Schedule -->
        <div class="space-y-6">
            <?php foreach ($grouped as $date => $daySlots): ?>
                <?php $dayTs = strtotime($date); ?>
                <section class="overflow-hidden rounded-2xl border border-white/5 bg-[#111]">
                    <div class="flex items-center justify-between gap-3 border-b border-white/5 px-5 py-4">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6]">
                                <?= e(date('l', $dayTs)) ?>
                            </div>
                            <div class="mt-0.5 font-heading text-xl tracking-wide text-white">
                                <?= e(date('M j', $dayTs)) ?>
                            </div>
                        </div>
                        <div class="text-[10px] uppercase tracking-widest text-gray-500">
                            <?= count($daySlots) ?> <?= count($daySlots) === 1 ? 'class' : 'classes' ?>
                        </div>
                    </div>

                    <ul class="divide-y divide-white/5">
                        <?php foreach ($daySlots as $slot): ?>
                            <?php
                            $class     = $slot['class'];
                            $seatsLeft = (int)$slot['seats_left'];
                            $full      = $seatsLeft < 1;
                            $booked    = !empty($slot['booking_id']);
                            $start     = date('g:i A', $slot['start_ts']);
                            $end       = date('g:i A', $slot['start_ts'] + ((int)$class['duration_min'] * 60));
                            ?>
                            <li class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="flex w-16 flex-shrink-0 flex-col items-center justify-center rounded-xl border border-white/10 bg-[#0a0a0a] px-2 py-2 text-center">
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-500">Start</span>
                                        <span class="mt-0.5 text-xs font-bold text-white"><?= e($start) ?></span>
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="font-heading text-lg tracking-wide text-white"><?= e($class['name']) ?></h3>
                                        <p class="mt-0.5 text-xs text-gray-400">
                                            <?= e($class['coach_name'] ?: 'Coach') ?> · <?= (int)$class['duration_min'] ?> min
                                            · <?= e($start) ?>–<?= e($end) ?>
                                        </p>
                                        <?php if (!empty($class['description'])): ?>
                                            <p class="mt-1.5 max-w-md text-xs leading-relaxed text-gray-500"><?= e($class['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 sm:flex-col sm:items-end">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider
                                        <?= $full && !$booked ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-white/5 text-gray-300 border border-white/10' ?>">
                                        <?= $full && !$booked ? 'Full' : $seatsLeft . ' seat' . ($seatsLeft === 1 ? '' : 's') . ' left' ?>
                                    </span>

                                    <?php if ($booked): ?>
                                        <form method="post" action="<?= url_e('/classes/cancel') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="booking_id" value="<?= e($slot['booking_id']) ?>">
                                            <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-colors hover:border-red-500/50 hover:text-white">
                                                Cancel booking
                                            </button>
                                        </form>
                                    <?php elseif ($full): ?>
                                        <button type="button" disabled
                                            class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-full bg-white/5 px-4 py-2 text-xs font-bold text-gray-600">
                                            Class full
                                        </button>
                                    <?php else: ?>
                                        <form method="post" action="<?= url_e('/classes/book') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="class_id" value="<?= e($class['id']) ?>">
                                            <input type="hidden" name="booking_date" value="<?= e($slot['date']) ?>">
                                            <button type="submit"
                                                class="group inline-flex items-center gap-1.5 rounded-full bg-[#00A6A6] px-4 py-2 text-xs font-bold text-white shadow-[0_0_12px_rgba(0,166,166,0.35)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                                                Book
                                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
