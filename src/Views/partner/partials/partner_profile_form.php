<?php

/**
 * Partner — Business Profile Form (2026 UI)
 * Expects $profile with business_name, partner_type, phone
 */

$profile = $profile ?? [];
$currentType = $profile['partner_type'] ?? 'coach';
?>

<form method="post" action="<?= url_e('/partner-dashboard/profile') ?>"
    class="group rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm transition-all duration-300 hover:border-[#00A6A6]/30 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.15)]"
    id="partnerProfileForm">

    <?= csrf_field() ?>

    <!-- Header -->
    <div class="flex items-start gap-4 mb-8">
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <div>
            <h3 class="font-heading text-2xl tracking-wide text-white">BUSINESS DETAILS</h3>
            <p class="mt-1 text-xs text-gray-400">
                Keep your business information up to date — visible to the gym admin only.
            </p>
        </div>
    </div>

    <!-- Fields -->
    <div class="grid gap-6 sm:grid-cols-2">

        <!-- Business Name (Full Width) -->
        <label class="group/field block sm:col-span-2">
            <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                Business Name <span class="text-[#D50000]">*</span>
            </span>
            <input type="text"
                name="business_name"
                value="<?= e($profile['business_name'] ?? '') ?>"
                required
                maxlength="120"
                placeholder="e.g. Juan's Coaching"
                autocomplete="organization"
                class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20
                    group-hover/field:border-[#00A6A6]/50">
            <span class="mt-2 block text-[11px] text-gray-500">
                This appears on your referral link and in the partner directory.
            </span>
        </label>

        <!-- Partner Type (Toggle) -->
        <label class="group/field block sm:col-span-2">
            <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Partner Type <span class="text-[#D50000]">*</span>
            </span>

            <div class="grid grid-cols-2 gap-2 rounded-xl border border-white/10 bg-[#0a0a0a] p-1">
                <label class="relative cursor-pointer">
                    <input type="radio" name="partner_type" value="coach" class="peer sr-only" <?= $currentType === 'coach' ? 'checked' : '' ?>>
                    <div class="flex items-center justify-center gap-2 rounded-lg px-3 py-3 text-sm font-bold text-gray-400 transition-all
                        peer-checked:bg-[#00A6A6] peer-checked:text-white peer-checked:shadow-[0_0_15px_rgba(0,166,166,0.4)]
                        hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Coach</span>
                    </div>
                </label>

                <label class="relative cursor-pointer">
                    <input type="radio" name="partner_type" value="supplier" class="peer sr-only" <?= $currentType === 'supplier' ? 'checked' : '' ?>>
                    <div class="flex items-center justify-center gap-2 rounded-lg px-3 py-3 text-sm font-bold text-gray-400 transition-all
                        peer-checked:bg-[#00A6A6] peer-checked:text-white peer-checked:shadow-[0_0_15px_rgba(0,166,166,0.4)]
                        hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Supplier</span>
                    </div>
                </label>
            </div>
        </label>

        <!-- Contact Number -->
        <label class="group/field block sm:col-span-2">
            <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                Contact Number
            </span>
            <input type="tel"
                name="phone"
                value="<?= e($profile['phone'] ?? '') ?>"
                maxlength="30"
                placeholder="09XX XXX XXXX"
                pattern="[0-9\s\-+()]*"
                autocomplete="tel"
                class="h-12 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white placeholder-gray-600 outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20
                    group-hover/field:border-[#00A6A6]/50">
            <span class="mt-2 block text-[11px] text-gray-500">
                Used by the gym admin to contact you regarding payouts and sales.
            </span>
        </label>
    </div>

    <!-- Info strip -->
    <div class="mt-8 flex items-start gap-3 rounded-xl border border-[#00A6A6]/20 bg-[#00A6A6]/5 p-4">
        <svg class="w-5 h-5 text-[#00A6A6] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="text-xs leading-relaxed text-gray-300">
            Your <strong class="text-white">business name</strong> is used when members link to you via referral.
            The <strong class="text-white">partner type</strong> determines what kind of products or services you offer.
        </div>
    </div>

    <!-- Submit -->
    <div class="mt-8 flex flex-col-reverse items-stretch gap-4 sm:flex-row sm:items-center sm:justify-between border-t border-white/5 pt-6">
        <p class="order-2 text-center text-xs text-gray-500 sm:order-1 sm:text-left flex items-center justify-center sm:justify-start gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Referral code cannot be changed. Contact the gym admin if you need a new one.
        </p>

        <button type="submit" id="profileSubmitBtn"
            class="group/btn relative order-1 inline-flex items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-8 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300
                   hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]
                   disabled:cursor-not-allowed disabled:opacity-60
                   sm:order-2">
            <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
            <span class="relative" id="profileBtnIcon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
            </span>
            <span class="relative" id="profileBtnLabel">Save Changes</span>
        </button>
    </div>
</form>

<script>
    (function() {
        const form = document.getElementById('partnerProfileForm');
        if (!form) return;

        const btn = document.getElementById('profileSubmitBtn');
        const icon = document.getElementById('profileBtnIcon');
        const label = document.getElementById('profileBtnLabel');

        form.addEventListener('submit', function() {
            if (btn.disabled) return;
            btn.disabled = true;

            // Change icon to a spinner
            icon.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            label.textContent = 'Saving…';
        });
    })();
</script>