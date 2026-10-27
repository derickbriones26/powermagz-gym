<section id="pricing" class="relative bg-[#0a0a0a] py-24 border-t border-white/5 overflow-hidden">
    <!-- Subtle Glowing Background -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-[400px] bg-[#00A6A6]/10 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Section Header -->
        <div class="text-center max-w-2xl mx-auto mb-20 reveal">
            <span class="inline-block rounded-full bg-white/5 border border-white/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-[#00A6A6] mb-4">
                Pricing Chart
            </span>
            <h2 class="font-heading text-5xl tracking-wide text-white sm:text-6xl">
                PREMIUM MEMBERSHIP PLAN
            </h2>
            <p class="mt-4 text-base text-gray-400">
                Simple, transparent pricing for every member. Choose the tier that fits your lifestyle and commit to be healthy.
            </p>
        </div>

        <!-- Pricing Cards Grid -->
        <div class="grid gap-8 lg:grid-cols-3 items-stretch">

            <!-- Card 1: Digital Tier -->
            <div class="group reveal flex flex-col h-full rounded-3xl border border-white/10 bg-[#111] p-8 shadow-xl transition-all duration-500 hover:border-white/20 hover:bg-[#151515] hover:-translate-y-2" style="transition-delay: 100ms;">

                <!-- Top Section (Fixed Height for alignment) -->
                <div class="min-h-[180px]">
                    <h3 class="font-heading text-3xl text-white">Digital Tier</h3>
                    <p class="mt-2 text-sm text-gray-400">Existing members digital access.</p>
                    <div class="mt-6 flex items-end gap-2">
                        <span class="font-heading text-6xl text-white">₱500</span>
                        <span class="text-sm text-gray-500 mb-2">/ month</span>
                    </div>
                </div>

                <!-- Features (flex-1 pushes the button to the bottom) -->
                <ul class="mt-8 space-y-4 text-sm text-gray-400 mb-8 flex-1">
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Digital workout system
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> QR time in/out
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Attendance calendar
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> AI workout recommender
                    </li>
                </ul>

                <!-- Button -->
                <a href="<?= url_e('/register') ?>" class="mt-auto block w-full rounded-full border-2 border-white/20 py-3 text-center text-sm font-bold text-white transition-all duration-300 group-hover:border-white group-hover:bg-white group-hover:text-black">
                    Purchase Now &rarr;
                </a>
            </div>

            <!-- Card 2: Standard Tier (Most Popular) -->
            <div class="group reveal relative flex flex-col h-full rounded-3xl border-2 border-[#00A6A6] bg-[#111] p-8 shadow-[0_0_40px_rgba(0,166,166,0.15)] lg:-translate-y-4 transition-all duration-500 hover:shadow-[0_0_60px_rgba(0,166,166,0.4)] hover:scale-[1.02]" style="transition-delay: 200ms;">

                <!-- Most Popular Badge with Pulse Animation -->
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-[#00A6A6] px-5 py-1.5 text-xs font-bold uppercase tracking-wider text-white shadow-[0_0_15px_rgba(0,166,166,0.6)] animate-pulse">
                    Most Popular
                </div>

                <!-- Top Section (Fixed Height for alignment) -->
                <div class="min-h-[180px]">
                    <h3 class="font-heading text-3xl text-[#00A6A6]">Standard Tier</h3>
                    <p class="mt-2 text-sm text-gray-400">Full gym access + GCash payments.</p>
                    <div class="mt-6 flex items-end gap-2">
                        <span class="font-heading text-6xl text-white">₱1,000</span>
                        <span class="text-sm text-gray-500 mb-2">/ initial</span>
                    </div>
                    <p class="text-xs text-[#00A6A6] mt-1 font-semibold">₱500 membership fee + ₱500 monthly</p>
                </div>

                <!-- Features -->
                <ul class="mt-6 space-y-4 text-sm text-gray-300 mb-8 flex-1">
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Full gym access
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> QR time in/out
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Attendance tracking
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Group class booking
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> GCash payments
                    </li>
                </ul>

                <!-- Button -->
                <a href="<?= url_e('/register') ?>" class="mt-auto block w-full rounded-full bg-[#00A6A6] py-3 text-center text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
                    Purchase Now &rarr;
                </a>
            </div>

            <!-- Card 3: Premium Tier -->
            <div class="group reveal flex flex-col h-full rounded-3xl border border-white/10 bg-[#111] p-8 shadow-xl transition-all duration-500 hover:border-white/20 hover:bg-[#151515] hover:-translate-y-2" style="transition-delay: 300ms;">

                <!-- Top Section (Fixed Height for alignment) -->
                <div class="min-h-[180px]">
                    <h3 class="font-heading text-3xl text-white">Premium Tier</h3>
                    <p class="mt-2 text-sm text-gray-400">All-inclusive + personal coaching.</p>
                    <div class="mt-6 flex items-end gap-2">
                        <span class="font-heading text-6xl text-white">₱2,000</span>
                        <span class="text-sm text-gray-500 mb-2">/ month</span>
                    </div>
                </div>

                <!-- Features -->
                <ul class="mt-8 space-y-4 text-sm text-gray-400 mb-8 flex-1">
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Everything in Standard
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Personal coaching
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Nutrition guidance
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> Priority class booking
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-[#00A6A6] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg> AI coach 24/7
                    </li>
                </ul>

                <!-- Button -->
                <a href="<?= url_e('/register') ?>" class="mt-auto block w-full rounded-full border-2 border-white/20 py-3 text-center text-sm font-bold text-white transition-all duration-300 group-hover:border-white group-hover:bg-white group-hover:text-black">
                    Purchase Now &rarr;
                </a>
            </div>

        </div>
    </div>
</section>