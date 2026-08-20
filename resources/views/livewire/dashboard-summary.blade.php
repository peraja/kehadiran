<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Carbon\Carbon;

new class extends Component {
    public function with(): array
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $query = Meeting::query();
        
        // Scope by user unit if not admin
        if (!auth()->user()->hasRole('admin')) {
            $query->whereHas('creator', function($q) {
                $q->where('unit_name', auth()->user()->unit_name);
            });
        }

        $meetingsToday = (clone $query)->whereDate('date', $today)->count();
        $meetingsThisWeek = (clone $query)->whereBetween('date', [$startOfWeek, $endOfWeek])->count();
        $meetingsWithoutMinutes = (clone $query)->whereDoesntHave('minutes')->where('status', 'completed')->count();

        $upcomingMeetings = (clone $query)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->whereDate('date', '>=', $today)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

        $missingMinutesMeetings = (clone $query)
            ->whereDoesntHave('minutes')
            ->where('status', 'completed')
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        return compact('meetingsToday', 'meetingsThisWeek', 'meetingsWithoutMinutes', 'upcomingMeetings', 'missingMinutesMeetings');
    }
}; ?>

<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <!-- Rapat Hari Ini -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-center items-center">
            <div class="p-3 rounded-full bg-primary-50 mb-3 text-primary-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Rapat Hari Ini</h3>
            <p class="text-3xl font-bold text-primary-700 mt-1">{{ $meetingsToday }}</p>
        </div>

        <!-- Rapat Minggu Ini -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-center items-center">
            <div class="p-3 rounded-full bg-blue-50 mb-3 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Rapat Minggu Ini</h3>
            <p class="text-3xl font-bold text-blue-700 mt-1">{{ $meetingsThisWeek }}</p>
        </div>

        <!-- Menunggu Notulen -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-center items-center">
            <div class="p-3 rounded-full bg-amber-50 mb-3 text-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Menunggu Notulen</h3>
            <p class="text-3xl font-bold text-amber-700 mt-1">{{ $meetingsWithoutMinutes }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Rapat Mendatang -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Rapat Mendatang</h3>
                <a href="{{ route('meetings.index') }}" wire:navigate class="text-sm text-primary-600 hover:text-primary-800 font-medium focus:outline-none focus:underline">Lihat Semua &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($upcomingMeetings as $meeting)
                    <div class="p-4 hover:bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                        <div>
                            <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="font-semibold text-gray-900 hover:text-primary-600">{{ $meeting->title }}</a>
                            <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                                <span class="flex items-center"><svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>{{ $meeting->date->translatedFormat('d F Y') }}</span>
                                <span class="flex items-center"><svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>{{ $meeting->start_time->format('H:i') }} WITA</span>
                            </div>
                        </div>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide {{ $meeting->status == 'ongoing' ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-gray-100 text-gray-800' }}">
                            {{ $meeting->status == 'ongoing' ? 'BERLANGSUNG' : 'DIJADWALKAN' }}
                        </span>
                    </div>
                @empty
                    <div class="py-8 px-6 text-center text-sm text-gray-500">
                        <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Tidak ada rapat terjadwal dalam waktu dekat.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Perlu Notulen -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Menunggu Notulen</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($missingMinutesMeetings as $meeting)
                    <div class="p-4 hover:bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $meeting->title }}</div>
                            <div class="text-xs text-gray-500 mt-1">Selesai pada: {{ $meeting->date->translatedFormat('d F Y') }}</div>
                        </div>
                        <a href="{{ route('meetings.notulen', $meeting->id) }}" wire:navigate class="inline-flex items-center justify-center px-3 py-1.5 border border-primary-200 text-xs font-semibold rounded-lg text-primary-700 bg-primary-50 hover:bg-primary-100 active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                            Isi Notulen
                        </a>
                    </div>
                @empty
                    <div class="py-8 px-6 text-center text-sm text-gray-500">
                        <svg class="w-10 h-10 mx-auto text-primary-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Semua rapat telah memiliki notulen.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

