<section id="home" class="hero">
    <div class="hero__media" aria-hidden="true">
        <img src="<?= url_e('/public/images/hero-athlete.png') ?>"
            alt=""
            width="1920" height="1080"
            fetchpriority="high" decoding="async">
        <span class="hero__aura"></span>
        <canvas class="hero__canvas" id="heroScene"></canvas>
    </div>

    <div class="wrap hero__content">
        <p class="eyebrow">Tabaco City, Albay</p>

        <h1 class="h1 hero__title">
            Commit to be<br>
            <span class="accent">healthy.</span>
        </h1>

        <p class="hero__lead">
            Coach-led strength training, QR check-in and flexible membership payments.
            Everything you need to train consistently, in one gym in the heart of Tabaco City.
        </p>

        <div class="hero__actions">
            <a href="<?= url_e('/register') ?>" class="btn btn--primary">
                Join now
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
            <a href="#pricing" class="btn btn--ghost">Explore memberships</a>
        </div>

        <div class="hero__proof">
            <span class="stars" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
            </span>
            <span><strong>4.9</strong> member rating &middot; trusted by <strong>500+</strong> locals</span>
        </div>
    </div>

    <a href="#features" class="hero__scroll" aria-label="Scroll to training">
        <span>Scroll</span>
        <i aria-hidden="true"></i>
    </a>

    <div class="wrap">
        <div class="stat-rail" aria-label="PowerMagz Gym at a glance">
            <div class="stat">
                <div class="stat__value"><span data-count="500">500</span><em>+</em></div>
                <div class="stat__label">Active members</div>
            </div>
            <div class="stat">
                <div class="stat__value"><span data-count="15">15</span></div>
                <div class="stat__label">Certified coaches</div>
            </div>
            <div class="stat">
                <div class="stat__value"><span data-count="50">50</span><em>+</em></div>
                <div class="stat__label">Weekly classes</div>
            </div>
            <div class="stat">
                <div class="stat__value">6AM&ndash;7PM</div>
                <div class="stat__label">Open daily</div>
            </div>
        </div>
    </div>
</section>
