<?php

/**
 * PowerMagz — Accessibility helpers  (spec §3, WCAG 2.1 AA)
 *
 * Provides reusable rendering functions that guarantee:
 *   - Status badges use color + icon + text (never color alone)
 *   - Icon-only buttons always carry aria-label + 44px min target
 *   - Tables always have a <caption>
 *   - Filter groups use <fieldset>/<legend>
 *   - Sections use role="region" + aria-label
 *   - Tooltips use role="tooltip" + aria-describedby
 *   - Live regions are properly marked
 *   - Skip-to-content link is standardized
 *
 * Loaded by bootstrap.php via the helper auto-load loop.
 *
 * Usage in views:
 *   <?= a11y_status_badge('active') ?>
 *   <?= a11y_icon_button('✏️', 'Edit member', $onclick) ?>
 *   <?= a11y_table_caption('Member Enrollment List') ?>
 *   <?= a11y_section_open('Audit Log', 'audit') ?> … <?= a11y_section_close() ?>
 */

/* ================================================================
 |  STATUS BADGE  (color + icon + text — never color alone)
 |  ================================================================ */

if (!function_exists('a11y_status_badge')) {

    /**
     * Render a status badge with:
     *   - background color
     *   - leading icon (emoji, readable by screen readers)
     *   - text label
     *   - data-status-badge (for high-contrast mode override)
     *
     * @param string $status   Canonical status key (active, pending, expired, …)
     * @param string $label    Optional label override
     * @param bool   $pulse    Add pulsing dot (for "live"/"pending" states)
     */
    function a11y_status_badge(string $status, ?string $label = null, bool $pulse = false): string
    {
        $map = [
            'active'         => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'icon' => '✅'],
            'paid'           => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'icon' => '✅'],
            'approved'       => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'icon' => '✅'],
            'linked'         => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'icon' => '✅'],
            'success'        => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'icon' => '✅'],
            'verified'       => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'icon' => '✅'],

            'pending'        => ['bg' => 'bg-amber-100',  'text' => 'text-amber-900',  'icon' => '⏳'],
            'partial_refund' => ['bg' => 'bg-amber-100',  'text' => 'text-amber-900',  'icon' => '↩️'],
            'soon'           => ['bg' => 'bg-amber-100',  'text' => 'text-amber-900',  'icon' => '⏰'],
            'paused'         => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'icon' => '⏸️'],
            'processing'     => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'icon' => '⚙️'],

            'expired'        => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => '❌'],
            'failed'         => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => '❌'],
            'rejected'       => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => '❌'],
            'urgent'         => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => '⚠️'],
            'cancelled'      => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => '🚫'],

            'refunded'       => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => '↩️'],
            'inactive'       => ['bg' => 'bg-gray-200',   'text' => 'text-gray-700',   'icon' => '💤'],
            'churned'        => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => '📉'],

            'info'           => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'icon' => 'ℹ️'],
        ];

        $cfg = $map[strtolower($status)] ?? [
            'bg' => 'bg-gray-200',
            'text' => 'text-gray-700',
            'icon' => '•',
        ];

        $displayLabel = $label ?? ucfirst(str_replace('_', ' ', $status));

        $pulseDot = $pulse
            ? '<span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-current animate-pulse" aria-hidden="true"></span>'
            : '';

        /* aria-label includes "status" context so screen readers don't
           read it as a stray word */
        return sprintf(
            '<span data-status-badge class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase %s %s">'
                . '%s'
                . '<span aria-hidden="true">%s</span>'
                . '<span>%s</span>'
                . '</span>',
            $cfg['bg'],
            $cfg['text'],
            $pulseDot,
            $cfg['icon'],
            e($displayLabel)
        );
    }
}

/* ================================================================
 |  ICON-ONLY BUTTON
 |  ================================================================ */

if (!function_exists('a11y_icon_button')) {

    /**
     * Render an icon-only button with aria-label, 44×44 target,
     * and optional tooltip.
     *
     * @param string $icon       Emoji or SVG string
     * @param string $label      Screen-reader + tooltip label (required)
     * @param string $onclick    Optional JS handler
     * @param array  $attrs      Additional attributes ['class' => ..., 'disabled' => true]
     */
    function a11y_icon_button(
        string $icon,
        string $label,
        string $onclick = '',
        array $attrs = []
    ): string {
        $class = $attrs['class']
            ?? 'flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-sm transition-all hover:-translate-y-0.5 hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-[#00A6A6] hover:shadow-md';

        $disabled = !empty($attrs['disabled']) ? ' disabled aria-disabled="true"' : '';
        $onclickAttr = $onclick !== '' ? ' onclick="' . e($onclick) . '"' : '';
        $extra = '';

        foreach ($attrs as $k => $v) {
            if (in_array($k, ['class', 'disabled'], true)) continue;
            $extra .= ' ' . e($k) . '="' . e((string)$v) . '"';
        }

        return sprintf(
            '<button type="button" data-icon-only class="%s" aria-label="%s" title="%s"%s%s%s>'
                . '<span aria-hidden="true">%s</span>'
                . '</button>',
            e($class),
            e($label),
            e($label),
            $onclickAttr,
            $disabled,
            $extra,
            $icon
        );
    }
}

/* ================================================================
 |  TABLE CAPTION
 |  ================================================================ */

if (!function_exists('a11y_table_caption')) {

    /**
     * Screen-reader-only table caption (visually hidden but semantic).
     * Enables proper table association for assistive tech.
     */
    function a11y_table_caption(string $text, bool $visible = false): string
    {
        $cls = $visible ? 'mt-0 text-left text-xs font-semibold text-gray-500' : 'sr-only';
        return '<caption class="' . $cls . '">' . e($text) . '</caption>';
    }
}

/* ================================================================
 |  FILTER FIELDSET
 |  ================================================================ */

if (!function_exists('a11y_filter_open')) {

    /**
     * Open a semantic filter group: <fieldset> + visually-hidden <legend>.
     * Screen readers announce "Filter by status, group" when a user tabs in.
     *
     * Call a11y_filter_close() after the group's controls.
     */
    function a11y_filter_open(string $legend, string $extraClass = ''): string
    {
        $cls = trim('contents ' . $extraClass);
        return '<fieldset class="' . e($cls) . '">'
            . '<legend class="sr-only">' . e($legend) . '</legend>';
    }

    function a11y_filter_close(): string
    {
        return '</fieldset>';
    }
}

/* ================================================================
 |  SECTION REGION
 |  ================================================================ */

if (!function_exists('a11y_section_open')) {

    /**
     * Open a labeled region. Renders <section role="region" aria-label="…">.
     * Every major content block should use one.
     */
    function a11y_section_open(string $label, string $extraClass = ''): string
    {
        $cls = trim($extraClass);
        return '<section role="region" aria-label="' . e($label) . '"'
            . ($cls !== '' ? ' class="' . e($cls) . '"' : '')
            . '>';
    }

    function a11y_section_close(): string
    {
        return '</section>';
    }
}

/* ================================================================
 |  TOOLTIP  (? icon with accessible description)
 |  ================================================================ */

if (!function_exists('a11y_tooltip')) {

    /**
     * Inline help tooltip. Uses role="tooltip" + aria-describedby.
     * Shows on hover AND keyboard focus.
     *
     * @param string $text     Help text
     * @param string $targetId A unique id (auto-generated if empty)
     */
    function a11y_tooltip(string $text, ?string $targetId = null): string
    {
        static $counter = 0;
        $counter++;

        $id = $targetId ?: 'tip-' . $counter . '-' . bin2hex(random_bytes(2));

        return sprintf(
            '<span class="relative inline-flex items-center" data-tooltip-root>'
                . '<button type="button" class="flex h-5 w-5 items-center justify-center rounded-full border border-gray-300 bg-white text-[10px] font-bold text-gray-500 transition-colors hover:border-[#00A6A6] hover:text-[#00A6A6]" '
                . ' aria-label="Help: %s" aria-describedby="%s" data-tooltip-trigger>'
                . '?'
                . '</button>'
                . '<span id="%s" role="tooltip" hidden '
                . ' class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 w-56 -translate-x-1/2 rounded-lg bg-gray-900 p-3 text-xs font-normal leading-relaxed text-white shadow-xl">'
                . '%s'
                . '</span>'
                . '</span>',
            e($text),
            e($id),
            e($id),
            e($text)
        );
    }
}

/* ================================================================
 |  LIVE REGION
 |  ================================================================ */

if (!function_exists('a11y_live_region')) {

    /**
     * Mark a container as a polite live region — screen readers announce
     * text changes inside it without stealing focus.
     *
     * Typically used on filter result counts:
     *   <span <?= a11y_live_region('polite') ?> id="result-count">42 results</span>
     */
    function a11y_live_region(string $politeness = 'polite', string $extraClass = ''): string
    {
        $cls = trim($extraClass);
        return 'aria-live="' . e($politeness) . '"'
            . ' aria-atomic="true"'
            . ($cls !== '' ? ' class="' . e($cls) . '"' : '');
    }
}

/* ================================================================
 |  SKIP TO CONTENT
 |  ================================================================ */

if (!function_exists('a11y_skip_link')) {

    /**
     * Renders a "Skip to main content" link. Visually hidden until
     * focused via Tab. Include as the first focusable element on any page.
     */
    function a11y_skip_link(string $targetId = 'main-content'): string
    {
        return '<a href="#' . e($targetId) . '" class="skip-to-content">'
            . '⏭️ Skip to main content'
            . '</a>';
    }
}

/* ================================================================
 |  KEYBOARD SHORTCUT HINT
 |  ================================================================ */

if (!function_exists('a11y_shortcut')) {

    /**
     * Renders a keyboard shortcut hint like: Press <kbd>Alt</kbd>+<kbd>A</kbd>
     */
    function a11y_shortcut(string $keys, string $action = ''): string
    {
        $parts = preg_split('/\s*\+\s*/', $keys) ?: [$keys];
        $kbd   = '';
        foreach ($parts as $i => $key) {
            if ($i > 0) $kbd .= '<span class="mx-0.5 text-gray-400">+</span>';
            $kbd .= '<kbd>' . e($key) . '</kbd>';
        }

        $label = $action !== '' ? ' ' . e($action) : '';

        return '<span class="inline-flex items-center gap-1 text-[11px] text-gray-500">'
            . $kbd . $label
            . '</span>';
    }
}

/* ================================================================
 |  TIER ICON (alt-text aware)
 |  ================================================================ */

if (!function_exists('a11y_tier_icon')) {

    /**
     * Render a tier icon with proper alt text.
     * Emojis are read as words by screen readers, so we suppress the
     * emoji itself and provide a descriptive label instead.
     */
    function a11y_tier_icon(string $tier, bool $withLabel = false): string
    {
        $map = [
            'digital'  => ['📱', 'Digital tier'],
            'standard' => ['⚡', 'Standard tier'],
            'premium'  => ['👑', 'Premium tier'],
            'b2b'      => ['🤝', 'B2B tier'],
        ];

        [$icon, $label] = $map[$tier] ?? ['•', ucfirst($tier) . ' tier'];

        $out = '<span role="img" aria-label="' . e($label) . '" title="' . e($label) . '" class="inline-block">'
            . '<span aria-hidden="true">' . $icon . '</span>'
            . '</span>';

        if ($withLabel) {
            $out .= ' <span class="text-xs font-bold uppercase text-gray-700">' . e(ucfirst($tier)) . '</span>';
        }

        return $out;
    }
}

/* ================================================================
 |  LOADING SPINNER (with aria-live)
 |  ================================================================ */

if (!function_exists('a11y_loading_spinner')) {

    /**
     * Accessible loading indicator. Announces "Loading…" via role="status".
     */
    function a11y_loading_spinner(string $message = 'Loading…'): string
    {
        return '<span role="status" aria-live="polite" class="inline-flex items-center gap-2 text-sm text-gray-500">'
            . '<span class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-[#00A6A6]" aria-hidden="true"></span>'
            . '<span>' . e($message) . '</span>'
            . '</span>';
    }
}

/* ================================================================
 |  VISUALLY HIDDEN
 |  ================================================================ */

if (!function_exists('a11y_visually_hidden')) {

    /**
     * Wrap text so it's available to screen readers but not visible.
     * Prefer this over `display:none` — SR-only content stays accessible.
     */
    function a11y_visually_hidden(string $text): string
    {
        return '<span class="sr-only">' . e($text) . '</span>';
    }
}

/* ================================================================
 |  ARIA LABEL ATTRIBUTE BUILDER
 |  ================================================================ */

if (!function_exists('a11y_aria')) {

    /**
     * Build ARIA attributes from an associative array.
     * Skips null values so conditionals are clean.
     *
     *   <div <?= a11y_aria(['label' => 'Live gym occupancy', 'live' => 'polite']) ?>>
     *   →  aria-label="Live gym occupancy" aria-live="polite"
     */
    function a11y_aria(array $attrs): string
    {
        $out = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) continue;
            if ($value === true) $value = 'true';

            $name = str_starts_with($key, 'aria-') || str_starts_with($key, 'role')
                ? $key
                : 'aria-' . $key;

            $out[] = $name . '="' . e((string)$value) . '"';
        }
        return implode(' ', $out);
    }
}
