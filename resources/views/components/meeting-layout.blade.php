@props(['meeting', 'activeTab'])

<div class="w-full">
    <!-- Breadcrumb & Navigation -->
    <nav class="flex items-center text-xs font-bold text-slate-500 mb-2">
        <a href="{{ route('meetings.index') }}" wire:navigate class="inline-flex items-center gap-2 hover:text-primary-600 transition-colors">
            <div class="p-1 rounded-xl bg-slate-200/50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </div>
            Kembali ke Daftar Rapat
        </a>
    </nav>

    <!-- Workspace Header Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 relative overflow-hidden mb-6">
        <!-- Decorative Blob -->
        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-gradient-to-br from-primary-50 to-indigo-50 rounded-full blur-3xl opacity-70 pointer-events-none"></div>
        
        <livewire:meetings.header :meeting="$meeting" :key="'meeting-header-'.$meeting->id" />
        
        @unless(auth()->user()?->hasActiveRole('pimpinan'))
        <!-- Premium Tabs (Pills) -->
        <nav class="flex items-center gap-2 mt-8 overflow-x-auto pb-2 scrollbar-hide relative z-10">
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
                   wire:navigate
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all shadow-sm whitespace-nowrap shrink-0 {{ $activeTab === $key ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300' }}">
                    {!! $tab['icon'] !!}
                    <span>{{ $tab['label'] }}</span>
                    @if($tab['count'] !== null)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $activeTab === $key ? 'bg-slate-700 text-slate-300' : 'bg-slate-100 text-slate-500' }}">
                            {{ $tab['count'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
        @endunless
    </div>

    <!-- Workspace Content Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 min-h-[400px]">
        {{ $slot }}
    </div>
</div>
