/**
 * PowerMagz — Client-side accessibility engine  (spec §3, WCAG 2.1 AA)
 *
 * Auto-initializes on DOMContentLoaded. Watches for dynamically-inserted
 * elements via MutationObserver so it works with modals opened later.
 *
 * Features:
 *   1. Modal focus trap — Tab stays inside the active modal
 *   2. Focus restoration — closing a modal returns focus to its trigger
 *   3. Keyboard shortcuts — Alt+A, Alt+M, Alt+P, /, ?, etc.
 *   4. Live-region announcer — screen reader notifications
 *   5. Tooltip controller — shows on hover AND keyboard focus
 *   6. Skip-link routing — moves focus to the target's main heading
 *   7. Table row activation — Enter/Space on a focused row trigger
 *   8. Form error announcements — the first invalid field is announced
 *   9. Consistent Escape handling across all modals
 *
 * Zero dependencies. ES2017. Works in evergreen browsers.
 */

(function () {
    'use strict';

    /* ================================================================
     *  CONFIG
     *  ================================================================ */

    const MODAL_SELECTOR     = '[role="dialog"], [data-modal], .modal';
    const MODAL_OPEN_CLASS   = 'flex';
    const MODAL_CLOSED_CLASS = 'hidden';
    const FOCUSABLE_SELECTOR = [
        'a[href]:not([disabled]):not([aria-hidden="true"])',
        'button:not([disabled]):not([aria-hidden="true"])',
        'input:not([disabled]):not([type="hidden"]):not([aria-hidden="true"])',
        'select:not([disabled]):not([aria-hidden="true"])',
        'textarea:not([disabled]):not([aria-hidden="true"])',
        '[tabindex]:not([tabindex="-1"]):not([aria-hidden="true"])',
        '[contenteditable="true"]',
    ].join(',');

    /* ================================================================
     *  STATE
     *  ================================================================ */

    const state = {
        lastTrigger: null,       /* Element that opened the current modal */
        activeModal: null,       /* Currently trapped modal */
        trapHandlers: new Map(), /* modalEl → keydown handler */
        shortcuts: new Map(),    /* "alt+a" → callback */
    };

    /* ================================================================
     *  LIVE REGION ANNOUNCER
     *  Two dedicated regions so we can use both politeness levels.
     *  ================================================================ */

    function ensureAnnouncer() {
        let polite = document.getElementById('a11y-live-polite');
        if (!polite) {
            polite = document.createElement('div');
            polite.id = 'a11y-live-polite';
            polite.setAttribute('aria-live', 'polite');
            polite.setAttribute('aria-atomic', 'true');
            polite.className = 'sr-only';
            document.body.appendChild(polite);
        }

        let assertive = document.getElementById('a11y-live-assertive');
        if (!assertive) {
            assertive = document.createElement('div');
            assertive.id = 'a11y-live-assertive';
            assertive.setAttribute('aria-live', 'assertive');
            assertive.setAttribute('aria-atomic', 'true');
            assertive.className = 'sr-only';
            document.body.appendChild(assertive);
        }

        return { polite, assertive };
    }

    function announce(message, politeness) {
        politeness = politeness || 'polite';
        const { polite, assertive } = ensureAnnouncer();
        const target = politeness === 'assertive' ? assertive : polite;

        /* Clear then set on next tick so screen readers re-announce
           identical strings (announcements are often suppressed otherwise) */
        target.textContent = '';
        setTimeout(() => {
            target.textContent = message;
        }, 50);

        /* Optional visual toast for sighted users (only in debug mode) */
        if (window.localStorage && localStorage.getItem('pmgz-a11y-debug') === '1') {
            showDebugToast(message);
        }
    }

    function showDebugToast(message) {
        const el = document.createElement('div');
        el.className = 'fixed bottom-6 right-6 z-[100] rounded-full bg-black/90 px-4 py-2 text-xs font-bold text-white shadow-2xl';
        el.textContent = '🔊 ' + message;
        el.style.animation = 'toast-in 0.25s ease-out';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2500);
    }

    /* ================================================================
     *  FOCUS MANAGEMENT
     *  ================================================================ */

    function isVisible(el) {
        if (!el) return false;
        if (el.offsetParent === null && getComputedStyle(el).position !== 'fixed') {
            return false;
        }
        const style = getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
    }

    function getFocusable(container) {
        if (!container) return [];
        return Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR))
            .filter(isVisible);
    }

    function setInitialFocus(modal) {
        /* Prefer explicit autofocus, then [data-autofocus], then first input, then first focusable */
        let target =
            modal.querySelector('[autofocus]') ||
            modal.querySelector('[data-autofocus]') ||
            modal.querySelector('input:not([type="hidden"]):not([disabled])') ||
            modal.querySelector('select:not([disabled])') ||
            modal.querySelector('textarea:not([disabled])') ||
            getFocusable(modal)[0];

        if (target) {
            /* Delay so the browser has finished painting the modal */
            setTimeout(() => target.focus(), 60);
        }
    }

    function trapFocus(modal) {
        if (state.trapHandlers.has(modal)) return;

        const handler = function (e) {
            if (e.key !== 'Tab') return;

            const focusable = getFocusable(modal);
            if (focusable.length === 0) {
                e.preventDefault();
                return;
            }

            const first = focusable[0];
            const last  = focusable[focusable.length - 1];
            const active = document.activeElement;

            /* If focus is outside the modal, pull it in */
            if (!modal.contains(active)) {
                e.preventDefault();
                (e.shiftKey ? last : first).focus();
                return;
            }

            /* Wrap at boundaries */
            if (e.shiftKey && active === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            }
        };

        modal.addEventListener('keydown', handler);
        state.trapHandlers.set(modal, handler);
    }

    function releaseFocus(modal) {
        const handler = state.trapHandlers.get(modal);
        if (handler) {
            modal.removeEventListener('keydown', handler);
            state.trapHandlers.delete(modal);
        }
    }

    function restoreFocusTo(trigger) {
        if (!trigger || typeof trigger.focus !== 'function') return;
        if (!document.body.contains(trigger)) return;

        /* Try to focus; if the element is no longer visible, fall back to body */
        try {
            trigger.focus({ preventScroll: false });
            trigger.setAttribute('data-focus-restored', '');
            setTimeout(() => trigger.removeAttribute('data-focus-restored'), 700);
        } catch (e) {
            /* noop */
        }
    }

    /* ================================================================
     *  MODAL DETECTION (works with any modal that toggles flex/hidden)
     *  ================================================================ */

    function isModalOpen(modal) {
        return modal.classList.contains(MODAL_OPEN_CLASS)
            && !modal.classList.contains(MODAL_CLOSED_CLASS);
    }

    function onModalOpened(modal, trigger) {
        if (state.activeModal === modal) return;

        state.activeModal = modal;
        state.lastTrigger = trigger || document.activeElement;

        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('role', 'dialog');
        if (!modal.hasAttribute('aria-labelledby') && !modal.hasAttribute('aria-label')) {
            /* Try to find an H2/H3 inside to use as the label */
            const heading = modal.querySelector('h1, h2, h3, [data-modal-title]');
            if (heading) {
                if (!heading.id) {
                    heading.id = 'modal-title-' + Date.now().toString(36);
                }
                modal.setAttribute('aria-labelledby', heading.id);
            }
        }

        trapFocus(modal);
        setInitialFocus(modal);

        /* Prevent body scroll while the modal is open */
        document.body.style.overflow = 'hidden';
    }

    function onModalClosed(modal) {
        if (state.activeModal !== modal) return;

        releaseFocus(modal);
        state.activeModal = null;
        document.body.style.overflow = '';

        const trigger = state.lastTrigger;
        state.lastTrigger = null;
        restoreFocusTo(trigger);
    }

    function watchModal(modal) {
        /* Watch class attribute for flex↔hidden toggles */
        const observer = new MutationObserver(function (mutations) {
            for (const m of mutations) {
                if (m.attributeName !== 'class') continue;

                if (isModalOpen(modal)) {
                    /* Capture the trigger — usually the element that was focused
                       before the click handler ran */
                    onModalOpened(modal, state.lastTrigger || document.activeElement);
                } else {
                    onModalClosed(modal);
                }
            }
        });

        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }

    /* ================================================================
     *  KEYBOARD SHORTCUTS
     *  Only active when the user is NOT typing into a field.
     *  ================================================================ */

    function isTypingTarget(el) {
        if (!el) return false;
        const tag = el.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    }

    /**
     * Register a keyboard shortcut.
     *   registerShortcut('alt+a', () => …)
     *   registerShortcut('/',     () => focus search)
     */
    function registerShortcut(combo, handler, options) {
        options = options || {};
        state.shortcuts.set(normalizeCombo(combo), {
            handler,
            description: options.description || '',
            allowWhileTyping: options.allowWhileTyping === true,
        });
    }

    function normalizeCombo(combo) {
        const parts = combo.toLowerCase().split('+').map(p => p.trim()).filter(Boolean);
        parts.sort();
        return parts.join('+');
    }

    function comboFromEvent(e) {
        const parts = [];
        if (e.ctrlKey)  parts.push('ctrl');
        if (e.altKey)   parts.push('alt');
        if (e.shiftKey) parts.push('shift');
        if (e.metaKey)  parts.push('meta');

        let key = e.key;
        if (key === ' ' || key === 'Spacebar') key = 'space';
        key = key.toLowerCase();
        if (key === 'esc') key = 'escape';

        /* Skip modifier-only presses */
        if (['control', 'alt', 'shift', 'meta'].includes(key)) return null;

        parts.push(key);
        parts.sort();
        return parts.join('+');
    }

    function handleShortcutEvent(e) {
        const combo = comboFromEvent(e);
        if (!combo) return;

        const binding = state.shortcuts.get(combo);
        if (!binding) return;

        if (!binding.allowWhileTyping && isTypingTarget(e.target)) return;

        e.preventDefault();
        try {
            binding.handler(e);
        } catch (err) {
            console.warn('[a11y] Shortcut handler threw:', err);
        }
    }

    /* ================================================================
     *  BUILT-IN SHORTCUTS
     *  ================================================================ */

    function installBuiltinShortcuts() {
        /* ? — show the shortcut help panel */
        registerShortcut('shift+/', () => toggleShortcutHelp(), {
            description: 'Show keyboard shortcuts',
            allowWhileTyping: true,
        });

        /* / — focus the first search input on the page */
        registerShortcut('/', () => {
            const inputs = document.querySelectorAll(
                'input[type="search"]:not([disabled]), input[data-search-input]:not([disabled])'
            );
            if (inputs.length > 0) {
                inputs[0].focus();
                inputs[0].select();
                announce('Search focused');
            } else {
                announce('No search on this page');
            }
        }, {
            description: 'Focus search',
        });

        /* Escape — close any open modal */
        registerShortcut('escape', () => {
            if (state.activeModal) {
                const closeBtn = state.activeModal.querySelector(
                    '[data-modal-close], button[aria-label*="Close"], button[onclick*="close"]'
                );
                if (closeBtn) closeBtn.click();
            }
        }, {
            description: 'Close modal',
            allowWhileTyping: true,
        });

        /* Alt+M — go to Members tab (admin) */
        registerShortcut('alt+m', () => navigateIfLinkExists('/admin-dashboard?tab=members'), {
            description: 'Go to Members',
        });

        /* Alt+P — go to Programs tab */
        registerShortcut('alt+p', () => navigateIfLinkExists('/admin-dashboard?tab=programs'), {
            description: 'Go to Programs',
        });

        /* Alt+A — focus the first "Add" button (Add Program, Add Client, etc.) */
        registerShortcut('alt+a', () => {
            const addBtn = Array.from(document.querySelectorAll('button, a'))
                .find(el => /\b(add|create|new)\b/i.test(el.textContent || ''));
            if (addBtn) {
                addBtn.focus();
                announce('Add button focused');
            } else {
                announce('No add button on this page');
            }
        }, {
            description: 'Focus "Add" button',
        });

        /* Alt+H — focus the first heading of the page */
        registerShortcut('alt+h', () => {
            const heading = document.querySelector('main h1, main h2, h1, h2');
            if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus();
            }
        }, {
            description: 'Focus heading',
        });
    }

    function navigateIfLinkExists(href) {
        const link = document.querySelector('a[href$="' + href.replace(/^.*\?/, '?') + '"]')
            || document.querySelector('a[href*="' + href.split('?')[0] + '"]');
        if (link) {
            window.location.href = link.href;
        } else {
            announce('Navigation not available here');
        }
    }

    /* ================================================================
     *  SHORTCUT HELP PANEL
     *  ================================================================ */

    function toggleShortcutHelp() {
        const existing = document.getElementById('a11y-shortcut-help');
        if (existing) {
            existing.remove();
            return;
        }

        const dialog = document.createElement('div');
        dialog.id = 'a11y-shortcut-help';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-labelledby', 'a11y-shortcut-help-title');
        dialog.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm';
        dialog.style.animation = 'modal-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';

        const shortcuts = Array.from(state.shortcuts.entries())
            .filter(([, b]) => b.description)
            .map(([combo, binding]) => {
                const parts = combo.split('+').map(p => {
                    if (p === 'alt')   return '<kbd>Alt</kbd>';
                    if (p === 'ctrl')  return '<kbd>Ctrl</kbd>';
                    if (p === 'shift') return '<kbd>Shift</kbd>';
                    if (p === 'meta')  return '<kbd>⌘</kbd>';
                    if (p === 'space') return '<kbd>Space</kbd>';
                    if (p === 'escape') return '<kbd>Esc</kbd>';
                    if (p === '/') return '<kbd>/</kbd>';
                    return '<kbd>' + p.toUpperCase() + '</kbd>';
                }).join('<span class="mx-1 text-gray-400">+</span>');

                return `
                    <div class="flex items-center justify-between border-b border-gray-100 py-3 last:border-0">
                        <span class="text-sm text-gray-700">${escapeHtml(binding.description)}</span>
                        <span class="flex items-center">${parts}</span>
                    </div>
                `;
            }).join('');

        dialog.innerHTML = `
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 p-5">
                    <div>
                        <h2 id="a11y-shortcut-help-title" class="font-heading text-2xl tracking-wide text-black">
                            KEYBOARD SHORTCUTS
                        </h2>
                        <p class="mt-0.5 text-xs text-gray-500">Press <kbd>?</kbd> any time to toggle this panel</p>
                    </div>
                    <button type="button" data-modal-close aria-label="Close shortcuts panel"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-black">
                        ✕
                    </button>
                </div>
                <div class="max-h-[60vh] overflow-y-auto p-5">
                    ${shortcuts || '<p class="text-sm text-gray-400">No shortcuts registered.</p>'}
                </div>
                <div class="border-t border-gray-100 p-4 text-center">
                    <button type="button" data-modal-close
                        class="rounded-full bg-[#00A6A6] px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-lg">
                        Close
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(dialog);
        watchModal(dialog);

        /* Focus the close button for keyboard users */
        setTimeout(() => {
            const closeBtn = dialog.querySelector('[data-modal-close]');
            if (closeBtn) closeBtn.focus();
        }, 80);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[c]);
    }

    /* ================================================================
     *  SKIP-TO-CONTENT LINK
     *  When the link is clicked, focus the target instead of just scrolling.
     *  ================================================================ */

    function wireSkipLink() {
        document.querySelectorAll('.skip-to-content, a[href^="#main-content"]').forEach(link => {
            link.addEventListener('click', e => {
                const id = (link.getAttribute('href') || '').replace(/^#/, '');
                if (!id) return;

                const target = document.getElementById(id);
                if (!target) return;

                e.preventDefault();

                /* Make target focusable if it isn't already */
                if (!target.hasAttribute('tabindex')) {
                    target.setAttribute('tabindex', '-1');
                }
                target.focus();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                announce('Skipped to main content');
            });
        });
    }

    /* ================================================================
     *  TOOLTIP CONTROLLER
     *  Shows [role="tooltip"] siblings on hover + keyboard focus.
     *  ================================================================ */

    function wireTooltips() {
        document.querySelectorAll('[data-tooltip-root]').forEach(root => {
            if (root.dataset.tooltipWired === '1') return;
            root.dataset.tooltipWired = '1';

            const trigger = root.querySelector('[data-tooltip-trigger], button, a');
            const tooltip = root.querySelector('[role="tooltip"]');
            if (!trigger || !tooltip) return;

            const show = () => {
                tooltip.hidden = false;
            };
            const hide = () => {
                tooltip.hidden = true;
            };

            trigger.addEventListener('mouseenter', show);
            trigger.addEventListener('mouseleave', hide);
            trigger.addEventListener('focus', show);
            trigger.addEventListener('blur', hide);

            /* Escape closes the tooltip */
            trigger.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    hide();
                    trigger.blur();
                }
            });
        });
    }

    /* ================================================================
     *  TABLE ROW ACTIVATION
     *  Rows marked [data-row-activate] behave like buttons.
     *  ================================================================ */

    function wireTableRows() {
        document.querySelectorAll('[data-row-activate]').forEach(row => {
            if (row.dataset.rowWired === '1') return;
            row.dataset.rowWired = '1';

            row.setAttribute('tabindex', '0');
            row.setAttribute('role', 'button');

            const activate = () => {
                /* Find the first [data-row-action] inside, click it */
                const action = row.querySelector('[data-row-action], button, a[role="button"]');
                if (action) action.click();
            };

            row.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    activate();
                }
            });
        });
    }

    /* ================================================================
     *  FORM ERROR ANNOUNCEMENT
     *  When a form submit is blocked by HTML5 validation, announce the
     *  first error and focus the offending field.
     *  ================================================================ */

    function wireFormValidation() {
        document.querySelectorAll('form').forEach(form => {
            if (form.dataset.a11yValidationWired === '1') return;
            form.dataset.a11yValidationWired = '1';

            form.addEventListener('invalid', e => {
                /* The browser has already moved focus; announce the error */
                const field = e.target;
                const label = field.getAttribute('data-error-label')
                    || field.getAttribute('aria-label')
                    || field.getAttribute('placeholder')
                    || field.name
                    || 'a field';
                const message = field.validationMessage || 'invalid';

                announce(label + ': ' + message, 'assertive');
            }, true /* use capture so we catch it before bubbling stops */);
        });
    }

    /* ================================================================
     *  LAZY INIT + MUTATION OBSERVER
     *  ================================================================ */

    function initAll() {
        /* Wire all modals on the page */
        document.querySelectorAll(MODAL_SELECTOR).forEach(watchModal);

        wireTooltips();
        wireTableRows();
        wireFormValidation();
        wireSkipLink();
    }

    let initScheduled = false;
    function scheduleInit() {
        if (initScheduled) return;
        initScheduled = true;
        requestAnimationFrame(() => {
            initScheduled = false;
            initAll();
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        ensureAnnouncer();
        installBuiltinShortcuts();

        /* Global keyboard listener */
        document.addEventListener('keydown', handleShortcutEvent);

        initAll();

        /* Watch for dynamically-inserted modals/rows */
        const observer = new MutationObserver(scheduleInit);
        observer.observe(document.body, { childList: true, subtree: true });
    });

    /* If DOM is already parsed when we load, initialize immediately */
    if (document.readyState !== 'loading') {
        ensureAnnouncer();
        installBuiltinShortcuts();
        document.addEventListener('keydown', handleShortcutEvent);
        initAll();

        const observer = new MutationObserver(scheduleInit);
        observer.observe(document.body, { childList: true, subtree: true });
    }

    /* ================================================================
     *  PUBLIC API
     *  Views / inline scripts can call these.
     *  ================================================================ */

    window.PMGZ_A11Y = {
        announce,
        registerShortcut,
        toggleShortcutHelp,
        openModal(trigger) {
            if (trigger) state.lastTrigger = trigger;
        },
        getActiveModal() {
            return state.activeModal;
        },
    };
})();