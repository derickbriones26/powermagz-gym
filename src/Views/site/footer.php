    </main> <!-- Close main tag from site_header.php -->

    <!-- Modern 2026 Footer -->
    <footer class="bg-[#0a0a0a] border-t border-white/10 pt-20 pb-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-4">

                <!-- Brand -->
                <div class="col-span-1 lg:col-span-1">
                    <a href="#home" class="flex items-center gap-3 group">
                        <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="Powermagz Gym" class="h-10 w-10 rounded-full group-hover:scale-105 transition-transform duration-300">
                        <div class="leading-none">
                            <div class="font-heading text-2xl tracking-widest text-white">POWERMAGZ</div>
                            <div class="mt-1 text-[9px] font-bold tracking-[0.3em] text-[#00A6A6]">GYM</div>
                        </div>
                    </a>
                    <p class="mt-6 text-sm text-gray-500 leading-relaxed">
                        Modern gym management for Tabaco City. Commit to be healthy with expert coaching, AI-driven tracking, and seamless digital payments.
                    </p>
                </div>

                <!-- Navigation -->
                <div>
                    <h4 class="font-heading text-xl tracking-wide text-white mb-6">NAVIGATION</h4>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li><a href="#home" class="hover:text-[#00A6A6] transition-colors">Home</a></li>
                        <li><a href="#features" class="hover:text-[#00A6A6] transition-colors">Training Tracks</a></li>
                        <li><a href="#about" class="hover:text-[#00A6A6] transition-colors">About Us</a></li>
                        <li><a href="#pricing" class="hover:text-[#00A6A6] transition-colors">Pricing</a></li>
                    </ul>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-heading text-xl tracking-wide text-white mb-6">QUICK LINKS</h4>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li><a href="<?= url_e('/login') ?>" class="hover:text-[#00A6A6] transition-colors">Member Login</a></li>
                        <li><a href="<?= url_e('/register') ?>" class="hover:text-[#00A6A6] transition-colors">Create Account</a></li>
                        <li><a href="<?= url_e('/partner-dashboard') ?>" class="hover:text-[#00A6A6] transition-colors">Partner Portal</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="font-heading text-xl tracking-wide text-white mb-6">CONTACT INFO</h4>
                    <ul class="space-y-4 text-sm text-gray-400">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-[#00A6A6] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Zone 4, Matagbac, Tabaco City, Albay
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-[#00A6A6] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            admin@powermagz.ph
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-[#00A6A6] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            0961 804 1523
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Footer Bottom -->
            <div class="mt-16 border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-gray-600">
                <div>
                    &copy; <?= date('Y') ?> PowerMagz Gym. All rights reserved.
                </div>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-[#00A6A6] transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-[#00A6A6] transition-colors">Terms & Conditions</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global Scripts -->
    <script>
        // 1. Smooth Scroll for all anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // 2. Scroll Reveal Animations
        document.addEventListener('DOMContentLoaded', () => {
            const reveals = document.querySelectorAll('.reveal');

            const revealOnScroll = () => {
                const windowHeight = window.innerHeight;
                const elementVisible = 100;

                reveals.forEach((reveal) => {
                    const elementTop = reveal.getBoundingClientRect().top;
                    if (elementTop < windowHeight - elementVisible) {
                        reveal.classList.add('active');
                    }
                });
            };

            window.addEventListener('scroll', revealOnScroll);
            revealOnScroll(); // Trigger on load
        });
    </script>
    </body>

    </html>