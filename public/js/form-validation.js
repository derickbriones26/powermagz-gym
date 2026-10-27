/**
 * PowerMagz — Client-side Form Validation  (spec §1.6)
 *
 * Attaches to any <form data-validate> element.
 * Reads validation rules from `data-validate-*` attributes:
 *
 *   data-validate="required|email|min:8|max:190|phone|date|numeric|positive"
 *   data-validate-match="#password"  → must match another field
 *   data-validate-after="#start"     → date must be after another field
 *   data-error-label="Email"         → used in error messages
 *
 * Features:
 *   - Live validation on blur + input (debounced)
 *   - Error summary at top with jump links
 *   - Auto-focus first invalid field on submit
 *   - Plain-language messages
 *   - Works with dynamically inserted fields (MutationObserver)
 */
(function () {
    'use strict';

    const DEBOUNCE_MS = 250;

    /* ============================================================
     *  MESSAGE TEMPLATES
     * ============================================================ */
    const MESSAGES = {
        required:      (label) => `${label} is required.`,
        email:         (label) => `${label} must be a valid email address.`,
        min:           (label, n) => `${label} must be at least ${n} characters.`,
        max:           (label, n) => `${label} must be ${n} characters or fewer.`,
        numeric:       (label) => `${label} must be a number.`,
        positive:      (label) => `${label} must be greater than zero.`,
        nonnegative:   (label) => `${label} cannot be negative.`,
        phone:         (label) => `${label} must be a valid phone number (digits, spaces, +, -, parens only).`,
        date:          (label) => `${label} must be a valid date.`,
        date_after:    (label) => `${label} must be after the start date.`,
        match:         (label) => `${label} does not match.`,
        maxlength:     (label, n) => `${label} is too long (max ${n}).`,
        minlength:     (label, n) => `${label} is too short (min ${n}).`,
    };

    /* ============================================================
     *  VALIDATORS
     *  Each returns null on success, or a plain-language message.
     * ============================================================ */
    const validators = {
        required: (el) => {
            const v = (el.value || '').trim();
            if (el.type === 'checkbox' && !el.checked) return 'required';
            if (v === '') return 'required';
            return null;
        },
        email: (el) => {
            if (!el.value) return null; // let required catch empty
            const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value);
            return ok ? null : 'email';
        },
        phone: (el) => {
            if (!el.value) return null;
            const ok = /^[0-9\s\-+()]+$/.test(el.value);
            return ok ? null : 'phone';
        },
        numeric: (el) => {
            if (!el.value) return null;
            return isNaN(parseFloat(el.value)) ? 'numeric' : null;
        },
        positive: (el) => {
            if (!el.value) return null;
            const v = parseFloat(el.value);
            if (isNaN(v)) return 'numeric';
            return v > 0 ? null : 'positive';
        },
        nonnegative: (el) => {
            if (!el.value) return null;
            const v = parseFloat(el.value);
            if (isNaN(v)) return 'numeric';
            return v >= 0 ? null : 'nonnegative';
        },
        date: (el) => {
            if (!el.value) return null;
            const d = new Date(el.value);
            return isNaN(d.getTime()) ? 'date' : null;
        },
        min: (el, n) => {
            if (!el.value) return null;
            const v = parseFloat(el.value);
            if (!isNaN(v) && el.type === 'number') return v >= parseFloat(n) ? null : 'minNum';
            return (el.value || '').length >= parseInt(n, 10) ? null : 'min';
        },
        max: (el, n) => {
            if (!el.value) return null;
            const v = parseFloat(el.value);
            if (!isNaN(v) && el.type === 'number') return v <= parseFloat(n) ? null : 'maxNum';
            return (el.value || '').length <= parseInt(n, 10) ? null : 'max';
        },
    };

    /* ============================================================
     *  FIELD HELPERS
     * ============================================================ */
    function labelFor(field) {
        if (field.dataset.errorLabel) return field.dataset.errorLabel;

        // Try the <label> wrapping the field
        const wrapLabel = field.closest('label');
        if (wrapLabel) {
            const span = wrapLabel.querySelector('span');
            if (span) return span.textContent.replace('*', '').trim();
        }

        // Try `for` association
        if (field.id) {
            const esc = (window.CSS && CSS.escape) ? CSS.escape(field.id) : field.id;
            const assoc = document.querySelector(`label[for="${esc}"]`);
            if (assoc) return assoc.textContent.replace('*', '').trim();
        }

        // Fallback: placeholders or name
        return field.getAttribute('placeholder')
            || field.getAttribute('name')
            || 'This field';
    }

    function ensureErrorBox(field) {
        // Look for a sibling error box
        let box = field.parentElement.querySelector('[data-field-error]');

        if (!box) {
            box = document.createElement('span');
            box.setAttribute('data-field-error', '');
            box.setAttribute('role', 'alert');
            box.className = 'mt-1 block text-xs font-semibold text-[#D50000]';
            field.parentElement.appendChild(box);
        }

        return box;
    }

    function clearError(field) {
        const box = field.parentElement.querySelector('[data-field-error]');
        if (box) {
            box.textContent = '';
            box.classList.add('hidden');
        }

        field.classList.remove('border-[#D50000]', 'bg-red-50/50');
        field.classList.add('border-gray-300', 'bg-white');
        field.removeAttribute('aria-invalid');
    }

    function showError(field, message) {
        const box = ensureErrorBox(field);
        box.textContent = message;
        box.classList.remove('hidden');

        field.classList.remove('border-gray-300', 'bg-white');
        field.classList.add('border-[#D50000]', 'bg-red-50/50');
        field.setAttribute('aria-invalid', 'true');
    }

    /* ============================================================
     *  RULE PARSER
     * ============================================================ */
    function parseRules(field) {
        const ruleStr = field.dataset.validate || '';
        const rules = [];

        ruleStr.split('|').forEach(part => {
            part = part.trim();
            if (!part) return;

            const [name, arg] = part.split(':');
            rules.push({ name, arg: arg !== undefined ? arg : null });
        });

        // Match another field
        const matchSel = field.dataset.validateMatch;
        if (matchSel) rules.push({ name: 'match', arg: matchSel });

        // Date-after another field
        const afterSel = field.dataset.validateAfter;
        if (afterSel) rules.push({ name: 'date_after', arg: afterSel });

        return rules;
    }

    /* ============================================================
     *  VALIDATE ONE FIELD
     * ============================================================ */
    function validateField(field) {
        if (field.disabled || field.type === 'hidden') return null;

        const rules = parseRules(field);
        if (rules.length === 0) return null;

        const label = labelFor(field);

        for (const rule of rules) {
            let errKey = null;

            if (rule.name === 'match') {
                const other = document.querySelector(rule.arg);
                if (other && other.value !== field.value) errKey = 'match';
            } else if (rule.name === 'date_after') {
                const other = document.querySelector(rule.arg);
                if (other && other.value && field.value) {
                    const a = new Date(other.value);
                    const b = new Date(field.value);
                    if (!isNaN(a) && !isNaN(b) && b <= a) errKey = 'date_after';
                }
            } else if (validators[rule.name]) {
                errKey = validators[rule.name](field, rule.arg);
            }

            if (errKey) {
                // Plain-language override
                if (errKey === 'minNum') return `${label} must be at least ${rule.arg}.`;
                if (errKey === 'maxNum') return `${label} must be at most ${rule.arg}.`;
                const tmpl = MESSAGES[errKey];
                return tmpl ? tmpl(label, rule.arg) : `${label} is invalid.`;
            }
        }

        return null;
    }

    /* ============================================================
     *  ERROR SUMMARY AT TOP OF FORM
     * ============================================================ */
    function buildErrorSummary(form, errors) {
        let summary = form.querySelector('[data-error-summary]');

        if (errors.length === 0) {
            if (summary) summary.remove();
            return;
        }

        if (!summary) {
            summary = document.createElement('div');
            summary.setAttribute('data-error-summary', '');
            summary.setAttribute('role', 'alert');
            summary.setAttribute('aria-live', 'assertive');
            summary.className = 'rounded-xl border-2 border-red-300 bg-red-50 p-4 mb-5';

            // Insert as first child of the form
            form.insertBefore(summary, form.firstChild);
        }

        const items = errors.map(e => {
            const id = e.field.id;
            const link = id
                ? `<a href="#${id}" class="font-semibold underline hover:no-underline" data-jump-to="${id}">${escapeHtml(e.message)}</a>`
                : escapeHtml(e.message);
            return `<li>${link}</li>`;
        }).join('');

        summary.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="text-2xl flex-shrink-0" aria-hidden="true">⚠️</span>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-[#D50000]">
                        Please fix ${errors.length} field${errors.length !== 1 ? 's' : ''} below:
                    </div>
                    <ul class="mt-2 list-disc space-y-0.5 pl-5 text-xs text-red-900">
                        ${items}
                    </ul>
                </div>
            </div>`;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    /* ============================================================
     *  FORM INITIALIZATION
     * ============================================================ */
    function initForm(form) {
        if (form.dataset.validationInitialized) return;
        form.dataset.validationInitialized = '1';

        const fields = Array.from(form.querySelectorAll('[data-validate], [data-validate-match], [data-validate-after]'));

        fields.forEach(field => {
            // Live validation on blur
            field.addEventListener('blur', () => {
                const err = validateField(field);
                if (err) showError(field, err);
                else     clearError(field);
            });

            // Debounced validation on input (only clear errors while typing)
            let debounce;
            field.addEventListener('input', () => {
                if (field.dataset.touched === '1') {
                    clearTimeout(debounce);
                    debounce = setTimeout(() => {
                        const err = validateField(field);
                        if (err) showError(field, err);
                        else     clearError(field);
                    }, DEBOUNCE_MS);
                }
            });

            // Mark as touched on blur
            field.addEventListener('blur', () => {
                field.dataset.touched = '1';
            }, { once: true });
        });

        // Submit handler
        form.addEventListener('submit', (e) => {
            const errors = [];

            fields.forEach(field => {
                field.dataset.touched = '1';
                const err = validateField(field);
                if (err) {
                    showError(field, err);
                    errors.push({ field, message: err });
                } else {
                    clearError(field);
                }
            });

            buildErrorSummary(form, errors);

            if (errors.length > 0) {
                e.preventDefault();
                e.stopPropagation();

                // Focus first invalid field
                const first = errors[0].field;
                first.focus();
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // Jump links in the error summary
        form.addEventListener('click', (e) => {
            const link = e.target.closest('[data-jump-to]');
            if (!link) return;
            e.preventDefault();
            const el = document.getElementById(link.dataset.jumpTo);
            if (el) {
                el.focus();
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    /* ============================================================
     *  AUTO-INIT + OBSERVER FOR DYNAMIC FORMS
     * ============================================================ */
    function initAll() {
        document.querySelectorAll('form[data-validate]').forEach(initForm);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initAll();

        // Watch for forms added after page load (e.g., modals rendered later)
        const obs = new MutationObserver(() => {
            initAll();
        });
        obs.observe(document.body, { childList: true, subtree: true });
    });

    // Expose for manual triggering
    window.PMGZ_validateForm = function (form) {
        if (!form) return;
        const evt = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(evt);
    };

})();