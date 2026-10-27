<?php

/**
 * Member — Enrollment Modal (2026 Dark UI)
 * Triggered from programs.php via openEnroll(program)
 */
$gcashNumber = GCASH_NUMBER; // "0961 804 1523"
?>

<div id="enrollModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-[#111] shadow-2xl modal-animate">

        <!-- Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#0a0a0a] to-[#00A6A6]/20 px-6 py-6 border-b border-white/5">
            <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[#00A6A6]/20 blur-3xl"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">
                        Enroll Now
                    </div>
                    <h3 id="em-title" class="mt-1 font-heading text-3xl tracking-wide text-white sm:text-4xl">
                        PROGRAM NAME
                    </h3>
                </div>
                <button type="button" onclick="closeEnrollModal()"
                    class="rounded-full bg-white/5 p-2 text-gray-400 transition-colors hover:bg-white/10 hover:text-white"
                    aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 sm:p-8">

            <!-- Program Summary -->
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl border border-white/5 bg-[#0a0a0a] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Amount</div>
                    <div id="em-price" class="mt-1 font-heading text-3xl text-[#00A6A6]">₱0</div>
                </div>
                <div class="rounded-2xl border border-white/5 bg-[#0a0a0a] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Duration</div>
                    <div id="em-duration" class="mt-1 font-heading text-3xl text-white">0d</div>
                </div>
            </div>

            <!-- Tier pill -->
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Tier:</span>
                <span id="em-tier" class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] font-bold uppercase text-white">
                    —
                </span>
            </div>

            <!-- GCash Payment Card -->
            <div class="mt-6 rounded-2xl border border-[#00A6A6]/30 bg-gradient-to-br from-[#00A6A6]/10 to-transparent p-5 text-center">
                <div class="flex items-center justify-center gap-2 text-[#00A6A6]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Send via GCash to</span>
                </div>

                <div class="mt-3 flex items-center justify-center gap-3">
                    <div class="font-heading text-3xl tracking-widest text-white"><?= e($gcashNumber) ?></div>
                    <button type="button"
                        onclick="copyGcash(event)"
                        class="group rounded-full border border-white/10 bg-white/5 p-2 text-white transition-all hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]"
                        title="Copy number">
                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
                <div class="mt-2 text-[10px] font-semibold uppercase tracking-widest text-gray-500">
                    GCash &middot; PowerMagz Gym
                </div>
            </div>

            <!-- Instructions -->
            <ol class="mt-6 space-y-3 text-xs text-gray-400">
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[10px] font-bold text-white">1</span>
                    <span>Open your GCash app and send the exact amount shown above.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[10px] font-bold text-white">2</span>
                    <span>Use your name as the remark / note: <strong id="em-remark" class="text-white">—</strong></span>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[10px] font-bold text-white">3</span>
                    <span>Tap <strong class="text-white">"I've Sent the Payment"</strong> below to submit.</span>
                </li>
            </ol>

            <!-- Info box -->
            <div class="mt-6 flex gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-[11px] leading-relaxed text-amber-200/80">
                    Your enrollment activates once the gym admin confirms your payment. You'll be notified in the app.
                </div>
            </div>

            <!-- Actions -->
            <form method="post" action="<?= url_e('/enroll') ?>" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row">
                <?= csrf_field() ?>
                <input type="hidden" name="program_id" id="em-program-id">

                <button type="button" onclick="closeEnrollModal()"
                    class="rounded-full border border-white/10 bg-transparent px-6 py-3.5 text-sm font-bold text-gray-300 transition-all hover:bg-white/5 hover:text-white sm:flex-1">
                    Cancel
                </button>

                <button type="submit"
                    class="group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-6 py-3.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)] sm:flex-[2]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    I've Sent the Payment
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes modal-pop {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .modal-animate {
        animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
</style>

<script>
    function openEnrollModal(program) {
        if (!program) return;

        document.getElementById('em-title').textContent = (program.name || 'Program').toUpperCase();
        document.getElementById('em-price').textContent = '₱' + Number(program.price || 0).toLocaleString();
        document.getElementById('em-duration').textContent = (program.duration_days || 30) + 'd';
        document.getElementById('em-tier').textContent = (program.tier || 'standard').toUpperCase();
        document.getElementById('em-program-id').value = program.id;

        const userName = '<?= e(addslashes(current_user()['full_name'] ?: current_user()['email'])) ?>';
        document.getElementById('em-remark').textContent = userName;

        const m = document.getElementById('enrollModal');
        m.classList.remove('hidden');
        m.classList.add('flex');

        // Focus trap for accessibility
        setTimeout(() => {
            const submitBtn = m.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.focus();
        }, 120);
    }

    function closeEnrollModal() {
        const m = document.getElementById('enrollModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // Copy GCash number
    function copyGcash(e) {
        const btn = e.currentTarget;
        const num = '<?= str_replace(' ', '', e($gcashNumber)) ?>';

        navigator.clipboard.writeText(num).then(() => {
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            setTimeout(() => btn.innerHTML = originalHTML, 1500);
        }).catch(() => {
            alert('GCash number: <?= e($gcashNumber) ?>');
        });
    }

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const m = document.getElementById('enrollModal');
            if (m && m.classList.contains('flex')) closeEnrollModal();
        }
    });

    // Close on backdrop click
    document.getElementById('enrollModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEnrollModal();
    });

    window.openEnroll = openEnrollModal;
    window.closeEnroll = closeEnrollModal;
</script>