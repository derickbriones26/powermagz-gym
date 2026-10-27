<?php

/**
 * Member — Contact Form (2026 Dark UI)
 * Expects $profile to be set by the parent view
 */

$profile = $profile ?? [];
?>

<form method="post" action="<?= url_e('/profile') ?>" class="rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm">

    <?= csrf_field() ?>

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0a2 2 0 104 0" />
                </svg>
            </div>
            <div>
                <h3 class="font-heading text-2xl tracking-wide text-white">CONTACT DETAILS</h3>
                <p class="mt-0.5 text-xs text-gray-400">Keep your details up to date so we can reach you</p>
            </div>
        </div>
    </div>

    <!-- Fields Grid -->
    <div class="grid gap-5 sm:grid-cols-2">

        <!-- Phone -->
        <label class="group block">
            <span class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                Phone Number
            </span>
            <input type="tel"
                name="phone"
                value="<?= e($profile['phone'] ?? '') ?>"
                placeholder="09XX XXX XXXX"
                pattern="[0-9\s\-+()]*"
                autocomplete="tel"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white placeholder-gray-600 outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
        </label>

        <!-- Birth Date -->
        <label class="group block">
            <span class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Birth Date
            </span>
            <input type="date"
                name="birth_date"
                value="<?= e(($profile['birth_date'] ?? '') ? substr($profile['birth_date'], 0, 10) : '') ?>"
                max="<?= date('Y-m-d') ?>"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20 [color-scheme:dark]">
        </label>

        <!-- Emergency Contact -->
        <label class="group block">
            <span class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Emergency Contact
            </span>
            <input type="text"
                name="emergency_contact"
                value="<?= e($profile['emergency_contact'] ?? '') ?>"
                placeholder="Name &amp; number"
                autocomplete="off"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white placeholder-gray-600 outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
        </label>

        <!-- Fitness Goal -->
        <label class="group block">
            <span class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Fitness Goal
            </span>
            <input type="text"
                name="fitness_goal"
                value="<?= e($profile['fitness_goal'] ?? '') ?>"
                placeholder="e.g. Lose weight, build muscle"
                autocomplete="off"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white placeholder-gray-600 outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
        </label>

        <!-- Address (full width) -->
        <label class="group block sm:col-span-2">
            <span class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Address
            </span>
            <input type="text"
                name="address"
                value="<?= e($profile['address'] ?? '') ?>"
                placeholder="Street, Barangay, City"
                autocomplete="street-address"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white placeholder-gray-600 outline-none transition-all
                    focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
        </label>
    </div>

    <!-- Save button -->
    <div class="mt-8 flex flex-col-reverse items-stretch gap-4 sm:flex-row sm:items-center sm:justify-between border-t border-white/5 pt-6">
        <p class="order-2 text-center text-xs text-gray-500 sm:order-1 sm:text-left flex items-center justify-center sm:justify-start gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Your data is private and only visible to gym staff.
        </p>

        <button type="submit"
            class="group relative order-1 inline-flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-8 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300
                   hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]
                   sm:order-2">
            <svg class="w-4 h-4 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
            Save Details
        </button>
    </div>
</form>

<style>
    /* Fix calendar icon visibility on dark mode date inputs */
    input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        filter: invert(1);
        opacity: 0.5;
        transition: opacity 0.2s ease;
    }

    input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
    }
</style>