<?php

/**
 * Admin — Form Helpers (2026 UI)
 *
 * Reusable form building blocks used by every admin modal.
 * Loaded via `require BASE_PATH . '/src/Views/admin/partials/_form_helpers.php'`
 * at the top of any tab that needs them.
 *
 * Provides:
 *   - form_required_mark()      → red asterisk
 *   - form_error_summary()      → top-of-form alert with jump links
 *   - form_field_error()        → inline error message under a field
 *   - form_input_class()        → error-aware border/ring classes
 *   - form_label()              → label with optional asterisk, help text, & SVG icon
 *   - form_title_options()      → title dropdown options
 *   - form_gender_options()     → inclusive gender dropdown options
 */

if (!function_exists('form_required_mark')) {

    /**
     * Red asterisk for required fields.
     */
    function form_required_mark(): string
    {
        return '<span class="text-[#D50000] font-bold" aria-hidden="true">*</span>';
    }

    /**
     * Error summary block at the top of a form.
     * Each error gets a jump link to its field if `field_id` is provided.
     *
     * @param array<int,array{field?:string,message:string}> $errors
     */
    function form_error_summary(array $errors): string
    {
        if (empty($errors)) return '';

        $items = '';
        foreach ($errors as $e) {
            $msg = $e['message'] ?? $e;
            $fid = $e['field']   ?? null;

            if ($fid) {
                $items .= '<li><a href="#' . e($fid) . '" class="font-semibold underline hover:no-underline transition-colors">'
                    . e($msg) . '</a></li>';
            } else {
                $items .= '<li>' . e($msg) . '</li>';
            }
        }

        return '<div role="alert" aria-live="assertive"
                     class="rounded-xl border border-red-500/30 bg-red-500/10 p-4 mb-5 animate-[fade-in_0.3s_ease-out]"
                     data-error-summary>
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-red-400">
                                Please fix the following before continuing:
                            </div>
                            <ul class="mt-2 list-disc space-y-0.5 pl-5 text-xs text-red-300">
                                ' . $items . '
                            </ul>
                        </div>
                    </div>
                </div>';
    }

    /**
     * Inline field-level error message.
     * Returns empty string if $message is null.
     */
    function form_field_error(?string $message, string $id = ''): string
    {
        if ($message === null || $message === '') return '';

        $idAttr = $id !== '' ? ' id="' . e($id) . '"' : '';

        return '<span' . $idAttr . ' role="alert"
                       class="mt-1 block text-xs font-semibold text-[#D50000]"
                       data-field-error>'
            . e($message)
            . '</span>';
    }

    /**
     * Error-aware input border + focus ring classes.
     */
    function form_input_class(bool $hasError, string $base = 'h-11 w-full rounded-xl border px-4 text-sm outline-none transition-all'): string
    {
        if ($hasError) {
            return $base . ' border-[#D50000] bg-red-500/5 text-white placeholder-gray-600 focus:border-[#D50000] focus:ring-2 focus:ring-[#D50000]/20';
        }
        return $base . ' border-white/10 bg-[#0a0a0a] text-white placeholder-gray-600 focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20';
    }

    /**
     * Label with optional required asterisk, helper text, and icon.
     * 
     * @param string $text       Label text
     * @param bool   $required   Show asterisk
     * @param string $help       Small helper text under the field
     * @param string $icon       SVG path string or emoji
     */
    function form_label(string $text, bool $required = false, string $help = '', string $icon = ''): string
    {
        $out  = '<span class="mb-2 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">';

        if ($icon !== '') {
            // Auto-detect if the icon is an SVG path (starts with M/m followed by coordinates)
            if (preg_match('/^[Mm][0-9\s\-\.]/', $icon)) {
                $out .= '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">'
                    . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . e($icon) . '"/>'
                    . '</svg>';
            } else {
                // Fallback for emoji or plain text icon
                $out .= '<span aria-hidden="true">' . e($icon) . '</span>';
            }
        }

        $out .= e($text);

        if ($required) {
            $out .= ' ' . form_required_mark();
        }

        $out .= '</span>';

        if ($help !== '') {
            $out .= '<span class="mt-2 block text-[11px] text-gray-500">' . e($help) . '</span>';
        }

        return $out;
    }

    /**
     * Title options for the admin member form (§1.6).
     */
    function form_title_options(): array
    {
        return [
            'none' => '— (none)',
            'mr'   => 'Mr.',
            'ms'   => 'Ms.',
            'mx'   => 'Mx.',
            'dr'   => 'Dr.',
        ];
    }

    /**
     * Inclusive gender options (§1.6).
     */
    function form_gender_options(): array
    {
        return [
            'prefer_not_to_say' => 'Prefer not to say',
            'female'            => 'Female',
            'male'              => 'Male',
            'non_binary'        => 'Non-binary',
            'self_describe'     => 'Prefer to self-describe',
        ];
    }
}
