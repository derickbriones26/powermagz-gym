<?php $user = current_user(); ?>
<!doctype html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle ?? 'Powermagz Gym') ?></title>
    <meta name="description" content="Powermagz Gym — Modern gym management for Tabaco City. QR attendance, GCash payments, AI coaching, and B2B partner portal.">
    <meta name="theme-color" content="#00A6A6">
    <link rel="icon" type="image/png" href="<?= url_e('/public/images/powermagz_gym.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
        body {
            background-color: #050505;
            color: #ffffff;
            overflow-x: hidden;
        }

        .glass-nav {
            background: rgba(5, 5, 5, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glow-text {
            text-shadow: 0 0 30px rgba(0, 166, 166, 0.4);
        }

        .glow-btn {
            box-shadow: 0 0 20px rgba(0, 166, 166, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glow-btn:hover {
            box-shadow: 0 0 35px rgba(0, 166, 166, 0.6);
            transform: translateY(-2px);
        }

        /* ========================================== */
        /* YOUR WORKING SCROLLBAR CSS                 */
        /* ========================================== */
        ::-webkit-scrollbar {
            width: 8px;
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

        /* ========================================== */
        /* CLEAN LINE ANIMATION                       */
        /* ========================================== */
        .nav-link {
            position: relative;
            color: #9ca3af;
            padding: 8px 12px;
            transition: color 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #00A6A6;
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .nav-link:hover {
            color: #ffffff;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-link.active {
            color: #00A6A6 !important;
            font-weight: 700;
        }

        .nav-link.active::after {
            width: 100%;
        }
    </style>
</head>

<body class="font-body antialiased selection:bg-brand selection:text-white">
    <?php require BASE_PATH . '/src/Views/layouts/loading_screen.php'; ?>

    <!-- Mobile Backdrop (Closes menu when tapped outside) -->
    <div id="mobile-backdrop" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm hidden md:hidden transition-opacity duration-300 opacity-0" onclick="closeMobileMenu()"></div>

    <header class="fixed inset-x-0 top-0 z-50 glass-nav transition-all duration-300">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

            <!-- Logo -->
            <a href="#home" class="flex items-center gap-3 group">
                <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="Powermagz Gym" class="h-11 w-11 rounded-full shadow-lg group-hover:scale-105 transition-transform duration-300">
                <div class="leading-none">
                    <div class="font-heading text-2xl tracking-widest text-white">POWERMAGZ</div>
                    <div class="mt-1 text-[9px] font-bold tracking-[0.3em] text-[#00A6A6]">GYM</div>
                </div>
            </a>

            <!-- Navigation Links (Complete List) -->
            <nav class="hidden items-center gap-2 lg:gap-4 md:flex">
                <a href="#home" class="nav-link text-sm font-semibold">Home</a>
                <a href="#features" class="nav-link text-sm font-semibold">Features</a>
                <a href="#about" class="nav-link text-sm font-semibold">About Us</a>
                <a href="#why-us" class="nav-link text-sm font-semibold">Why Us</a>
                <a href="#pricing" class="nav-link text-sm font-semibold">Pricing</a>
                <a href="#contact" class="nav-link text-sm font-semibold">Contact</a>
            </nav>

            <!-- CTA Buttons -->
            <div class="hidden items-center gap-6 md:flex">
                <?php if ($user):
                    $dash = match ($user['role']) {
                        'admin' => '/admin-dashboard',
                        'partner' => '/partner-dashboard',
                        default => '/home'
                    };
                ?>
                    <a href="<?= url_e($dash) ?>" class="text-sm font-semibold text-white hover:text-[#00A6A6] transition-colors">Dashboard</a>
                <?php else: ?>
                    <a href="<?= url_e('/login') ?>" class="text-sm font-semibold text-white hover:text-[#00A6A6] transition-colors">Log in</a>
                    <a href="<?= url_e('/register') ?>" class="glow-btn rounded-full bg-[#00A6A6] px-6 py-2.5 text-sm font-bold text-white">Buy Membership</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button (Hamburger / X) -->
            <button type="button" id="mobile-menu-btn" onclick="toggleMobileMenu()" class="md:hidden text-white p-2 hover:text-[#00A6A6] transition-colors relative z-50 focus:outline-none">
                <!-- Hamburger Icon (No rotate) -->
                <svg id="icon-hamburger" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <!-- Close (X) Icon (No rotate) -->
                <svg id="icon-close" class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden border-t border-white/10 bg-[#050505] md:hidden absolute w-full shadow-2xl transition-all duration-300 transform -translate-y-2 opacity-0">
            <div class="space-y-2 px-4 pb-6 pt-4">
                <!-- Added onclick to close menu when a link is clicked -->
                <a href="#home" onclick="closeMobileMenu()" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Home</a>
                <a href="#features" onclick="closeMobileMenu()" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Features</a>
                <a href="#about" onclick="closeMobileMenu()" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">About Us</a>
                <a href="#why-us" onclick="closeMobileMenu()" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Why Us</a>
                <a href="#pricing" onclick="closeMobileMenu()" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Pricing</a>
                <a href="#contact" onclick="closeMobileMenu()" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Contact</a>

                <div class="border-t border-white/10 mt-4 pt-4">
                    <?php if ($user): ?>
                        <a href="<?= url_e($dash) ?>" class="block rounded-full bg-white/10 text-center py-3 text-sm font-bold text-white hover:bg-white/20">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="<?= url_e('/login') ?>" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Log in</a>
                        <a href="<?= url_e('/register') ?>" class="mt-2 block rounded-full bg-[#00A6A6] text-center py-3 text-sm font-bold text-white glow-btn">Buy Membership</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main id="main-content" class="pt-20">

        <script>
            // ==========================================
            // Mobile Menu Logic
            // ==========================================
            const menu = document.getElementById('mobile-menu');
            const backdrop = document.getElementById('mobile-backdrop');
            const iconHamburger = document.getElementById('icon-hamburger');
            const iconClose = document.getElementById('icon-close');

            function toggleMobileMenu() {
                const isHidden = menu.classList.contains('hidden');

                if (isHidden) {
                    // Open Menu
                    menu.classList.remove('hidden');
                    backdrop.classList.remove('hidden');

                    // Small delay to allow CSS transition to trigger
                    setTimeout(() => {
                        menu.classList.remove('-translate-y-2', 'opacity-0');
                        menu.classList.add('translate-y-0', 'opacity-100');
                        backdrop.classList.remove('opacity-0');
                        backdrop.classList.add('opacity-100');
                    }, 10);

                    // Swap icons
                    iconHamburger.classList.add('hidden');
                    iconClose.classList.remove('hidden');
                } else {
                    closeMobileMenu();
                }
            }

            function closeMobileMenu() {
                if (menu.classList.contains('hidden')) return; // Already closed

                // Trigger closing animation
                menu.classList.remove('translate-y-0', 'opacity-100');
                menu.classList.add('-translate-y-2', 'opacity-0');
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');

                // Wait for animation to finish before hiding
                setTimeout(() => {
                    menu.classList.add('hidden');
                    backdrop.classList.add('hidden');
                }, 300);

                // Swap icons back
                iconClose.classList.add('hidden');
                iconHamburger.classList.remove('hidden');
            }

            // ==========================================
            // ScrollSpy Script
            // ==========================================
            document.addEventListener('DOMContentLoaded', () => {
                const sections = document.querySelectorAll('section[id]');
                const navLinks = document.querySelectorAll('.nav-link');

                const scrollSpy = () => {
                    let current = '';
                    const scrollY = window.pageYOffset;

                    sections.forEach(section => {
                        const sectionHeight = section.offsetHeight;
                        const sectionTop = section.offsetTop - 100; // Offset for header
                        const sectionId = section.getAttribute('id');

                        if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                            current = sectionId;
                        }
                    });

                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${current}`) {
                            link.classList.add('active');
                        }
                    });
                };

                window.addEventListener('scroll', scrollSpy);
                scrollSpy(); // Run once on page load
            });
        </script>