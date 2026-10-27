<!doctype html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle ?? 'Login') ?> — Powermagz Gym</title>
    <link rel="icon" href="<?= url_e('/public/images/powermagz_gym.png') ?>">
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
                        brand: '#00A6A6'
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #050505;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(17, 17, 17, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        /* BULLETPROOF Input Fields */
        .modern-input {
            background-color: #0a0a0a !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }

        .modern-input:focus {
            border-color: #00A6A6 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(0, 166, 166, 0.2) !important;
        }

        .modern-input::placeholder {
            color: #6b7280 !important;
            opacity: 1 !important;
        }

        /* Chrome Autofill Fix */
        .modern-input:-webkit-autofill,
        .modern-input:-webkit-autofill:hover,
        .modern-input:-webkit-autofill:focus,
        .modern-input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #0a0a0a inset !important;
            -webkit-text-fill-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Password Eye Toggle */
        .pw-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.2s;
            z-index: 10;
        }

        .pw-toggle:hover {
            color: #00A6A6;
        }

        /* Button Glow */
        .glow-btn {
            transition: all 0.3s ease;
            box-shadow: 0 0 15px rgba(0, 166, 166, 0.2);
        }

        .glow-btn:hover {
            box-shadow: 0 0 25px rgba(0, 166, 166, 0.5);
            transform: translateY(-1px);
        }

        /* Back Button */
        .back-btn {
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #00A6A6;
            transform: translateX(-4px);
        }
    </style>
</head>

<body class="flex min-h-screen items-center justify-center p-4 relative overflow-y-auto">
    <?php require BASE_PATH . '/src/Views/layouts/loading_screen.php'; ?>

    <!-- Ambient Glowing Orbs -->
    <div class="fixed top-0 left-0 w-[400px] sm:w-[600px] h-[400px] sm:h-[600px] bg-[#00A6A6]/10 blur-[100px] sm:blur-[120px] rounded-full pointer-events-none -translate-x-1/2 -translate-y-1/2"></div>
    <div class="fixed bottom-0 right-0 w-[300px] sm:w-[500px] h-[300px] sm:h-[500px] bg-[#00A6A6]/5 blur-[80px] sm:blur-[100px] rounded-full pointer-events-none translate-x-1/3 translate-y-1/3"></div>

    <div class="relative w-full max-w-md z-10 animate-[fade-in_0.6s_ease-out] my-8">

        <!-- THE BOX -->
        <div class="glass-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 lg:p-10">

            <!-- Back Link INSIDE the box -->
            <a href="<?= url_e('/') ?>" class="back-btn inline-flex items-center gap-2 text-[10px] sm:text-xs font-bold uppercase tracking-widest text-gray-500 mb-6 hover:text-white">
                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Home
            </a>

            <!-- Brand Logo -->
            <div class="flex flex-col items-center justify-center mb-6 sm:mb-8">
                <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="Powermagz Gym" class="h-14 w-14 sm:h-16 sm:w-16 rounded-full shadow-lg mb-3 sm:mb-4 ring-2 ring-[#00A6A6]/30">
                <div class="text-center leading-none">
                    <div class="font-heading text-2xl sm:text-3xl tracking-widest text-white">POWERMAGZ</div>
                    <div class="mt-1 text-[9px] sm:text-[10px] font-bold tracking-[0.4em] text-[#00A6A6]">GYM</div>
                </div>
            </div>

            <!-- Flash Messages (Success/Error) -->
            <?php if ($m = flash('error')): ?>
                <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-xs sm:text-sm text-red-400 text-center">
                    <?= e($m) ?>
                </div>
            <?php endif; ?>
            <?php if ($m = flash('success')): ?>
                <div class="mb-5 rounded-xl border border-green-500/30 bg-green-500/10 p-3 text-xs sm:text-sm text-green-400 text-center">
                    <?= e($m) ?>
                </div>
            <?php endif; ?>

            <!-- The Form Content -->
            <?= $content ?>
        </div>

    </div>

    <script>
        // Password Toggle Logic
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-pw-toggle]');
            if (!btn) return;

            const wrap = btn.closest('.relative');
            if (!wrap) return;

            const input = wrap.querySelector('input');
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            // Change icon opacity based on state
            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');
            if (show) {
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                eyeClosed.classList.add('hidden');
                eyeOpen.classList.remove('hidden');
            }
        });
    </script>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>

</html>