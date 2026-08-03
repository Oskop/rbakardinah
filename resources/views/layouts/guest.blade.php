<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIPAKAR') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- CSS (CDN as fallback) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent),
                radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.1), transparent);
        }
    </style>
</head>

<body class="antialiased bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white font-sans">
    <div
        class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 hero-gradient overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/hero.png') }}"
                class="w-full h-full object-cover opacity-10 filter grayscale-[0.2]" alt="Background">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-50/50 via-transparent to-slate-50/50"></div>
        </div>

        <div class="relative z-10">
            <a href="/" class="flex items-center gap-3 group mb-8">
                <img src="{{ asset('images/LogoSipakar.png') }}" class="h-12 w-auto object-contain group-hover:scale-105 transition-transform duration-300" alt="SIPAKAR Logo">
                <span class="text-3xl font-extrabold tracking-tight text-slate-800 uppercase"><span
                        class="text-indigo-600">SIPAKAR</span></span>
            </a>
        </div>

        <div
            class="relative z-10 w-full sm:max-w-md mt-6 px-8 py-10 glass shadow-2xl shadow-indigo-100/50 overflow-hidden sm:rounded-[2rem] border border-white/60">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-900">Selamat Datang Kembali</h2>
                <p class="text-slate-500 mt-1">Silakan masuk ke akun Anda untuk melanjutkan.</p>
            </div>

            {{ $slot }}
        </div>

        <!-- Footer Info -->
        <div class="relative z-10 mt-8 text-center">
            <p class="text-sm text-slate-400">
                &copy; {{ date('Y') }} SIPAKAR. Sistem Perencanaan dan Penganggaran RSUD Kardinah.
            </p>
        </div>
    </div>
</body>

</html>