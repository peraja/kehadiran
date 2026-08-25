<?php

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $actionFilter = '';
    public string $date_from = '';
    public string $date_to = '';

    public function mount(): void
    {
        if (!auth()->user()->hasActiveRole('admin')) {
            abort(403, 'Akses khusus Super Admin.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
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
        $this->reset(['search', 'actionFilter', 'date_from', 'date_to']);
        $this->resetPage();
    }

    public function with(): array
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        if (!empty($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('user_nip', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if (!empty($this->actionFilter)) {
            if ($this->actionFilter === 'auth') {
                $query->whereIn('action', ['login', 'logout']);
            } elseif ($this->actionFilter === 'meeting') {
                $query->whereIn('action', ['create_meeting', 'delete_meeting']);
            } elseif ($this->actionFilter === 'tte') {
                $query->where('action', 'sign_tte');
            } else {
                $query->where('action', $this->actionFilter);
            }
        }

        if (!empty($this->date_from)) {
            $query->whereDate('created_at', '>=', $this->date_from);
        }

        if (!empty($this->date_to)) {
            $query->whereDate('created_at', '<=', $this->date_to);
        }

        $logs = $query->paginate(15);

        $counts = [
            'total' => AuditLog::count(),
            'auth' => AuditLog::whereIn('action', ['login', 'logout'])->count(),
            'meeting' => AuditLog::whereIn('action', ['create_meeting', 'delete_meeting'])->count(),
            'tte' => AuditLog::where('action', 'sign_tte')->count(),
        ];

        return [
            'logs' => $logs,
            'counts' => $counts,
        ];
    }
}; ?>

<div class="space-y-6 pb-10">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Audit Log
            </h1>
            <p class="text-sm font-medium text-slate-500">
                Pemerintah Kabupaten Sinjai
            </p>
        </div>
        <div class="relative z-10 flex items-center gap-2">
            <x-user-role-badge role="admin" />
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('message'))
    <x-alert type="success">
        {{ session('message') }}
    </x-alert>
    @endif

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Toolbar (Top Pills + Filter Bar) -->
        <div class="p-4 sm:p-6 border-b border-slate-100 bg-slate-50/50 space-y-4">
            <!-- Filter Pills (Grid) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 w-full">
                <button wire:click="$set('actionFilter', '')"
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-3 sm:px-4 rounded-xl text-xs font-bold transition-all {{ $actionFilter === '' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
                    Semua
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $actionFilter === '' ? 'bg-slate-800 text-slate-300' : 'bg-slate-100 text-slate-500' }}">{{ $counts['total'] }}</span>
                </button>
                <button wire:click="$set('actionFilter', 'auth')"
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-3 sm:px-4 rounded-xl text-xs font-bold transition-all {{ $actionFilter === 'auth' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $actionFilter === 'auth' ? 'bg-emerald-200' : 'bg-emerald-500' }}"></span>
                    Otentikasi
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $actionFilter === 'auth' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-100 text-emerald-700' }}">{{ $counts['auth'] }}</span>
                </button>
                <button wire:click="$set('actionFilter', 'meeting')"
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-3 sm:px-4 rounded-xl text-xs font-bold transition-all {{ $actionFilter === 'meeting' ? 'bg-primary-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $actionFilter === 'meeting' ? 'bg-primary-200' : 'bg-primary-500' }}"></span>
                    Rapat
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $actionFilter === 'meeting' ? 'bg-primary-700 text-primary-100' : 'bg-primary-100 text-primary-700' }}">{{ $counts['meeting'] }}</span>
                </button>
                <button wire:click="$set('actionFilter', 'tte')"
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-3 sm:px-4 rounded-xl text-xs font-bold transition-all {{ $actionFilter === 'tte' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $actionFilter === 'tte' ? 'bg-indigo-200' : 'bg-indigo-500' }}"></span>
                    TTE
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $actionFilter === 'tte' ? 'bg-indigo-700 text-indigo-100' : 'bg-indigo-100 text-indigo-700' }}">{{ $counts['tte'] }}</span>
                </button>
            </div>

            <!-- Search Field & Date Range (Grid) -->
            @php $hasActiveFilters = $actionFilter || $date_from || $date_to; @endphp
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-center">
                <!-- Search Input -->
                <div class="relative lg:col-span-6 xl:col-span-7 w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full h-10 rounded-xl border border-slate-200 pl-9 pr-9 py-2 text-base sm:text-sm focus:border-primary-500 focus:ring-primary-500 shadow-2xs transition-colors bg-white placeholder:text-slate-400"
                        placeholder="Cari nama, NIP, aksi, keterangan, IP...">
                    @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors" title="Hapus pencarian">
                        <svg class="w-4 h-4 bg-slate-100 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>

                <!-- Date Range Container -->
                <div class="{{ $hasActiveFilters ? 'lg:col-span-5 xl:col-span-4' : 'lg:col-span-6 xl:col-span-5' }} grid grid-cols-2 items-center bg-white border border-slate-200 rounded-xl shadow-2xs overflow-hidden h-10 divide-x divide-slate-200 w-full">
                    <div class="flex items-center px-2.5 py-1 gap-1.5 min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-400 shrink-0">Dari</span>
                        <input wire:model.live="date_from" type="date"
                            class="border-0 p-0 text-base sm:text-xs font-semibold text-slate-700 focus:ring-0 bg-transparent cursor-pointer w-full min-w-0" />
                    </div>
                    <div class="flex items-center px-2.5 py-1 gap-1.5 min-w-0">
                        <span class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-slate-400 shrink-0">Sampai</span>
                        <input wire:model.live="date_to" type="date"
                            class="border-0 p-0 text-base sm:text-xs font-semibold text-slate-700 focus:ring-0 bg-transparent cursor-pointer w-full min-w-0" />
                    </div>
                </div>

                @if($hasActiveFilters)
                <!-- Reset Button -->
                <div class="lg:col-span-1 flex items-center">
                    <button wire:click="resetFilters"
                        class="h-10 w-full inline-flex items-center justify-center gap-1.5 px-3 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 active:scale-95 transition-all border border-rose-200/80 shadow-2xs cursor-pointer"
                        title="Reset semua filter">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset</span>
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto min-h-[400px] rounded-2xl">
            <table class="w-full text-left border-collapse min-w-[760px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="py-4 px-6 text-left whitespace-nowrap w-48">Tanggal & Waktu</th>
                        <th class="py-4 px-6 text-left w-56">Nama & NIP</th>
                        <th class="py-4 px-6 text-center w-32">Aksi</th>
                        <th class="py-4 px-6 text-left">Keterangan</th>
                        <th class="py-4 px-6 text-right w-36">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm bg-white">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <!-- Waktu -->
                        <td class="py-4 px-6 text-left whitespace-nowrap">
                            <div class="text-sm font-extrabold text-slate-900">
                                {{ $log->created_at->translatedFormat('d M Y, H:i') }} WITA
                            </div>
                            <div class="text-xs text-slate-400 font-medium mt-0.5">
                                {{ $log->created_at->diffForHumans() }}
                            </div>
                        </td>

                        <!-- Nama & NIP -->
                        <td class="py-4 px-6 text-left">
                            <div class="font-extrabold text-slate-900 group-hover:text-primary-600 transition-colors block truncate text-sm leading-tight" title="{{ $log->user_name }}">
                                {{ $log->user_name ?: 'Sistem/Tamu' }}
                            </div>
                            <div class="text-xs text-slate-500 font-medium font-mono mt-1">
                                {{ $log->user_nip ? 'NIP. ' . $log->user_nip : '-' }}
                            </div>
                        </td>

                        <!-- Aksi -->
                        <td class="py-4 px-6 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border shadow-2xs {{ $log->action_badge_class }}">
                                {{ $log->action_label }}
                            </span>
                        </td>

                        <!-- Keterangan -->
                        <td class="py-4 px-6 text-left">
                            <div class="text-sm font-semibold text-slate-800 leading-snug break-words line-clamp-2 max-w-xl" title="{{ $log->description }}">
                                {{ $log->description }}
                            </div>
                        </td>

                        <!-- Alamat IP -->
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                {{ $log->ip_address ?: '-' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 px-6 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3 text-slate-400">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900">Tidak Ada Riwayat Aktivitas</h3>
                                @if($search || $actionFilter || $date_from || $date_to)
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

        <x-pagination :paginator="$logs" />
    </div>
</div>
