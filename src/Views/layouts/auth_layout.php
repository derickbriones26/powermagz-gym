<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <meta name="theme-color" content="#060707">
    <title><?= e($pageTitle ?? 'Login') ?> — Powermagz Gym</title>
    <link rel="icon" href="<?= url_e('/public/images/powermagz_gym.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['"Bebas Neue"', 'sans-serif'],
                        body: ['Montserrat', 'sans-serif']
                    },
                    colors: {
                        brand: '#00A6A6'
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="<?= url_e('/public/css/auth.css') ?>">
    <script>
        document.documentElement.classList.add('js');
    </script>
</head>

<body class="auth-body">
    <div class="auth-atmosphere" aria-hidden="true"></div>

    <main class="auth-shell">
        <aside class="auth-showcase">
            <div class="auth-showcase__media" aria-hidden="true">
                <img src="<?= url_e('/public/images/auth-showcase.jpg') ?>" alt="" width="1448" height="1092" fetchpriority="high" decoding="async">
            </div>
            <div class="auth-showcase__veil" aria-hidden="true"></div>

            <a href="<?= url_e('/') ?>" class="auth-showcase__brand" aria-label="PowerMagz Gym — back to home">
                <span class="auth-showcase__mark">
                    <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="" width="44" height="44">
                </span>
                <span class="auth-showcase__word">POWERMAGZ<span>GYM</span></span>
            </a>

            <div class="auth-showcase__copy">
                <p class="auth-showcase__eyebrow">Tabaco City · Albay</p>
                <p class="auth-showcase__display">
                    <span class="auth-showcase__line"><span>TRAIN</span></span>
                    <span class="auth-showcase__line"><span>WITH</span></span>
                    <span class="auth-showcase__line"><span class="is-outline">INTENT.</span></span>
                </p>
                <p class="auth-showcase__lede">Coach-led strength training, QR check-in and flexible payments — one membership for the whole gym.</p>

                <dl class="auth-showcase__facts">
                    <div>
                        <dt>Open daily</dt>
                        <dd>6:00 AM — 7:00 PM</dd>
                    </div>
                    <div>
                        <dt>Find us</dt>
                        <dd>Zone 4, Matagbac</dd>
                    </div>
                </dl>
            </div>

            <div class="auth-showcase__footer">
                <p class="auth-showcase__note">One system for members, coaches and the front desk.</p>
            </div>
        </aside>

        <section class="auth-panel">
            <div class="auth-card">
                <div class="auth-card__top">
                    <a href="<?= url_e('/') ?>" class="auth-back">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to home
                    </a>

                    <a href="<?= url_e('/') ?>" class="auth-mini-brand" aria-label="PowerMagz Gym — back to home">
                        <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="" width="28" height="28">
                        <span>POWERMAGZ</span>
                    </a>
                </div>

                <?php if ($m = flash('error')): ?>
                    <div role="alert" class="auth-flash auth-flash--error">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                        <span><?= e($m) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($m = flash('success')): ?>
                    <div role="status" class="auth-flash auth-flash--success">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= e($m) ?></span>
                    </div>
                <?php endif; ?>

                <div class="auth-card__content">
                    <?= $content ?>
                </div>
            </div>

            <p class="auth-legal">© 2026 PowerMagz Gym — Tabaco City, Albay</p>
        </section>
    </main>

    <script>
        (function() {
            'use strict';

            function controlFor(btn) {
                return btn.closest('.auth-field__control') || btn.closest('.pw-wrap') || btn.closest('.relative');
            }

            // Initialise eye state (covers the other auth views too).
            document.querySelectorAll('[data-pw-toggle]').forEach(function(btn) {
                var open = btn.querySelector('.eye-open');
                var closed = btn.querySelector('.eye-closed');
                if (open) open.hidden = false;
                if (closed) closed.hidden = true;
                if (!btn.hasAttribute('aria-label')) btn.setAttribute('aria-label', 'Show password');
                btn.setAttribute('aria-pressed', 'false');
            });

            document.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-pw-toggle]');
                if (!btn) return;

                var wrap = controlFor(btn);
                var input = wrap ? wrap.querySelector('input') : null;
                if (!input) return;

                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';

                var open = btn.querySelector('.eye-open');
                var closed = btn.querySelector('.eye-closed');
                if (open) open.hidden = show;
                if (closed) closed.hidden = !show;

                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });

            // Quick sign-in buttons: no OAuth app is connected yet, so answer
            // honestly and point people at the working email form.
            document.addEventListener('click', function(e) {
                var social = e.target.closest('[data-social]');
                if (!social) return;

                var provider = social.getAttribute('data-social') === 'facebook' ? 'Facebook' : 'Google';
                var note = document.getElementById('authSocialNote');
                if (!note) return;

                note.textContent = provider + ' quick sign-in isn\'t connected yet. Use your email and password to continue.';
                note.hidden = false;
                note.classList.remove('is-in');
                void note.offsetWidth;
                note.classList.add('is-in');
            });

            // Give the primary action a visible working state while the POST runs.
            document.addEventListener('submit', function(e) {
                var form = e.target;
                if (!form.matches || !form.matches('[data-auth-form]')) return;

                var btn = form.querySelector('[data-submit]');
                if (!btn) return;

                var label = btn.querySelector('.btn-label');
                if (label) {
                    if (!btn.dataset.restingLabel) btn.dataset.restingLabel = label.textContent;
                    if (btn.dataset.loading) label.textContent = btn.dataset.loading;
                }

                btn.classList.add('is-loading');
                btn.disabled = true;
                btn.setAttribute('aria-busy', 'true');
            });

            // Returning via bfcache must not restore a stuck spinner.
            window.addEventListener('pageshow', function() {
                document.querySelectorAll('[data-auth-form] [data-submit]').forEach(function(btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                    btn.removeAttribute('aria-busy');
                    var label = btn.querySelector('.btn-label');
                    if (label && btn.dataset.restingLabel) label.textContent = btn.dataset.restingLabel;
                });
            });
        })();
    </script>
</body>

</html>
