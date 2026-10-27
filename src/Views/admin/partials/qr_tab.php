<?php

/**
 * Admin — QR Codes Tab (2026 UI)
 *
 * Receives from AdminController::dashboard():
 *   $data['users']  — User::listWithStats() rows
 *
 * ⚠️ SECURITY NOTE (review §2.1 + prior batch):
 *    This tab generates QR codes through api.qrserver.com, meaning the
 *    payload (gym secret / member ID) is sent to a third party.
 *    Before production, migrate to a local QR library (e.g.
 *    endroid/qr-code) and replace the static gym secret with a
 *    rotating signed token. Until then, treat this tab as admin-only.
 */

$users = $data['users'] ?? [];

/* Filter out admins from member QR list */
$members = array_values(array_filter($users, fn($u) => ($u['role'] ?? '') !== 'admin'));

/* Gym entrance QR */
$gymQrData  = defined('GYM_QR_DATA') ? GYM_QR_DATA : 'PMGZ-CHECKIN';
$gymQrSmall = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . urlencode($gymQrData);
$gymQrLarge = 'https://api.qrserver.com/v1/create-qr-code/?size=1200x1200&data=' . urlencode($gymQrData);

/* Member counts */
$totalMembers  = count($members);
$membersWithQr = 0;
foreach ($members as $m) {
    if (!empty($m['id'])) $membersWithQr++;
}

/* Quick sanity check on the secret strength */
$secretOk = strlen((string)$gymQrData) >= 16 && !str_starts_with((string)$gymQrData, 'INSECURE-');
?>

<div class="space-y-8 animate-[fade-in_0.4s_ease-out]">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-8 sm:p-10 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[#00A6A6]/20 blur-[80px]"></div>

        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-sm">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-[#00A6A6]">Admin Panel</span>
            </div>
            <h1 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                QR CODES
            </h1>
            <p class="mt-2 max-w-xl text-sm text-gray-400 sm:text-base">
                Gym entrance poster + member QR cards for check-in.
            </p>
        </div>
    </div>

    <!-- ============================================================
         SECURITY WARNING (only if secret is weak)
         ============================================================ -->
    <?php if (!$secretOk): ?>
        <div class="flex items-start gap-4 rounded-2xl border border-red-500/30 bg-red-500/5 p-5">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-red-500">Gym QR secret is insecure</div>
                <div class="mt-1 text-xs leading-relaxed text-gray-300">
                    <code class="text-white">GYM_QR_DATA</code> is either missing or too short. Set a 32-character
                    random value in <code class="text-white">.env</code> before printing this poster.
                    Until fixed, check-ins will be rejected by the server.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         HERO — GYM ENTRANCE QR
         ============================================================ -->
    <section class="relative overflow-hidden rounded-3xl border-2 border-[#00A6A6]/30 bg-gradient-to-br from-[#00A6A6]/5 to-[#111] p-8 sm:p-10 shadow-xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-[#00A6A6]/15 blur-3xl"></div>

        <div class="relative grid gap-8 lg:grid-cols-[auto_1fr]">
            <!-- QR preview -->
            <div class="mx-auto w-fit">
                <div class="group relative rounded-2xl border-4 border-[#00A6A6] bg-white p-4 shadow-[0_0_40px_rgba(0,166,166,0.4)] transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_0_60px_rgba(0,166,166,0.6)]">
                    <img src="<?= e($gymQrSmall) ?>"
                        alt="Gym entrance QR code — scan to check in"
                        width="220" height="220"
                        class="transition-transform duration-500 group-hover:scale-105">
                    <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-[#00A6A6] px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white shadow-md whitespace-nowrap">
                        Scan Me
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="flex flex-col justify-center text-center lg:text-left">
                <span class="inline-flex items-center gap-2 self-center rounded-full border border-[#00A6A6]/30 bg-[#00A6A6]/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-[#00A6A6] lg:self-start">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    <span>Gym Entrance</span>
                </span>

                <h2 class="mt-4 font-heading text-4xl tracking-wide text-white sm:text-5xl">
                    ENTRANCE QR CODE
                </h2>

                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-gray-300">
                    Print and post this QR code at the gym entrance. Members scan it on their
                    <strong class="text-white">Time In</strong> page to log their session automatically.
                </p>

                <div class="mt-5 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] px-4 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Payload</div>
                        <code class="text-xs font-semibold text-[#00A6A6]"><?= e(strlen($gymQrData) > 20 ? substr($gymQrData, 0, 8) . '…' . substr($gymQrData, -8) : $gymQrData) ?></code>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] px-4 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Total Members</div>
                        <div class="text-xs font-semibold text-white"><?= $totalMembers ?> active cards</div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                    <a href="<?= e($gymQrLarge) ?>&download=1"
                        download="powermagz-gym-entrance-qr.png"
                        class="tap-effect inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-6 py-3 text-sm font-bold text-white transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Download High-Res (1200×1200)</span>
                    </a>

                    <button type="button" onclick="printGymQr()"
                        class="tap-effect inline-flex items-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print Poster</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         STATS ROW
         ============================================================ -->
    <div class="grid gap-4 sm:grid-cols-3">

        <!-- Members with QR -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Members with QR</div>
                    <div class="font-heading text-2xl text-white"><?= $membersWithQr ?></div>
                </div>
            </div>
        </div>

        <!-- Gym QR Status -->
        <div class="group relative overflow-hidden rounded-2xl border <?= $secretOk ? 'border-green-500/20 bg-green-500/5' : 'border-red-500/20 bg-red-500/5' ?> p-6 shadow-sm transition-all duration-300 hover:-translate-y-1">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full <?= $secretOk ? 'bg-green-500/5' : 'bg-red-500/5' ?> transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl <?= $secretOk ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500' ?> transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest <?= $secretOk ? 'text-green-500' : 'text-red-500' ?>">Gym QR Status</div>
                    <div class="flex items-center gap-2 font-heading text-2xl <?= $secretOk ? 'text-green-500' : 'text-red-500' ?>">
                        <?php if ($secretOk): ?>
                            <span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                            Active
                        <?php else: ?>
                            <span class="h-2 w-2 animate-pulse rounded-full bg-red-500"></span>
                            Insecure
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Updated -->
        <div class="group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">
            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-[#00A6A6]/5 transition-transform duration-500 group-hover:scale-150"></div>
            <div class="relative flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6] transition-all duration-300 group-hover:rotate-6 group-hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Last Updated</div>
                    <div class="font-heading text-2xl text-white"><?= date('M j') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         MEMBER QR CODES SECTION
         ============================================================ -->
    <section>
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center mb-4">
            <div>
                <h3 class="font-heading text-3xl tracking-wide text-white">MEMBER QR CODES</h3>
                <p class="mt-1 text-sm text-gray-400">
                    Click any member to preview their QR code. Print it on their membership card.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="qrSearch" type="search" placeholder="Search member…"
                        aria-label="Search members by name or email"
                        class="h-11 w-full rounded-full border border-white/10 bg-[#0a0a0a] pl-11 pr-4 text-sm text-white placeholder-gray-600 outline-none transition-all focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </div>

                <button type="button" onclick="printAllMemberQr()"
                    class="tap-effect inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print All
                </button>
            </div>
        </div>

        <?php if (empty($members)): ?>
            <div class="rounded-2xl border border-dashed border-white/10 bg-[#111] p-16 text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h4 class="mt-4 font-heading text-2xl tracking-wide text-white">NO MEMBERS YET</h4>
                <p class="mt-2 text-sm text-gray-400">Once members register, their QR codes will appear here.</p>
            </div>
        <?php else: ?>
            <div id="qrGrid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <?php foreach ($members as $idx => $m):
                    $memberQrUrl   = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . urlencode('PMGZ:' . $m['id']);
                    $memberQrLarge = 'https://api.qrserver.com/v1/create-qr-code/?size=1200x1200&data=' . urlencode('PMGZ:' . $m['id']);
                    $name = $m['full_name'] ?: 'Unnamed';
                    $safeName = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($name));
                ?>
                    <div class="qr-card group relative overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm transition-all duration-300
                        hover:-translate-y-2 hover:border-[#00A6A6]/40 hover:shadow-[0_20px_40px_-15px_rgba(0,166,166,0.2)]"
                        data-search="<?= e(strtolower($name . ' ' . $m['email'])) ?>"
                        style="animation: card-enter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) <?= min($idx * 40, 400) ?>ms backwards;">

                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-base text-white shadow-md transition-transform duration-300 group-hover:scale-110">
                                <?= e(initials($name)) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-bold text-white transition-colors group-hover:text-[#00A6A6]"><?= e($name) ?></div>
                                <div class="truncate text-xs text-gray-500"><?= e($m['email']) ?></div>
                            </div>
                        </div>

                        <button type="button"
                            onclick='openQrModal(<?= json_encode([
                                                        "id" => $m["id"],
                                                        "name" => $name,
                                                        "email" => $m["email"],
                                                        "qr_small" => $memberQrUrl,
                                                        "qr_large" => $memberQrLarge,
                                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            aria-label="Preview QR code for <?= e($name) ?>"
                            class="mt-4 flex w-full items-center justify-center rounded-xl border-2 border-dashed border-white/10 bg-white/[0.02] p-3 transition-all
                             hover:border-[#00A6A6] hover:bg-[#00A6A6]/5">

                            <img src="<?= e($memberQrUrl) ?>"
                                alt="QR code for <?= e($name) ?>"
                                width="120" height="120"
                                loading="lazy"
                                class="rounded-lg bg-white p-1 shadow-sm transition-transform duration-300 group-hover:scale-105">
                        </button>

                        <div class="mt-4 flex items-center gap-2">
                            <button type="button"
                                onclick='openQrModal(<?= json_encode([
                                                            "id" => $m["id"],
                                                            "name" => $name,
                                                            "email" => $m["email"],
                                                            "qr_small" => $memberQrUrl,
                                                            "qr_large" => $memberQrLarge,
                                                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View
                            </button>
                            <a href="<?= e($memberQrLarge) ?>&download=1"
                                download="powermagz-qr-<?= e($safeName) ?>.png"
                                aria-label="Download QR code for <?= e($name) ?>"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-[#00A6A6] text-xs text-white shadow-md transition-all
                          hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]"
                                title="Download QR">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="noQrResults" class="hidden mt-6 rounded-2xl border border-dashed border-white/10 bg-[#111] p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p class="mt-4 text-sm text-gray-400">No members match your search.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============================================================
         QR PREVIEW MODAL
         ============================================================ -->
    <div id="qrModal" role="dialog" aria-modal="true" aria-labelledby="qrModalTitle"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#111] shadow-2xl"
            style="animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">

            <div class="flex items-center justify-between border-b border-white/5 p-5">
                <div>
                    <h2 id="qrModalTitle" class="font-heading text-xl tracking-wide text-white">QR CODE PREVIEW</h2>
                    <p class="mt-0.5 text-xs text-gray-400">Ready to print on a card</p>
                </div>
                <button type="button" onclick="closeQrModal()"
                    aria-label="Close QR preview"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-white/5 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <div id="qrCardPrint" class="rounded-2xl border-2 border-[#00A6A6] bg-white p-5 text-center shadow-[0_0_30px_rgba(0,166,166,0.3)]">
                    <div class="flex items-center justify-between">
                        <div class="font-heading text-lg tracking-wide text-black">POWERMAGZ</div>
                        <div class="text-[10px] font-bold tracking-[0.4em] text-[#00A6A6]">GYM</div>
                    </div>
                    <div class="mt-1 border-b border-gray-200"></div>

                    <img id="qrm-img" src="" alt="Member QR code"
                        width="240" height="240"
                        class="mx-auto mt-4 rounded-lg border-2 border-gray-100 bg-white p-2">

                    <div id="qrm-name" class="mt-4 font-heading text-2xl tracking-wide text-black">Member Name</div>
                    <div id="qrm-email" class="text-xs text-gray-600">email@example.com</div>
                    <div id="qrm-id" class="mt-2 break-all font-mono text-[10px] text-gray-500"></div>
                    <div class="mt-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        Scan to time in / out
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" onclick="downloadQr()"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download PNG
                    </button>
                    <button type="button" onclick="printCurrentQr()"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-bold text-gray-300 transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
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

    @keyframes card-enter {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.96);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes modal-pop {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .qr-card,
        #qrModal>div {
            animation: none !important;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        /* ============ Current selection ============ */
        let currentQr = null;

        /* ============ Search filter ============ */
        document.getElementById('qrSearch')?.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#qrGrid .qr-card');
            let visible = 0;
            cards.forEach(card => {
                const match = q === '' || (card.dataset.search || '').includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            document.getElementById('noQrResults')?.classList.toggle('hidden', visible > 0 || cards.length === 0);

            if (window.PMGZ_A11Y && window.PMGZ_A11Y.announce) {
                window.PMGZ_A11Y.announce(visible + ' member' + (visible !== 1 ? 's' : '') + ' shown');
            }
        });

        /* ============ Modal helpers ============ */
        function trackTrigger() {
            if (window.PMGZ_A11Y && window.PMGZ_A11Y.openModal) {
                window.PMGZ_A11Y.openModal(document.activeElement);
            }
        }

        /* ============ QR modal ============ */
        window.openQrModal = function(m) {
            trackTrigger();

            currentQr = m;
            document.getElementById('qrm-img').src = m.qr_small;
            document.getElementById('qrm-img').alt = 'QR code for ' + (m.name || 'member');
            document.getElementById('qrm-name').textContent = m.name;
            document.getElementById('qrm-email').textContent = m.email;
            document.getElementById('qrm-id').textContent = 'ID: ' + m.id;

            document.getElementById('qrModal').classList.replace('hidden', 'flex');
        };

        window.closeQrModal = function() {
            document.getElementById('qrModal').classList.replace('flex', 'hidden');
        };

        /* ============ Download ============ */
        window.downloadQr = function() {
            if (!currentQr) return;
            const safe = currentQr.name.replace(/[^a-zA-Z0-9]+/g, '-').toLowerCase();
            const a = document.createElement('a');
            a.href = currentQr.qr_large + '&download=1';
            a.download = 'powermagz-qr-' + safe + '.png';
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        };

        /* ============ Print current QR card ============ */
        window.printCurrentQr = function() {
            if (!currentQr) return;
            const html = document.getElementById('qrCardPrint').outerHTML;
            openPrintWindow(html, currentQr.name);
        };

        /* ============ Print gym QR poster ============ */
        window.printGymQr = function() {
            const gymData = '<?= e($gymQrData) ?>';
            const gymQr = '<?= e($gymQrLarge) ?>';
            const html = `
    <div style="text-align:center;font-family:'Montserrat',sans-serif;padding:40px;max-width:700px;margin:0 auto;">
      <div style="font-family:'Bebas Neue',sans-serif;font-size:64px;letter-spacing:4px;margin-bottom:4px;color:#0a0a0a;">POWERMAGZ</div>
      <div style="font-size:14px;letter-spacing:12px;color:#00A6A6;font-weight:700;margin-bottom:40px;margin-left:12px;">GYM</div>
      <div style="font-family:'Bebas Neue',sans-serif;font-size:48px;margin-bottom:20px;color:#0a0a0a;">SCAN TO TIME IN</div>
      <img src="${gymQr}" style="width:420px;height:420px;border:8px solid #00A6A6;border-radius:20px;padding:16px;background:#fff;margin:0 auto;display:block;" alt="Gym entrance QR code">
      <div style="margin-top:32px;font-size:18px;color:#555;font-weight:600;">Point your phone at the QR code above</div>
      <div style="margin-top:10px;font-size:13px;color:#888;">Or open the app and use Manual Entry</div>
    </div>`;
            openPrintWindow(html, 'gym-entrance-qr');
        };

        /* ============ Print all member QR cards ============ */
        window.printAllMemberQr = function() {
            const cards = document.querySelectorAll('#qrGrid .qr-card');
            let inner = '<div style="font-family:\'Montserrat\',sans-serif;padding:24px;background:#fff;">';
            inner += '<h1 style="font-family:\'Bebas Neue\',sans-serif;font-size:36px;text-align:center;margin-bottom:8px;letter-spacing:2px;">POWERMAGZ — MEMBER QR CARDS</h1>';
            inner += '<p style="text-align:center;color:#666;margin-bottom:32px;font-size:12px;">Printed ' + new Date().toLocaleDateString() + '</p>';
            inner += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">';

            cards.forEach(card => {
                if (card.style.display === 'none') return;
                const img = card.querySelector('img');
                const name = card.querySelector('.text-sm.font-bold')?.textContent.trim() || '';
                const email = card.querySelector('.text-xs.text-gray-500')?.textContent.trim() || '';
                if (!img) return;
                const largeUrl = img.src.replace('size=280x280', 'size=600x600');
                inner += `
      <div style="border:2px solid #0a0a0a;border-radius:16px;padding:16px;text-align:center;background:#fff;">
        <div style="font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:2px;">POWERMAGZ</div>
        <div style="font-size:9px;letter-spacing:4px;color:#00A6A6;margin-bottom:8px;">GYM</div>
        <img src="${largeUrl}" style="width:180px;height:180px;margin:8px auto;display:block;" alt="QR for ${name}">
        <div style="font-weight:700;font-size:14px;margin-top:4px;">${name}</div>
        <div style="font-size:11px;color:#666;">${email}</div>
      </div>`;
            });

            inner += '</div></div>';
            openPrintWindow(inner, 'member-qr-cards');
        };

        /* ============ Print helper ============ */
        function openPrintWindow(bodyHtml, title) {
            const w = window.open('', '_blank', 'width=900,height=700');
            if (!w) {
                alert('Please allow pop-ups to print.');
                return;
            }
            w.document.write(`
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>${title}</title>
      <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
      <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; }
        @media print {
          @page { margin: 12mm; }
          body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
      </style>
    </head>
    <body>${bodyHtml}</body>
    </html>`);
            w.document.close();
            w.focus();
            setTimeout(() => {
                w.print();
            }, 500);
        }

        /* ============ Backdrop close ============ */
        document.getElementById('qrModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'qrModal') window.closeQrModal();
        });
    })();
</script>