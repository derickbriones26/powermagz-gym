</main>

<?php require BASE_PATH . '/src/Views/site/faq_widget.php'; ?>

<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="#home" class="brand" aria-label="PowerMagz Gym — back to top">
                    <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="" width="42" height="42" loading="lazy">
                    <span class="brand__name">POWERMAGZ<span>GYM</span></span>
                </a>
                <p>
                    Coach-led strength training, QR check-in and flexible payments —
                    modern gym management for Tabaco City, Albay.
                </p>
                <div class="footer-social">
                    <a href="https://web.facebook.com/zabbyandamber" target="_blank" rel="noopener noreferrer" aria-label="PowerMagz Gym on Facebook">
                        <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M22 12a10 10 0 10-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 2.9h-2.3v7A10 10 0 0022 12z" />
                        </svg>
                    </a>
                    <a href="mailto:powermagzgym@gmail.com" aria-label="Email PowerMagz Gym">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </a>
                    <a href="tel:+639618041523" aria-label="Call PowerMagz Gym">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h3>Explore</h3>
                <ul>
                    <li><a href="#features">Training</a></li>
                    <li><a href="#core">The system</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">Inside PowerMagz</a></li>
                    <li><a href="#pricing">Membership</a></li>
                    <li><a href="#visit">Visit us</a></li>
                    <li><a href="#join">Join PowerMagz</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Members</h3>
                <ul>
                    <li><a href="<?= url_e('/login') ?>">Member login</a></li>
                    <li><a href="<?= url_e('/register') ?>">Create account</a></li>
                    <li><a href="<?= url_e('/partner-dashboard') ?>">Partner portal</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>Contact</h3>
                <ul>
                    <li><a href="tel:+639618041523">0961 804 1523</a></li>
                    <li><a href="mailto:powermagzgym@gmail.com">powermagzgym@gmail.com</a></li>
                    <li><a href="https://www.google.com/maps/search/?api=1&query=Powermagz+Gym+Tabaco+City+Albay" target="_blank" rel="noopener noreferrer">Zone 4, Matagbac, Tabaco City</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> PowerMagz Gym. All rights reserved.</p>
            <p class="footer-bottom__links">
                <a href="<?= url_e('/privacy') ?>">Privacy Policy</a>
                <span aria-hidden="true">&middot;</span>
                <a href="<?= url_e('/terms') ?>">Terms &amp; Conditions</a>
                <span aria-hidden="true">&middot;</span>
                Open daily &middot; 6:00 AM &ndash; 7:00 PM
            </p>
        </div>
    </div>
</footer>

<script>
    /* One-time scroll reveal: content enters as it reaches the viewport,
       then stays put. Skipped entirely under reduced motion or no IO. */
    (function() {
        var items = document.querySelectorAll('[data-reveal]');
        if (!items.length) return;

        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduceMotion || !('IntersectionObserver' in window)) {
            items.forEach(function(el) { el.classList.add('is-in'); });
            return;
        }

        var reveal = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-in');
                reveal.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -10% 0px'
        });

        items.forEach(function(el) { reveal.observe(el); });
    })();
</script>

<!-- FAQ assistant — verified answers, no network -->
<script defer src="<?= url_e('/public/js/landing-faq.js') ?>?v=<?= (int) @filemtime(BASE_PATH . '/public/js/landing-faq.js') ?>"></script>

<!-- Offline tracker & service worker -->
<script src="<?= url_e('/public/js/offline.js') ?>"></script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?= url_e('/public/sw.js') ?>').catch(function() {
                /* Offline support is optional; never block the page. */
            });
        });
    }
</script>
</body>

</html>
