<?php
$user = current_user();
$name = $user['full_name'] ?: 'Member';
$initials = strtoupper(substr($name, 0, 1));

// Generate QR code for the member's ID
// We use their user ID to generate a unique code
$qrData = 'PMGZ-MEMBER-' . $user['id'];
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData) . '&color=000000&bgcolor=ffffff&margin=0';
?>

<div id="digitalIdModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/90 p-4 backdrop-blur-md">

    <div class="w-full max-w-sm flex flex-col items-center gap-6">

        <!-- The ID Card -->
        <div class="relative w-full overflow-hidden rounded-3xl bg-gradient-to-br from-[#0a0a0a] via-[#111] to-[#00A6A6]/20 border border-white/10 p-6 shadow-[0_20px_50px_-15px_rgba(0,166,166,0.4)]">

            <!-- Decorative Elements -->
            <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-[#00A6A6]/20 blur-2xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 20px 20px;"></div>

            <!-- Top Row: Logo -->
            <div class="relative flex items-center justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-2">
                    <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="PowerMagz" class="h-8 w-8 rounded-full">
                    <div class="font-heading text-lg tracking-widest text-white">POWERMAGZ</div>
                </div>
                <div class="text-[9px] font-bold uppercase tracking-widest text-[#00A6A6]">Member ID</div>
            </div>

            <!-- Middle: QR Code -->
            <div class="relative mt-6 flex justify-center">
                <div class="rounded-2xl bg-white p-3 shadow-[0_0_30px_rgba(0,166,166,0.2)]">
                    <img src="<?= e($qrUrl) ?>" alt="Digital QR ID" class="h-40 w-40 object-contain">
                </div>
            </div>

            <!-- Bottom: User Info -->
            <div class="relative mt-6 text-center">
                <div class="font-heading text-3xl tracking-wide text-white"><?= e(strtoupper($name)) ?></div>
                <div class="mt-1 font-mono text-[10px] text-[#00A6A6]"><?= e(substr($user['id'], 0, 12)) ?></div>
                <div class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white backdrop-blur-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#00A6A6] animate-pulse"></span>
                    Active Member
                </div>
            </div>
        </div>

        <!-- Close Button -->
        <button type="button" onclick="closeDigitalId()" class="group flex items-center gap-2 rounded-full bg-white/10 px-6 py-3 text-sm font-bold text-white backdrop-blur-md transition-all hover:bg-white/20 hover:scale-105">
            <svg class="w-4 h-4 transition-transform group-hover:-rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Close
        </button>
    </div>
</div>

<script>
    function openDigitalId() {
        const m = document.getElementById('digitalIdModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closeDigitalId() {
        const m = document.getElementById('digitalIdModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = ''; // Restore background scrolling
    }

    // Close on backdrop click
    document.getElementById('digitalIdModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDigitalId();
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDigitalId();
    });
</script>