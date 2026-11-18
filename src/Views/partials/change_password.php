<?php

/**
 * Shared self-service password form. Posts to AuthController::changePassword,
 * which sends the user back to their own portal's profile page.
 */
?>

<form method="post" action="<?= url_e('/profile/password') ?>" class="mt-6 border-t border-white/5 pt-6">
    <?= csrf_field() ?>

    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
        </div>
        <div>
            <h4 class="font-heading text-lg tracking-wide text-white">CHANGE PASSWORD</h4>
            <p class="mt-0.5 text-xs text-gray-400">Use at least 8 characters.</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <label class="block">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Current Password</span>
            <input type="password" name="current_password" required autocomplete="current-password"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
        </label>
        <label class="block">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-500">New Password</span>
            <input type="password" name="new_password" required minlength="8" autocomplete="new-password"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
        </label>
        <label class="block">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Confirm New Password</span>
            <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"
                class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-3 text-sm text-white outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
        </label>
    </div>

    <button type="submit"
        class="mt-5 inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-2.5 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        Update Password
    </button>
</form>
