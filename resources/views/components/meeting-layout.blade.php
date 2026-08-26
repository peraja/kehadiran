@props(['meeting', 'activeTab'])

<div class="w-full">
    <!-- Breadcrumb & Back Navigation -->
    <div class="mb-4 sm:mb-5">
        <a href="{{ route('meetings.index') }}" wire:navigate.hover class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900 hover:border-slate-300 text-xs font-bold uppercase tracking-wider shadow-2xs group transition-all">
            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-700 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Daftar Rapat</span>
        </a>
    </div>

    <!-- Workspace Header Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 md:p-8 relative overflow-hidden mb-4 sm:mb-6">
        <!-- Decorative Blob -->
        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-gradient-to-br from-primary-50 to-indigo-50 rounded-full blur-3xl opacity-70 pointer-events-none"></div>
        
        <livewire:meetings.header :meeting="$meeting" :key="'meeting-header-'.$meeting->id" />
    </div>

    <!-- Workspace Content Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 md:p-8 min-h-[400px]">
        @unless(auth()->user()?->hasActiveRole('pimpinan'))
        <!-- Sub-Header Tabs (Pills) -->
        <nav class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-4 gap-2.5 pb-6 sm:pb-8 mb-6 sm:mb-8 border-b border-slate-100 relative z-10">
            @php
                $tabs = [
                    'overview' => [
                        'label' => 'Ringkasan',
                        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        'count' => null
                    ],
                    'presensi' => [
                        'label' => 'Presensi',
                        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
                        'count' => $meeting->attendances()->count()
                    ],
                    'dokumentasi' => [
                        'label' => 'Dokumentasi',
                        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
                        'count' => $meeting->photos()->count()
                    ],
                    'notulen' => [
                        'label' => 'Notulen',
                        'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
                        'count' => null
                    ]
                ];
            @endphp
            
            @foreach($tabs as $key => $tab)
                <a href="{{ route('meetings.'.$key, $meeting->id) }}"
                   wire:navigate.hover
                   class="flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all shadow-xs w-full {{ $activeTab === $key ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200/80 hover:bg-slate-100 hover:text-slate-900 hover:border-slate-300' }}">
                    {!! $tab['icon'] !!}
                    <span>{{ $tab['label'] }}</span>
                    @if($tab['count'] !== null)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $activeTab === $key ? 'bg-slate-700 text-slate-300' : 'bg-slate-200/80 text-slate-600' }}">
                            {{ $tab['count'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
        @endunless

        <div class="transition-opacity duration-150">
            {{ $slot }}
        </div>
    </div>
</div>
