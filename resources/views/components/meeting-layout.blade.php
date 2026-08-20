@props(['meeting', 'activeTab' => 'overview'])

<div>
    <x-slot name="header">
        <livewire:meetings.header :meeting="$meeting" :key="'meeting-header-'.$meeting->id" />
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                    @php
                        $tabs = [
                            'overview' => 'Overview',
                            'presensi' => 'Presensi',
                            'dokumentasi' => 'Dokumentasi',
                            'notulen' => 'Notulen',
                        ];
                    @endphp

                    @php
                        $tabIcons = [
                            'overview' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                            'presensi' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>',
                            'dokumentasi' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
                            'notulen' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                        ];
                    @endphp

                    @foreach($tabs as $key => $label)
                        <a href="{{ route('meetings.'.$key, $meeting->id) }}"
                           wire:navigate
                           class="inline-flex items-center whitespace-nowrap py-3 px-2.5 border-b-2 font-semibold text-sm transition-colors focus:outline-none focus:text-primary-700 focus:border-primary-500 {{ $activeTab === $key ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {!! $tabIcons[$key] ?? '' !!}
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <!-- Content -->
            <div class="bg-white shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
