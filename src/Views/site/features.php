<section id="features" class="relative bg-[#0a0a0a] py-24 border-t border-white/5">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-12">
            <h2 class="font-heading text-5xl tracking-wide text-white sm:text-6xl lg:text-7xl leading-[0.9]">
                BUILT FOR YOUR <span class="text-[#00A6A6]">SUCCESS.</span>
            </h2>
            <p class="text-base sm:text-lg text-gray-400 max-w-md lg:text-right">
                Three core pillars. One standard: a seamless, tech-driven fitness experience.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3 items-stretch">
            <!-- Feature 1 -->
            <div class="group flex flex-col h-full rounded-2xl bg-[#111] border border-white/5 transition-all duration-500 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_40px_-10px_rgba(0,166,166,0.2)] overflow-hidden">
                <div class="relative h-64 overflow-hidden">
                    <img src="<?= url_e('/public/images/training-1.png') ?>" alt="QR Attendance" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute top-4 right-6 font-heading text-7xl text-white/10 group-hover:text-[#00A6A6]/30 transition-colors duration-500">01</div>
                    <div class="absolute bottom-4 left-6 flex h-10 w-10 items-center justify-center rounded-full bg-[#00A6A6] text-white shadow-lg">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-1">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6] mb-2">Track Progress</span>
                    <h3 class="font-heading text-3xl tracking-wide text-white">QR Time In/Out</h3>
                    <p class="mt-4 text-sm text-gray-400 leading-relaxed flex-1">Log your sessions instantly. Track your streaks, view your attendance calendar, and build unbreakable habits.</p>
                    <div class="mt-8 pt-4 border-t border-white/10">
                        <a href="<?= url_e('/register') ?>" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-white transition-colors hover:text-[#00A6A6] group-hover:gap-3">Explore Feature <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg></a>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="group flex flex-col h-full rounded-2xl bg-[#111] border border-white/5 transition-all duration-500 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_40px_-10px_rgba(0,166,166,0.2)] overflow-hidden">
                <div class="relative h-64 overflow-hidden">
                    <img src="<?= url_e('/public/images/training-2.png') ?>" alt="AI Coaching" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute top-4 right-6 font-heading text-7xl text-white/10 group-hover:text-[#00A6A6]/30 transition-colors duration-500">02</div>
                    <div class="absolute bottom-4 left-6 flex h-10 w-10 items-center justify-center rounded-full bg-[#00A6A6] text-white shadow-lg">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-1">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6] mb-2">Train Smarter</span>
                    <h3 class="font-heading text-3xl tracking-wide text-white">AI & GCash</h3>
                    <p class="mt-4 text-sm text-gray-400 leading-relaxed flex-1">Get personalized workout recommendations from our 24/7 AI coach. Pay your dues instantly and securely.</p>
                    <div class="mt-8 pt-4 border-t border-white/10">
                        <a href="<?= url_e('/register') ?>" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-white transition-colors hover:text-[#00A6A6] group-hover:gap-3">Explore Feature <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg></a>
                    </div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="group flex flex-col h-full rounded-2xl bg-[#111] border border-white/5 transition-all duration-500 hover:border-[#00A6A6]/40 hover:shadow-[0_10px_40px_-10px_rgba(0,166,166,0.2)] overflow-hidden">
                <div class="relative h-64 overflow-hidden">
                    <img src="<?= url_e('/public/images/training-3.png') ?>" alt="Partner Portal" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute top-4 right-6 font-heading text-7xl text-white/10 group-hover:text-[#00A6A6]/30 transition-colors duration-500">03</div>
                    <div class="absolute bottom-4 left-6 flex h-10 w-10 items-center justify-center rounded-full bg-[#00A6A6] text-white shadow-lg">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-1">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#00A6A6] mb-2">Grow Together</span>
                    <h3 class="font-heading text-3xl tracking-wide text-white">B2B Portal</h3>
                    <p class="mt-4 text-sm text-gray-400 leading-relaxed flex-1">A dedicated space for coaches and suppliers. Manage clients, track referral sales, and request payouts.</p>
                    <div class="mt-8 pt-4 border-t border-white/10">
                        <a href="<?= url_e('/partner-dashboard') ?>" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-white transition-colors hover:text-[#00A6A6] group-hover:gap-3">Partner Login <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW BUTTON ADDED HERE -->
        <div class="mt-16 text-center">
            <a href="#pricing" class="inline-flex items-center gap-2 rounded-full border-2 border-[#00A6A6] bg-transparent px-8 py-4 text-sm font-bold uppercase tracking-wider text-[#00A6A6] transition-all duration-300 hover:bg-[#00A6A6] hover:text-white hover:shadow-[0_0_20px_rgba(0,166,166,0.4)]">
                View Membership Tiers
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </a>
        </div>

    </div>
</section>