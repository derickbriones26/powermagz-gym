<!-- Global Loading Screen (2026 UI) -->
<div id="pmgz-loader" class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-[#050505] transition-opacity duration-500 ease-in-out">

    <!-- Ambient Teal Glow (Pulsing) -->
    <div class="absolute w-[300px] h-[300px] bg-[#00A6A6]/20 blur-[100px] rounded-full animate-pulse"></div>

    <div class="relative z-10 flex flex-col items-center">

        <!-- Orbital Rings + Logo Center -->
        <div class="relative flex items-center justify-center w-32 h-32">

            <!-- Outer dashed ring (slow spin) -->
            <div class="absolute inset-0 rounded-full border border-dashed border-[#00A6A6]/40 animate-[spin_4s_linear_infinite]"></div>

            <!-- Inner glowing solid ring (reverse spin) -->
            <div class="absolute inset-2 rounded-full border-2 border-transparent border-t-[#00A6A6] border-r-[#00A6A6]/30 animate-[spin_2s_ease-in-out_infinite_reverse]"></div>

            <!-- Center Logo -->
            <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-[#0a0a0a] ring-2 ring-[#00A6A6]/50 shadow-[0_0_30px_rgba(0,166,166,0.4)]">
                <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="PowerMagz" class="h-10 w-10 rounded-full">
            </div>

        </div>

        <!-- Brand Text (Glassmorphism style) -->
        <div class="mt-8 flex flex-col items-center gap-1">
            <div class="font-heading text-2xl tracking-[0.2em] text-white">POWERMAGZ</div>
            <div class="text-[9px] font-bold tracking-[0.5em] text-[#00A6A6] uppercase">Gym</div>
        </div>

        <!-- Sleek Loading Bar (Shimmer) -->
        <div class="mt-8 h-1 w-48 overflow-hidden rounded-full bg-white/5">
            <div class="h-full w-1/2 rounded-full bg-gradient-to-r from-transparent via-[#00A6A6] to-transparent animate-[shimmer_1.5s_infinite]"></div>
        </div>

        <!-- Modern Loading Text -->
        <div class="mt-4 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500 animate-pulse">
            Loading...
        </div>

    </div>
</div>

<!-- Custom Keyframes to guarantee animations work -->
<style>
    @keyframes shimmer {
        0% {
            transform: translateX(-150%);
        }

        100% {
            transform: translateX(250%);
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>

<!-- Smart Auto-hide Script -->
<script>
    (function() {
        const loader = document.getElementById('pmgz-loader');
        if (!loader) return;

        // Make the fade-out transition smooth
        loader.style.transition = 'opacity 0.4s ease-out';
        const startTime = Date.now();

        // Function to hide the loader
        function hideLoader() {
            loader.style.opacity = '0';
            setTimeout(() => loader.remove(), 400);
        }

        // 1. Wait for the page to fully load (HTML, CSS, JS, Images)
        window.addEventListener('load', function() {
            const elapsedTime = Date.now() - startTime;
            const minimumDisplayTime = 800; // Always show the loader for at least 0.8s

            // If the page loaded very fast (e.g. 100ms), wait the remaining 700ms
            // If the internet was slow (e.g. 3 seconds), hide it immediately
            const remainingTime = Math.max(0, minimumDisplayTime - elapsedTime);

            setTimeout(hideLoader, remainingTime);
        });

        // 2. Failsafe: If the internet is completely dead and the page hangs forever,
        // force the loader to hide after 10 seconds so it's not permanently stuck.
        setTimeout(function() {
            if (loader && loader.style.opacity !== '0') {
                hideLoader();
            }
        }, 10000);
    })();
</script>