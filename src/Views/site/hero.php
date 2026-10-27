<section id="home" class="relative flex min-h-screen flex-col justify-center overflow-hidden bg-[#050505] pt-20">

    <!-- Background Image with PC Head Fix -->
    <div class="absolute inset-0 z-0">
        <!-- Changed to object-top and added a downward push (translate-y) so the head clears the navbar -->
        <img src="<?= url_e('/public/images/hero-athlete.png') ?>"
            alt="Athlete"
            class="h-[115%] w-full object-cover object-[75%_top] md:object-[85%_top] opacity-80 translate-y-8 md:translate-y-16 animate-[kenburns_20s_ease-in-out_infinite_alternate]"
            style="transform-origin: top right;">

        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/90 to-transparent"></div>
        <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#050505] to-transparent"></div>
    </div>

    <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 pt-10 pb-32">

        <!-- Eyebrow (Fades in first) -->
        <div class="flex items-center gap-2 mb-6 opacity-0 animate-[reveal-up_0.8s_ease-out_0.1s_forwards]">
            <span class="text-[#00A6A6] text-lg animate-pulse">✦</span>
            <span class="text-xs font-bold uppercase tracking-[0.2em] text-[#00A6A6]">Tabaco City, Albay</span>
        </div>

        <!-- Massive Headline with Staggered Kinetic Reveal -->
        <h1 class="font-heading text-7xl leading-[0.9] tracking-wide text-white sm:text-8xl lg:text-[130px]">

            <!-- Line 1: COMMIT TO BE -->
            <span class="block overflow-hidden pb-2">
                <span class="inline-block opacity-0 animate-[reveal-up_1s_cubic-bezier(0.2,0.8,0.2,1)_0.3s_forwards]">COMMIT</span>
                <span class="inline-block opacity-0 animate-[reveal-up_1s_cubic-bezier(0.2,0.8,0.2,1)_0.4s_forwards]">TO</span>
                <span class="inline-block opacity-0 animate-[reveal-up_1s_cubic-bezier(0.2,0.8,0.2,1)_0.5s_forwards]">BE</span>
            </span>

            <!-- Line 2: HEALTHY -->
            <span class="block overflow-hidden pb-4">
                <span class="inline-block text-[#00A6A6] glow-text opacity-0 animate-[reveal-up_1s_cubic-bezier(0.2,0.8,0.2,1)_0.7s_forwards]">HEALTHY</span>
            </span>
        </h1>

        <!-- Subtitle (Fades in next) -->
        <p class="mt-8 max-w-xl text-lg text-gray-300 leading-relaxed opacity-0 animate-[reveal-up_0.8s_ease-out_1.1s_forwards]">
            A complete gym management platform QR attendance, GCash payments, AI workout coaching, and a B2B partner portal for coaches and suppliers.
        </p>

        <!-- Buttons (Fades in next) -->
        <div class="mt-10 flex flex-wrap items-center gap-4 opacity-0 animate-[reveal-up_0.8s_ease-out_1.3s_forwards]">

            <!-- Primary Button with Shimmer -->
            <a href="<?= url_e('/register') ?>" class="group relative inline-flex items-center justify-center gap-3 overflow-hidden bg-[#00A6A6] px-8 py-4 text-sm font-bold uppercase tracking-wider text-white transition-all duration-300 hover:-translate-y-1 hover:bg-[#008c8c] hover:shadow-[0_0_30px_rgba(0,166,166,0.5)]">
                <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>
                <span class="relative">Get Started</span>
                <svg class="relative h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>

            <!-- Secondary Button -->
            <a href="#pricing" class="inline-flex items-center justify-center gap-3 border border-gray-600 bg-black/50 px-8 py-4 text-sm font-bold uppercase tracking-wider text-white backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-white hover:bg-white/10">
                View Pricing
            </a>
        </div>

        <!-- Stats Row (Fades in last) -->
        <div class="mt-20 grid max-w-3xl grid-cols-1 gap-8 border-t border-white/10 pt-8 sm:grid-cols-3 opacity-0 animate-[reveal-up_0.8s_ease-out_1.5s_forwards]">
            <div>
                <div class="font-heading text-4xl text-white">24/7</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-widest text-gray-400">Open Access</div>
            </div>
            <div>
                <div class="font-heading text-4xl text-white">100%</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-widest text-gray-400">Certified Coaches</div>
            </div>
            <div>
                <div class="font-heading text-4xl text-white">50+</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-widest text-gray-400">Weekly Classes</div>
            </div>
        </div>
    </div>

    <!-- Infinite Marquee Ticker -->
    <div class="absolute bottom-0 left-0 z-20 w-full overflow-hidden border-y border-white/10 bg-[#00A6A6] py-3">
        <div class="flex w-max animate-[marquee_20s_linear_infinite] items-center gap-8 px-4">
            <?php for ($i = 0; $i < 10; $i++): ?>
                <div class="flex items-center gap-4 text-sm font-bold uppercase tracking-widest text-white">
                    Commit To Be Healthy
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                    </svg>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Animations -->
    <style>
        /* Kinematic Blur Reveal */
        @keyframes reveal-up {
            0% {
                opacity: 0;
                transform: translateY(40px);
                filter: blur(10px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        /* Gentle Background Zoom */
        @keyframes kenburns {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.06);
            }
        }

        /* Marquee Scroll */
        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }
    </style>
</section>