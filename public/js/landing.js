/**
 * PowerMagz Gym — landing experience
 * Cinematic intro, scroll progress, counters, pointer details and the
 * interactive gravity core. Static-first: every block is optional.
 */
(function () {
    'use strict';

    var doc = document;
    var root = doc.documentElement;
    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var finePointer = !!(window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches);

    function clamp(value, min, max) {
        return value < min ? min : value > max ? max : value;
    }

    function raf(fn) {
        return window.requestAnimationFrame(fn);
    }

    /* ======================================================================
       1. Intro — count the session in, then split the curtain
       ====================================================================== */
    var intro = doc.getElementById('intro');
    var introFinished = false;

    function finishIntro() {
        if (introFinished) return;
        introFinished = true;
        root.classList.add('intro-done');
        root.classList.remove('intro-live');
        if (intro && intro.parentNode) intro.parentNode.removeChild(intro);
        intro = null;
        doc.dispatchEvent(new CustomEvent('pmgz:intro-done'));
    }

    if (reduceMotion) {
        if (intro && intro.parentNode) intro.parentNode.removeChild(intro);
        intro = null;
        introFinished = true;
        root.classList.add('intro-off', 'intro-done');
        root.classList.remove('intro-live');
    } else if (!intro || root.classList.contains('intro-off')) {
        introFinished = true;
        root.classList.add('intro-done');
        root.classList.remove('intro-live');
        if (intro && intro.parentNode) intro.parentNode.removeChild(intro);
        intro = null;
    } else {
        var markReady = function () {
            root.classList.add('intro-ready');
        };
        if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(markReady);
        window.setTimeout(markReady, 500);

        var countEl = doc.getElementById('introCount');
        var countStart = (window.performance && window.performance.now) ? window.performance.now() : Date.now();
        var countFrame = 0;
        var paintCount = function (now) {
            var t = clamp((now - countStart) / 1500, 0, 1);
            var eased = 1 - Math.pow(1 - t, 3);
            if (countEl) {
                countEl.textContent = String(Math.round(eased * 100)).padStart(2, '0');
            }
            if (t < 1) countFrame = raf(paintCount);
        };
        countFrame = raf(paintCount);

        intro.addEventListener('animationend', function (event) {
            if (event.animationName === 'intro-panel-top') finishIntro();
        });

        var skipIntro = function () {
            if (introFinished) return;
            root.classList.add('intro-skipped');
            window.cancelAnimationFrame(countFrame);
            finishIntro();
        };
        intro.addEventListener('pointerdown', skipIntro, { once: true });
        window.addEventListener('keydown', skipIntro, { once: true });
        window.setTimeout(finishIntro, 3400);
    }

    /* ======================================================================
       2. Scroll: progress, header state, hero cue — one rAF
       ====================================================================== */
    var progressFill = doc.getElementById('scrollProgress');
    var header = doc.getElementById('siteHeader');
    var scrollCue = doc.querySelector('.hero__scroll');
    var scrollFrame = 0;

    function paintScroll() {
        scrollFrame = 0;
        var y = window.scrollY;

        if (progressFill) {
            var max = doc.documentElement.scrollHeight - window.innerHeight;
            var ratio = max > 0 ? clamp(y / max, 0, 1) : 0;
            progressFill.style.transform = 'scaleX(' + ratio.toFixed(4) + ')';
        }
        if (header) {
            if (y > 12) header.classList.add('is-scrolled');
            else header.classList.remove('is-scrolled');
        }
        if (scrollCue) {
            if (y > 90) scrollCue.classList.add('is-hidden');
            else scrollCue.classList.remove('is-hidden');
        }
    }

    function queueScroll() {
        if (!scrollFrame) scrollFrame = raf(paintScroll);
    }

    window.addEventListener('scroll', queueScroll, { passive: true });
    window.addEventListener('resize', queueScroll);
    window.addEventListener('load', queueScroll);
    paintScroll();

    /* ======================================================================
       3. Menu + active section
       ====================================================================== */
    var menuBtn = doc.getElementById('menuBtn');
    var mobileMenu = doc.getElementById('mobileMenu');

    function openMenu() {
        if (!menuBtn || !mobileMenu) return;
        mobileMenu.classList.add('is-open');
        mobileMenu.setAttribute('aria-hidden', 'false');
        menuBtn.setAttribute('aria-expanded', 'true');
        menuBtn.setAttribute('aria-label', 'Close menu');
        doc.body.classList.add('menu-open');
        window.setTimeout(function () {
            var first = mobileMenu.querySelector('a');
            if (first) first.focus();
        }, 60);
    }

    function closeMenu() {
        if (!menuBtn || !mobileMenu) return;
        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.setAttribute('aria-label', 'Open menu');
        doc.body.classList.remove('menu-open');
    }

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', function () {
            if (menuBtn.getAttribute('aria-expanded') === 'true') closeMenu();
            else openMenu();
        });

        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMenu);
        });

        /* Tap/click outside the menu links (blank menu space or the header
           strip) closes it, same as the X button. */
        doc.addEventListener('click', function (event) {
            if (menuBtn.getAttribute('aria-expanded') !== 'true') return;
            if (event.target.closest('#mobileMenu a, #menuBtn')) return;
            closeMenu();
        });

        doc.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && menuBtn.getAttribute('aria-expanded') === 'true') {
                closeMenu();
                menuBtn.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 860 && menuBtn.getAttribute('aria-expanded') === 'true') {
                closeMenu();
            }
        });
    }

    var links = Array.prototype.slice.call(doc.querySelectorAll('[data-nav]'));
    var sections = links.map(function (link) {
        var id = link.getAttribute('href');
        return id && id.charAt(0) === '#' ? doc.querySelector(id) : null;
    }).filter(Boolean);

    if ('IntersectionObserver' in window && sections.length) {
        var visibleSections = {};
        var navObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                visibleSections[entry.target.id] = entry.isIntersecting;
            });
            var current = '';
            sections.forEach(function (section) {
                if (visibleSections[section.id]) current = section.id;
            });
            links.forEach(function (link) {
                if (current && link.getAttribute('href') === '#' + current) {
                    link.setAttribute('aria-current', 'true');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });

        sections.forEach(function (section) {
            navObserver.observe(section);
        });
    }

    /* ======================================================================
       4. Stat count-up — the real numbers, counted once
       ====================================================================== */
    var counters = doc.querySelectorAll('[data-count]');

    function runCounters() {
        counters.forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            if (reduceMotion) {
                el.textContent = String(target);
                return;
            }
            var started = 0;
            var step = function (now) {
                if (!started) started = now;
                var t = clamp((now - started) / 1150, 0, 1);
                var eased = 1 - Math.pow(2, -10 * t);
                el.textContent = String(Math.round(target * eased));
                if (t < 1) raf(step);
                else el.textContent = String(target);
            };
            window.setTimeout(function () {
                raf(step);
            }, 80);
        });
    }

    if (counters.length) {
        if (!reduceMotion) {
            counters.forEach(function (el) { el.textContent = '0'; });
        }

        var countersStarted = false;
        var startCounters = function () {
            if (countersStarted) return;
            countersStarted = true;
            if (reduceMotion) {
                runCounters();
            } else {
                window.setTimeout(runCounters, 260);
            }
        };

        if (reduceMotion || introFinished) {
            startCounters();
        } else {
            doc.addEventListener('pmgz:intro-done', startCounters, { once: true });
            // If the reveal never signals (slow font, background tab), count anyway.
            window.setTimeout(startCounters, 3800);
        }
    }

    /* ======================================================================
       5. Pointer details — spotlight on cards + hero scene parallax
       ====================================================================== */
    var heroPointer = { x: 0, y: 0 };
    var corePointer = { x: 0, y: 0 };
    var activeCard = null;

    if (finePointer) {
        doc.addEventListener('pointermove', function (event) {
            heroPointer.x = clamp((event.clientX / window.innerWidth) * 2 - 1, -1, 1);
            heroPointer.y = clamp((event.clientY / window.innerHeight) * 2 - 1, -1, 1);

            var target = event.target;
            var card = target && target.closest ? target.closest('.feature-card, .price-card') : null;
            if (card !== activeCard) {
                if (activeCard) {
                    activeCard.style.removeProperty('--spot-x');
                    activeCard.style.removeProperty('--spot-y');
                }
                activeCard = card;
            }
            if (card) {
                var rect = card.getBoundingClientRect();
                card.style.setProperty('--spot-x', (((event.clientX - rect.left) / rect.width) * 100).toFixed(1) + '%');
                card.style.setProperty('--spot-y', (((event.clientY - rect.top) / rect.height) * 100).toFixed(1) + '%');
            }
        }, { passive: true });
    }

    /* ======================================================================
       6. Gravity scenes
       ====================================================================== */
    var scenes = window.PMGZScenes;
    if (!reduceMotion && scenes) {
        var heroCanvas = doc.getElementById('heroScene');
        if (heroCanvas) {
            var heroScene = scenes.create(heroCanvas, { kind: 'hero', pointer: heroPointer });
            if (heroScene && heroCanvas.parentNode) heroCanvas.parentNode.classList.add('is-live');
        }

        var coreCanvas = doc.getElementById('coreScene');
        var coreStage = doc.getElementById('coreStage');
        if (coreCanvas && coreStage) {
            var coreScene = scenes.create(coreCanvas, { kind: 'core', pointer: corePointer });
            if (coreScene) {
                coreStage.classList.add('is-live');

                var drag = null;
                coreStage.addEventListener('pointerdown', function (event) {
                    if (event.pointerType === 'mouse' && event.button !== 0) return;
                    drag = { id: event.pointerId, x: event.clientX, y: event.clientY };
                    coreScene.hold();
                    coreStage.classList.add('is-dragging');
                    try { coreStage.setPointerCapture(event.pointerId); } catch (e) {}
                });

                coreStage.addEventListener('pointermove', function (event) {
                    var rect = coreStage.getBoundingClientRect();
                    if (!drag) {
                        corePointer.x = clamp(((event.clientX - rect.left) / rect.width) * 2 - 1, -1, 1);
                        corePointer.y = clamp(((event.clientY - rect.top) / rect.height) * 2 - 1, -1, 1);
                        return;
                    }
                    if (event.pointerId !== drag.id) return;
                    var dx = event.clientX - drag.x;
                    var dy = event.clientY - drag.y;
                    drag.x = event.clientX;
                    drag.y = event.clientY;
                    coreScene.rotate(dx, dy);
                });

                var endDrag = function (event) {
                    if (!drag || event.pointerId !== drag.id) return;
                    drag = null;
                    coreScene.release();
                    coreStage.classList.remove('is-dragging');
                };
                coreStage.addEventListener('pointerup', endDrag);
                coreStage.addEventListener('pointercancel', endDrag);

                coreStage.addEventListener('pointerleave', function () {
                    if (drag) return;
                    corePointer.x = 0;
                    corePointer.y = 0;
                });

                coreStage.addEventListener('keydown', function (event) {
                    var step = event.key;
                    if (step !== 'ArrowLeft' && step !== 'ArrowRight' && step !== 'ArrowUp' && step !== 'ArrowDown') return;
                    event.preventDefault();
                    if (step === 'ArrowLeft') coreScene.rotate(-30, 0);
                    if (step === 'ArrowRight') coreScene.rotate(30, 0);
                    if (step === 'ArrowUp') coreScene.rotate(0, -22);
                    if (step === 'ArrowDown') coreScene.rotate(0, 22);
                    coreScene.release();
                });
            }
        }
    }
})();
