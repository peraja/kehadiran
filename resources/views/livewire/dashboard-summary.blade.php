<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Carbon\Carbon;

new class extends Component {
    public function with()
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $query = Meeting::query();
        
        // Filter based on role
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

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Dashboard
            </h1>
            <p class="text-sm font-medium text-slate-500">
                {{ auth()->user()->hasRole('admin') ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('meetings.index') }}" wire:navigate class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Rapat
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Rapat Hari Ini -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex items-center gap-5 relative overflow-hidden group hover:border-primary-300 transition-colors">
            <div class="absolute right-0 top-0 w-24 h-24 bg-gradient-to-br from-primary-50 to-primary-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 relative z-10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-1">Hari Ini</h3>
                <p class="text-3xl font-black text-slate-900 leading-none">{{ $meetingsToday }}</p>
            </div>
        </div>

        <!-- Rapat Minggu Ini -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex items-center gap-5 relative overflow-hidden group hover:border-primary-300 transition-colors">
            <div class="absolute right-0 top-0 w-24 h-24 bg-gradient-to-br from-primary-50 to-primary-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 relative z-10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-1">Minggu Ini</h3>
                <p class="text-3xl font-black text-slate-900 leading-none">{{ $meetingsThisWeek }}</p>
            </div>
        </div>

        <!-- Menunggu Notulen -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex items-center gap-5 relative overflow-hidden group hover:border-amber-300 transition-colors">
            <div class="absolute right-0 top-0 w-24 h-24 bg-gradient-to-br from-amber-50 to-amber-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 relative z-10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-1">Tanpa Notulen</h3>
                <p class="text-3xl font-black text-slate-900 leading-none">{{ $meetingsWithoutMinutes }}</p>
            </div>
        </div>
    </div>

    <!-- Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Rapat Mendatang -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center shrink-0">
                <h3 class="font-extrabold text-slate-900 text-sm">Rapat Mendatang</h3>
                <a href="{{ route('meetings.index') }}" wire:navigate class="text-xs text-primary-600 hover:text-primary-700 font-bold focus:outline-none focus:underline">Lihat Semua &rarr;</a>
            </div>
            <div class="divide-y divide-slate-100 flex-1 flex flex-col">
                @forelse($upcomingMeetings as $meeting)
                    <div class="p-5 hover:bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors group">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="font-extrabold text-sm text-slate-900 group-hover:text-primary-600 transition-colors truncate block">{{ $meeting->title }}</a>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1.5 font-medium">
                                {{ $meeting->date->translatedFormat('d F Y') }}
                                {{ $meeting->start_time->format('H:i') }} WITA
                            </div>
                        </div>
                        <x-meeting-status-badge :status="$meeting->status" />
                    </div>
                @empty
                    <div class="py-12 px-6 text-center flex flex-col items-center justify-center h-full">
                        <div class="w-12 h-12 bg-slate-50 text-slate-300 rounded-2xl flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Tidak ada rapat mendatang.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Perlu Notulen -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center shrink-0">
                <h3 class="font-extrabold text-slate-900 text-sm">Menunggu Notulen</h3>
            </div>
            <div class="divide-y divide-slate-100 flex-1 flex flex-col">
                @forelse($missingMinutesMeetings as $meeting)
                    <div class="p-5 hover:bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="font-extrabold text-sm text-slate-900 truncate">{{ $meeting->title }}</div>
                            <div class="text-xs font-medium text-slate-500 mt-1 ">
                                Selesai: {{ $meeting->date->translatedFormat('d F Y') }}
                            </div>
                        </div>
                        <a href="{{ route('meetings.notulen', $meeting->id) }}" wire:navigate class="shrink-0 inline-flex items-center justify-center px-4 py-2 border border-primary-200 text-xs font-bold rounded-xl text-primary-700 bg-primary-50 hover:bg-primary-100 active:scale-95 transition-all shadow-sm">
                            Isi Notulen
                        </a>
                    </div>
                @empty
                    <div class="py-12 px-6 text-center flex flex-col items-center justify-center h-full">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Semua notulen telah diisi.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
