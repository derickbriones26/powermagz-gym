<?php $user = current_user(); ?>
<!doctype html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle ?? 'Powermagz Gym') ?> — Commit to be Healthy</title>
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
    </style>
</head>

<body class="font-body antialiased selection:bg-brand selection:text-white">

    <header class="fixed inset-x-0 top-0 z-50 glass-nav transition-all duration-300">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="#home" class="flex items-center gap-3 group">
                <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="Powermagz Gym" class="h-11 w-11 rounded-full shadow-lg group-hover:scale-105 transition-transform duration-300">
                <div class="leading-none">
                    <div class="font-heading text-2xl tracking-widest text-white">POWERMAGZ</div>
                    <div class="mt-1 text-[9px] font-bold tracking-[0.3em] text-[#00A6A6]">GYM</div>
                </div>
            </a>

            <!-- Navigation (IDs link directly to sections) -->
            <nav class="hidden items-center gap-8 md:flex">
                <a href="#home" class="text-sm font-semibold text-gray-300 hover:text-[#00A6A6] transition-colors">Home</a>
                <a href="#pricing" class="text-sm font-semibold text-gray-300 hover:text-[#00A6A6] transition-colors">Membership</a>
                <a href="#about" class="text-sm font-semibold text-gray-300 hover:text-[#00A6A6] transition-colors">About Us</a>
                <a href="#contact" class="text-sm font-semibold text-gray-300 hover:text-[#00A6A6] transition-colors">Contact</a>
            </nav>

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

            <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="md:hidden text-white p-2 hover:text-[#00A6A6] transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <div id="mobile-menu" class="hidden border-t border-white/10 bg-[#050505] md:hidden absolute w-full">
            <div class="space-y-2 px-4 pb-6 pt-4">
                <a href="#home" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Home</a>
                <a href="#pricing" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Membership</a>
                <a href="#about" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">About Us</a>
                <a href="#contact" class="block py-2 text-base font-semibold text-white hover:text-[#00A6A6]">Contact</a>
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