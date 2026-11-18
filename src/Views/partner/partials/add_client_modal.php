<?php

/**
 * Partner — Add Client Modal (2026 UI)
 * Triggered from clients.php via openAddClientModal()
 */
?>

<div id="addClientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
        style="animation: client-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

        <!-- Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#0a0a0a] to-[#00A6A6]/20 px-6 py-6 border-b border-white/5">
            <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[#00A6A6]/20 blur-3xl"></div>

            <div class="relative flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/5 text-[#00A6A6]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">
                            Partner Portal
                        </div>
                        <h3 class="mt-1 font-heading text-2xl tracking-wide text-white">
                            ADD CLIENT
                        </h3>
                    </div>
                </div>

                <button type="button" onclick="closeAddClientModal()"
                    class="rounded-full bg-white/5 p-2 text-gray-400 transition-colors hover:bg-white/10 hover:text-white"
                    aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <form method="post" action="<?= url_e('/partner-dashboard/clients') ?>" id="addClientForm" class="p-6 sm:p-8">
            <?= csrf_field() ?>

            <!-- Explanation -->
            <div class="flex items-start gap-3 rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-4">
                <svg class="w-5 h-5 text-[#00A6A6] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <div class="text-xs leading-relaxed text-gray-300">
                    Enter your client's details. Once they sign up using your
                    <strong class="text-[#00A6A6]">referral code</strong> or link,
                    they'll be linked to your account automatically.
                </div>
            </div>

            <!-- Fields -->
            <div class="mt-6 space-y-5">

                <!-- Client Name -->
                <label class="group block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Client Name <span class="text-[#D50000]">*</span>
                    </span>
                    <input type="text"
                        name="client_name"
                        id="clientName"
                        required
                        maxlength="120"
                        placeholder="Juan Dela Cruz"
                        autocomplete="off"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all
                        focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20
                        group-hover:border-[#00A6A6]/50">
                </label>

                <!-- Client Email -->
                <label class="group block">
                    <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Member Email <span class="text-[#D50000]">*</span>
                    </span>
                    <input type="email"
                        name="client_email"
                        id="clientEmail"
                        required
                        maxlength="190"
                        placeholder="juan@example.com"
                        autocomplete="off"
                        class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all
                        focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20
                        group-hover:border-[#00A6A6]/50">
                    <span class="mt-2 block text-[11px] text-gray-500">
                        Use the email the member will register with for the automatic link to work.
                    </span>
                </label>
            </div>

            <!-- Status info -->
            <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-500/20 bg-amber-500/5 p-4">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs leading-relaxed text-amber-200/80">
                    The client will show as <strong class="text-amber-400">Pending</strong> until they complete registration.
                    You can see their status on the <strong class="text-amber-400">My Clients</strong> page.
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row">
                <button type="button" onclick="closeAddClientModal()"
                    class="rounded-full border border-white/10 bg-transparent px-6 py-3.5 text-sm font-bold text-gray-300 transition-all
                       hover:bg-white/5 hover:text-white sm:flex-1">
                    Cancel
                </button>

                <button type="submit" id="addClientSubmitBtn"
                    class="group/btn relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-6 py-3.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                       hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]
                       disabled:cursor-not-allowed disabled:opacity-60 sm:flex-[2]">
                    <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
                    <span class="relative" id="addClientBtnIcon">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </span>
                    <span class="relative" id="addClientBtnLabel">Request to Link</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes client-pop {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    #addClientModal.closing>div {
        animation: client-out 0.2s ease-in forwards;
    }

    @keyframes client-out {
        from {
            opacity: 1;
            transform: scale(1);
        }

        to {
            opacity: 0;
            transform: scale(0.96);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #addClientModal>div {
            animation: none !important;
        }
    }
</style>

<script>
    /* ============ Open / Close ============ */
    function openAddClientModal() {
        const m = document.getElementById('addClientModal');
        m.classList.remove('closing');
        m.classList.replace('hidden', 'flex');

        // Reset fields each time
        const f = document.getElementById('addClientForm');
        if (f) f.reset();

        const nameInput = document.getElementById('clientName');
        if (nameInput) setTimeout(() => nameInput.focus(), 120);
    }

    function closeAddClientModal() {
        const m = document.getElementById('addClientModal');
        m.classList.add('closing');
        setTimeout(() => {
            m.classList.replace('flex', 'hidden');
            m.classList.remove('closing');
        }, 200);
    }

    /* ============ Prevent double submit ============ */
    (function() {
        const form = document.getElementById('addClientForm');
        if (!form) return;
        const btn = document.getElementById('addClientSubmitBtn');
        const icon = document.getElementById('addClientBtnIcon');
        const label = document.getElementById('addClientBtnLabel');

        form.addEventListener('submit', function() {
            if (btn.disabled) return;
            btn.disabled = true;

            // Swap paper airplane for a spinner
            icon.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            label.textContent = 'Sending…';
        });
    })();

    /* ============ Esc + backdrop close ============ */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const m = document.getElementById('addClientModal');
            if (m && m.classList.contains('flex')) closeAddClientModal();
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'addClientModal') closeAddClientModal();
    });
</script>