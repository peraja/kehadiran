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
    public $selected_signer_id = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255',
        ];
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['title', 'date', 'start_time', 'end_time', 'location', 'selected_signer_id']);
    }

    public function closeModal()
    {
        // Now handled by Alpine
    }

    public function saveMeeting()
    {
        $validated = $this->validate();
        $validated['agenda'] = $validated['title']; // Fallback for DB constraint
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'scheduled';

        // Signer logic
        $opd = \App\Models\Opd::where('name', auth()->user()->unit_name)->orWhere('name', auth()->user()->unit_name)->first(); // Fallback if name differs
        if (!$opd) $opd = \App\Models\Opd::where('name', 'like', '%' . auth()->user()->unit_name . '%')->first();

        if ($this->selected_signer_id) {
            $signer = \App\Models\OpdSigner::find($this->selected_signer_id);
            if ($signer) {
                $validated['signer_title'] = $signer->title;
                $validated['signer_name'] = $signer->name;
                $validated['signer_nip'] = $signer->nip;
                $validated['signer_rank'] = $signer->eselon;
            }
        } else if ($opd) {
            $validated['signer_title'] = $opd->leader_title ?: 'Kepala OPD';
            $validated['signer_name'] = $opd->leader_name;
            $validated['signer_nip'] = $opd->leader_nip;
            $validated['signer_rank'] = $opd->leader_rank;
        }

        Meeting::create($validated);

        $this->reset(['title', 'date', 'start_time', 'end_time', 'location', 'selected_signer_id']);
        $this->dispatch('close-modal', 'add-meeting-modal');
        $this->dispatch('meeting-saved');
        session()->flash('message', 'Rapat berhasil dibuat.');
    }

    public function with(): array
    {
        $query = Meeting::query()->where(function ($q) {
            $q->where('title', 'like', '%' . $this->search . '%')
                ->orWhere('location', 'like', '%' . $this->search . '%');
        });

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if (!auth()->user()->hasRole('admin')) {
            // Admin OPD and Pegawai only see meetings created by someone in their own unit
            $query->whereHas('creator', function ($q) {
                $q->where('unit_name', auth()->user()->unit_name);
            });
        }

        $opd = \App\Models\Opd::where('name', auth()->user()->unit_name)->first();
        if (!$opd) $opd = \App\Models\Opd::where('name', 'like', '%' . auth()->user()->unit_name . '%')->first();

        $opdSigners = $opd ? $opd->signers()->where('is_active', true)->orderByRaw("CASE eselon WHEN 'II.a' THEN 1 WHEN 'II.b' THEN 2 WHEN 'III.a' THEN 3 WHEN 'III.b' THEN 4 WHEN 'IV.a' THEN 5 WHEN 'IV.b' THEN 6 ELSE 7 END, id ASC")->get() : collect();

        $baseCountQuery = Meeting::query();
        if (!auth()->user()->hasRole('admin')) {
            $baseCountQuery->whereHas('creator', function ($q) {
                $q->where('unit_name', auth()->user()->unit_name);
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

        return compact('meetings', 'opdSigners', 'opd', 'counts');
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
                {{ auth()->user()->hasRole('admin') ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>
        <div class="relative z-10">
            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-meeting-modal'); $wire.openModal()" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm shadow-sm transition-all gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Rapat Baru
            </button>
        </div>
    </div>

    @if (session()->has('message'))
    <x-alert type="success">
        <h3 class="font-semibold text-emerald-800">Berhasil</h3>
        <p class="text-emerald-700 mt-0.5">{{ session('message') }}</p>
    </x-alert>
    @endif

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

        <!-- Toolbar -->
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
            <!-- Filter Pills -->
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="$set('statusFilter','')"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $statusFilter === '' ? 'bg-slate-800 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
                    Semua Status
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === '' ? 'bg-slate-700 text-slate-300' : 'bg-slate-100 text-slate-500' }}">{{ $counts['total'] }}</span>
                </button>
                <button wire:click="$set('statusFilter','scheduled')"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $statusFilter === 'scheduled' ? 'bg-slate-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700' }}">
                    Dijadwalkan
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'scheduled' ? 'bg-slate-700 text-slate-100' : 'bg-slate-100 text-slate-700' }}">{{ $counts['scheduled'] }}</span>
                </button>
                <button wire:click="$set('statusFilter','ongoing')"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $statusFilter === 'ongoing' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700' }}">
                    Berlangsung
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'ongoing' ? 'bg-rose-700 text-rose-100' : 'bg-rose-100 text-rose-700' }}">{{ $counts['ongoing'] }}</span>
                </button>
                <button wire:click="$set('statusFilter','completed')"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $statusFilter === 'completed' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700' }}">
                    Selesai
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'completed' ? 'bg-primary-700 text-primary-100' : 'bg-primary-100 text-primary-700' }}">{{ $counts['completed'] }}</span>
                </button>
            </div>

            <!-- Search Field -->
            <div class="w-full md:w-80">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full rounded-xl border border-slate-200 pl-10 pr-10 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500 shadow-sm transition-colors" placeholder="Cari agenda atau lokasi rapat...">
                    @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5 bg-slate-100 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="py-4 px-6 text-left">Agenda & Lokasi</th>
                        <th class="py-4 px-6 text-left">Tanggal & Waktu</th>
                        <th class="py-4 px-6 text-center w-32">Status</th>
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
                                Lokasi: {{ $meeting->location ?? 'Online' }}
                            </div>
                        </td>

                        <!-- Tanggal & Waktu -->
                        <td class="py-4 px-6 text-left whitespace-nowrap">
                            <div class="font-bold text-slate-700 mb-2 text-sm">
                                {{ $meeting->date->translatedFormat('d F Y') }}
                            </div>
                            <div class="text-xs text-slate-500 font-semibold flex items-center">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 shadow-sm">{{ $meeting->start_time ? $meeting->start_time->format('H:i') : '' }}</span>
                                <span class="text-slate-400 mx-1.5">s/d</span>
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 shadow-sm">{{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }}</span>
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-6 text-center whitespace-nowrap">
                            <x-meeting-status-badge :status="$meeting->status" />
                        </td>

                        <!-- Aksi -->
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="inline-flex items-center px-4 py-2 border border-slate-200 text-xs font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 hover:text-slate-900 active:scale-95 transition-all shadow-sm">
                                Kelola
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 px-6 text-center text-slate-500">
                            <div class="w-12 h-12 bg-slate-50 text-slate-300 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            @if($search || $statusFilter)
                            <p class="font-bold text-slate-500 text-sm">Tidak ada data rapat yang sesuai pencarian.</p>
                            <button wire:click="$set('search', ''); $set('statusFilter', '')" class="mt-3 inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                                Reset Pencarian
                            </button>
                            @else
                            <p class="font-bold text-slate-500 text-sm">Belum ada data agenda rapat.</p>
                            <div class="mt-3">
                                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-meeting-modal'); $wire.openModal()" class="inline-flex items-center px-4 py-2 bg-primary-50 hover:bg-primary-100 text-primary-700 text-xs font-bold rounded-xl transition-colors border border-primary-200 shadow-sm">
                                    + Tambah Rapat Baru
                                </button>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 bg-white">
            {{ $meetings->links() }}
        </div>
    </div>

    <!-- Modal Tambah Rapat -->
    <x-modal name="add-meeting-modal" maxWidth="3xl" :show="$errors->isNotEmpty()" x-on:meeting-saved.window="show = false">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-6 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Tambah Rapat Baru
                </h2>
                <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveMeeting" class="space-y-5">
                <div>
                    <label for="title" class="block text-sm font-bold text-slate-700 mb-1">Agenda</label>
                    <input wire:model="title" id="title" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Rapat Koordinasi SPBE" required />
                    @error('title') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="date" class="block text-sm font-bold text-slate-700 mb-1">Tanggal</label>
                        <input wire:model="date" id="date" type="date" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" required />
                        @error('date') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-3">
                        <div class="w-1/2">
                            <label for="start_time" class="block text-sm font-bold text-slate-700 mb-1">Mulai (WITA)</label>
                            <input wire:model="start_time" id="start_time" type="time" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" required />
                            @error('start_time') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label for="end_time" class="block text-sm font-bold text-slate-700 mb-1">Selesai (WITA)</label>
                            <input wire:model="end_time" id="end_time" type="time" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" required />
                            @error('end_time') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="location" class="block text-sm font-bold text-slate-700 mb-1">Lokasi</label>
                    <input wire:model="location" id="location" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Ruang Pola Kantor Bupati" required />
                    @error('location') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <!-- Penandatangan -->
                <div class="pt-4 border-t border-slate-100">
                    <label for="selected_signer_id" class="block text-sm font-bold text-slate-700 mb-1">Penandatangan Dokumen</label>
                    <select wire:model.live="selected_signer_id" id="selected_signer_id" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors">
                        <option value="">
                            {{ $opd?->leader_title ?: 'Kepala OPD' }}{{ !empty($opd?->leader_name) ? ' — ' . $opd->leader_name : ' (Default)' }}
                        </option>
                        @foreach($opdSigners as $s)
                        <option value="{{ $s->id }}">{{ $s->title }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-sm transition-colors shadow-sm">
                        Batal
                    </button>

                    <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm active:scale-95">
                        <span wire:loading.remove wire:target="saveMeeting">Simpan Rapat</span>
                        <span wire:loading wire:target="saveMeeting" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>