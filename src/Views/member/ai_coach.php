<?php

/**
 * Member — AI Coach (Premium). Deterministic replies, no external API.
 */

$caps      = $caps ?? [];
$hasActive = $hasActive ?? false;
$question  = $question ?? null;
$answer    = $answer ?? null;

$unlocked = $hasActive && !empty($caps['ai_coach']);

$suggestions = [
    'What is my class schedule this week?',
    'How much protein should I eat?',
    'My legs are sore — should I train?',
    'How do I pay for my renewal?',
];
?>

<div class="space-y-6 sm:space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>
        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Member Portal</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">AI COACH</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-400">
                Ask about your schedule, training, nutrition basics, recovery, or payments.
            </p>
            <span class="mt-5 inline-flex items-center gap-2 rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                <?= $unlocked ? 'Available 24/7' : 'Premium only' ?>
            </span>
        </div>
    </div>

    <?php if (!$unlocked): ?>
        <div class="rounded-3xl border border-dashed border-white/10 bg-[#111] p-10 text-center sm:p-14">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 text-gray-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="mt-5 font-heading text-2xl tracking-wide text-white">24/7 COACH IS A PREMIUM FEATURE</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-gray-400">
                <?= $hasActive
                    ? 'Upgrade to Premium for AI answers, personal coaching, and nutrition guidance.'
                    : 'You need an active Premium membership to use the AI coach.' ?>
            </p>
            <a href="<?= url_e('/programs') ?>"
                class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                <?= $hasActive ? 'Upgrade to Premium' : 'View plans' ?>
            </a>
        </div>
    <?php else: ?>
        <!-- Conversation -->
        <div class="rounded-2xl border border-white/5 bg-[#111] p-5 sm:p-6">
            <?php if ($question): ?>
                <div class="flex justify-end">
                    <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-[#00A6A6] px-4 py-3 text-sm font-semibold text-white">
                        <?= e($question) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($answer): ?>
                <div class="mt-4 flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </span>
                    <div class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-bl-sm border border-white/5 bg-[#0a0a0a] px-4 py-3 text-sm leading-relaxed text-gray-300">
                        <?= e($answer) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$question): ?>
                <p class="text-sm text-gray-400">Start with one of these questions:</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach ($suggestions as $suggestion): ?>
                        <form method="post" action="<?= url_e('/ai-coach') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="question" value="<?= e($suggestion) ?>">
                            <button type="submit"
                                class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6]/50 hover:text-white">
                                <?= e($suggestion) ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= url_e('/ai-coach') ?>" class="mt-6 flex flex-col gap-3 sm:flex-row">
                <?= csrf_field() ?>
                <label for="ai-question" class="sr-only">Your question</label>
                <input id="ai-question" name="question" type="text" required minlength="3" maxlength="500"
                    autocomplete="off" placeholder="Ask your coach a question…"
                    class="h-12 flex-1 rounded-full border border-white/10 bg-[#0a0a0a] px-5 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                <button type="submit"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-6 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.35)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                    Ask
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
