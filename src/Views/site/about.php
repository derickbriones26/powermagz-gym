<section id="about" class="relative bg-[#050505] py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Text & Vision Section -->
        <div class="grid gap-12 lg:grid-cols-2 items-center">
            <div>
                <span class="inline-block rounded-full bg-white/5 border border-white/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-[#00A6A6] mb-6">
                    About Us
                </span>
                <h2 class="font-heading text-5xl tracking-wide text-white sm:text-6xl lg:text-7xl leading-[0.95]">
                    THE POWER <br>BEHIND YOUR <br><span class="text-[#00A6A6]">VISION</span>
                </h2>
            </div>

            <div class="flex flex-col justify-between h-full">
                <p class="text-base sm:text-lg text-gray-400 leading-relaxed max-w-lg">
                    PowerMagz is committed to helping you reach your fitness and wellness goals with expert coaching, personalized workout plans, and tailored nutrition guidance.
                </p>

                <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-8 border-t border-white/10 pt-8">
                    <div>
                        <h4 class="font-heading text-xl tracking-wide text-white">OUR VISION</h4>
                        <p class="mt-2 text-sm text-gray-500 leading-relaxed">
                            Empowering everyone to embrace a stronger, healthier, and fulfilling lifestyle through fitness and wellness.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-heading text-xl tracking-wide text-white">OUR MISSION</h4>
                        <p class="mt-2 text-sm text-gray-500 leading-relaxed">
                            Empowering individuals with expert coaching and guidance that creates strong, lasting habits.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Banner -->
        <div class="mt-20 relative group rounded-3xl overflow-hidden border border-white/10 bg-[#111]">
            <img src="<?= url_e('/public/images/about-video-bg.png') ?>" alt="Gym workout video background" class="w-full h-[400px] lg:h-[600px] object-cover transition-transform duration-1000 group-hover:scale-105 opacity-80">
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center transition-colors duration-500 group-hover:bg-black/50">
                <button type="button"
                    onclick="openVideoModal('https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0&modestbranding=1')"
                    aria-label="Play gym video"
                    class="relative flex aspect-square h-20 w-20 lg:h-28 lg:w-28 items-center justify-center rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white transition-all duration-300 hover:scale-110 hover:bg-[#00A6A6] hover:border-[#00A6A6] hover:shadow-[0_0_50px_rgba(0,166,166,0.6)] z-10 cursor-pointer">
                    <!-- Subtle ping ring -->
                    <span class="absolute inset-0 rounded-full bg-white/20 animate-ping opacity-30"></span>
                    <svg class="relative h-8 w-8 lg:h-12 lg:w-12 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>

    <!-- =========================================================
         VIDEO MODAL
         Full-screen overlay, loads the YouTube iframe only when
         opened, clears the src when closed so audio doesn't linger.
         ========================================================= -->
    <div id="videoModal"
        role="dialog"
        aria-modal="true"
        aria-label="Gym video player"
        class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/90 p-4 backdrop-blur-md">

        <div class="relative w-full max-w-5xl">

            <!-- Close button -->
            <button type="button"
                onclick="closeVideoModal()"
                aria-label="Close video"
                class="absolute -right-2 -top-12 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition-all duration-300
                       hover:scale-110 hover:bg-[#00A6A6] md:-right-12 md:top-0">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Player -->
            <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-black shadow-2xl" style="aspect-ratio: 16 / 9;">
                <iframe id="videoFrame"
                    class="absolute inset-0 h-full w-full"
                    src=""
                    title="PowerMagz Gym video"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>

        </div>
    </div>
</section>

<script>
    (function() {
        'use strict';

        const modal = document.getElementById('videoModal');
        const frame = document.getElementById('videoFrame');

        if (!modal || !frame) return;

        window.openVideoModal = function(url) {
            if (!url) return;
            frame.src = url;
            modal.classList.replace('hidden', 'flex');
            document.body.style.overflow = 'hidden';

            // Focus the close button for keyboard users
            setTimeout(() => {
                const closeBtn = modal.querySelector('button[aria-label="Close video"]');
                if (closeBtn) closeBtn.focus();
            }, 120);
        };

        window.closeVideoModal = function() {
            // Clear the src so the audio stops immediately
            frame.src = '';
            modal.classList.replace('flex', 'hidden');
            document.body.style.overflow = '';
        };

        // Backdrop click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) window.closeVideoModal();
        });

        // Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('flex')) {
                window.closeVideoModal();
            }
        });
    })();
</script>