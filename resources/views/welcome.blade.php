<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-slate-900 min-h-screen flex flex-col justify-between relative selection:bg-primary-500 selection:text-white overflow-x-hidden">

    <!-- Ambient Background Pattern -->
    <div class="fixed inset-0 z-[-1] bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:20px_20px] opacity-60"></div>
    <div class="fixed top-0 inset-x-0 h-[600px] bg-gradient-to-b from-primary-50/80 to-transparent -z-10"></div>
    <div class="fixed top-0 left-0 w-[600px] h-[600px] bg-primary-100/50 rounded-full blur-[120px] -translate-y-1/2 -translate-x-1/2 -z-10"></div>
    <div class="fixed bottom-0 right-0 w-[600px] h-[600px] bg-indigo-50/50 rounded-full blur-[100px] translate-y-1/4 translate-x-1/3 -z-10"></div>

    <!-- Header / Nav -->
    <header class="w-full border-b border-slate-200/80 bg-white/80 backdrop-blur-md sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Pemkab Sinjai" class="h-11 w-auto drop-shadow-sm">
                <div>
                    <span class="text-xl font-extrabold text-slate-900 tracking-tight leading-tight block">e<span class="text-primary-600">Rapat</span></span>
                    <span class="block text-[10px] text-slate-500 font-bold mt-0.5 uppercase tracking-widest">Pemkab Sinjai</span>
                </div>
            </div>

            <div>
                @auth
                <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm">
                    Dashboard &rarr;
                </a>
                @else
                <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center justify-center px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Login NIP
                </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col items-center pt-16 pb-24 px-4 sm:px-6 lg:px-8 relative z-10">

        <div class="max-w-4xl w-full text-center relative z-10">
            <h1 class="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15] mb-6">
                Manajemen Rapat <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-indigo-600">Pemerintah Kabupaten Sinjai</span>
            </h1>

            <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto mb-10 font-medium leading-relaxed">
                Platform digital resmi untuk pengelolaan agenda rapat, presensi cerdas berbasis QR code, pencatatan notulen, hingga pengarsipan dokumentasi kegiatan.
            </p>

            <!-- Today's Open Meetings Widget -->
            @php
            $todayMeetings = \App\Models\Meeting::with(['opd', 'creator'])->whereDate('date', today())->orderBy('start_time')->get();
            @endphp

            @if($todayMeetings->count() > 0)
            <div class="mt-14 text-left bg-white/60 backdrop-blur-sm rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow max-w-2xl mx-auto overflow-hidden">
                <!-- Header -->
                <div class="p-6 pb-4 border-b border-slate-200/80 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-extrabold text-slate-900 text-base leading-tight">Rapat Hari Ini</h2>
                            <p class="text-xs font-medium text-slate-500 mt-0.5">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 bg-white border border-slate-200 text-slate-700 rounded-full text-xs font-bold shadow-2xs">{{ $todayMeetings->count() }} Rapat</span>
                </div>

                <!-- Body -->
                <div class="p-6 pt-2 divide-y divide-slate-200/60">
                    @foreach($todayMeetings as $m)
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 group">
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-slate-900 text-sm group-hover:text-primary-600 transition-colors leading-snug">{{ $m->title }}</div>
                            <div class="text-xs text-slate-500 font-normal mt-1 flex flex-wrap items-center gap-1.5">
                                <span class="font-semibold text-slate-700">{{ $m->start_time ? $m->start_time->format('H:i') . ' WITA' : '' }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="truncate text-slate-500">{{ $m->location ?: 'Ruang Rapat' }}</span>
                            </div>
                        </div>
                        <div class="shrink-0 self-start sm:self-auto">
                            @if($m->status === 'ongoing')
                            <a href="{{ route('meetings.check-in', $m->id) }}" class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Presensi
                            </a>
                            @else
                            <x-meeting-status-badge :status="$m->status" />
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- 3 Core Feature Highlights -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 text-left">
                <div class="bg-white/60 backdrop-blur-sm p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-primary-100 text-primary-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                    </div>
                    <h3 class="font-extrabold text-slate-900 text-base mb-2">Presensi Digital QR</h3>
                    <p class="text-sm font-medium text-slate-500 leading-relaxed">Sistem kehadiran rapat berbasis pemindaian QR code dan integrasi tanda tangan digital instan.</p>
                </div>

                <div class="bg-white/60 backdrop-blur-sm p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-extrabold text-slate-900 text-base mb-2">Notulen & Cetak PDF</h3>
                    <p class="text-sm font-medium text-slate-500 leading-relaxed">Rekap hasil keputusan rapat dengan fitur unduh otomatis berformat PDF standar kop resmi daerah.</p>
                </div>

                <div class="bg-white/60 backdrop-blur-sm p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-extrabold text-slate-900 text-base mb-2">Arsip Dokumentasi</h3>
                    <p class="text-sm font-medium text-slate-500 leading-relaxed">Pengarsipan foto aktivitas yang terkompresi otomatis, dapat diunduh sekaligus dalam format ZIP.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full border-t border-slate-200 bg-white py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-center text-xs font-medium text-slate-500">
            <span>&copy; {{ date('Y') }} <strong class="text-slate-700 font-bold">Diskominfo-SP Sinjai</strong></span>
        </div>
    </footer>
</body>

</html>