<?php

/**
 * Member — Enrollment Modal (2026 Dark UI)
 * Triggered from programs.php via openEnroll(program)
 */
$gcashNumber = GCASH_NUMBER; // "0961 804 1523"

$paymentMethods = $paymentMethods ?? ['gcash', 'cash', 'bank_transfer', 'credit_card'];
if (!is_array($paymentMethods) || !$paymentMethods) {
    $paymentMethods = ['gcash', 'cash', 'bank_transfer', 'credit_card'];
}

$methodIcons = [
    'gcash' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
    'cash'  => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    'credit_card'   => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
    'bank_transfer' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 10V8l7-4 7 4v2M5 10v8m4-8v8m6-8v8m4-8v8M3 21h18"/></svg>',
];
$methodLabels = [
    'gcash'         => 'GCash',
    'cash'          => 'Cash at desk',
    'credit_card'   => 'Credit card',
    'bank_transfer' => 'Bank transfer',
];
$firstMethod = $paymentMethods[0];
?>

<div id="enrollModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
    <div class="w-full max-w-lg overflow-hidden rounded-3xl border border-white/10 bg-[#111] shadow-2xl modal-animate max-h-[92vh] overflow-y-auto">

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

            <!-- Payment method -->
            <div class="mt-6">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Payment method</div>
                <div class="mt-3 grid grid-cols-2 gap-2" role="radiogroup" aria-label="Payment method">
                    <?php foreach ($paymentMethods as $m): ?>
                        <label class="group cursor-pointer">
                            <input type="radio" name="method" value="<?= e($m) ?>"
                                form="enroll-form"
                                class="peer sr-only"
                                <?= $m === $firstMethod ? 'checked' : '' ?>
                                onchange="selectPaymentMethod('<?= e($m) ?>')">
                            <span class="flex items-center gap-2 rounded-xl border border-white/10 bg-[#0a0a0a] px-3 py-3 text-xs font-bold text-gray-300 transition-all peer-checked:border-[#00A6A6] peer-checked:bg-[#00A6A6]/10 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[#00A6A6]/50 hover:border-white/20">
                                <span class="text-[#00A6A6]"><?= $methodIcons[$m] ?? $methodIcons['cash'] ?></span>
                                <?= e($methodLabels[$m] ?? ucfirst($m)) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- GCash number -->
            <div id="em-gcash" class="mt-4 rounded-2xl border border-[#00A6A6]/30 bg-gradient-to-br from-[#00A6A6]/10 to-transparent p-4 text-center">
                <div class="flex items-center justify-center gap-2 text-[#00A6A6]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Send via GCash to</span>
                </div>
                <div class="mt-2 flex items-center justify-center gap-3">
                    <div class="font-heading text-2xl tracking-widest text-white"><?= e($gcashNumber) ?></div>
                    <button type="button" onclick="copyGcash(event)"
                        class="group rounded-full border border-white/10 bg-white/5 p-2 text-white transition-all hover:border-[#00A6A6] hover:bg-[#00A6A6]"
                        aria-label="Copy GCash number">
                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
                <div class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-gray-500">GCash · PowerMagz Gym</div>
            </div>

            <!-- Method note -->
            <p id="em-method-note" class="mt-3 text-[11px] leading-relaxed text-gray-400"></p>

            <!-- Reference -->
            <div id="em-ref-wrap" class="mt-4">
                <label for="em-reference" class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    Reference / transaction no.
                </label>
                <input id="em-reference" name="reference_no" type="text" form="enroll-form"
                    maxlength="80" autocomplete="off"
                    placeholder="e.g. 1234567890"
                    class="mt-2 h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                <p class="mt-1.5 text-[10px] text-gray-500">Required for GCash, bank, and card payments.</p>
            </div>

            <!-- Info box -->
            <div class="mt-5 flex gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3.5">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-[11px] leading-relaxed text-amber-200/80">
                    Your enrollment activates once the gym admin confirms your payment. You'll be notified in the app.
                </div>
            </div>

            <!-- Actions -->
            <form id="enroll-form" method="post" action="<?= url_e('/enroll') ?>" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row">
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
    function selectPaymentMethod(method) {
        const refWrap = document.getElementById('em-ref-wrap');
        const note = document.getElementById('em-method-note');
        const gcash = document.getElementById('em-gcash');
        const ref = document.getElementById('em-reference');

        const notes = {
            gcash: 'Send the exact amount, then enter the GCash reference number below.',
            cash: 'Pay at the front desk within 24 hours. Your enrollment stays pending until staff confirms.',
            credit_card: 'Pay at the front desk by card, then enter the receipt reference below.',
            bank_transfer: 'Transfer to the gym account shown at the desk, then enter the bank reference below.'
        };

        if (note) note.textContent = notes[method] || '';
        if (gcash) gcash.classList.toggle('hidden', method !== 'gcash');

        const needsRef = method !== 'cash';
        if (refWrap) refWrap.classList.toggle('hidden', !needsRef);
        if (ref) {
            ref.required = needsRef;
            if (!needsRef) ref.value = '';
        }
    }

    function openEnrollModal(program) {
        if (!program) return;

        document.getElementById('em-title').textContent = (program.name || 'Program').toUpperCase();
        document.getElementById('em-price').textContent = '₱' + Number(program.price || 0).toLocaleString();
        document.getElementById('em-duration').textContent = (program.duration_days || 30) + 'd';
        document.getElementById('em-tier').textContent = (program.tier || 'standard').toUpperCase();
        document.getElementById('em-program-id').value = program.id;

        const checked = document.querySelector('#enrollModal input[name="method"]:checked')
            || document.querySelector('#enrollModal input[name="method"]');
        selectPaymentMethod(checked ? checked.value : 'cash');

        const m = document.getElementById('enrollModal');
        m.classList.remove('hidden');
        m.classList.add('flex');

        setTimeout(() => {
            const first = m.querySelector('#em-reference');
            if (first && !first.closest('#em-ref-wrap').classList.contains('hidden')) first.focus();
            else m.querySelector('input[name="method"]')?.focus();
        }, 120);
    }

    function closeEnrollModal() {
        const m = document.getElementById('enrollModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const m = document.getElementById('enrollModal');
            if (m && m.classList.contains('flex')) closeEnrollModal();
        }
    });

    document.getElementById('enrollModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEnrollModal();
    });

    window.openEnroll = openEnrollModal;
    window.closeEnroll = closeEnrollModal;
</script>
