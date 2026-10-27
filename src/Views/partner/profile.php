<?php

/**
 * Partner — Profile (2026 UI)
 * Partner card + business form + account section
 */

$user    = current_user();
$profile = $profile ?? [];

$businessName = $profile['business_name'] ?? ($user['full_name'] ?: 'Partner');
$partnerType  = $profile['partner_type'] ?? 'coach';
$referralCode = $profile['referral_code'] ?? 'PMZ-XXXXXX';
$phone        = $profile['phone'] ?? '';

$memberSince = !empty($user['created_date'])
    ? date('M Y', strtotime($user['created_date']))
    : date('M Y');

$initials = strtoupper(substr($businessName, 0, 1));
$typeLabel = strtoupper($partnerType);
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ========== Page header ========== -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-heading text-4xl tracking-wide text-white sm:text-5xl">MY PROFILE</h1>
            <p class="mt-1 text-sm text-gray-400">Manage your business details and account</p>
        </div>

        <span class="inline-flex items-center gap-2 self-start rounded-full border border-[#00A6A6]/40 bg-[#00A6A6]/10 px-4 py-2 text-xs font-bold uppercase tracking-wider text-[#00A6A6] backdrop-blur-sm">
            <?php if ($partnerType === 'supplier'): ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            <?php else: ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            <?php endif; ?>
            <?= e($typeLabel) ?> PARTNER
        </span>
    </div>

    <!-- ========== Partner card (hero) ========== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 border border-white/5 p-8 sm:p-10 shadow-[0_20px_50px_-15px_rgba(0,166,166,0.15)]">
        <!-- Decorative glow & texture -->
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 20px 20px;"></div>

        <div class="relative">
            <!-- Top row: brand + partner type -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <img src="<?= url_e('/public/images/powermagz_gym.png') ?>"
                        alt="PowerMagz Gym"
                        class="h-12 w-12 rounded-full shadow-lg ring-2 ring-white/20">
                    <div class="leading-none">
                        <div class="font-heading text-xl tracking-widest text-white">POWERMAGZ</div>
                        <div class="mt-1 text-[9px] font-bold tracking-[0.4em] text-[#00A6A6]">B2B PARTNER</div>
                    </div>
                </div>

                <!-- Partner type badge -->
                <div class="rounded-xl border border-white/20 bg-white/5 px-4 py-2 backdrop-blur-md text-right">
                    <div class="text-[9px] font-bold uppercase tracking-[0.3em] text-gray-400">Type</div>
                    <div class="font-heading text-lg text-white">
                        <?= e(ucfirst($partnerType)) ?>
                    </div>
                </div>
            </div>

            <!-- Business name -->
            <div class="mt-12">
                <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Business Name</div>
                <div class="mt-2 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    <?= e(strtoupper($businessName)) ?>
                </div>
                <div class="mt-1 text-sm text-gray-400"><?= e($user['email']) ?></div>
            </div>

            <!-- Bottom row: numbers -->
            <div class="mt-12 grid grid-cols-2 gap-6 sm:grid-cols-4 border-t border-white/10 pt-6">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Referral Code</div>
                    <div class="mt-1 break-all font-heading text-xl tracking-widest text-[#00A6A6]">
                        <?= e($referralCode) ?>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Partner Since</div>
                    <div class="mt-1 font-heading text-xl text-white"><?= e($memberSince) ?></div>
                </div>

                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Contact</div>
                    <div class="mt-1 truncate font-heading text-xl text-white">
                        <?= $phone ? e($phone) : '—' ?>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500">Partner ID</div>
                    <div class="mt-1 font-mono text-sm text-gray-400"><?= e(substr($user['id'] ?? '', 0, 8)) ?>…</div>
                </div>
            </div>

            <!-- Bottom strip -->
            <div class="mt-8 flex items-center justify-between border-t border-white/10 pt-6">
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-green-500"></span>
                    Active Partner Account
                </div>
                <button type="button"
                    onclick="pmgzCopyReferral(this, '<?= e($referralCode) ?>')"
                    class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-2 text-[11px] font-bold text-white backdrop-blur-sm transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Copy Code</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========== Quick info tiles ========== -->
    <div class="grid gap-4 sm:grid-cols-3">
        <!-- Referral Link -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Referral Link</div>
                    <div class="mt-1 truncate text-sm font-bold text-white">
                        <?= e(url('/register?ref=' . $referralCode)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Email</div>
                    <div class="mt-1 truncate text-sm font-bold text-white"><?= e($user['email']) ?></div>
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-transform duration-300 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Contact</div>
                    <div class="mt-1 truncate text-sm font-bold text-white">
                        <?= $phone ? e($phone) : 'Not set' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== Business details form (partial) ========== -->
    <?php require BASE_PATH . '/src/Views/partner/partials/partner_profile_form.php'; ?>

    <!-- ========== Account section ========== -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-6 sm:p-8 shadow-sm">
        <div class="flex items-center gap-3 mb-6">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h3 class="font-heading text-xl tracking-wide text-white">ACCOUNT SECURITY</h3>
                <p class="mt-0.5 text-xs text-gray-400">Your account details and security</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Email</div>
                <div class="mt-1 truncate text-sm font-semibold text-white"><?= e($user['email']) ?></div>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Role</div>
                <div class="mt-1 text-sm font-semibold text-white capitalize"><?= e($user['role'] ?? 'partner') ?></div>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Account Created</div>
                <div class="mt-1 text-sm font-semibold text-white"><?= e(short_date($user['created_date'] ?? null)) ?></div>
            </div>
            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Last Updated</div>
                <div class="mt-1 text-sm font-semibold text-white"><?= e(short_date($user['updated_date'] ?? null)) ?></div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a href="<?= url_e('/forgot-password') ?>"
                class="tap-effect inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-gray-300 transition-all hover:bg-white/10 hover:text-white">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Change Password
            </a>
            <button type="button" onclick="openSignOutModal()"
                class="tap-effect inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-2 text-xs font-bold text-red-500 transition-all hover:bg-red-500/20">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sign Out
            </button>
        </div>
    </div>

    <!-- ========== Help box ========== -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/5 text-[#00A6A6]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-white">Need help?</div>
                <div class="mt-1 text-xs text-gray-400">
                    Contact the gym admin for account changes or referral code issues.
                </div>
            </div>
        </div>
        <a href="tel:09618041523"
            class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-xs font-bold text-white transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            (0961) 804-1523
        </a>
    </div>
</div>

<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    /* ========== Copy referral code with clean SVG feedback ========== */
    window.pmgzCopyReferral = function(btn, code) {
        if (!code) return;

        const originalHTML = btn.innerHTML;

        const done = () => {
            btn.innerHTML =
                '<svg class="w-3.5 h-3.5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' +
                '<span>Copied!</span>';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
            }, 1400);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(done).catch(() => {
                window.prompt('Copy this code:', code);
            });
        } else {
            window.prompt('Copy this code:', code);
        }
    };
</script>