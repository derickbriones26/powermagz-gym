<?php

/** @var string $content  Rendered view HTML, injected by Controller::view() */
$user = current_user();

// Helper to keep SVG icons clean and consistent
function icon($path)
{
    return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '"/></svg>';
}

/* ================================================================
 |  SIDEBAR CONFIGURATION
 | ================================================================ */

if ($user && $user['role'] === 'admin') {
    $brandSub = 'ADMIN PANEL';
    $sections = [
        ['label' => 'MAIN', 'items' => [
            ['to' => '/admin-dashboard?tab=overview', 'icon' => icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'), 'label' => 'Overview'],
            ['to' => '/admin-dashboard?tab=members',  'icon' => icon('M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'), 'label' => 'Members'],
        ]],
        ['label' => 'MANAGE', 'items' => [
            ['to' => '/admin-dashboard?tab=programs',    'icon' => icon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'), 'label' => 'Programs'],
            ['to' => '/admin-dashboard?tab=enrollments', 'icon' => icon('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'), 'label' => 'Enrollments'],
            ['to' => '/admin-dashboard?tab=payments',    'icon' => icon('M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'), 'label' => 'Payments'],
            ['to' => '/admin-dashboard?tab=partners',    'icon' => icon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'), 'label' => 'Partners'],
            ['to' => '/admin-dashboard?tab=payouts',     'icon' => icon('M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'), 'label' => 'Payouts'],
        ]],
        ['label' => 'MONITOR', 'items' => [
            ['to' => '/admin-dashboard?tab=live', 'icon' => icon('M13 10V3L4 14h7v7l9-11h-7z'), 'label' => 'Live Time In/Out'],
        ]],
        ['label' => 'TOOLS', 'items' => [
            ['to' => '/admin-dashboard?tab=analytics', 'icon' => icon('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'), 'label' => 'Analytics'],
            ['to' => '/admin-dashboard?tab=qr',        'icon' => icon('M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'), 'label' => 'QR Codes'],
            ['to' => '/admin-dashboard?tab=audit',     'icon' => icon('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'), 'label' => 'Audit Log'],
        ]],
        ['label' => 'ACCOUNT', 'items' => [
            ['to' => '/mfa/setup',                    'icon' => icon('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'), 'label' => 'Security'],
            ['to' => '/admin-dashboard?tab=settings', 'icon' => icon('M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'), 'label' => 'Settings'],
        ]],
    ];
} elseif ($user && $user['role'] === 'staff') {
    $brandSub = 'STAFF PANEL';
    $sections = [
        ['label' => 'MAIN', 'items' => [
            ['to' => '/admin-dashboard?tab=overview', 'icon' => icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'), 'label' => 'Overview'],
            ['to' => '/admin-dashboard?tab=members',  'icon' => icon('M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'), 'label' => 'Members'],
        ]],
        ['label' => 'OPERATIONS', 'items' => [
            ['to' => '/admin-dashboard?tab=enrollments', 'icon' => icon('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'), 'label' => 'Enrollments'],
            ['to' => '/admin-dashboard?tab=payments',    'icon' => icon('M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'), 'label' => 'Payments'],
        ]],
        ['label' => 'MONITOR', 'items' => [
            ['to' => '/admin-dashboard?tab=live', 'icon' => icon('M13 10V3L4 14h7v7l9-11h-7z'), 'label' => 'Live Time In/Out'],
        ]],
        ['label' => 'TOOLS', 'items' => [
            ['to' => '/admin-dashboard?tab=analytics', 'icon' => icon('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'), 'label' => 'Analytics'],
            ['to' => '/admin-dashboard?tab=qr',        'icon' => icon('M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'), 'label' => 'QR Codes'],
        ]],
        ['label' => 'ACCOUNT', 'items' => [
            ['to' => '/mfa/setup', 'icon' => icon('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'), 'label' => 'Security'],
        ]],
    ];
} elseif ($user && $user['role'] === 'partner') {
    $brandSub = 'PARTNER PORTAL';
    $sections = [
        ['label' => 'MAIN', 'items' => [
            ['to' => '/partner-dashboard',         'icon' => icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'), 'label' => 'Dashboard'],
            ['to' => '/partner-dashboard/clients', 'icon' => icon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'), 'label' => 'My Clients'],
        ]],
        ['label' => 'EARNINGS', 'items' => [
            ['to' => '/partner-dashboard/sales',  'icon' => icon('M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'), 'label' => 'Sales & Referrals'],
            ['to' => '/partner-dashboard/wallet', 'icon' => icon('M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'), 'label' => 'Wallet'],
        ]],
        ['label' => 'ACCOUNT', 'items' => [
            ['to' => '/partner-dashboard/profile', 'icon' => icon('M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'), 'label' => 'My Profile'],
        ]],
    ];
} else {
    $brandSub = 'MEMBER PORTAL';
    $sections = [
        ['label' => 'MAIN', 'items' => [
            ['to' => '/home',     'icon' => icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'), 'label' => 'Home'],
            ['to' => '/programs', 'icon' => icon('M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'), 'label' => 'Programs'],
        ]],
        ['label' => 'TRACKING', 'items' => [
            ['to' => '/attendance', 'icon' => icon('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'), 'label' => 'Attendance'],
            ['to' => '/payments',   'icon' => icon('M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'), 'label' => 'Payments'],
        ]],
        ['label' => 'ACCOUNT', 'items' => [
            ['to' => '/profile', 'icon' => icon('M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'), 'label' => 'My Profile'],
        ]],
    ];
}

require BASE_PATH . '/src/Views/layouts/portal_head.php';
echo $content;
require BASE_PATH . '/src/Views/layouts/portal_foot.php';
