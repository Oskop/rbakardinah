<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>REST API Documentation - SIPAKAR RSUD Kardinah</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if($view === 'swagger')
        <!-- Swagger UI CSS -->
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
        <style>
            /* Custom Modern Swagger Styling */
            body {
                margin: 0;
                background-color: #f8fafc;
                font-family: 'Figtree', sans-serif;
            }
            .swagger-ui .topbar { display: none !important; }
            .swagger-ui {
                max-width: 1200px;
                margin: 0 auto;
                padding: 24px 16px 80px;
            }
            .swagger-ui .info {
                margin: 20px 0 30px;
            }
            .swagger-ui .info .title {
                font-size: 2rem;
                font-weight: 800;
                color: #0f172a;
            }
            .swagger-ui .info p, .swagger-ui .info li {
                font-size: 0.95rem;
                line-height: 1.7;
                color: #334155;
            }
            .swagger-ui .opblock {
                border-radius: 14px !important;
                box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.07);
                margin-bottom: 16px;
                border: 1px solid #e2e8f0;
            }
            .swagger-ui .opblock.opblock-get {
                background: #f0fdf4 !important;
                border-color: #bbf7d0 !important;
            }
            .swagger-ui .opblock.opblock-get .opblock-summary {
                border-color: #bbf7d0 !important;
            }
            .swagger-ui .btn.authorize {
                border-color: #4f46e5 !important;
                color: #4f46e5 !important;
                border-radius: 10px;
                font-weight: 700;
                padding: 8px 18px;
            }
            .swagger-ui .btn.authorize svg {
                fill: #4f46e5 !important;
            }
            .swagger-ui .btn.execute {
                background-color: #4f46e5 !important;
                border-color: #4f46e5 !important;
                border-radius: 10px;
                color: #ffffff;
                font-weight: 700;
            }
            .swagger-ui select, .swagger-ui input[type=text] {
                border-radius: 8px !important;
            }
            .swagger-ui pre {
                border-radius: 10px;
            }
        </style>
    @else
        <style>
            body {
                margin: 0;
                padding: 0;
                background: #ffffff;
                font-family: 'Figtree', sans-serif;
            }
            redoc {
                display: block;
            }
            /* Redoc custom tweaks */
            .menu-content {
                background-color: #0f172a !important;
            }
        </style>
    @endif
</head>
<body class="min-h-full flex flex-col bg-slate-50 antialiased">

    <!-- Header Navigation Bar -->
    <header class="sticky top-0 z-50 bg-slate-900 border-b border-slate-800 shadow-md text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <!-- Left: Branding -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('documentation.index') }}" class="flex items-center gap-2.5 group">
                        <img src="{{ asset('images/LogoSipakar.png') }}" alt="SIPAKAR" class="h-9 w-auto object-contain bg-white/10 p-1 rounded-lg">
                        <div>
                            <div class="font-black text-base text-white tracking-wide flex items-center gap-2">
                                <span>REST API SIPAKAR</span>
                                <span class="px-2 py-0.5 text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full font-mono">v1.0</span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-medium">RSUD Kardinah Kota Tegal</div>
                        </div>
                    </a>
                </div>

                <!-- Center: View Switcher (Swagger vs Redoc) -->
                <div class="flex items-center bg-slate-800/90 p-1 rounded-xl border border-slate-700/80 shadow-inner">
                    <a href="{{ route('api.documentation', ['view' => 'swagger']) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $view === 'swagger' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}"
                       title="Mode Swagger UI: Interaktif dengan fitur Try It Out untuk langsung menguji API">
                        <span>⚡ Swagger UI</span>
                        <span class="hidden md:inline text-[10px] opacity-80">(Uji Coba Langsung)</span>
                    </a>
                    <a href="{{ route('api.documentation', ['view' => 'redoc']) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $view === 'redoc' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}"
                       title="Mode Redoc: Dokumentasi lengkap 3-kolom yang rapi dan elegan">
                        <span>📖 Redoc</span>
                        <span class="hidden md:inline text-[10px] opacity-80">(Dokumentasi 3-Kolom)</span>
                    </a>
                </div>

                <!-- Right: Quick Actions -->
                <div class="flex items-center gap-2">
                    <!-- Download OpenAPI JSON -->
                    <a href="{{ route('api.openapi.json') }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg border border-slate-700 transition shadow-2xs"
                       title="Lihat atau Unduh spesifikasi OpenAPI 3.0 (JSON) untuk diimpor ke Postman / Insomnia">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span class="hidden sm:inline">OpenAPI Spec</span>
                        <span class="font-mono text-[10px] text-slate-400">.json</span>
                    </a>

                    <!-- Return to SIPAKAR -->
                    <a href="{{ route('documentation.index') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition shadow-xs">
                        <span>🏠</span>
                        <span class="hidden sm:inline">Portal SIPAKAR</span>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1">
        @if($view === 'swagger')
            <!-- Swagger UI Container -->
            <div id="swagger-ui"></div>
        @else
            <!-- Redoc Container -->
            <redoc spec-url="{{ $specUrl }}"
                   suppress-warnings="true"
                   expand-responses="200,401"
                   hide-download-button="true"
                   theme='{
                       "colors": {
                           "primary": { "main": "#4f46e5" },
                           "success": { "main": "#10b981" }
                       },
                       "typography": {
                           "fontFamily": "Figtree, sans-serif",
                           "headings": { "fontFamily": "Figtree, sans-serif", "fontWeight": "800" }
                       }
                   }'>
            </redoc>
        @endif
    </main>

    @if($view === 'swagger')
        <!-- Swagger UI Scripts -->
        <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" charset="UTF-8"></script>
        <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
        <script>
            window.onload = function() {
                window.ui = SwaggerUIBundle({
                    url: "{{ $specUrl }}",
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset
                    ],
                    plugins: [
                        SwaggerUIBundle.plugins.DownloadUrl
                    ],
                    layout: "BaseLayout",
                    defaultModelsExpandDepth: 1,
                    defaultModelExpandDepth: 1,
                    docExpansion: "list",
                    persistAuthorization: true
                });
            };
        </script>
    @else
        <!-- Redoc Script -->
        <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
    @endif

</body>
</html>
