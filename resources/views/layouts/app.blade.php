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
</head>

<body class="h-full font-sans antialiased text-slate-900 selection:bg-primary-500 selection:text-white" x-data="{ sidebarOpen: false }">
    <!-- Top Loading Indicator -->
    <div wire:loading.delay.shortest class="fixed top-0 left-0 right-0 z-[100] h-1 bg-gradient-to-r from-primary-400 via-primary-600 to-indigo-600 animate-pulse pointer-events-none"></div>

    <div class="min-h-full flex flex-col">
        <livewire:layout.navigation />

        <!-- Main Content Area (Offset by sidebar width on desktop) -->
        <div class="lg:pl-72 flex flex-col min-h-screen">
            <!-- Top App-Bar -->
            <header class="sticky top-0 z-30 flex h-20 shrink-0 items-center justify-between gap-x-4 border-b border-slate-200 bg-white/80 px-4 sm:px-6 lg:px-8 backdrop-blur-md shadow-sm">
                <div class="flex items-center gap-x-4">
                    <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-slate-700 lg:hidden rounded-xl hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors" aria-label="Buka navigasi">
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
                    @php
                    $roleName = auth()->user()->roles->first()?->name ?? 'pegawai';
                    $roleLabel = match($roleName) {
                    'admin' => 'Super Admin',
                    'admin_opd' => 'Admin OPD',
                    'pimpinan' => 'Pimpinan',
                    default => 'Pegawai'
                    };
                    @endphp

                    <!-- User Quick Profile / Dropdown -->
                    <x-dropdown align="right" width="w-56">
                        <x-slot name="trigger">
                            <button type="button" class="flex items-center gap-3 py-1.5 px-2.5 sm:py-2 sm:px-3.5 rounded-2xl bg-slate-50/80 hover:bg-slate-100/80 border border-slate-200/80 outline-none focus:outline-none focus-visible:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 active:scale-[0.99] transition-all shadow-2xs select-none cursor-pointer">
                                <div class="w-9 h-9 rounded-xl bg-primary-600 text-white font-extrabold text-sm flex items-center justify-center shadow-xs shrink-0">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <div class="text-left pr-1">
                                    <div class="text-xs font-bold text-slate-900 truncate max-w-[180px] sm:max-w-[220px] leading-tight">{{ auth()->user()->name }}</div>
                                    <div class="text-[10px] font-semibold text-slate-500 leading-tight mt-0.5">{{ $roleLabel }}</div>
                                </div>
                                <svg class="h-4 w-4 text-slate-400 shrink-0 ml-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="py-1">
                                <x-dropdown-link :href="route('profile')" wire:navigate class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 hover:text-primary-600 hover:bg-slate-50">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profil
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full text-start">
                                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50">
                                            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Logout
                                        </x-dropdown-link>
                                    </button>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
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