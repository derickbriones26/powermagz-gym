<?php

/**
 * Admin — Reusable Stat Card Component (2026 UI)
 *
 * Usage:
 *   $card = [
 *     'icon'  => '<svg class="w-6 h-6" ...></svg>', // Heroicon SVG string
 *     'label' => 'Total Members',
 *     'value' => 42,
 *     'sub'   => 'Registered users',
 *     'color' => '#00A6A6', // Hex color for accents/glow
 *     'trend' => 12.5,      // optional: positive or negative %
 *     'delay' => 0,         // optional: animation delay in ms
 *     'href'  => '/admin-dashboard?tab=members', // optional: makes card clickable
 *   ];
 *   require BASE_PATH . '/src/Views/admin/partials/stat_card.php';
 */

// ---------- Defaults ----------
$icon  = $icon  ?? ($card['icon']  ?? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>');
$label = $label ?? ($card['label'] ?? 'Metric');
$value = $value ?? ($card['value'] ?? '—');
$sub   = $sub   ?? ($card['sub']   ?? null);
$color = $color ?? ($card['color'] ?? '#00A6A6');
$trend = $trend ?? ($card['trend'] ?? null);
$delay = $delay ?? ($card['delay'] ?? 0);
$href  = $href  ?? ($card['href']  ?? null);

$isClickable = !empty($href);
$tag = $isClickable ? 'a' : 'div';
$hrefAttr = $isClickable ? ' href="' . url_e($href) . '"' : '';

$trendPositive = $trend !== null && $trend > 0;
$trendNegative = $trend !== null && $trend < 0;
$trendZero     = $trend !== null && $trend == 0;
?>

<<?= $tag ?><?= $hrefAttr ?>
    class="stat-card-component group relative block overflow-hidden rounded-2xl border border-white/5 bg-[#111] p-6 shadow-sm
          transition-all duration-300
          hover:-translate-y-1
          <?= $isClickable ? 'cursor-pointer' : '' ?>"
    style="--hover-color: <?= e($color) ?>; animation-delay: <?= (int)$delay ?>ms;">

    <!-- Colored glow blob (top-right) -->
    <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full opacity-[0.05] transition-transform duration-500 group-hover:scale-[2]"
        style="background-color: <?= e($color) ?>;"></div>

    <!-- Main Content Row -->
    <div class="relative flex items-center justify-between">
        <div class="min-w-0 flex-1">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                <?= e($label) ?>
            </div>

            <div class="mt-2 font-heading text-4xl transition-transform duration-300 group-hover:scale-105"
                style="color: <?= e($color) ?>;">
                <?= e((string)$value) ?>
            </div>

            <?php if ($sub || $trend !== null): ?>
                <div class="mt-1.5 flex items-center gap-2 text-xs text-gray-400">
                    <?php if ($trend !== null): ?>
                        <?php if ($trendPositive): ?>
                            <span class="inline-flex items-center gap-0.5 font-bold text-green-500">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <?= number_format($trend, 1) ?>%
                                <span class="sr-only">increase</span>
                            </span>
                        <?php elseif ($trendNegative): ?>
                            <span class="inline-flex items-center gap-0.5 font-bold text-red-500">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                </svg>
                                <?= number_format(abs($trend), 1) ?>%
                                <span class="sr-only">decrease</span>
                            </span>
                        <?php elseif ($trendZero): ?>
                            <span class="font-bold text-gray-500">— 0%</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($sub): ?>
                        <span class="truncate" title="<?= e($sub) ?>"><?= e($sub) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Icon tile -->
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl transition-all duration-300
                group-hover:rotate-6 group-hover:scale-110"
            style="background-color: <?= e($color) ?>1A; color: <?= e($color) ?>;">
            <div aria-hidden="true" class="transition-transform duration-300 group-hover:scale-110">
                <?= $icon ?>
            </div>
        </div>
    </div>

    <!-- Bottom accent line — sweeps in on hover -->
    <div class="absolute bottom-0 left-0 h-1 w-full origin-left scale-x-0 transition-transform duration-500 group-hover:scale-x-100"
        style="background: linear-gradient(to right, <?= e($color) ?>, transparent);"></div>

    <?php if ($isClickable): ?>
        <div class="absolute right-4 bottom-3 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
            <span class="text-[10px] font-bold uppercase tracking-wider" style="color: <?= e($color) ?>;">
                View &rarr;
            </span>
        </div>
    <?php endif; ?>
</<?= $tag ?>>

<style>
    @keyframes stat-card-enter {
        from {
            opacity: 0;
            transform: translateY(16px) scale(0.96);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .stat-card-component {
        animation: stat-card-enter 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
        text-decoration: none;
        color: inherit;
    }

    /* Dynamic hover border and shadow using CSS variables */
    .stat-card-component:hover {
        border-color: var(--hover-color, #00A6A6) !important;
        box-shadow: 0 20px 40px -12px var(--hover-shadow, rgba(0, 166, 166, 0.25)) !important;
    }

    @media (prefers-reduced-motion: reduce) {

        .stat-card-component,
        .stat-card-component * {
            animation: none !important;
            transition: none !important;
        }
    }
</style>