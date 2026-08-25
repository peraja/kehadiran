<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'eRapat') }} | Pemkab Sinjai</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">

    <!-- Open Graph / Meta Image -->
    <meta property="og:image" content="{{ asset('img/meta.png') }}">
    <meta name="twitter:image" content="{{ asset('img/meta.png') }}">
    <meta property="og:title" content="{{ config('app.name', 'eRapat') }} | Pemkab Sinjai">
    <meta property="og:description" content="Aplikasi Manajemen Rapat Resmi Pemerintah Kabupaten Sinjai">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans antialiased text-slate-900 selection:bg-primary-500 selection:text-white" x-data="{ sidebarOpen: false }">
    <!-- Top Loading Indicator -->
    <div wire:loading.delay.shortest class="fixed top-0 left-0 right-0 z-[100] h-1 bg-gradient-to-r from-primary-400 via-primary-600 to-indigo-600 animate-pulse pointer-events-none"></div>

    <div class="min-h-full flex flex-col">
        <livewire:layout.navigation />

        <!-- Main Content Area (Offset by sidebar width on desktop) -->
        <div class="lg:pl-72 flex flex-col min-h-screen">
            <!-- Top App-Bar -->
            <header class="sticky top-0 z-30 flex h-16 sm:h-20 shrink-0 items-center justify-between gap-x-4 border-b border-slate-200 bg-white/80 px-4 sm:px-6 lg:px-8 backdrop-blur-md shadow-sm">
                <div class="flex items-center gap-x-4">
                    <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-slate-700 lg:hidden rounded-xl hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary-500 active:scale-95 transition-all" aria-label="Buka navigasi">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <div class="hidden sm:flex items-center">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 font-bold text-sm tracking-wide shadow-inner">
                            {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-x-4">
                    <livewire:layout.topbar-profile />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] w-full mx-auto">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="mt-auto border-t border-slate-200 bg-white py-6">
                <div class="px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto flex items-center justify-center text-xs font-medium text-slate-500">
                    <span>&copy; {{ date('Y') }} <strong class="text-slate-700 font-bold">Diskominfo-SP Sinjai</strong></span>
                </div>
            </footer>
        </div>
    </div>
</body>

</html>