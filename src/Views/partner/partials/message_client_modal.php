<?php

/**
 * Partner — Message Client Modal (2026 UI)
 * Triggered from clients.php via openMessageModal(client)
 */

?>

<div id="messageClientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
    <div class="w-full max-w-lg overflow-hidden rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
        style="animation: msg-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

        <!-- Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#0a0a0a] to-[#00A6A6]/20 px-6 py-6 border-b border-white/5">
            <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[#00A6A6]/20 blur-3xl"></div>

            <div class="relative flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/5 text-[#00A6A6]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">
                            Message Client
                        </div>
                        <h3 id="mc-name" class="mt-1 truncate font-heading text-2xl tracking-wide text-white">
                            Client Name
                        </h3>
                    </div>
                </div>

                <button type="button" onclick="closeMessageModal()"
                    class="rounded-full bg-white/5 p-2 text-gray-400 transition-colors hover:bg-white/10 hover:text-white"
                    aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <form method="post" action="<?= url_e('/partner-dashboard/message-client') ?>" id="messageClientForm" class="p-6 sm:p-8">
            <?= csrf_field() ?>
            <input type="hidden" name="client_user_id" id="mc-user-id">
            <input type="hidden" name="client_email" id="mc-email-hidden">
            <input type="hidden" name="client_name" id="mc-name-hidden">

            <!-- Recipient strip -->
            <div class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] p-3">
                <div id="mc-avatar"
                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-sm text-white shadow-md">
                    ?
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Recipient
                    </div>
                    <div id="mc-email-display" class="mt-0.5 truncate text-sm font-semibold text-white">
                        client@example.com
                    </div>
                </div>
            </div>

            <!-- Message field -->
            <label class="group mt-6 block">
                <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Message <span class="text-[#D50000]">*</span>
                </span>
                <textarea name="message"
                    id="mc-message"
                    rows="6"
                    maxlength="2000"
                    required
                    placeholder="Hi! Just checking in on your training this week. How are the workouts going?"
                    class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white placeholder-gray-600 outline-none transition-all
                         focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20
                         group-hover:border-[#00A6A6]/50"></textarea>

                <div class="mt-2 flex items-center justify-between text-[11px]">
                    <span class="text-gray-500">Keep it short and friendly — max 2000 characters.</span>
                    <span id="mc-counter" class="font-mono font-semibold text-gray-500">0 / 2000</span>
                </div>
            </label>

            <!-- Quick templates -->
            <div class="mt-4">
                <div class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    Quick templates:
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="useTemplate('checkin')"
                        class="tap-effect inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-gray-300 transition-all
                         hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                        </svg>
                        Check-in
                    </button>
                    <button type="button" onclick="useTemplate('reminder')"
                        class="tap-effect inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-gray-300 transition-all
                         hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        Reminder
                    </button>
                    <button type="button" onclick="useTemplate('congrats')"
                        class="tap-effect inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-gray-300 transition-all
                         hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Congrats
                    </button>
                </div>
            </div>

            <!-- Info strip -->
            <div class="mt-6 flex items-start gap-3 rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-4">
                <svg class="w-5 h-5 text-[#00A6A6] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <div class="text-xs leading-relaxed text-gray-300">
                    Your message will be sent to the client's email address. If email delivery isn't set up yet,
                    the message will be logged in the system.
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row">
                <button type="button" onclick="closeMessageModal()"
                    class="rounded-full border border-white/10 bg-transparent px-6 py-3.5 text-sm font-bold text-gray-300 transition-all
                       hover:bg-white/5 hover:text-white sm:flex-1">
                    Cancel
                </button>

                <button type="submit" id="mc-submit"
                    class="group/btn relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-6 py-3.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                       hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]
                       disabled:cursor-not-allowed disabled:opacity-60 sm:flex-[2]">
                    <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
                    <span class="relative" id="mc-icon">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </span>
                    <span class="relative" id="mc-label">Send Message</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes msg-pop {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    #messageClientModal.closing>div {
        animation: msg-out 0.2s ease-in forwards;
    }

    @keyframes msg-out {
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
        #messageClientModal>div {
            animation: none !important;
        }
    }
</style>

<script>
    /* ============ Templates ============ */
    const MESSAGE_TEMPLATES = {
        checkin: "Hi! Just checking in on your training this week. How are the workouts going? Let me know if you need any adjustments to the plan.",
        reminder: "Hey! Quick reminder about your upcoming session. See you at the gym — let me know if you need to reschedule.",
        congrats: "Congratulations on your progress! Really proud of the work you've been putting in. Keep it up!"
    };

    /* ============ Open modal ============ */
    function openMessageModal(client) {
        if (!client) return;

        const m = document.getElementById('messageClientModal');
        m.classList.remove('closing');

        document.getElementById('mc-name').textContent = client.client_name || 'Client';
        document.getElementById('mc-email-display').textContent = client.client_email || '';
        document.getElementById('mc-email-hidden').value = client.client_email || '';
        document.getElementById('mc-name-hidden').value = client.client_name || '';
        document.getElementById('mc-user-id').value = client.client_user_id || '';

        const initial = (client.client_name || client.client_email || '?').charAt(0).toUpperCase();
        document.getElementById('mc-avatar').textContent = initial;

        const msg = document.getElementById('mc-message');
        msg.value = '';
        updateCounter();

        m.classList.replace('hidden', 'flex');
        setTimeout(() => msg.focus(), 120);
    }

    /* ============ Close modal ============ */
    function closeMessageModal() {
        const m = document.getElementById('messageClientModal');
        m.classList.add('closing');
        setTimeout(() => {
            m.classList.replace('flex', 'hidden');
            m.classList.remove('closing');
        }, 200);
    }

    /* ============ Character counter ============ */
    function updateCounter() {
        const msg = document.getElementById('mc-message');
        const counter = document.getElementById('mc-counter');
        if (!msg || !counter) return;

        const len = msg.value.length;
        const max = 2000;
        counter.textContent = len + ' / ' + max;

        counter.classList.remove('text-gray-500', 'text-amber-500', 'text-[#D50000]');
        if (len >= max) {
            counter.classList.add('text-[#D50000]');
        } else if (len >= max * 0.9) {
            counter.classList.add('text-amber-500');
        } else {
            counter.classList.add('text-gray-500');
        }
    }

    document.getElementById('mc-message')?.addEventListener('input', updateCounter);

    /* ============ Quick templates ============ */
    function useTemplate(key) {
        const msg = document.getElementById('mc-message');
        if (!msg) return;
        msg.value = MESSAGE_TEMPLATES[key] || '';
        msg.focus();
        updateCounter();
    }

    /* ============ Prevent double submit ============ */
    (function() {
        const form = document.getElementById('messageClientForm');
        if (!form) return;

        const btn = document.getElementById('mc-submit');
        const icon = document.getElementById('mc-icon');
        const label = document.getElementById('mc-label');

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
            const m = document.getElementById('messageClientModal');
            if (m && m.classList.contains('flex')) closeMessageModal();
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'messageClientModal') closeMessageModal();
    });

    /* Alias for older code */
    window.openMessage = openMessageModal;
</script>