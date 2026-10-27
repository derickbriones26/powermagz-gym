<?php
$user = current_user();
$brandSub = $brandSub ?? 'PORTAL';
$sections = $sections ?? [];

/* §2.1 — session warning + MFA nudge data */
$sessionWarning = function_exists('session_security_warning_data')
    ? session_security_warning_data()
    : null;

$needsMfaNudge = $user
    && in_array($user['role'] ?? '', ['admin', 'staff'], true)
    && empty($user['mfa_enabled']);
?>
<!doctype html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <meta name="base" content="<?= e(base_path()) ?>">
    <title><?= e($pageTitle ?? 'Portal') ?> — <?= APP_NAME ?></title>
    <link rel="icon" href="<?= url_e('/public/images/powermagz_gym.png') ?>">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind -->
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
                        brand: '#00A6A6',
                        darkbg: '#050505',
                        cardbg: '#111111'
                    }
                }
            }
        }
    </script>

    <style>
        /* Core Body Settings */
        body {
            background-color: #050505;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
        }

        /* Safe Area for iPhone Notch/Home Indicator */
        .pb-safe {
            padding-bottom: env(safe-area-inset-bottom, 0.5rem);
        }

        /* Ambient Background Animations */
        @keyframes float-orb-1 {
            0% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-40px, 60px) scale(1.1);
            }

            100% {
                transform: translate(0, 0) scale(1);
            }
        }

        @keyframes float-orb-2 {
            0% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(50px, -40px) scale(1.05);
            }

            100% {
                transform: translate(0, 0) scale(1);
            }
        }

        .orb-1 {
            animation: float-orb-1 20s ease-in-out infinite alternate;
        }

        .orb-2 {
            animation: float-orb-2 25s ease-in-out infinite alternate;
        }

        @media (prefers-reduced-motion: reduce) {

            .orb-1,
            .orb-2 {
                animation: none !important;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #050505;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #00A6A6;
        }

        /* Sidebar Glassmorphism */
        .sidebar-glass {
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Topbar Glassmorphism */
        .topbar-glass {
            background: rgba(5, 5, 5, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Nav Items */
        .nav-item {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.03);
            color: #ffffff;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(0, 166, 166, 0.15) 0%, transparent 100%);
            border-left-color: #00A6A6;
            color: #00A6A6;
        }

        /* Micro-interactions (Tap Effects) */
        .tap-effect {
            transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.15s ease;
        }

        .tap-effect:active {
            transform: scale(0.96);
            opacity: 0.9;
        }

        /* Modal Animations */
        @keyframes pop-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-animate {
            animation: pop-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* ========================================== */
        /* MODERN NUMBER INPUTS (2026)                */
        /* Hides the ugly default white spinner arrows */
        /* ========================================== */

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>
</head>

<body class="min-h-screen font-body text-gray-300 relative">
    <?php require BASE_PATH . '/src/Views/layouts/loading_screen.php'; ?>

    <!-- AMBIENT ANIMATED BACKGROUND -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
        <div class="orb-1 absolute -top-[10%] -right-[10%] w-[600px] h-[600px] bg-[#00A6A6] rounded-full blur-[150px] opacity-[0.12]"></div>
        <div class="orb-2 absolute -bottom-[10%] -left-[10%] w-[500px] h-[500px] bg-[#00A6A6] rounded-full blur-[150px] opacity-[0.08]"></div>
    </div>

    <?php
    // Active-link detection
    $requestUri   = $_SERVER['REQUEST_URI'] ?? '/';
    $currentPath  = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $currentQuery = [];
    parse_str(parse_url($requestUri, PHP_URL_QUERY) ?? '', $currentQuery);

    $base = rtrim(base_path(), '/');
    if ($base && str_starts_with($currentPath, $base)) {
        $currentPath = substr($currentPath, strlen($base));
    }
    $currentPath = rtrim($currentPath, '/') ?: '/';

    if ($currentPath === '/admin-dashboard' && !isset($currentQuery['tab'])) {
        $currentQuery['tab'] = 'overview';
    }

    function nav_link_is_active(string $linkTo, string $currentPath, array $currentQuery): bool
    {
        $linkParts = parse_url($linkTo);
        $linkPath  = rtrim($linkParts['path'] ?? '/', '/') ?: '/';
        $linkQuery = [];
        parse_str($linkParts['query'] ?? '', $linkQuery);

        if ($linkPath !== $currentPath) return false;

        foreach ($linkQuery as $k => $v) {
            if (($currentQuery[$k] ?? null) !== $v) return false;
        }
        if (empty($linkQuery) && !empty($currentQuery['tab'])) return false;
        return true;
    }
    ?>

    <!-- Mobile Overlay (Tap outside to close) -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-black/70 backdrop-blur-sm lg:hidden transition-opacity" onclick="toggleSidebarMobile()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col sidebar-glass transition-transform duration-300 -translate-x-full lg:translate-x-0">

        <!-- Brand -->
        <div class="flex h-20 flex-shrink-0 items-center border-b border-white/5 px-6">
            <a href="<?= url_e('/home') ?>" class="flex items-center gap-3 group">
                <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="PowerMagz" class="h-10 w-10 flex-shrink-0 rounded-full ring-2 ring-[#00A6A6]/30 group-hover:scale-105 transition-transform">

                <div class="flex flex-col justify-center">
                    <div class="font-heading text-xl tracking-widest text-white leading-none">POWERMAGZ</div>
                    <div class="mt-1 text-[9px] font-bold tracking-[0.25em] text-[#00A6A6] leading-none"><?= e($brandSub) ?></div>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
            <?php foreach ($sections as $sec): ?>
                <div>
                    <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500">
                        <?= e($sec['label']) ?>
                    </div>
                    <div class="space-y-1">
                        <?php foreach ($sec['items'] as $item):
                            $active = nav_link_is_active($item['to'], $currentPath, $currentQuery);
                        ?>
                            <a href="<?= url_e($item['to']) ?>"
                                class="nav-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold <?= $active ? 'active' : 'text-gray-400' ?>">
                                <!-- SVG Icon -->
                                <span class="flex w-5 flex-shrink-0 items-center justify-center">
                                    <?= $item['icon'] ?>
                                </span>
                                <span class="truncate"><?= e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom Actions -->
        <div class="flex-shrink-0 border-t border-white/5 p-4">
            <a href="<?= url_e('/') ?>" class="nav-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="truncate">Back to Site</span>
            </a>
            <button type="button" onclick="openSignOutModal()" class="nav-item mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-gray-400 hover:bg-red-500/10 hover:text-red-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="truncate">Sign Out</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Wrapper (pb-24 for mobile bottom nav) -->
    <div id="main-content" class="transition-all duration-300 lg:pl-64 relative z-10 pb-24 lg:pb-0">

        <!-- Topbar -->
        <div class="sticky top-0 z-30 flex h-20 items-center justify-between topbar-glass px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <button type="button" onclick="toggleSidebarMobile()" class="lg:hidden text-gray-400 hover:text-white p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="font-heading text-2xl tracking-wide text-white"><?= e($pageTitle ?? '') ?></div>
            </div>

            <div class="flex items-center gap-4 sm:gap-6">

                <!-- NOTIFICATION BELL -->
                <div class="relative">
                    <button type="button"
                        id="notif-bell"
                        onclick="toggleNotif()"
                        aria-label="Notifications"
                        class="relative flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-300 transition-all hover:border-[#00A6A6] hover:text-[#00A6A6] hover:bg-[#00A6A6]/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span id="notif-dot" class="hidden absolute right-2 top-2 flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-60"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        </span>
                    </button>

                    <!-- Notification Panel -->
                    <div id="notif-panel" class="hidden absolute right-0 top-14 z-50 w-80 overflow-hidden rounded-2xl border border-white/10 bg-[#111] shadow-2xl backdrop-blur-md">
                        <div class="border-b border-white/5 px-4 py-3 flex items-center justify-between">
                            <div class="text-xs font-bold uppercase tracking-widest text-gray-400">Notifications</div>
                            <button type="button" class="text-[10px] font-bold text-[#00A6A6] hover:text-white transition-colors" onclick="markAllRead()">Mark all read</button>
                        </div>
                        <div id="notif-list" class="max-h-80 overflow-y-auto"></div>
                    </div>
                </div>

                <!-- CLICKABLE USER PROFILE -->
                <?php
                // Determine the correct profile link based on role
                $profileUrl = match ($user['role'] ?? 'user') {
                    'admin', 'staff' => '/admin-dashboard?tab=settings',
                    'partner'        => '/partner-dashboard/profile',
                    default          => '/profile',
                };
                ?>
                <a href="<?= url_e($profileUrl) ?>" class="group flex items-center gap-3 sm:gap-4 rounded-full p-1 pr-2 transition-all duration-300 hover:bg-white/5 hover:pr-3 cursor-pointer" title="View Profile">

                    <!-- User Info -->
                    <div class="hidden sm:flex flex-col items-end">
                        <span class="text-sm font-bold text-white transition-colors group-hover:text-[#00A6A6]">
                            <?= e($user['full_name'] ?: $user['email']) ?>
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#00A6A6]">
                            <?= e($user['role'] ?? 'Member') ?>
                        </span>
                    </div>

                    <!-- Avatar -->
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-[#00A6A6] to-[#008c8c] font-heading text-lg text-white shadow-lg transition-transform duration-300 group-hover:scale-105 group-hover:shadow-[0_0_15px_rgba(0,166,166,0.4)]">
                        <?= e(strtoupper(substr($user['full_name'] ?: $user['email'], 0, 1))) ?>
                    </div>
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8 pb-12 lg:pb-8">
            <?php if ($m = flash('success')): ?><div data-flash class="mb-6 rounded-xl border border-green-500/30 bg-green-500/10 p-4 text-sm text-green-400"><?= e($m) ?></div><?php endif; ?>
            <?php if ($m = flash('error')): ?><div data-flash class="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400"><?= e($m) ?></div><?php endif; ?>
            <?php if ($m = flash('warning')): ?><div data-flash class="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-400"><?= e($m) ?></div><?php endif; ?>

            <!-- MOBILE BOTTOM NAVIGATION (MEMBERS ONLY) -->
            <?php if (($user['role'] ?? '') === 'user'): ?>
                <nav class="fixed bottom-0 left-0 z-40 w-full border-t border-white/10 bg-[#0a0a0a]/95 backdrop-blur-xl lg:hidden pb-safe">
                    <div class="flex items-end justify-around px-2 pb-2 pt-3">
                        <!-- Home -->
                        <a href="<?= url_e('/home') ?>" class="group flex flex-col items-center justify-center gap-1 w-16 transition-colors <?= $currentPath === '/home' ? 'text-[#00A6A6]' : 'text-gray-500 hover:text-gray-300' ?>">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="text-[10px] font-bold">Home</span>
                        </a>

                        <!-- Attendance -->
                        <a href="<?= url_e('/attendance') ?>" class="group flex flex-col items-center justify-center gap-1 w-16 transition-colors <?= $currentPath === '/attendance' ? 'text-[#00A6A6]' : 'text-gray-500 hover:text-gray-300' ?>">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[10px] font-bold">History</span>
                        </a>

                        <!-- TIME IN (Center Floating Button) -->
                        <a href="<?= url_e('/time-in') ?>" class="group flex flex-col items-center justify-center -mt-7 w-16 tap-effect">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#00A6A6] text-white shadow-[0_0_20px_rgba(0,166,166,0.4)] border-4 border-[#0a0a0a] transition-transform duration-300 active:scale-95 <?= $currentPath === '/time-in' ? 'scale-110 shadow-[0_0_30px_rgba(0,166,166,0.6)]' : '' ?>">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold text-[#00A6A6] mt-1">Time In</span>
                        </a>

                        <!-- Payments -->
                        <a href="<?= url_e('/payments') ?>" class="group flex flex-col items-center justify-center gap-1 w-16 transition-colors <?= $currentPath === '/payments' ? 'text-[#00A6A6]' : 'text-gray-500 hover:text-gray-300' ?>">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span class="text-[10px] font-bold">Payments</span>
                        </a>

                        <!-- Profile -->
                        <a href="<?= url_e('/profile') ?>" class="group flex flex-col items-center justify-center gap-1 w-16 transition-colors <?= $currentPath === '/profile' ? 'text-[#00A6A6]' : 'text-gray-500 hover:text-gray-300' ?>">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="text-[10px] font-bold">Profile</span>
                        </a>
                    </div>
                </nav>
            <?php endif; ?>