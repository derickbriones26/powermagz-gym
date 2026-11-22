<?php

/**
 * Member — Coaches linked to this account.
 * Accept links the partner's B2B client row to this account; decline archives it.
 */

$requests = $requests ?? [];
$coaches  = $coaches ?? [];
?>

<div class="space-y-6 sm:space-y-8 animate-[fade-in_0.4s_ease-out]">

    <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>
        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Member Portal</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">MY COACHES</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-400">
                Coaches linked to your account, and requests waiting for your answer.
            </p>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                    <?= count($coaches) ?> linked
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-300">
                    <?= count($requests) ?> pending
                </span>
            </div>
        </div>
    </div>

    <!-- Linked coaches -->
    <section class="space-y-4">
        <h2 class="font-heading text-2xl tracking-wide text-white">YOUR COACHES</h2>

        <?php if (!$coaches): ?>
            <div class="rounded-3xl border border-dashed border-white/10 bg-[#111] p-8 text-center">
                <p class="text-sm text-gray-400">No coach is linked to your account yet. Accept a pending request below.</p>
            </div>
        <?php else: ?>
            <?php foreach ($coaches as $c): ?>
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-white/5 bg-[#111] p-6 sm:p-7">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-lg font-bold text-[#00A6A6]">
                            <?= e(strtoupper(substr((string)$c['partner_name'], 0, 1))) ?>
                        </div>
                        <div>
                            <div class="font-heading text-xl text-white"><?= e($c['partner_name']) ?></div>
                            <div class="mt-1 text-xs text-gray-400">
                                Linked <?= e(date('M j, Y', strtotime((string)($c['linked_at'] ?: $c['created_date'])))) ?>
                            </div>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-green-500/30 bg-green-500/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-green-400">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Linked
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Pending requests -->
    <section class="space-y-4">
        <h2 class="font-heading text-2xl tracking-wide text-white">PENDING REQUESTS</h2>

        <?php if (!$requests): ?>
            <div class="rounded-3xl border border-dashed border-white/10 bg-[#111] p-8 text-center">
                <p class="text-sm text-gray-400">Nothing waiting for you.</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $r): ?>
                <div class="rounded-3xl border border-white/5 bg-[#111] p-6 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-[#00A6A6]/10 text-lg font-bold text-[#00A6A6]">
                                <?= e(strtoupper(substr((string)$r['partner_name'], 0, 1))) ?>
                            </div>
                            <div>
                                <div class="font-heading text-xl text-white"><?= e($r['partner_name']) ?></div>
                                <div class="mt-1 text-xs text-gray-400">
                                    Requested <?= e(date('M j, Y', strtotime((string)$r['created_date']))) ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="post" action="<?= url_e('/coach-requests') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                <button type="submit" name="action" value="decline"
                                    class="h-11 rounded-xl border border-white/10 bg-white/5 px-5 text-xs font-bold uppercase tracking-wider text-gray-300 transition-colors hover:bg-white/10 hover:text-white">
                                    Decline
                                </button>
                            </form>
                            <form method="post" action="<?= url_e('/coach-requests') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                <button type="submit" name="action" value="accept"
                                    class="h-11 rounded-xl bg-[#00A6A6] px-5 text-xs font-bold uppercase tracking-wider text-black transition-colors hover:bg-[#22C9C9]">
                                    Accept
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
