<section id="contact" class="relative bg-[#050505] py-16 sm:py-20 lg:py-24 overflow-hidden border-t border-white/5">

    <!-- Animated Background — scales progressively from mobile to desktop -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden" aria-hidden="true">

        <!-- Outer Slow Spinning Ring (Reverse) -->
        <div class="absolute w-[400px] h-[400px] sm:w-[600px] sm:h-[600px] lg:w-[800px] lg:h-[800px] rounded-full border border-[#00A6A6]/10 animate-[spin-reverse_60s_linear_infinite]"></div>

        <!-- Inner Fast Spinning Dashed Ring -->
        <div class="absolute w-[260px] h-[260px] sm:w-[400px] sm:h-[400px] lg:w-[500px] lg:h-[500px] rounded-full border border-dashed border-[#00A6A6]/20 animate-[spin-slow_40s_linear_infinite]"></div>

        <!-- Pulsing Center Ring (Ripple) -->
        <div class="absolute w-[160px] h-[160px] sm:w-[200px] sm:h-[200px] lg:w-[250px] lg:h-[250px] rounded-full border border-[#00A6A6]/30 animate-[pulse-ring_4s_ease-out_infinite]"></div>

        <!-- Center Glow Orb -->
        <div class="absolute w-[240px] h-[240px] sm:w-[380px] sm:h-[380px] lg:w-[500px] lg:h-[500px] bg-[#00A6A6]/10 blur-[60px] sm:blur-[80px] lg:blur-[100px] rounded-full animate-[pulse-glow_6s_ease-in-out_infinite_alternate]"></div>
    </div>

    <!-- Content container — properly constrained and centered -->
    <div class="relative z-10 mx-auto max-w-3xl px-5 sm:px-6 lg:px-8 text-center flex flex-col items-center">

        <!-- Eyebrow Text -->
        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-[0.3em] sm:tracking-[0.4em] text-[#00A6A6] mb-5 sm:mb-6">
            Your First Step
        </span>

        <!-- Headline — starts at text-4xl (36px) to fit "GET STARTED" on 320px screens -->
        <h2 class="font-heading text-4xl sm:text-6xl lg:text-7xl leading-[1.05] sm:leading-[0.95] tracking-wide text-white mb-5 sm:mb-6 drop-shadow-2xl">
            GET STARTED <br>
            <span class="text-[#00A6A6] glow-text">TODAY!</span>
        </h2>

        <!-- Subtitle — narrow on mobile, wider on larger screens -->
        <p class="w-full max-w-xs sm:max-w-xl text-sm sm:text-base text-gray-400 leading-relaxed mb-8 sm:mb-10">
            Ready to commit to a healthier lifestyle? Join PowerMagz Gym now and get instant access to our complete gym management platform, expert coaches, and AI workout tracking.
        </p>

        <!-- CTA Button — full width on mobile, auto on desktop -->
        <a href="<?= url_e('/register') ?>"
            class="group relative inline-flex w-full sm:w-auto items-center justify-center gap-2 overflow-hidden rounded-full bg-[#00A6A6] px-8 py-4 min-h-[56px] text-sm sm:text-base font-bold uppercase tracking-[0.2em] sm:tracking-widest text-white transition-all duration-500
                hover:-translate-y-1 hover:bg-[#008c8c]
                shadow-[0_0_20px_rgba(0,166,166,0.4)] hover:shadow-[0_0_50px_rgba(0,166,166,0.7)]">

            <!-- Shimmer sweep -->
            <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>

            <span class="relative">Start Your Journey</span>

            <svg class="relative h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>

    </div>

    <!-- Custom Animations -->
    <style>
        @keyframes spin-slow {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes spin-reverse {
            from {
                transform: rotate(360deg);
            }

            to {
                transform: rotate(0deg);
            }
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 0.6;
            }

            100% {
                transform: scale(1.6);
                opacity: 0;
            }
        }

        @keyframes pulse-glow {
            0% {
                opacity: 0.08;
                transform: scale(0.9);
            }

            100% {
                opacity: 0.2;
                transform: scale(1.1);
            }
        }

        /* Respect user preference for reduced motion */
        @media (prefers-reduced-motion: reduce) {

            .animate-\[spin-slow_40s_linear_infinite\],
            .animate-\[spin-reverse_60s_linear_infinite\],
            .animate-\[pulse-ring_4s_ease-out_infinite\],
            .animate-\[pulse-glow_6s_ease-in-out_infinite_alternate\] {
                animation: none !important;
            }
        }
    </style>
</section>