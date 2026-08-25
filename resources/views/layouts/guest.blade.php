<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
        @livewireStyles
    </head>
    <body class="h-full font-sans text-slate-900 antialiased selection:bg-primary-500 selection:text-white relative">
        <!-- Top Loading Bar indicator for Livewire -->
        <div wire:loading.delay.shortest class="fixed top-0 left-0 right-0 z-[100] h-1 bg-gradient-to-r from-primary-400 via-primary-600 to-indigo-600 animate-pulse pointer-events-none"></div>

        <!-- Ambient Background Pattern -->
        <div class="fixed inset-0 z-[-1] bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:20px_20px] opacity-40"></div>
        <div class="fixed top-0 inset-x-0 h-[500px] bg-gradient-to-b from-primary-50 to-transparent -z-10"></div>
        <div class="fixed top-0 right-0 w-[600px] h-[600px] bg-indigo-50/50 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/3 -z-10"></div>

        <div class="min-h-full flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center group">
                <a href="/" wire:navigate class="inline-flex flex-col items-center gap-3">
                    <img src="{{ asset('img/logo.png') }}" alt="Logo Pemkab Sinjai" class="h-14 w-auto drop-shadow-md group-hover:scale-105 transition-transform duration-300" />
                    <div>
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">e<span class="text-primary-600">Rapat</span></span>
                        <span class="block text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-widest">Pemkab Sinjai</span>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-lg mt-8 px-6 sm:px-10 py-10 bg-white shadow-xl shadow-slate-200/50 overflow-hidden rounded-[2rem] border border-slate-100 relative">
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-primary-400 via-primary-600 to-indigo-600"></div>
                {{ $slot }}
            </div>
            
            <footer class="mt-8 text-center text-xs font-medium text-slate-500">
                <span>&copy; {{ date('Y') }} <strong class="text-slate-700 font-bold">Diskominfo-SP Sinjai</strong></span>
            </footer>
        </div>
        @livewireScripts
    </body>
</html>
