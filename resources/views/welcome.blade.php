<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'RapatPro') }} - Pemkab Sinjai</title>

    <!-- Open Graph / Meta Image -->
    <meta property="og:image" content="{{ asset('img/meta.png') }}">
    <meta name="twitter:image" content="{{ asset('img/meta.png') }}">
    <meta property="og:title" content="{{ config('app.name', 'RapatPro') }} - Pemkab Sinjai">
    <meta property="og:description" content="Aplikasi Manajemen Rapat Resmi Pemerintah Kabupaten Sinjai">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col justify-between relative overflow-x-hidden">
    <!-- Header / Nav -->
    <header class="w-full border-b border-gray-200 bg-white sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Pemkab Sinjai" class="h-10 w-auto">
                <div>
                    <span class="text-lg font-bold text-gray-900 tracking-tight">Rapat<span class="text-primary-600">Pro</span></span>
                    <span class="hidden sm:inline-block text-xs text-gray-500 font-medium ml-2 pl-2 border-l border-gray-300">Kabupaten Sinjai</span>
                </div>
            </div>

            <div>
                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 active:scale-95 transition ease-in-out duration-150">
                        Buka Dashboard &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 active:scale-95 transition ease-in-out duration-150 shadow-sm">
                        Masuk Pegawai
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative">
        <div class="absolute inset-0 z-0 opacity-40" style="background-image: radial-gradient(#10b981 1px, transparent 1px); background-size: 24px 24px;"></div>
        
        <div class="max-w-4xl w-full text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-50 border border-primary-200 text-primary-700 text-xs font-semibold uppercase tracking-wider mb-6">
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                Sistem Tata Kelola Administrasi Rapat Digital
            </div>

            <h1 class="text-3xl sm:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight mb-4">
                Manajemen Rapat Terpadu <br class="hidden sm:inline">
                <span class="text-primary-600">Pemerintah Kabupaten Sinjai</span>
            </h1>

            <p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto mb-8">
                Platform digitalisasi pengelolaan agenda rapat, presensi mandiri dengan QR Code & tanda tangan digital, pencatatan notulen resmi, hingga dokumentasi terintegrasi.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm shadow-md transition active:scale-95">
                        Menuju Dashboard Utama
                    </a>
                    <a href="{{ route('meetings.index') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl font-semibold text-sm shadow-sm transition active:scale-95">
                        Daftar Agenda Rapat
                    </a>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm shadow-md transition active:scale-95">
                        Masuk Menggunakan NIP
                    </a>
                @endauth
            </div>

            <!-- 3 Core Feature Highlights -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="w-10 h-10 bg-primary-100 text-primary-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">Presensi Digital QR</h3>
                    <p class="text-xs text-gray-500">Scan QR Code dan tanda tangan langsung melalui smartphone untuk pegawai ASN maupun tamu eksternal.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="w-10 h-10 bg-primary-100 text-primary-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">Notulen & Cetak PDF</h3>
                    <p class="text-xs text-gray-500">Pencatatan poin pembahasan terpusat dan siap diekspor ke format PDF resmi dengan kop dinas.</p>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="w-10 h-10 bg-primary-100 text-primary-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">Dokumentasi & Arsip</h3>
                    <p class="text-xs text-gray-500">Unggah dokumentasi dengan kompresi otomatis untuk efisiensi server serta unduh seluruh arsip foto dalam ZIP.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-500">
        &copy; {{ date('Y') }} Pemerintah Kabupaten Sinjai. Hak Cipta Dilindungi.
    </footer>
</body>
</html>
