<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\Opd;
use App\Models\MeetingAttendance;
use Carbon\Carbon;

new class extends Component {
    public function with()
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();
        $isAdmin = auth()->user()->hasActiveRole('admin');

        $query = Meeting::query();
        
        $user = auth()->user();

        // Filter based on active role
        if ($user->hasActiveRole('pimpinan')) {
            $query->where(function ($q) use ($user) {
                $q->where(function ($sq) use ($user) {
                    if (!empty($user->nip)) {
                        $sq->where('signer_nip', $user->nip)
                            ->orWhere('signer_name', $user->name);
                    } else {
                        $sq->where('signer_name', $user->name);
                    }
                })->orWhereHas('opd', function ($oq) use ($user) {
                    if (!empty($user->nip)) {
                        $oq->where('leader_nip', $user->nip)->orWhere('leader_name', $user->name);
                    } else {
                        $oq->where('leader_name', $user->name);
                    }
                });
            });
        } elseif ($user->hasActiveRole('admin_opd')) {
            $unitName = $user->unit_name;
            $query->where(function ($q) use ($unitName) {
                $q->whereHas('creator', function ($cq) use ($unitName) {
                    $cq->where('unit_name', $unitName);
                })->orWhereHas('opd', function ($oq) use ($unitName) {
                    $oq->where('name', $unitName)
                       ->orWhere('name', 'like', '%' . $unitName . '%');
                });
            });
        } elseif ($user->hasActiveRole('pegawai')) {
            $query->where('created_by', $user->id);
        }

        $meetingsToday = (clone $query)->whereDate('date', $today)->count();
        $meetingsThisWeek = (clone $query)->whereBetween('date', [$startOfWeek, $endOfWeek])->count();
        $meetingsThisMonth = (clone $query)->whereBetween('date', [$startOfMonth, $endOfMonth])->count();
        $meetingsThisYear = (clone $query)->whereBetween('date', [$startOfYear, $endOfYear])->count();

        // Live Ongoing Meetings
        $ongoingMeetings = (clone $query)
            ->where('status', 'ongoing')
            ->with(['opd', 'creator', 'attendances'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        // Upcoming Meetings (Next 5)
        $upcomingMeetings = (clone $query)
            ->where(function($q) use ($today) {
                $q->where('date', '>', $today)
                  ->orWhere(function($sub) use ($today) {
                      $sub->whereDate('date', $today)
                          ->where('status', 'scheduled');
                  });
            })
            ->with(['opd', 'creator'])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();

        // Needs Action: Meetings that are ongoing or completed but missing minutes
        $missingMinutesMeetings = (clone $query)
            ->whereIn('status', ['ongoing', 'completed'])
            ->where(function($q) {
                $q->whereDoesntHave('minutes')
                  ->orWhereHas('minutes', function($mq) {
                      $mq->whereNull('content')->orWhere('content', '');
                  });
            })
            ->with(['opd', 'creator'])
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        $isPimpinan = auth()->user()->hasActiveRole('pimpinan');
        $isAdminOpd = auth()->user()->hasActiveRole('admin_opd');

        // Meetings waiting for TTE (pimpinan, super admin, admin OPD, & pegawai creator)
        $pendingTteMeetings = (clone $query)
            ->where('status', 'completed')
            ->where(function($q) {
                $q->whereNull('minutes_signed_at')
                  ->orWhereNull('attendance_signed_at')
                  ->orWhereNull('photos_signed_at');
            })
            ->with(['opd', 'creator', 'attendances', 'photos', 'minutes'])
            ->orderBy('date', 'desc')
            ->take(6)
            ->get();

        return compact(
            'isAdmin',
            'isAdminOpd',
            'isPimpinan',
            'meetingsToday',
            'meetingsThisWeek',
            'meetingsThisMonth',
            'meetingsThisYear',
            'ongoingMeetings',
            'upcomingMeetings',
            'missingMinutesMeetings',
            'pendingTteMeetings'
        );
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
                {{ $isAdmin ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>
        @unless($isPimpinan)
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('meetings.index') }}" wire:navigate class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Rapat
            </a>
        </div>
        @endunless
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
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
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex items-center gap-5 relative overflow-hidden group hover:border-sky-300 transition-colors">
            <div class="absolute right-0 top-0 w-24 h-24 bg-gradient-to-br from-sky-50 to-sky-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0 relative z-10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-1">Minggu Ini</h3>
                <p class="text-3xl font-black text-slate-900 leading-none">{{ $meetingsThisWeek }}</p>
            </div>
        </div>

        <!-- Rapat Bulan Ini -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex items-center gap-5 relative overflow-hidden group hover:border-indigo-300 transition-colors">
            <div class="absolute right-0 top-0 w-24 h-24 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 relative z-10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM9 15h.01M12 15h.01M15 15h.01M9 18h.01M12 18h.01M15 18h.01"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-1">Bulan Ini</h3>
                <p class="text-3xl font-black text-slate-900 leading-none">{{ $meetingsThisMonth }}</p>
            </div>
        </div>

        <!-- Rapat Tahun Ini -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex items-center gap-5 relative overflow-hidden group hover:border-amber-300 transition-colors">
            <div class="absolute right-0 top-0 w-24 h-24 bg-gradient-to-br from-amber-50 to-amber-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 relative z-10">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-1">Tahun Ini</h3>
                <p class="text-3xl font-black text-slate-900 leading-none">{{ $meetingsThisYear }}</p>
            </div>
        </div>
    </div>

    <!-- Live Ongoing Meetings Widget -->
    @if($ongoingMeetings->isNotEmpty())
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                <h3 class="font-extrabold text-slate-900 text-sm">Rapat Berlangsung</h3>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800">
                {{ $ongoingMeetings->count() }}
            </span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($ongoingMeetings as $meeting)
            <div class="p-5 hover:bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors group">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="font-extrabold text-sm text-slate-900 group-hover:text-primary-600 transition-colors truncate block">
                        {{ $meeting->title }}
                    </a>
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-500 mt-1.5 font-medium">
                        @if($isAdmin)
                        <span class="font-bold text-slate-700">{{ $meeting->opd?->name ?? $meeting->creator?->unit_name ?? 'Pemerintah Kabupaten Sinjai' }}</span>
                        <span>&bull;</span>
                        @endif
                        <span>{{ $meeting->location ?: 'Ruang Rapat' }}</span>
                        <span>&bull;</span>
                        <span class="font-bold text-emerald-600">{{ $meeting->attendances->count() }} Hadir</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('meetings.presensi', $meeting->id) }}" wire:navigate class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-xs transition-all shadow-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Lihat Presensi
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Menunggu TTE Widget (Pimpinan, Super Admin, Admin OPD, & Pegawai) -->
    @if($isPimpinan || $pendingTteMeetings->isNotEmpty())
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <h3 class="font-extrabold text-slate-900 text-sm">Menunggu TTE</h3>
            </div>
            @if($pendingTteMeetings->isNotEmpty())
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800">
                {{ $pendingTteMeetings->count() }}
            </span>
            @endif
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($pendingTteMeetings as $meeting)
            <div class="p-5 hover:bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors group">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="font-extrabold text-sm text-slate-900 group-hover:text-primary-600 transition-colors truncate block">
                        {{ $meeting->title }}
                    </a>
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-slate-500 mt-1.5 font-medium">
                        @if($isAdmin)
                        <span class="font-bold text-slate-700">{{ $meeting->opd?->name ?? $meeting->creator?->unit_name ?? 'Pemerintah Kabupaten Sinjai' }}</span>
                        @if($meeting->signer_name)
                        <span>&bull;</span>
                        <span>Penandatangan: <span class="font-bold text-slate-700">{{ $meeting->signer_name }}</span></span>
                        @endif
                        @else
                        <span>{{ $meeting->date->translatedFormat('d F Y') }}</span>
                        @if($meeting->start_time)
                        <span>&bull;</span>
                        <span>{{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA</span>
                        @endif
                        @if($meeting->signer_name)
                        <span>&bull;</span>
                        <span>Penandatangan: <span class="font-bold text-slate-700">{{ $meeting->signer_name }}</span></span>
                        @endif
                        @endif
                    </div>

                    <!-- 3 Dokumen Badges -->
                    <div class="flex flex-wrap items-center gap-2 mt-2.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $meeting->attendance_signed_at ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                            @if($meeting->attendance_signed_at)
                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            @endif
                            <span>Presensi</span>
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $meeting->photos_signed_at ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                            @if($meeting->photos_signed_at)
                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            @endif
                            <span>Dokumentasi</span>
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $meeting->minutes_signed_at ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                            @if($meeting->minutes_signed_at)
                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            @else
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            @endif
                            <span>Notulen</span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if($isPimpinan)
                    <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-xs transition-all shadow-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        TTE Dokumen
                    </a>
                    @else
                    <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-xs transition-all shadow-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Lihat Dokumen
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="py-14 px-6 text-center flex flex-col items-center justify-center h-full">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-2.5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-sm font-extrabold text-slate-900">Tidak Ada Dokumen yang Menunggu TTE</p>
            </div>
            @endforelse
        </div>
    </div>
    @endif

    @unless($isPimpinan)
    <!-- Lists (Admin OPD & Super Admin & Pegawai) -->
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
                    <div class="py-14 px-6 text-center flex flex-col items-center justify-center h-full">
                        <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mb-2.5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <p class="text-sm font-extrabold text-slate-900">Tidak Ada Rapat Mendatang</p>
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
                    @php
                        $canEditMinute = auth()->user()->hasActiveRole(['admin', 'admin_opd']) || $meeting->created_by === auth()->id();
                    @endphp
                    <div class="p-5 hover:bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('meetings.notulen', $meeting->id) }}" wire:navigate class="font-extrabold text-sm text-slate-900 hover:text-primary-600 truncate block transition-colors">
                                {{ $meeting->title }}
                            </a>
                            <div class="text-xs font-medium text-slate-500 mt-1">
                                {{ $meeting->date->translatedFormat('d F Y') }}
                            </div>
                        </div>
                        @if($canEditMinute)
                        <a href="{{ route('meetings.notulen', $meeting->id) }}" wire:navigate class="shrink-0 inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-xs transition-all shadow-sm gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Isi Notulen
                        </a>
                        @endif
                    </div>
                @empty
                    <div class="py-14 px-6 text-center flex flex-col items-center justify-center h-full">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-2.5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-extrabold text-slate-900">Semua Notulen Lengkap</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    @endunless
</div>
