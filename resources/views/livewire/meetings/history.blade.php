<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\Opd;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $selected_opd_id = '';
    public string $date_from = '';
    public string $date_to = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyActiveRole(['admin', 'admin_opd'])) {
            abort(403, 'Akses ke halaman Riwayat Rapat tidak diizinkan untuk peran ini.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedOpdId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->selected_opd_id = '';
        $this->date_from = '';
        $this->date_to = '';
        $this->resetPage();
    }

    public function with(): array
    {
        $user = auth()->user();

        $query = Meeting::query()
            ->with(['opd', 'creator', 'attendances', 'photos'])
            ->where('status', 'completed')
            ->whereNotNull('minutes_signed_at')
            ->whereNotNull('attendance_signed_at')
            ->whereNotNull('photos_signed_at');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('location', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->date_from) {
            $query->whereDate('date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('date', '<=', $this->date_to);
        }

        if ($user->hasActiveRole('admin')) {
            if ($this->selected_opd_id) {
                $query->where('opd_id', $this->selected_opd_id);
            }
        } elseif ($user->hasActiveRole('admin_opd')) {
            // Admin OPD melihat riwayat rapat di lingkungan unit kerjanya
            $query->where(function ($q) use ($user) {
                $q->whereHas('creator', function ($cq) use ($user) {
                    $cq->where('unit_name', $user->unit_name);
                })->orWhereHas('opd', function ($oq) use ($user) {
                    $oq->where('name', $user->unit_name);
                });
            });
        }

        $meetings = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        $allOpds = $user->hasActiveRole('admin')
            ? Opd::where('is_active', true)->orderBy('name')->get()
            : collect();

        $totalCompletedSigned = (clone $query)->count();

        return [
            'meetings' => $meetings,
            'allOpds' => $allOpds,
            'totalCount' => $totalCompletedSigned,
        ];
    }
}; ?>

<div class="space-y-6 pb-10">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Riwayat Rapat
            </h1>
            <p class="text-sm font-medium text-slate-500">
                {{ auth()->user()->hasActiveRole('admin') ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>

        <div class="relative z-10 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-100 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ $totalCount }} Rapat</span>
            </span>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

        <!-- Toolbar -->
        <div class="p-4 sm:p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2.5">
                <!-- Search Field (Fluid width) -->
                <div class="relative flex-1 min-w-[200px]">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full h-10 rounded-xl border border-slate-200 pl-9 pr-9 py-2 text-xs sm:text-sm focus:border-primary-500 focus:ring-primary-500 shadow-2xs transition-colors bg-white placeholder:text-slate-400 placeholder:text-xs sm:placeholder:text-sm"
                        placeholder="Cari agenda atau lokasi rapat...">
                    @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors" title="Hapus pencarian">
                        <svg class="w-4 h-4 bg-slate-100 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>

                <!-- Right Side Filters (Full width on mobile, inline on desktop) -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full lg:w-auto shrink-0">
                    @if(auth()->user()->hasActiveRole('admin'))
                    <!-- OPD Dropdown -->
                    <div class="relative w-full sm:w-auto min-w-[160px] sm:min-w-[180px] max-w-none sm:max-w-[240px]">
                        <select wire:model.live="selected_opd_id"
                            class="w-full h-10 rounded-xl border border-slate-200 bg-white pl-3 pr-8 py-2 text-xs sm:text-sm font-semibold text-slate-700 focus:border-primary-500 focus:ring-primary-500 shadow-2xs transition-colors cursor-pointer appearance-none truncate">
                            <option value="">Semua OPD</option>
                            @foreach($allOpds as $opd)
                            <option value="{{ $opd->id }}">{{ $opd->name }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    @endif

                    <!-- Date Range Container (Full width 2 columns on mobile) -->
                    <div class="grid grid-cols-2 sm:inline-flex items-center bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden h-10 divide-x divide-slate-200 w-full sm:w-auto">
                        <div class="flex items-center px-2.5 py-1 gap-1.5 min-w-0">
                            <span class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-400 shrink-0">Dari</span>
                            <input wire:model.live="date_from" type="date"
                                class="border-0 p-0 text-xs font-semibold text-slate-700 focus:ring-0 bg-transparent cursor-pointer w-full min-w-0" />
                        </div>
                        <div class="flex items-center px-2.5 py-1 gap-1.5 min-w-0">
                            <span class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-400 shrink-0">Sampai</span>
                            <input wire:model.live="date_to" type="date"
                                class="border-0 p-0 text-xs font-semibold text-slate-700 focus:ring-0 bg-transparent cursor-pointer w-full min-w-0" />
                        </div>
                    </div>

                    @if($selected_opd_id || $date_from || $date_to)
                    <!-- Reset Button -->
                    <button wire:click="resetFilters"
                        class="h-10 inline-flex items-center justify-center gap-1.5 px-3 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 active:scale-95 transition-all border border-rose-200/80 shadow-2xs cursor-pointer shrink-0 w-full sm:w-auto"
                        title="Reset semua filter">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto min-h-[400px] rounded-2xl">
            <table class="w-full text-left border-collapse min-w-[720px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="py-4 px-6 text-left">Agenda & Lokasi</th>
                        <th class="py-4 px-6 text-left">Tanggal & Waktu</th>
                        <th class="py-4 px-6 text-left">Penandatangan</th>
                        <th class="py-4 px-6 text-center">Download</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    @forelse($meetings as $meeting)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <!-- Agenda & Lokasi -->
                        <td class="py-4 px-6 text-left">
                            <div class="font-extrabold text-slate-900 block text-sm sm:text-base leading-snug">
                                {{ $meeting->title }}
                            </div>

                            <div class="text-xs text-slate-500 font-medium mt-1">
                                <span class="truncate max-w-[240px] sm:max-w-none">{{ $meeting->location ?: 'Ruang Rapat' }}</span>
                            </div>

                            @if(auth()->user()->hasActiveRole('admin'))
                            @php
                                $opdName = $meeting->opd?->name ?? ($meeting->creator?->unit_name ?? 'Pemerintah Kabupaten Sinjai');
                            @endphp
                            <div class="mt-1.5 flex items-center">
                                <span class="inline-flex items-center text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200/80 transition-colors border border-slate-200/80 px-2 py-0.5 rounded-md max-w-[280px] sm:max-w-md truncate cursor-default shadow-2xs"
                                    title="{{ $opdName }}">
                                    <span class="truncate">{{ $opdName }}</span>
                                </span>
                            </div>
                            @endif
                        </td>

                        <!-- Tanggal & Waktu -->
                        <td class="py-4 px-6 text-left whitespace-nowrap">
                            <div class="font-bold text-slate-700 mb-1.5 text-sm">
                                {{ $meeting->date->translatedFormat('d F Y') }}
                            </div>
                            <div class="text-xs text-slate-500 font-semibold flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 font-mono">{{ $meeting->start_time ? $meeting->start_time->format('H:i') : '' }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA</span>
                            </div>
                        </td>

                        <!-- Penandatangan -->
                        <td class="py-4 px-6 text-left whitespace-nowrap">
                            <div class="font-bold text-slate-800 text-xs sm:text-sm">
                                {{ $meeting->signer_name ?? ($meeting->signer?->name ?? '-') }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                {{ $meeting->signer_title ?? ($meeting->signer?->title ?? 'Penandatangan') }}
                            </div>
                        </td>

                        <!-- Dokumen TTE -->
                        <td class="py-4 px-6 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                <!-- 1. Presensi -->
                                <a href="{{ route('meetings.export.attendance', ['meeting' => $meeting->id, 'action' => 'download']) }}" download
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95"
                                    title="Download Presensi">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span>Presensi</span>
                                </a>

                                <!-- 2. Dokumentasi -->
                                <a href="{{ route('meetings.export.photos', ['meeting' => $meeting->id, 'action' => 'download']) }}" download
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95"
                                    title="Download Dokumentasi">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Dokumentasi</span>
                                </a>

                                <!-- 3. Notulen -->
                                <a href="{{ route('meetings.export.minutes', ['meeting' => $meeting->id, 'action' => 'download']) }}" download
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95"
                                    title="Download Notulen">
                                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Notulen</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-16 px-6 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3 text-slate-400">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900">Tidak Ada Riwayat Rapat</h3>
                                @if($search || $selected_opd_id || $date_from || $date_to)
                                <button type="button" wire:click="resetFilters" class="mt-3 px-4 py-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                    Reset Filter
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$meetings" />
    </div>
</div>
