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
    public $title, $agenda, $date, $start_time, $end_time, $location;

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
            'agenda' => 'required|string',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255',
        ];
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['title', 'agenda', 'date', 'start_time', 'end_time', 'location']);
    }

    public function closeModal()
    {
        // Now handled by Alpine
    }

    public function saveMeeting()
    {
        $validated = $this->validate();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'scheduled';

        Meeting::create($validated);

        $this->reset(['title', 'agenda', 'date', 'start_time', 'end_time', 'location']);
        $this->dispatch('close-modal', 'add-meeting-modal');
        $this->dispatch('meeting-saved');
        session()->flash('message', 'Rapat berhasil dibuat.');
    }

    public function with(): array
    {
        $query = Meeting::query()->where(function($q) {
            $q->where('title', 'like', '%'.$this->search.'%')
              ->orWhere('location', 'like', '%'.$this->search.'%');
        });
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        if (!auth()->user()->hasRole('admin')) {
            // Admin OPD and Pegawai only see meetings created by someone in their own unit
            $query->whereHas('creator', function($q) {
                $q->where('unit_name', auth()->user()->unit_name);
            });
        }

        $meetings = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);
            
        return compact('meetings');
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manajemen Rapat') }}
            </h2>
            @if(auth()->user()->hasRole(['admin', 'admin_opd']))
            <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-meeting-modal'); $wire.openModal()">
                + Tambah Rapat
            </x-primary-button>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session()->has('message'))
                <div class="mb-4">
                    <x-alert type="success">
                        {{ session('message') }}
                    </x-alert>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-6 flex flex-col md:flex-row gap-4 justify-between items-center bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <div class="relative w-full md:w-1/2">
                            <label for="search-meetings" class="sr-only">Cari judul atau lokasi rapat</label>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <x-text-input wire:model.live.debounce.300ms="search" id="search-meetings" placeholder="Cari judul atau lokasi rapat..." class="w-full pl-10" />
                        </div>
                        <div class="w-full md:w-1/4">
                            <label for="status-filter" class="sr-only">Filter Berdasarkan Status</label>
                            <select wire:model.live="statusFilter" id="status-filter" class="w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm">
                                <option value="">Semua Status</option>
                                <option value="scheduled">Dijadwalkan</option>
                                <option value="ongoing">Berlangsung</option>
                                <option value="completed">Selesai</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto bg-white rounded-xl border border-gray-200">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-600 uppercase text-xs font-semibold tracking-wider border-b border-gray-200">
                                    <th class="py-3 px-6 text-left">Informasi Rapat</th>
                                    <th class="py-3 px-6 text-left">Jadwal</th>
                                    <th class="py-3 px-6 text-center">Status</th>
                                    <th class="py-3 px-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm divide-y divide-gray-100">
                                @forelse($meetings as $meeting)
                                    <tr class="hover:bg-primary-50/50 transition-colors">
                                        <td class="py-4 px-6 text-left">
                                            <div class="font-bold text-gray-900">{{ $meeting->title }}</div>
                                            <div class="text-xs text-gray-500 mt-1 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                {{ $meeting->location ?? 'Online' }}
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-left whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $meeting->date->translatedFormat('d F Y') }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA</div>
                                        </td>
                                        <td class="py-4 px-6 text-center whitespace-nowrap">
                                            @if($meeting->status == 'scheduled')
                                                <span class="inline-flex px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold tracking-wide">DIJADWALKAN</span>
                                            @elseif($meeting->status == 'ongoing')
                                                <span class="inline-flex px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold tracking-wide animate-pulse">BERLANGSUNG</span>
                                            @elseif($meeting->status == 'completed')
                                                <span class="inline-flex px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-xs font-semibold tracking-wide">SELESAI</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-right whitespace-nowrap">
                                            <a href="{{ route('meetings.overview', $meeting->id) }}" wire:navigate class="inline-flex items-center px-3.5 py-1.5 border border-transparent text-xs font-semibold rounded-lg shadow-sm text-white bg-primary-600 hover:bg-primary-700 active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all">
                                                Kelola
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-12 px-6 text-center text-gray-500">
                                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            @if($search || $statusFilter)
                                                <p class="font-medium text-gray-700 text-sm">Tidak ada data rapat yang sesuai pencarian / filter.</p>
                                                <button wire:click="$set('search', ''); $set('statusFilter', '')" class="mt-3 inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition active:scale-95">
                                                    Reset Pencarian & Filter
                                                </button>
                                            @else
                                                <p class="font-medium text-gray-700 text-sm">Belum ada data agenda rapat.</p>
                                                @if(auth()->user()->hasRole(['admin', 'admin_opd']))
                                                    <div class="mt-3">
                                                        <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-meeting-modal'); $wire.openModal()">
                                                            + Tambah Rapat Baru
                                                        </x-primary-button>
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $meetings->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Rapat -->
    <x-modal name="add-meeting-modal" maxWidth="2xl" :show="$errors->isNotEmpty()" x-on:meeting-saved.window="show = false">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                Tambah Rapat Baru
            </h2>

            <form wire:submit="saveMeeting">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <x-input-label for="title" value="Judul Rapat" />
                        <x-text-input wire:model="title" id="title" type="text" class="mt-1 block w-full" placeholder="Contoh: Rapat Koordinasi SPBE Tahunan" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="col-span-2">
                        <x-input-label for="agenda" value="Agenda" />
                        <x-textarea-input wire:model="agenda" id="agenda" class="mt-1 block w-full" rows="3" placeholder="Deskripsikan garis besar hal yang akan dibahas..." required></x-textarea-input>
                        <x-input-error :messages="$errors->get('agenda')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="date" value="Tanggal" />
                        <x-text-input wire:model="date" id="date" type="date" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>

                    <div class="flex gap-2">
                        <div class="w-1/2">
                            <x-input-label for="start_time" value="Mulai" />
                            <x-text-input wire:model="start_time" id="start_time" type="time" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                        </div>
                        <div class="w-1/2">
                            <x-input-label for="end_time" value="Selesai" />
                            <x-text-input wire:model="end_time" id="end_time" type="time" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                        </div>
                    </div>

                    <div class="col-span-2">
                        <x-input-label for="location" value="Tempat / Ruangan" />
                        <x-text-input wire:model="location" id="location" type="text" class="mt-1 block w-full" placeholder="Contoh: Ruang Pola Kantor Bupati" required />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        Batal
                    </x-secondary-button>

                    <x-primary-button class="ml-3">
                        Simpan Rapat
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</div>


