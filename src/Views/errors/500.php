<!doctype html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Server Error</title>
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
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.5s ease-out forwards;
        }
    </style>
</head>

<body class="flex min-h-screen items-center justify-center p-4 font-body relative overflow-hidden">

    <!-- Ambient Glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-red-500/10 blur-[100px] rounded-full pointer-events-none"></div>

    <div class="w-full max-w-lg animate-fade-in relative z-10">
        <div class="rounded-3xl border border-white/5 bg-[#111] p-8 sm:p-10 text-center shadow-2xl">

            <!-- Icon -->
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-500/10 text-red-500 shadow-[0_0_30px_rgba(239,68,68,0.15)] mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Text -->
            <h1 class="font-heading text-6xl tracking-widest text-white">500</h1>
            <h2 class="mt-2 text-xl font-bold text-white">Server Error</h2>
            <p class="mt-3 text-sm text-gray-400 leading-relaxed">
                Something went wrong on our side. We've been notified and are working on it.
            </p>

            <!-- Debug Info (Local only) -->
            <?php if (defined('APP_ENV') && APP_ENV === 'local' && !empty($errorMessage ?? '')): ?>
                <div class="mt-6 rounded-xl border border-red-500/20 bg-red-500/5 p-4 text-left">
                    <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Debug info (local only)
                    </div>
                    <pre class="mt-2 overflow-x-auto whitespace-pre-wrap break-words text-xs text-red-300 font-mono"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></pre>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="<?= htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'] ?? ''), ENT_QUOTES) ?>/"
                    class="group inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                        hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Home
                </a>

                <button type="button" onclick="window.location.reload()"
                    class="group inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-3.5 text-sm font-bold text-white transition-all duration-300
                        hover:-translate-y-0.5 hover:border-white hover:bg-white/10">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Try Again
                </button>
            </div>
        </div>
    </div>
</body>

</html>