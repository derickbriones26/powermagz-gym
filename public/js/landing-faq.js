/**
 * PowerMagz landing — FAQ assistant dock (bottom-right).
 *
 * Deterministic on purpose: chips and typed questions resolve against the
 * same verified FAQ set rendered by faq_widget.php. No network, no API key,
 * nothing that can invent a price or a policy.
 */
(function () {
    'use strict';

    var dock = document.getElementById('faqDock');
    if (!dock) return;

    var panel = document.getElementById('faqPanel');
    var launcher = document.getElementById('faqLauncher');
    var closeBtn = dock.querySelector('[data-faq-close]');
    var answerBox = document.getElementById('faqAnswer');
    var form = document.getElementById('faqForm');
    var input = document.getElementById('faqInput');
    var chips = Array.prototype.slice.call(dock.querySelectorAll('.faq-chip'));

    var FALLBACK = '<p>I can answer questions about <strong>prices, QR check-in, hours and location, payments, coaching</strong> and <strong>how to join</strong>.</p>'
        + '<p>For anything else, <a href="tel:+639618041523">call 0961 804 1523</a> or '
        + '<a href="mailto:powermagzgym@gmail.com">email the gym</a>.</p>';

    function normalize(text) {
        return ' ' + String(text === undefined || text === null ? '' : text)
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim() + ' ';
    }

    var sources = {};
    Array.prototype.forEach.call(dock.querySelectorAll('[data-faq-source]'), function (node) {
        var keywords = (node.getAttribute('data-keywords') || '').split('|');
        sources[node.getAttribute('data-faq-source')] = {
            html: node.innerHTML.trim(),
            keywords: keywords.map(normalize).filter(function (word) {
                return word !== '  ' && word.length > 2;
            })
        };
    });

    function activeChip() {
        for (var i = 0; i < chips.length; i++) {
            if (chips[i].getAttribute('aria-pressed') === 'true') return chips[i];
        }
        return null;
    }

    function setActiveChip(id) {
        chips.forEach(function (chip) {
            chip.setAttribute('aria-pressed', chip.getAttribute('data-faq') === id ? 'true' : 'false');
        });
    }

    function showEntry(id) {
        var entry = sources[id];
        if (!entry) return false;
        answerBox.innerHTML = entry.html;
        answerBox.classList.add('has-answer');
        setActiveChip(id);
        return true;
    }

    function matchEntry(text) {
        var question = normalize(text);
        var bestId = null;
        var bestScore = 0;

        Object.keys(sources).forEach(function (id) {
            var score = 0;
            sources[id].keywords.forEach(function (word) {
                if (question.indexOf(word) !== -1) {
                    // Longer phrases are stronger signals than single words.
                    score += word.replace(/\s/g, '').length >= 5 ? 2 : 1;
                }
            });
            if (score > bestScore) {
                bestScore = score;
                bestId = id;
            }
        });

        return bestScore > 0 ? bestId : null;
    }

    /* ------------------------------------------------------------ open/close -- */
    var open = false;
    var footerInView = false;
    var footerBottom = document.querySelector('.site-footer .footer-bottom');

    function syncTuck() {
        dock.classList.toggle('is-tucked', footerInView && !open);
    }

    function setOpen(next, viaKeyboard) {
        if (next === open) return;
        open = next;
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
        dock.classList.toggle('is-open', open);

        if (open) {
            if (viaKeyboard) {
                (activeChip() || chips[0] || closeBtn).focus();
            }
        } else if (panel.contains(document.activeElement)) {
            launcher.focus();
        }

        syncTuck();
    }

    // The launcher only appears once this script runs, so a failure never
    // leaves a dead button on the page.
    dock.hidden = false;

    launcher.addEventListener('click', function (event) {
        setOpen(!open, event.detail === 0);
    });

    closeBtn.addEventListener('click', function () {
        setOpen(false);
        launcher.focus();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && open) {
            event.preventDefault();
            setOpen(false);
            launcher.focus();
        }
    });

    document.addEventListener('pointerdown', function (event) {
        if (!open) return;
        if (dock.contains(event.target)) return;
        setOpen(false);
    });

    /* --------------------------------------------------------------- answers -- */
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            showEntry(chip.getAttribute('data-faq'));
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var text = input.value.trim();
        if (text === '') {
            input.focus();
            return;
        }

        var id = matchEntry(text);
        if (!id) {
            answerBox.innerHTML = FALLBACK;
            answerBox.classList.add('has-answer', 'is-fallback');
            setActiveChip(null);
        } else {
            answerBox.classList.remove('is-fallback');
            showEntry(id);
        }

        input.value = '';
    });

    // In-page links should reveal the section they point at, not stay
    // behind an open panel.
    answerBox.addEventListener('click', function (event) {
        var link = event.target && event.target.closest ? event.target.closest('a') : null;
        if (!link) return;
        var href = link.getAttribute('href') || '';
        if (href.charAt(0) === '#') setOpen(false);
    });

    /* -------------------------------------------- stay clear of the footer -- */
    if (footerBottom && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            footerInView = entries[0].isIntersecting;
            syncTuck();
        }, { threshold: 0 }).observe(footerBottom);
    }
})();
