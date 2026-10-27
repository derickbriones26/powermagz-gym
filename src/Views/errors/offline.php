<!doctype html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — PowerMagz Gym</title>
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
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-[#00A6A6]/10 blur-[100px] rounded-full pointer-events-none"></div>

    <div class="w-full max-w-md animate-fade-in relative z-10">
        <div class="rounded-3xl border border-white/5 bg-[#111] p-8 sm:p-10 text-center shadow-2xl">

            <!-- Icon -->
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-gray-400 shadow-[0_0_30px_rgba(255,255,255,0.05)] mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
                </svg>
            </div>

            <!-- Text -->
            <h1 class="font-heading text-5xl tracking-widest text-white">OFFLINE</h1>
            <h2 class="mt-2 text-xl font-bold text-white">No Internet Connection</h2>
            <p class="mt-3 text-sm text-gray-400 leading-relaxed">
                It looks like you've lost your Wi-Fi or mobile data. Please check your connection and try again.
            </p>

            <!-- Button -->
            <div class="mt-8">
                <button type="button" onclick="window.location.reload()"
                    class="group inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-6 py-3.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.4)] transition-all duration-300
                        hover:-translate-y-0.5 hover:bg-[#008c8c] hover:shadow-[0_0_25px_rgba(0,166,166,0.6)]">
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