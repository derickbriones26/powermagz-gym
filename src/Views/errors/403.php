<!doctype html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Access Denied</title>
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

    <div class="w-full max-w-md animate-fade-in relative z-10">
        <div class="rounded-3xl border border-white/5 bg-[#111] p-8 sm:p-10 text-center shadow-2xl">

            <!-- Icon -->
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-500/10 text-red-500 shadow-[0_0_30px_rgba(239,68,68,0.15)] mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Text -->
            <h1 class="font-heading text-6xl tracking-widest text-white">403</h1>
            <h2 class="mt-2 text-xl font-bold text-white">Access Denied</h2>
            <p class="mt-3 text-sm text-gray-400 leading-relaxed">
                You do not have the required permissions to access this page. If you believe this is a mistake, please contact the gym administrator.
            </p>

            <!-- Button -->
            <div class="mt-8">
                <a href="<?= htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'] ?? ''), ENT_QUOTES) ?>/"
                    class="group inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                        hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</body>

</html>