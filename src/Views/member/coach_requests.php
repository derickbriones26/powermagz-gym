<?php

/**
 * Member — Pending coach (partner) link requests.
 * Accept links the partner's B2B client row to this account; decline archives it.
 */

$requests = $requests ?? [];
?>

<div class="space-y-6 sm:space-y-8 animate-[fade-in_0.4s_ease-out]">

    <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>
        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Member Portal</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">COACH REQUESTS</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-400">
                Coaches who added your email as a client. Accept to link your account and
                let them log your sessions; decline to remove the request.
            </p>
        </div>
    </div>

    <?php if (!$requests): ?>
        <div class="rounded-3xl border border-dashed border-white/10 bg-[#111] p-10 text-center sm:p-14">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 text-gray-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="mt-5 font-heading text-2xl tracking-wide text-white">NO PENDING REQUESTS</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-gray-400">
                You're all caught up. New coach requests will appear here.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
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
        </div>
    <?php endif; ?>
</div>
