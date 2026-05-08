<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DIPAESI - Digitalisasi Perencanaan dan Evaluasi</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- CSS (CDN as fallback to ensure layout is not broken) -->
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
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent),
                radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.1), transparent);
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
            }
        }
    </style>
</head>

<body class="antialiased bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white font-sans">
    <div class="relative min-h-screen overflow-hidden hero-gradient">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/hero.png') }}"
                class="w-full h-full object-cover opacity-20 filter grayscale-[0.5] contrast-[1.1]"
                alt="Hero Background">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-50/80 via-transparent to-slate-50"></div>
        </div>

        <!-- Navigation -->
        <nav class="relative z-50 flex items-center justify-between px-6 py-8 mx-auto max-w-7xl lg:px-8">
            <div class="flex items-center gap-3 group cursor-pointer">
                <div
                    class="p-2 bg-indigo-600 rounded-xl shadow-lg shadow-indigo-200 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <span class="text-2xl font-extrabold tracking-tight text-slate-800 uppercase"><span
                        class="text-indigo-600">DIPAESI</span></span>
            </div>

            @if (Route::has('login'))
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="px-6 py-2.5 text-sm font-semibold text-white bg-slate-900 rounded-full hover:bg-slate-800 shadow-xl shadow-slate-200 transition-all active:scale-95">
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm font-semibold leading-6 text-slate-700 hover:text-indigo-600 transition-colors">
                            Masuk
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-full hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all active:scale-95">
                                Daftar Akun
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </nav>

        <!-- Hero Section -->
        <div class="relative z-10 px-6 py-24 mx-auto max-w-7xl sm:py-32 lg:px-8 lg:flex lg:items-center lg:gap-x-10">
            <div class="max-w-2xl mx-auto lg:mx-0 lg:flex-auto">
                <div class="flex mb-8">
                    <div
                        class="relative px-3 py-1 text-sm leading-6 rounded-full text-slate-600 ring-1 ring-slate-900/10 hover:ring-slate-900/20 glass">
                        DIGITALISASI PERENCANAAN DAN EVALUASI.
                    </div>
                </div>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl">
                    Kelola <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-emerald-600">Anggaran &
                        Belanja</span> Dengan Lebih Efisien.
                </h1>
                <p class="mt-6 text-lg leading-8 text-slate-600">
                    Platform modern untuk menyusun Rancangan Belanja dan Anggaran (RBA) secara sistematis, transparan,
                    dan terintegrasi. Membantu unit kerja dalam monitoring pagu dan usulan secara real-time.
                </p>
                <div class="flex items-center mt-10 gap-x-6">
                    <a href="{{ route('login') }}"
                        class="px-8 py-4 text-base font-bold text-white transition-all bg-indigo-600 rounded-2xl shadow-2xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 active:scale-95">
                        Mulai Sekarang
                    </a>
                    <a href="#" class="text-sm font-bold leading-6 text-slate-900 group flex items-center gap-2">
                        Pelajari lebih lanjut
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>

                <!-- Stats/Badges -->
                <div class="mt-16 grid grid-cols-2 gap-8 sm:mt-20 lg:mt-24">
                    <div class="flex flex-col gap-y-1 border-l-2 border-indigo-600 pl-4">
                        <dt class="text-sm leading-6 text-slate-600">Monitoring</dt>
                        <dd class="text-2xl font-bold tracking-tight text-slate-900">Real-time</dd>
                    </div>
                    <div class="flex flex-col gap-y-1 border-l-2 border-emerald-500 pl-4">
                        <dt class="text-sm leading-6 text-slate-600">Validasi</dt>
                        <dd class="text-2xl font-bold tracking-tight text-slate-900">Terpusat</dd>
                    </div>
                </div>
            </div>

            <!-- Decorative Element -->
            <div class="hidden lg:block lg:flex-auto">
                <div class="relative animate-float">
                    <div
                        class="absolute -inset-2 bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-[2rem] blur-2xl opacity-20">
                    </div>
                    <div class="relative p-8 glass rounded-[2rem] shadow-2xl border border-white/50 overflow-hidden">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex gap-2">
                                <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            </div>
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Preview Dashboard
                            </div>
                        </div>

                        <!-- Mockup Content -->
                        <div class="space-y-4">
                            <div class="h-8 bg-slate-100 rounded-lg w-full"></div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="h-24 bg-indigo-50 rounded-xl border border-indigo-100"></div>
                                <div class="h-24 bg-emerald-50 rounded-xl border border-emerald-100"></div>
                                <div class="h-24 bg-slate-50 rounded-xl border border-slate-100"></div>
                            </div>
                            <div class="h-32 bg-slate-50 rounded-xl"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 px-6 py-12 mx-auto max-w-7xl lg:px-8 border-t border-slate-200/50">
            <p class="text-sm leading-5 text-slate-500">
                &copy; {{ date('Y') }} DIPAESI. All rights reserved. Professional Budgeting Solution.
            </p>
        </div>
    </div>
</body>

</html>