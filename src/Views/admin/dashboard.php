<?php

/**
 * Admin Dashboard — tab container
 * Renders the correct partial based on ?tab= query parameter.
 * Navigation lives in the sidebar (portal_head.php), not here.
 */

$tabs = [
    'overview'    => 'overview_tab.php',
    'members'     => 'members_tab.php',
    'programs'    => 'programs_tab.php',
    'enrollments' => 'enrollments_tab.php',
    'payments'    => 'payments_tab.php',
    'payouts'     => 'payouts_tab.php',
    'live'        => 'live_tab.php',
    'analytics'   => 'analytics_tab.php',
    'settings'    => 'settings_tab.php',
    'qr'          => 'qr_tab.php',
    'partners'    => 'partners_tab.php',
    'audit'       => 'audit_tab.php',        // ← NEW
];

$activeTab = $_GET['tab'] ?? 'overview';
if (!isset($tabs[$activeTab])) {
    $activeTab = 'overview';
}

$partialPath = BASE_PATH . "/src/Views/admin/partials/{$tabs[$activeTab]}";
?>

<div class="space-y-6 tab-content">
    <?php
    if (is_file($partialPath)) {
        require $partialPath;
    } else {
        echo '<div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">';
        echo '<p class="text-sm text-gray-500">Tab not found: <code>' . e($activeTab) . '</code></p>';
        echo '</div>';
    }
    ?>
</div>

<style>
    /* Fade-in animation when switching tabs */
    .tab-content {
        animation: tab-fade-in 0.35s ease-out;
    }

    @keyframes tab-fade-in {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .tab-content {
            animation: none;
        }
    }
</style>