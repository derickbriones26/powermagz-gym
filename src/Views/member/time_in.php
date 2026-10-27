<?php
$user = current_user();
$openRecord = \App\Models\Attendance::openToday($user['id']);
$hasOpenSession = !empty($openRecord);
?>
<!-- Base + CSRF only. Never expose GYM_QR_DATA in the DOM. -->
<meta name="base" content="<?= e(base_path()) ?>">
<meta name="csrf" content="<?= csrf_token() ?>">

<div class="mx-auto w-full max-w-xl space-y-6 animate-[fade-in_0.4s_ease-out]">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 p-6 sm:p-8 border border-white/5 shadow-2xl">
        <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[#00A6A6]/20 blur-[60px]"></div>
        <div class="relative flex items-center gap-4">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/5 text-[#00A6A6]">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="font-heading text-3xl tracking-wide text-white sm:text-4xl">TIME IN / OUT</h1>
                <p class="mt-1 text-xs text-gray-400 sm:text-sm">Scan the gym's QR code at the entrance</p>
            </div>
        </div>
    </div>

    <!-- Open Session Banner -->
    <?php if ($hasOpenSession): ?>
        <div class="relative overflow-hidden rounded-3xl border border-green-500/30 bg-green-500/10 p-6 sm:p-8 shadow-[0_0_30px_rgba(34,197,94,0.15)]">
            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="relative flex-shrink-0">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-500 text-white shadow-[0_0_15px_rgba(34,197,94,0.4)]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="absolute -bottom-1 -right-1 flex h-5 w-5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex h-5 w-5 rounded-full border-2 border-[#111] bg-green-500"></span>
                        </span>
                    </div>
                    <div class="min-w-0">
                        <span class="inline-block rounded-full bg-green-500/20 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-green-400">In Session</span>
                        <div class="mt-2 text-sm font-bold text-white">You're currently in the gym</div>
                        <div class="mt-0.5 text-xs text-gray-400">Timed in at <strong class="text-green-400"><?= e(time_short($openRecord['time_in'])) ?></strong></div>
                    </div>
                </div>
                <div class="flex-shrink-0 text-right">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Elapsed</div>
                    <div id="session-timer" class="font-heading text-3xl text-green-400" data-start="<?= e($openRecord['time_in']) ?>">0m</div>
                </div>
            </div>
            <button type="button" onclick="confirmTimeOut('<?= e($openRecord['id']) ?>')"
                class="group mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-[#111] border border-white/10 px-6 py-4 text-sm font-bold text-white shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-500 hover:border-red-500 hover:shadow-[0_0_20px_rgba(239,68,68,0.4)]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Time Out Now
            </button>
        </div>
    <?php endif; ?>

    <!-- Scanner Area -->
    <div id="scan-area" class="overflow-hidden rounded-3xl border border-white/5 bg-[#111] shadow-2xl">
        <div class="relative w-full bg-black" style="aspect-ratio: 1 / 1;">
            <div id="qr-reader" class="absolute inset-0"></div>

            <!-- Scanner Overlay (Teal corners) -->
            <div id="scanner-overlay" class="pointer-events-none absolute inset-0 flex items-center justify-center p-6">
                <div class="relative h-full w-full" style="max-height:85%; max-width:85%;">
                    <span class="corner corner-tl"></span>
                    <span class="corner corner-tr"></span>
                    <span class="corner corner-bl"></span>
                    <span class="corner corner-br"></span>
                    <span class="scan-line"></span>
                </div>
            </div>

            <!-- Camera Loading -->
            <div id="camera-loading" class="absolute inset-0 flex flex-col items-center justify-center bg-[#0a0a0a] text-white">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-white/10 border-t-[#00A6A6]"></div>
                <p class="mt-4 text-sm font-bold tracking-wide text-white">Starting camera…</p>
                <p id="camera-loading-sub" class="mt-1 text-xs text-gray-500">Allow camera access if prompted</p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-white/5 bg-[#111] px-6 py-4">
            <div class="flex min-w-0 items-center gap-2">
                <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#00A6A6] opacity-60"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-[#00A6A6]"></span>
                </span>
                <span id="scanner-status-text" class="truncate text-xs font-semibold text-gray-300">Point at the gym's QR code</span>
            </div>
            <button type="button" onclick="toggleManualEntry()"
                class="flex-shrink-0 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-white transition-all hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-[#00A6A6]">
                <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                Manual
            </button>
        </div>

        <!-- Manual Entry Panel -->
        <div id="manual-entry" class="hidden border-t border-white/5 bg-[#0a0a0a] p-6">
            <label class="block">
                <span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    Enter the code manually
                </span>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input type="text" id="manual-code-input" placeholder="Paste or type the gym code"
                        autocomplete="off" autocapitalize="off" spellcheck="false"
                        class="h-12 flex-1 rounded-xl border border-white/10 bg-[#111] px-4 text-sm font-mono text-white outline-none transition-all placeholder-gray-600 focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <button type="button" onclick="submitManualCode()"
                        class="h-12 flex-shrink-0 rounded-xl bg-[#00A6A6] px-6 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.3)] transition-all hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.5)]">
                        Submit
                    </button>
                </div>
                <p class="mt-2 text-[11px] text-gray-500">The code is printed on the poster next to the entrance QR.</p>
            </label>
        </div>
    </div>

    <!-- Camera Error State -->
    <div id="camera-error" class="hidden overflow-hidden rounded-3xl border border-red-500/30 bg-[#111] p-8 text-center shadow-2xl">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="mt-4 font-heading text-2xl tracking-wide text-white">CAMERA UNAVAILABLE</h3>
        <p id="camera-error-message" class="mx-auto mt-2 max-w-md text-sm text-gray-400">
            Allow camera access in your browser settings, or use manual entry to check in.
        </p>

        <div id="camera-error-detail" class="hidden mx-auto mt-4 max-w-md rounded-xl border border-red-500/20 bg-red-500/5 p-3 text-left">
            <div class="text-[10px] font-bold uppercase tracking-widest text-red-500">Error detail</div>
            <code id="camera-error-detail-text" class="mt-1 block whitespace-pre-wrap break-words text-xs text-red-400"></code>
        </div>

        <div class="mx-auto mt-6 max-w-md rounded-xl border border-white/5 bg-[#0a0a0a] p-4 text-left">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Common fixes</div>
            <ol class="mt-2 space-y-2 text-xs text-gray-400 list-decimal list-inside">
                <li>Click the lock icon in the address bar &rarr; allow <strong>Camera</strong> &rarr; reload</li>
                <li>Close other apps using the camera (Zoom, Teams, OBS)</li>
                <li>On a PC without a webcam, use <strong>Manual Entry</strong></li>
            </ol>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <button type="button" onclick="retryCamera()" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Retry Camera
            </button>
            <button type="button" onclick="showManualFromError()" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                Use Manual Entry
            </button>
        </div>
    </div>

    <!-- Success State -->
    <div id="success-card" class="hidden overflow-hidden rounded-3xl border border-green-500/30 bg-[#111] p-8 text-center shadow-2xl">
        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-500 text-white shadow-[0_0_30px_rgba(34,197,94,0.4)]">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="absolute inset-0 animate-ping rounded-full bg-green-500 opacity-20"></span>
        </div>
        <h3 class="mt-6 font-heading text-3xl tracking-wide text-white sm:text-4xl">TIME IN SUCCESSFUL</h3>
        <p id="success-text" class="mt-2 text-sm text-gray-400">Welcome back! Enjoy your workout.</p>

        <div class="mt-6 grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Time In</div>
                <div id="success-time" class="mt-1 font-heading text-xl text-green-400">—</div>
            </div>
            <div class="rounded-xl border border-white/5 bg-[#0a0a0a] p-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Date</div>
                <div id="success-date" class="mt-1 font-heading text-xl text-white">—</div>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="<?= url_e('/attendance') ?>" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-bold text-white transition-all hover:bg-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                View History
            </a>
            <a href="<?= url_e('/home') ?>" class="inline-flex items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Back to Home
            </a>
        </div>
    </div>

    <!-- Already Timed In -->
    <div id="already-card" class="hidden overflow-hidden rounded-3xl border border-amber-500/30 bg-[#111] p-8 text-center shadow-2xl">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-amber-500/20 text-amber-500">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="mt-6 font-heading text-3xl tracking-wide text-white sm:text-4xl">ALREADY TIMED IN</h3>
        <p id="already-text" class="mt-2 text-sm text-gray-400">You're already in the gym.</p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="<?= url_e('/home') ?>" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-bold text-white transition-all hover:bg-white/10">
                Back to Home
            </a>
            <button type="button" id="already-timeout-btn" class="inline-flex items-center justify-center gap-2 rounded-full bg-red-500 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:bg-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Time Out Now
            </button>
        </div>
    </div>

    <!-- Tips -->
    <div class="rounded-2xl border border-white/5 bg-[#111] p-5 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#00A6A6]/10 text-[#00A6A6]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-bold text-white">Camera tips</div>
                <p class="mt-1 text-xs leading-relaxed text-gray-400">
                    <strong>On a phone:</strong> the rear camera is used automatically for scanning.
                    <strong>On a laptop/PC:</strong> the front-facing webcam works too — hold the QR code up to it.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Timeout Modal -->
<div id="timeout-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4 backdrop-blur-sm">
    <div class="w-full max-w-sm overflow-hidden rounded-2xl border border-white/10 bg-[#111] shadow-2xl modal-animate">
        <div class="p-6 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <h3 class="mt-4 font-heading text-2xl tracking-wide text-white">TIME OUT?</h3>
            <p class="mt-2 text-sm text-gray-400">You're about to close your session. Your hours will be recorded.</p>
            <div class="mt-4 rounded-xl border border-white/5 bg-[#0a0a0a] p-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500">Session duration</span>
                    <span id="timeout-duration" class="font-heading text-lg text-green-400">0m</span>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 border-t border-white/5 p-4 bg-[#0a0a0a]">
            <button type="button" onclick="closeTimeoutModal()" class="rounded-xl border border-white/10 bg-transparent px-5 py-3 text-sm font-bold text-gray-300 transition-all hover:bg-white/5">Cancel</button>
            <button type="button" id="confirm-timeout-btn" class="rounded-xl bg-red-500 px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(239,68,68,0.3)] transition-all hover:bg-red-600">Time Out</button>
        </div>
    </div>
</div>

<style>
    /* QR Scanner specific styles */
    #qr-reader {
        position: relative;
        width: 100%;
        height: 100%;
    }

    #qr-reader video {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        background: #000 !important;
    }

    #qr-reader__scan_region {
        position: absolute !important;
        inset: 0 !important;
        background: transparent !important;
        border: none !important;
        min-height: 0 !important;
    }

    #qr-reader__scan_region img {
        display: none !important;
    }

    #qr-reader__dashboard_section,
    #qr-reader__header_message,
    #qr-reader__dashboard {
        display: none !important;
    }

    @keyframes scan-sweep {
        0% {
            top: 0;
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            top: 100%;
            opacity: 0;
        }
    }

    .scan-line {
        position: absolute;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(to right, transparent, #00A6A6 20%, #00A6A6 80%, transparent);
        box-shadow: 0 0 12px 2px rgba(0, 166, 166, .8);
        animation: scan-sweep 2.5s ease-in-out infinite;
        border-radius: 2px;
    }

    .corner {
        position: absolute;
        width: 32px;
        height: 32px;
    }

    .corner-tl {
        top: 0;
        left: 0;
        border-left: 4px solid #00A6A6;
        border-top: 4px solid #00A6A6;
        border-top-left-radius: 12px;
    }

    .corner-tr {
        top: 0;
        right: 0;
        border-right: 4px solid #00A6A6;
        border-top: 4px solid #00A6A6;
        border-top-right-radius: 12px;
    }

    .corner-bl {
        bottom: 0;
        left: 0;
        border-left: 4px solid #00A6A6;
        border-bottom: 4px solid #00A6A6;
        border-bottom-left-radius: 12px;
    }

    .corner-br {
        bottom: 0;
        right: 0;
        border-right: 4px solid #00A6A6;
        border-bottom: 4px solid #00A6A6;
        border-bottom-right-radius: 12px;
    }

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

    .modal-animate {
        animation: modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
</style>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    (function() {
        'use strict';

        const base = document.querySelector('meta[name="base"]')?.content || '';
        const csrf = document.querySelector('meta[name="csrf"]')?.content || '';

        // 🔐 NOTE: We no longer read GYM_QR_DATA from the DOM.
        // The server validates the scanned text — the client just sends it raw.

        const hasOpenSession = <?= $hasOpenSession ? 'true' : 'false' ?>;
        const openRecordTime = <?= $hasOpenSession ? json_encode($openRecord['time_in']) : 'null' ?>;

        let scanner = null;
        let done = false;

        const CONSTRAINT_CHAIN = [{
                label: 'rear camera',
                constraints: {
                    facingMode: {
                        exact: 'environment'
                    }
                }
            },
            {
                label: 'front camera',
                constraints: {
                    facingMode: 'user'
                }
            },
            {
                label: 'default camera',
                constraints: true
            }
        ];

        function formatElapsed(startIso) {
            if (!startIso) return '0m';
            const start = new Date(String(startIso).replace(' ', 'T')).getTime();
            if (isNaN(start)) return '0m';
            const mins = Math.max(0, Math.floor((Date.now() - start) / 60000));
            const h = Math.floor(mins / 60),
                m = mins % 60;
            return h > 0 ? h + 'h ' + m + 'm' : m + 'm';
        }

        function updateTimers() {
            const st = document.getElementById('session-timer');
            if (st && st.dataset.start) st.textContent = formatElapsed(st.dataset.start);
            const td = document.getElementById('timeout-duration');
            if (td && openRecordTime) td.textContent = formatElapsed(openRecordTime);
        }
        updateTimers();
        setInterval(updateTimers, 30000);

        function showState(id) {
            ['scan-area', 'camera-error', 'success-card', 'already-card'].forEach(s => {
                const el = document.getElementById(s);
                if (el) el.classList.add('hidden');
            });
            const t = document.getElementById(id);
            if (t) t.classList.remove('hidden');
        }

        function showCameraError(message, detail) {
            const msgEl = document.getElementById('camera-error-message');
            if (msgEl && message) msgEl.textContent = message;
            const detailBox = document.getElementById('camera-error-detail');
            const detailTxt = document.getElementById('camera-error-detail-text');
            if (detail && detailBox && detailTxt) {
                detailTxt.textContent = detail;
                detailBox.classList.remove('hidden');
            }
            showState('camera-error');
        }

        async function testCameraPermission() {
            if (!window.isSecureContext) return {
                ok: false,
                message: 'Camera requires HTTPS.'
            };
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return {
                ok: false,
                message: 'Browser does not support camera.'
            };
            let lastError = null;
            for (const entry of CONSTRAINT_CHAIN) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: entry.constraints,
                        audio: false
                    });
                    stream.getTracks().forEach(t => t.stop());
                    return {
                        ok: true,
                        camLabel: entry.label
                    };
                } catch (e) {
                    lastError = e;
                    if (e.name === 'NotAllowedError') return {
                        ok: false,
                        message: 'Camera permission denied.',
                        detail: e.message
                    };
                    if (e.name === 'NotReadableError') return {
                        ok: false,
                        message: 'Camera in use by another app.',
                        detail: e.message
                    };
                }
            }
            return {
                ok: false,
                message: 'No camera detected.',
                detail: lastError?.message
            };
        }

        window.toggleManualEntry = function() {
            const panel = document.getElementById('manual-entry');
            const input = document.getElementById('manual-code-input');
            if (!panel) return;
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden') && input) setTimeout(() => input.focus(), 80);
        };

        window.showManualFromError = function() {
            showState('scan-area');
            const panel = document.getElementById('manual-entry');
            if (panel && panel.classList.contains('hidden')) window.toggleManualEntry();
        };

        // 🔐 Send the raw input to the server. No prefix manipulation.
        window.submitManualCode = function() {
            const input = document.getElementById('manual-code-input');
            if (!input) return;
            const raw = (input.value || '').trim();
            if (!raw) {
                input.focus();
                return;
            }
            processCode(raw);
        };

        async function processCode(text) {
            if (done) return;
            done = true;
            try {
                if (scanner) {
                    await scanner.stop();
                    scanner.clear();
                    scanner = null;
                }
            } catch (_) {}

            const fd = new FormData();
            fd.append('_csrf', csrf);
            fd.append('decodedText', text);

            try {
                const res = await fetch(base + '/time-in/scan', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await res.json();

                if (data.status === 'success' && data.record) {
                    const t = new Date(String(data.record.time_in || '').replace(' ', 'T'));
                    document.getElementById('success-text').textContent = 'Welcome back! Enjoy your workout.';
                    document.getElementById('success-time').textContent = t.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    document.getElementById('success-date').textContent = t.toLocaleDateString([], {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric'
                    });
                    showState('success-card');
                } else if (data.status === 'already' && data.record) {
                    const t = new Date(String(data.record.time_in || '').replace(' ', 'T'));
                    document.getElementById('already-text').textContent = "You timed in at " + t.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    }) + ".";
                    document.getElementById('already-timeout-btn').onclick = () => submitTimeOut(data.record.id);
                    showState('already-card');
                } else {
                    done = false;
                    alert(data.error || 'Invalid code. Try again.');
                    startScanner();
                }
            } catch (err) {
                done = false;
                alert('Network error. Try again.');
                startScanner();
            }
        }

        async function startScanner() {
            const loading = document.getElementById('camera-loading');
            const loadingSub = document.getElementById('camera-loading-sub');
            if (loading) loading.classList.remove('hidden');
            if (loadingSub) loadingSub.textContent = 'Allow camera access if prompted';

            const perm = await testCameraPermission();
            if (!perm.ok) {
                showCameraError(perm.message, perm.detail);
                return;
            }
            if (loadingSub) loadingSub.textContent = 'Using ' + perm.camLabel;

            const reader = document.getElementById('qr-reader');
            if (!reader || typeof Html5Qrcode === 'undefined') {
                showCameraError('Scanner library failed to load.');
                return;
            }
            reader.innerHTML = '';

            try {
                scanner = new Html5Qrcode('qr-reader');
            } catch (_) {
                showCameraError('Failed to initialize the scanner.');
                return;
            }

            const qrBoxFn = (w, h) => {
                const edge = Math.min(w, h);
                const size = Math.floor(edge * 0.7);
                return {
                    width: size,
                    height: size
                };
            };
            let lastError = null;

            for (const entry of CONSTRAINT_CHAIN) {
                try {
                    await scanner.start(entry.constraints, {
                        fps: 10,
                        qrbox: qrBoxFn,
                        aspectRatio: 1.0
                    }, (text) => processCode(text), () => {});
                    if (loading) loading.classList.add('hidden');
                    return;
                } catch (e) {
                    lastError = e;
                    try {
                        scanner.clear();
                    } catch (_) {}
                    reader.innerHTML = '';
                    try {
                        scanner = new Html5Qrcode('qr-reader');
                    } catch (_) {}
                }
            }
            showCameraError('Could not start any camera.', lastError?.message);
        }

        window.retryCamera = function() {
            showState('scan-area');
            const loading = document.getElementById('camera-loading');
            if (loading) loading.classList.remove('hidden');
            startScanner();
        };

        window.confirmTimeOut = function(recordId) {
            window._pendingTimeoutId = recordId;
            updateTimers();
            const m = document.getElementById('timeout-modal');
            if (m) m.classList.replace('hidden', 'flex');
        };
        window.closeTimeoutModal = function() {
            const m = document.getElementById('timeout-modal');
            if (m) m.classList.replace('flex', 'hidden');
        };

        async function submitTimeOut(recordId) {
            const fd = new FormData();
            fd.append('_csrf', csrf);
            fd.append('record_id', recordId);
            try {
                const res = await fetch(base + '/time-in/out', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                if (res.ok) {
                    window.closeTimeoutModal();
                    window.location.href = base + '/attendance';
                } else alert('Could not time out.');
            } catch (err) {
                alert('Network error.');
            }
        }

        document.getElementById('confirm-timeout-btn')?.addEventListener('click', () => {
            if (window._pendingTimeoutId) submitTimeOut(window._pendingTimeoutId);
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') window.closeTimeoutModal();
            if (e.key === 'Enter' && document.activeElement?.id === 'manual-code-input') window.submitManualCode();
        });

        document.getElementById('timeout-modal')?.addEventListener('click', e => {
            if (e.target.id === 'timeout-modal') window.closeTimeoutModal();
        });

        function boot() {
            if (!hasOpenSession) setTimeout(startScanner, 100);
            else showState('scan-area');
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
        else boot();
    })();
</script>