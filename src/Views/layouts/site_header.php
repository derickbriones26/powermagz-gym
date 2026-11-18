<?php
$user = current_user();
$dash = null;
if ($user) {
    $dash = match ($user['role']) {
        'admin'   => '/admin-dashboard',
        'partner' => '/partner-dashboard',
        default   => '/home',
    };
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle ?? 'Powermagz Gym — Commit to Be Healthy') ?></title>
    <meta name="description" content="PowerMagz Gym in Tabaco City, Albay — coach-led strength training, QR check-in, flexible payments and smart progress tracking in one membership.">
    <meta name="theme-color" content="#060707">
    <script>
        (function() {
            var root = document.documentElement;
            root.classList.add('js');
            // The brand reveal belongs to opening the system, not to every visit.
            // sessionStorage keeps it once per tab/session: coming back from
            // /login (or reloading) skips it; a fresh open/restart plays it.
            try {
                if (window.sessionStorage.getItem('pmgz-intro-seen') === '1') {
                    root.classList.add('intro-off');
                } else {
                    root.classList.add('intro-live');
                    window.sessionStorage.setItem('pmgz-intro-seen', '1');
                    // Never leave the page locked if a later script fails.
                    window.setTimeout(function() {
                        root.classList.remove('intro-live');
                    }, 4200);
                }
            } catch (e) {}
        })();
    </script>
    <link rel="icon" type="image/png" href="<?= url_e('/public/images/powermagz_gym.png') ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="PowerMagz Gym">
    <meta property="og:title" content="PowerMagz Gym — Commit to Be Healthy">
    <meta property="og:description" content="Coach-led strength training, QR check-in and flexible payments. Train in the heart of Tabaco City.">
    <meta property="og:image" content="<?= url_e('/public/images/hero-athlete.png') ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="preload" as="image" href="<?= url_e('/public/images/hero-athlete.png') ?>" fetchpriority="high">
    <link rel="stylesheet" href="<?= url_e('/public/css/site.css') ?>?v=<?= (int) @filemtime(BASE_PATH . '/public/css/site.css') ?>">
    <script defer src="<?= url_e('/public/js/landing-webgl.js') ?>?v=<?= (int) @filemtime(BASE_PATH . '/public/js/landing-webgl.js') ?>"></script>
    <script defer src="<?= url_e('/public/js/landing.js') ?>?v=<?= (int) @filemtime(BASE_PATH . '/public/js/landing.js') ?>"></script>

    <!-- Local business structured data -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "ExerciseGym",
            "name": "PowerMagz Gym",
            "description": "Coach-led strength training, QR check-in and flexible payments in Tabaco City, Albay.",
            "image": "<?= url_e('/public/images/powermagz_gym.png') ?>",
            "telephone": "+63 961 804 1523",
            "email": "powermagzgym@gmail.com",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Zone 4, Matagbac",
                "addressLocality": "Tabaco City",
                "addressRegion": "Albay",
                "addressCountry": "PH"
            },
            "openingHoursSpecification": {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
                "opens": "06:00",
                "closes": "19:00"
            },
            "sameAs": ["https://web.facebook.com/zabbyandamber"]
        }
    </script>
</head>

<body>
    <!-- Landing reveal: a count-in, then the curtain splits around the wordmark. -->
    <div class="intro" id="intro" aria-hidden="true">
        <span class="intro__panel intro__panel--top"></span>
        <span class="intro__panel intro__panel--bottom"></span>

        <span class="intro__veil">
            <span class="intro__glow"></span>
            <span class="intro__grid"></span>
            <span class="intro__orbit"></span>
        </span>

        <div class="intro__stage">
            <svg class="intro__word" viewBox="0 0 1200 250" role="presentation" focusable="false">
                <defs>
                    <linearGradient id="introWordFill" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#ffffff" />
                        <stop offset="0.55" stop-color="#22c9c9" />
                        <stop offset="1" stop-color="#00a6a6" />
                    </linearGradient>
                </defs>
                <text class="intro__word-outline" x="0" y="205" textLength="1200" lengthAdjust="spacingAndGlyphs">POWERMAGZ</text>
                <text class="intro__word-fill" x="0" y="205" textLength="1200" lengthAdjust="spacingAndGlyphs">POWERMAGZ</text>
            </svg>

            <span class="intro__sub"><span class="intro__bar"></span>GYM<span class="intro__bar"></span></span>

            <div class="intro__loader">
                <span class="intro__loader-label">Preparing your session</span>
                <span class="intro__count" id="introCount">00</span>
                <span class="intro__rule"><span class="intro__rule-fill"></span></span>
            </div>
        </div>

        <span class="intro__meta intro__meta--tl">PowerMagz Gym</span>
        <span class="intro__meta intro__meta--br">Tabaco City &middot; Albay</span>
    </div>

    <!-- Scroll progress: exact page position, brand teal. -->
    <div class="scroll-progress" aria-hidden="true">
        <span class="scroll-progress__fill" id="scrollProgress"></span>
    </div>

    <a href="#main-content" class="skip-link">Skip to content</a>

    <header class="site-header" id="siteHeader">
        <div class="wrap nav">
            <a href="#home" class="brand" aria-label="PowerMagz Gym — back to top">
                <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="" width="42" height="42">
                <span class="brand__name">POWERMAGZ<span>GYM</span></span>
            </a>

            <nav class="nav__links" aria-label="Primary">
                <a href="#features" class="nav__link" data-nav>Training</a>
                <a href="#core" class="nav__link" data-nav>System</a>
                <a href="#services" class="nav__link" data-nav>Services</a>
                <a href="#about" class="nav__link" data-nav>About</a>
                <a href="#pricing" class="nav__link" data-nav>Membership</a>
                <a href="#visit" class="nav__link" data-nav>Visit</a>
            </nav>

            <div class="nav__actions">
                <?php if ($user): ?>
                    <a href="<?= url_e($dash) ?>" class="btn btn--primary nav__cta">Dashboard</a>
                <?php else: ?>
                    <a href="<?= url_e('/login') ?>" class="nav__login">Log in</a>
                    <a href="<?= url_e('/register') ?>" class="btn btn--primary nav__cta">Join now</a>
                <?php endif; ?>
            </div>

            <button type="button" class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="mobileMenu" aria-label="Open menu">
                <svg class="icon-open" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
                <svg class="icon-close" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </header>

    <nav class="mobile-menu" id="mobileMenu" aria-label="Mobile" aria-hidden="true">
        <a href="#features" data-nav>Training</a>
        <a href="#core" data-nav>The system</a>
        <a href="#services" data-nav>Services</a>
        <a href="#about" data-nav>Inside PowerMagz</a>
        <a href="#pricing" data-nav>Membership</a>
        <a href="#visit" data-nav>Visit us</a>

        <div class="mobile-menu__actions">
            <?php if ($user): ?>
                <a href="<?= url_e($dash) ?>" class="btn btn--primary btn--full">Go to dashboard</a>
            <?php else: ?>
                <a href="<?= url_e('/login') ?>" class="btn btn--ghost btn--full">Log in</a>
                <a href="<?= url_e('/register') ?>" class="btn btn--primary btn--full">Join now</a>
            <?php endif; ?>
        </div>
    </nav>

    <main id="main-content">

