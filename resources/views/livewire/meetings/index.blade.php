<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    // Form fields
    public $title, $date, $start_time, $end_time, $location;
    public $start_hour = '09', $start_minute = '00';
    public $end_hour = '11', $end_minute = '00';
    public $selected_opd_id = '';
    public $selected_signer_id = 'kepala_opd';

    public function mount()
    {
        $this->selected_signer_id = 'kepala_opd';
        $this->start_hour = '09';
        $this->start_minute = '00';
        $this->end_hour = '11';
        $this->end_minute = '00';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectedOpdId($val)
    {
        $this->selected_signer_id = 'kepala_opd';
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255',
            'selected_signer_id' => 'required',
        ];

        if (auth()->user()->hasActiveRole('admin')) {
            $rules['selected_opd_id'] = 'required|exists:opds,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'Agenda wajib diisi.',
            'date.required' => 'Tanggal wajib diisi.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'location.required' => 'Lokasi wajib diisi.',
            'selected_signer_id.required' => 'Penandatangan dokumen wajib dipilih.',
            'selected_opd_id.required' => 'OPD wajib dipilih.',
            'selected_opd_id.exists' => 'OPD tidak valid.',
        ];
    }

    public function openModal()
    {
        if (auth()->user()->hasActiveRole('pimpinan')) {
            return;
        }
        $this->resetValidation();
        $this->reset(['title', 'date', 'location', 'selected_opd_id']);
        $this->start_hour = '09';
        $this->start_minute = '00';
        $this->end_hour = '11';
        $this->end_minute = '00';
        $this->start_time = '09:00';
        $this->end_time = '11:00';
        $this->selected_signer_id = 'kepala_opd';
    }

    public function updatedStartTime($val): void
    {
        if (!empty($val)) {
            try {
                $c = \Carbon\Carbon::parse($val);
                $this->start_hour = $c->format('H');
                $this->start_minute = $c->format('i');
            } catch (\Throwable $e) {}
        }
    }

    public function updatedEndTime($val): void
    {
        if (!empty($val)) {
            try {
                $c = \Carbon\Carbon::parse($val);
                $this->end_hour = $c->format('H');
                $this->end_minute = $c->format('i');
            } catch (\Throwable $e) {}
        }
    }

    public function closeModal()
    {
        // Now handled by Alpine
    }

    protected function normalizeTimes(): void
    {
        if ($this->start_hour !== '' && $this->start_minute !== '') {
            $this->start_time = sprintf('%02d:%02d', (int) $this->start_hour, (int) $this->start_minute);
        } elseif (!empty($this->start_time)) {
            try {
                $this->start_time = \Carbon\Carbon::parse($this->start_time)->format('H:i');
            } catch (\Throwable $e) {
                // Keep original to let validator catch invalid format
            }
        }

        if ($this->end_hour !== '' && $this->end_minute !== '') {
            $this->end_time = sprintf('%02d:%02d', (int) $this->end_hour, (int) $this->end_minute);
        } elseif (!empty($this->end_time)) {
            try {
                $this->end_time = \Carbon\Carbon::parse($this->end_time)->format('H:i');
            } catch (\Throwable $e) {
                // Keep original to let validator catch invalid format
            }
        }
    }

    public function saveMeeting()
    {
        if (auth()->user()->hasActiveRole('pimpinan')) {
            abort(403, 'Pimpinan tidak memiliki akses untuk membuat rapat.');
        }

        $this->normalizeTimes();
        $validated = $this->validate();
        $validated['agenda'] = $validated['title']; // Fallback for DB constraint
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'scheduled';

        $opd = null;
        if (auth()->user()->hasActiveRole('admin') && $this->selected_opd_id) {
            $opd = \App\Models\Opd::find($this->selected_opd_id);
            $validated['opd_id'] = $this->selected_opd_id;
        } else {
            $userUnit = auth()->user()->unit_name;
            $opd = \App\Models\Opd::where('name', $userUnit)->first();
            if (!$opd && $userUnit) {
                $cleanUnit = str_replace([',', '.', '-'], '', $userUnit);
                $opd = \App\Models\Opd::whereRaw("REPLACE(REPLACE(REPLACE(name, ',', ''), '.', ''), '-', '') LIKE ?", ['%' . $cleanUnit . '%'])->first();
            }
            if ($opd) {
                $validated['opd_id'] = $opd->id;
            }
        }

        if ($this->selected_signer_id && $this->selected_signer_id !== 'kepala_opd') {
            if (str_starts_with($this->selected_signer_id, 'pimpinan_')) {
                // Pimpinan manual: ambil dari tabel users
                $userId = (int) str_replace('pimpinan_', '', $this->selected_signer_id);
                $pimpinanUser = \App\Models\User::find($userId);
                if ($pimpinanUser) {
                    $validated['signer_title'] = $pimpinanUser->jabatan ?: 'Pimpinan';
                    $validated['signer_name']  = $pimpinanUser->name;
                    $validated['signer_nip']   = $pimpinanUser->nip;
                    $validated['signer_rank']  = $pimpinanUser->pangkat ?: null;
                }
            } else {
                $signer = \App\Models\OpdSigner::find($this->selected_signer_id);
                if ($signer) {
                    $validated['signer_title'] = $signer->title;
                    $validated['signer_name']  = $signer->name;
                    $validated['signer_nip']   = $signer->nip;
                    $validated['signer_rank']  = $signer->rank ?: $signer->eselon;
                }
            }
        } else if ($opd) {
            $validated['signer_title'] = $opd->leader_title ?: ('Kepala ' . $opd->name);
            $validated['signer_name']  = $opd->leader_name;
            $validated['signer_nip']   = $opd->leader_nip;
            $validated['signer_rank']  = $opd->leader_rank ?: $opd->leader_eselon;
        }


        unset($validated['selected_opd_id'], $validated['selected_signer_id']);

        $meeting = Meeting::create($validated);

        \App\Services\AuditLogger::log('create_meeting', "Buat rapat: {$meeting->title}");

        $this->reset(['title', 'date', 'start_time', 'end_time', 'location', 'selected_opd_id', 'search', 'statusFilter']);
        $this->selected_signer_id = 'kepala_opd';
        $this->resetPage();
        $this->dispatch('close-modal', 'add-meeting-modal');
        $this->dispatch('meeting-saved');
        session()->flash('message', 'Rapat berhasil dibuat.');
    }

    public function with(): array
    {
        $query = Meeting::query()->with(['opd', 'creator'])->where(function ($q) {
            $q->where('title', 'like', '%' . $this->search . '%')
                ->orWhere('location', 'like', '%' . $this->search . '%');
        });

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $user = auth()->user();
        if ($user->hasActiveRole('pimpinan')) {
            // Pimpinan hanya melihat rapat yang berstatus SELESAI dan penandatangannya adalah dirinya sendiri
            $query->where('status', 'completed')
                ->where(function ($q) use ($user) {
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
                })->where(function ($q) {
                    // Jangan tampilkan jika belum ada berkas dokumen (presensi, foto, atau notulen) dan belum pernah di-TTE
                    $q->has('attendances')
                        ->orHas('photos')
                        ->orWhereHas('minutes', function ($mq) {
                            $mq->whereNotNull('content')->where('content', '!=', '');
                        })
                        ->orWhereNotNull('minutes_signed_at')
                        ->orWhereNotNull('attendance_signed_at')
                        ->orWhereNotNull('photos_signed_at');
                });
        } elseif ($user->hasActiveRole('admin_opd')) {
            // Admin OPD melihat seluruh rapat di lingkungan unit kerjanya
            $query->where(function ($q) use ($user) {
                $q->whereHas('creator', function ($cq) use ($user) {
                    $cq->where('unit_name', $user->unit_name);
                })->orWhereHas('opd', function ($oq) use ($user) {
                    $oq->where('name', $user->unit_name);
                });
            });
        } elseif ($user->hasActiveRole('pegawai')) {
            // Pegawai biasa HANYA melihat rapat yang dibuatnya sendiri
            $query->where('created_by', $user->id);
        }

        $allOpds = auth()->user()->hasActiveRole('admin') ? \App\Models\Opd::where('is_active', true)->orderBy('name')->get() : collect();

        $targetOpd = null;
        if (auth()->user()->hasActiveRole('admin')) {
            if ($this->selected_opd_id) {
                $targetOpd = \App\Models\Opd::find($this->selected_opd_id);
            }
        } else {
            $userUnit = auth()->user()->unit_name;
            $targetOpd = \App\Models\Opd::where('name', $userUnit)->first();
            if (!$targetOpd && $userUnit) {
                $cleanUnit = str_replace([',', '.', '-'], '', $userUnit);
                $targetOpd = \App\Models\Opd::whereRaw("REPLACE(REPLACE(REPLACE(name, ',', ''), '.', ''), '-', '') LIKE ?", ['%' . $cleanUnit . '%'])->first();
            }
        }

        $opdSigners = $targetOpd ? $targetOpd->signers()->where('is_active', true)->orderByRaw("CASE eselon WHEN 'II.a' THEN 1 WHEN 'II.b' THEN 2 WHEN 'III.a' THEN 3 WHEN 'III.b' THEN 4 ELSE 5 END, id ASC")->get() : collect();

        // Tambahkan pimpinan manual (user ber-role pimpinan yang NIP-nya belum ada di opd_signers)
        if ($targetOpd) {
            $signerNips   = $opdSigners->pluck('nip')->filter()->values();
            $leaderNip    = $targetOpd->leader_nip;
            $manualPimpinan = \App\Models\User::role('pimpinan')
                ->where(function ($q) use ($targetOpd) {
                    $q->where('unit_name', $targetOpd->name);
                    if (auth()->user()->unit_name) {
                        $q->orWhere('unit_name', auth()->user()->unit_name);
                    }
                })
                ->get()
                ->filter(function ($u) use ($signerNips, $leaderNip) {
                    return ($leaderNip ? $u->nip !== $leaderNip : true)
                        && (empty($u->nip) || !$signerNips->contains($u->nip));
                });
            foreach ($manualPimpinan as $u) {
                $opdSigners->push((object)[
                    'id'          => 'pimpinan_' . $u->id,
                    'name'        => $u->name,
                    'nip'         => $u->nip,
                    'title'       => $u->jabatan ?: 'Pimpinan',
                    'bidang_name' => null,
                    'eselon'      => null,
                    'rank'        => $u->pangkat,
                    'is_manual'   => true,
                ]);
            }
        }

        $baseCountQuery = Meeting::query();
        if (!auth()->user()->hasActiveRole('admin')) {
            $baseCountQuery->where(function ($q) {
                $q->whereHas('creator', function ($cq) {
                    $cq->where('unit_name', auth()->user()->unit_name);
                })->orWhereHas('opd', function ($oq) {
                    $oq->where('name', auth()->user()->unit_name);
                });
            });
        }
        $counts = [
            'total' => (clone $baseCountQuery)->count(),
            'scheduled' => (clone $baseCountQuery)->where('status', 'scheduled')->count(),
            'ongoing' => (clone $baseCountQuery)->where('status', 'ongoing')->count(),
            'completed' => (clone $baseCountQuery)->where('status', 'completed')->count(),
        ];

        $meetings = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        return [
            'meetings' => $meetings,
            'counts' => $counts,
            'opd' => $targetOpd,
            'opdSigners' => $opdSigners,
            'allOpds' => $allOpds,
            'isAdmin' => auth()->user()->hasActiveRole('admin'),
        ];
    }
}; ?>

<div class="space-y-6 pb-10">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Daftar Rapat
            </h1>
            <p class="text-sm font-medium text-slate-500">
                {{ auth()->user()->hasActiveRole('admin') ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>
        @unless(auth()->user()->hasActiveRole('pimpinan'))
        <div class="relative z-10">
            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-meeting-modal'); $wire.openModal()" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm shadow-sm transition-all gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Rapat
            </button>
        </div>
        @endunless
    </div>

    @if (session()->has('message'))
    <x-alert type="success">
        {{ session('message') }}
    </x-alert>
    @endif

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

        <!-- Toolbar -->
        <div class="p-4 sm:p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-center">
                <!-- Filter Pills -->
                @unless(auth()->user()->hasActiveRole('pimpinan'))
                <div class="lg:col-span-7 xl:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-2 w-full">
                    <button wire:click="$set('statusFilter','')"
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-3 rounded-xl text-xs font-bold transition-all {{ $statusFilter === '' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
                        Semua
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === '' ? 'bg-slate-800 text-slate-300' : 'bg-slate-100 text-slate-500' }}">{{ $counts['total'] }}</span>
                    </button>
                    <button wire:click="$set('statusFilter','scheduled')"
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-3 rounded-xl text-xs font-bold transition-all {{ $statusFilter === 'scheduled' ? 'bg-slate-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusFilter === 'scheduled' ? 'bg-slate-300' : 'bg-slate-400' }}"></span>
                        Dijadwalkan
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'scheduled' ? 'bg-slate-700 text-slate-100' : 'bg-slate-100 text-slate-700' }}">{{ $counts['scheduled'] }}</span>
                    </button>
                    <button wire:click="$set('statusFilter','ongoing')"
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-3 rounded-xl text-xs font-bold transition-all {{ $statusFilter === 'ongoing' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusFilter === 'ongoing' ? 'bg-rose-200' : 'bg-rose-500' }}"></span>
                        Berlangsung
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'ongoing' ? 'bg-rose-700 text-rose-100' : 'bg-rose-100 text-rose-700' }}">{{ $counts['ongoing'] }}</span>
                    </button>
                    <button wire:click="$set('statusFilter','completed')"
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-3 rounded-xl text-xs font-bold transition-all {{ $statusFilter === 'completed' ? 'bg-primary-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusFilter === 'completed' ? 'bg-primary-200' : 'bg-primary-500' }}"></span>
                        Selesai
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'completed' ? 'bg-primary-700 text-primary-100' : 'bg-primary-100 text-primary-700' }}">{{ $counts['completed'] }}</span>
                    </button>
                </div>
                @endunless

                <!-- Search Field & Reset -->
                <div class="{{ auth()->user()->hasActiveRole('pimpinan') ? 'lg:col-span-12' : 'lg:col-span-5 xl:col-span-4' }} grid grid-cols-1 {{ $statusFilter ? 'sm:grid-cols-[1fr_auto]' : '' }} gap-2.5 w-full">
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            class="block w-full h-10 rounded-xl border border-slate-200 pl-9 pr-9 py-2 text-base sm:text-sm focus:border-primary-500 focus:ring-primary-500 shadow-2xs transition-colors bg-white placeholder:text-slate-400"
                            placeholder="Cari agenda atau lokasi rapat...">
                        @if($search)
                        <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors" title="Hapus pencarian">
                            <svg class="w-4 h-4 bg-slate-100 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        @endif
                    </div>

                    @if($statusFilter)
                    <button wire:click="resetFilters"
                        class="h-10 inline-flex items-center justify-center gap-1.5 px-3.5 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 active:scale-95 transition-all border border-rose-200/80 shadow-2xs cursor-pointer shrink-0"
                        title="Reset semua filter">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset</span>
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
                        <th class="py-4 px-6 text-center w-52">Status</th>
                        <th class="py-4 px-6 text-right w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                    @forelse($meetings as $meeting)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <!-- Agenda & Lokasi -->
                        <td class="py-4 px-6 text-left">
                            <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="font-extrabold text-slate-900 group-hover:text-primary-600 block text-base leading-tight mb-1.5 transition-colors">
                                {{ $meeting->title }}
                            </a>
                            <div class="text-xs text-slate-500 font-medium mt-1">
                                <span>{{ $meeting->location ?: 'Ruang Rapat' }}</span>
                            </div>
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

                        <!-- Status -->
                        <td class="py-4 px-6 text-center whitespace-nowrap">
                            <x-meeting-status-badge :meeting="$meeting" />
                        </td>

                        <!-- Aksi -->
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="inline-flex items-center justify-center px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-sm gap-1.5">
                                <svg class="w-3.5 h-3.5 text-primary-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Lihat</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-16 px-6 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3 text-slate-400">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900">Tidak Ada Data Rapat</h3>
                                @if($search || $statusFilter)
                                <button type="button" wire:click="resetFilters" class="mt-3 px-4 py-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                    Reset Filter
                                </button>
                                @elseif(!auth()->user()->hasActiveRole('pimpinan'))
                                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-meeting-modal'); $wire.openModal()" class="mt-3 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-sm gap-2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Buat Rapat
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

    @unless(auth()->user()->hasActiveRole('pimpinan'))
    <!-- Modal Buat Rapat -->
    <x-modal name="add-meeting-modal" maxWidth="3xl" :show="$errors->isNotEmpty()" x-on:meeting-saved.window="show = false">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-6 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Buat Rapat
                </h2>
                <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveMeeting" class="space-y-5">
                <!-- Agenda / Judul Rapat -->
                <div>
                    <label for="title" class="block text-sm font-bold text-slate-700 mb-1">Agenda</label>
                    <input wire:model="title" id="title" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Rapat Koordinasi Evaluasi SPBE Triwulan II" />
                    @error('title') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-4">
                    <!-- Tanggal -->
                    <div>
                        <label for="date" class="block text-sm font-bold text-slate-700 mb-1">Tanggal</label>
                        <input wire:model="date" id="date" type="date" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors cursor-pointer" />
                        @error('date') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Grid Waktu (2 Kolom di Mobile, Sejajar di Desktop) -->
                    <div class="grid grid-cols-2 gap-4 md:contents">
                        <!-- Jam Mulai -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Waktu Mulai</label>
                            <div class="flex items-center gap-1.5">
                                <div class="flex-1 md:flex-none">
                                    <select wire:model="start_hour" class="w-full md:w-16 text-base sm:text-sm py-2.5 px-2 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors cursor-pointer">
                                        @for($h = 8; $h <= 16; $h++)
                                            @php $hStr = sprintf('%02d', $h); @endphp
                                            <option value="{{ $hStr }}">{{ $hStr }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <span class="text-slate-400 font-bold text-sm">:</span>
                                <div class="flex-1 md:flex-none">
                                    <select wire:model="start_minute" class="w-full md:w-16 text-base sm:text-sm py-2.5 px-2 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors cursor-pointer">
                                        @foreach(['00', '15', '30', '45'] as $mOption)
                                            <option value="{{ $mOption }}">{{ $mOption }}</option>
                                        @endforeach
                                        @if(!in_array($start_minute, ['00', '15', '30', '45']) && $start_minute !== '')
                                            <option value="{{ $start_minute }}">{{ $start_minute }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            @error('start_time') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <!-- Jam Selesai -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Waktu Selesai</label>
                            <div class="flex items-center gap-1.5">
                                <div class="flex-1 md:flex-none">
                                    <select wire:model="end_hour" class="w-full md:w-16 text-base sm:text-sm py-2.5 px-2 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors cursor-pointer">
                                        @for($h = 8; $h <= 16; $h++)
                                            @php $hStr = sprintf('%02d', $h); @endphp
                                            <option value="{{ $hStr }}">{{ $hStr }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <span class="text-slate-400 font-bold text-sm">:</span>
                                <div class="flex-1 md:flex-none">
                                    <select wire:model="end_minute" class="w-full md:w-16 text-base sm:text-sm py-2.5 px-2 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors cursor-pointer">
                                        @foreach(['00', '15', '30', '45'] as $mOption)
                                            <option value="{{ $mOption }}">{{ $mOption }}</option>
                                        @endforeach
                                        @if(!in_array($end_minute, ['00', '15', '30', '45']) && $end_minute !== '')
                                            <option value="{{ $end_minute }}">{{ $end_minute }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            @error('end_time') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Lokasi -->
                <div>
                    <label for="location" class="block text-sm font-bold text-slate-700 mb-1">Lokasi</label>
                    <input wire:model="location" id="location" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Ruang Pola Kantor Bupati Sinjai" />
                    @error('location') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                @if($isAdmin)
                <!-- OPD Selection (Admin Only) -->
                <div>
                    <label for="selected_opd_id" class="block text-sm font-bold text-slate-700 mb-1">OPD / Instansi</label>
                    <select wire:model.live="selected_opd_id" id="selected_opd_id" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors">
                        <option value="">Pilih OPD</option>
                        @foreach($allOpds as $o)
                        <option value="{{ $o->id }}">{{ $o->name }}</option>
                        @endforeach
                    </select>
                    @error('selected_opd_id') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                @endif

                <!-- Penandatangan Dokumen -->
                <div>
                    <label for="selected_signer_id" class="block text-sm font-bold text-slate-700 mb-1">
                        Penandatangan Dokumen
                    </label>
                    <select wire:model="selected_signer_id" id="selected_signer_id" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors">
                        @php
                            $leaderTitle = $opd?->leader_title ?: ($opd ? 'Kepala ' . $opd->name : 'Kepala OPD');
                        @endphp
                        <option value="kepala_opd">{{ $leaderTitle }}{{ $opd?->leader_name ? ' — ' . $opd->leader_name : '' }}</option>
                        @foreach($opdSigners as $s)
                        <option value="{{ $s->id }}">{{ $s->title }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('selected_signer_id') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="w-full sm:w-auto px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                        Batal
                    </button>

                    <button type="submit" wire:loading.attr="disabled" wire:target="saveMeeting" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                        <svg wire:loading.remove wire:target="saveMeeting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="saveMeeting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Simpan Rapat</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
    @endunless
</div>