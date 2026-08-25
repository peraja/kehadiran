<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">

    <!-- Dynamic SEO & Open Graph Meta -->
    <x-seo-meta
        title="eRapat | Pemkab Sinjai"
        description="Manajemen Rapat Pemerintah Kabupaten Sinjai"
        robots="index, follow"
    />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-slate-900 min-h-screen flex flex-col justify-between relative selection:bg-primary-500 selection:text-white overflow-x-hidden">

    <!-- Ambient Background Pattern -->
    <div class="fixed inset-0 z-[-1] bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:24px_24px] opacity-70"></div>
    <div class="fixed top-0 inset-x-0 h-[650px] bg-gradient-to-b from-primary-50/70 via-indigo-50/30 to-transparent -z-10"></div>
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-primary-200/30 rounded-full blur-[140px] pointer-events-none -z-10"></div>
    <div class="fixed bottom-0 right-0 w-[500px] h-[500px] bg-indigo-100/40 rounded-full blur-[120px] pointer-events-none -z-10"></div>

    <!-- Header / Nav -->
    <header class="w-full border-b border-slate-200/80 bg-white/80 backdrop-blur-md sticky top-0 z-50 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Pemkab Sinjai" class="h-11 w-auto drop-shadow-sm transition-transform group-hover:scale-105">
                <div>
                    <span class="text-xl font-black text-slate-900 tracking-tight leading-tight block">e<span class="text-primary-600">Rapat</span></span>
                    <span class="block text-[10px] text-slate-500 font-bold mt-0.5 uppercase tracking-widest">Pemkab Sinjai</span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                @auth
                <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <span>Dashboard</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                @else
                <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4 text-primary-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Login</span>
                </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col items-center pt-10 sm:pt-16 md:pt-20 pb-16 sm:pb-24 px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Hero Container -->
        <div class="max-w-4xl w-full text-center relative z-10">
            <!-- Main Heading -->
            <h1 class="font-extrabold text-slate-900 tracking-tight leading-[1.15] mb-10 sm:mb-14">
                <span class="block text-4xl sm:text-6xl md:text-7xl font-black">Manajemen Rapat</span>
                <span class="block text-2xl sm:text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-indigo-600 mt-1.5 sm:mt-2.5">Pemerintah Kabupaten Sinjai</span>
            </h1>

            <!-- Today's Meetings Section -->
            @php
            $todayMeetings = \App\Models\Meeting::with(['opd', 'creator'])->whereDate('date', today())->orderBy('start_time')->get();
            @endphp

            @if($todayMeetings->count() > 0)
            <div class="text-left bg-white/80 backdrop-blur-md rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all max-w-3xl mx-auto overflow-hidden">
                <!-- Header Widget -->
                <div class="p-5 sm:p-6 pb-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0 shadow-2xs">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-extrabold text-slate-900 text-base leading-tight">Rapat Hari Ini</h2>
                            <p class="text-xs font-medium text-slate-500 mt-0.5">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white border border-slate-200 text-slate-700 rounded-full text-xs font-bold shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ $todayMeetings->count() }} Rapat</span>
                    </span>
                </div>

                <!-- Body List -->
                <div class="divide-y divide-slate-100">
                    @foreach($todayMeetings as $m)
                    @php
                        $opdName = $m->opd?->name ?? $m->creator?->unit_name ?? 'Pemerintah Kabupaten Sinjai';
                        $isOngoing = $m->status === 'ongoing';
                    @endphp
                    <div class="p-5 sm:p-6 hover:bg-slate-50/80 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4 group">
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <!-- Judul Agenda -->
                            <div class="font-extrabold text-slate-900 text-sm sm:text-base group-hover:text-primary-600 transition-colors leading-snug break-words">
                                {{ $m->title }}
                            </div>

                            <!-- Nama OPD -->
                            <div class="font-bold text-slate-700 text-xs break-words leading-relaxed">
                                {{ $opdName }}
                            </div>

                            <!-- Waktu & Lokasi -->
                            <div class="text-xs text-slate-500 font-medium flex flex-wrap items-center gap-x-2.5 gap-y-1 pt-0.5">
                                <span class="font-semibold text-slate-700">{{ $m->start_time ? $m->start_time->format('H:i') : '' }} - {{ $m->end_time ? $m->end_time->format('H:i') : 'Selesai' }} WITA</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="break-words">{{ $m->location ?: 'Ruang Rapat' }}</span>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="shrink-0 self-start sm:self-center">
                            <x-meeting-status-badge :meeting="$m" :with-document-status="false" />
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-white/70 backdrop-blur-md rounded-3xl border border-slate-200 shadow-sm max-w-2xl mx-auto overflow-hidden p-8 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 text-slate-600 rounded-full text-xs font-bold shadow-2xs mb-4">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
                <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="font-extrabold text-slate-900 text-base">
                    Tidak Ada Rapat Hari Ini
                </h3>
                @auth
                @unless(auth()->user()->hasRole('pimpinan'))
                <div class="mt-5">
                    <a href="{{ route('meetings.index') }}" wire:navigate class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Buat Rapat
                    </a>
                </div>
                @endunless
                @endauth
            </div>
            @endif
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