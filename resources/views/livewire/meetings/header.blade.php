<?php

use Livewire\Volt\Component;
use App\Models\Meeting;

new class extends Component {
    public Meeting $meeting;

    // Edit form fields
    public $title;
    public $agenda;
    public $date;
    public $start_time;
    public $end_time;
    public $location;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
        $this->loadMeetingData();
    }

    public function loadMeetingData()
    {
        $this->title = $this->meeting->title;
        $this->agenda = $this->meeting->agenda;
        $this->date = $this->meeting->date ? $this->meeting->date->format('Y-m-d') : '';
        $this->start_time = $this->meeting->start_time ? $this->meeting->start_time->format('H:i') : '';
        $this->end_time = $this->meeting->end_time ? $this->meeting->end_time->format('H:i') : '';
        $this->location = $this->meeting->location;
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

    public function startMeeting()
    {
        if (!auth()->user()->hasRole(['admin', 'admin_opd']) && $this->meeting->created_by !== auth()->id()) {
            return;
        }

        $this->meeting->update(['status' => 'ongoing']);
        $this->meeting->refresh();
        session()->flash('message', 'Rapat telah dimulai (Status: Ongoing).');
    }

    public function finishMeeting()
    {
        if (!auth()->user()->hasRole(['admin', 'admin_opd']) && $this->meeting->created_by !== auth()->id()) {
            return;
        }

        $this->meeting->update(['status' => 'completed']);
        $this->meeting->refresh();
        session()->flash('message', 'Rapat telah selesai (Status: Completed).');
    }

    public function openEditModal()
    {
        $this->resetValidation();
        $this->loadMeetingData();
    }

    public function updateMeeting()
    {
        if (!auth()->user()->hasRole(['admin', 'admin_opd']) && $this->meeting->created_by !== auth()->id()) {
            return;
        }

        $validated = $this->validate();
        $this->meeting->update($validated);
        $this->meeting->refresh();

        $this->dispatch('close-modal', 'edit-meeting-modal');
        session()->flash('message', 'Data rapat berhasil diperbarui.');
    }

    public function deleteMeeting()
    {
        if (!auth()->user()->hasRole(['admin', 'admin_opd']) && $this->meeting->created_by !== auth()->id()) {
            return;
        }

        $this->meeting->delete();
        session()->flash('message', 'Rapat berhasil dihapus.');
        $this->redirect(route('meetings.index'), navigate: true);
    }
}; ?>

<div>
    @if (session()->has('message'))
        <div class="mb-4">
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    {{ $meeting->title }}
                </h2>
                @if($meeting->status == 'scheduled')
                    <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wide">Dijadwalkan</span>
                @elseif($meeting->status == 'ongoing')
                    <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wide animate-pulse">Sedang Berlangsung</span>
                @elseif($meeting->status == 'completed')
                    <span class="bg-primary-100 text-primary-800 text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wide">Selesai</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center text-sm text-gray-500 mt-2 gap-x-4 gap-y-1">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    {{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ $meeting->start_time ? $meeting->start_time->format('H:i') : '' }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA
                </span>
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ $meeting->location }}
                </span>
            </div>
        </div>
        
        @if(auth()->user()->hasRole(['admin', 'admin_opd']) || $meeting->created_by === auth()->id())
            <div class="flex items-center space-x-2">
                @if($meeting->status == 'scheduled')
                    <x-primary-button wire:click="startMeeting" wire:confirm="Mulai rapat ini sekarang?">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Mulai Rapat
                    </x-primary-button>
                @elseif($meeting->status == 'ongoing')
                    <x-primary-button wire:click="finishMeeting" wire:confirm="Tandai rapat ini telah selesai?" class="bg-emerald-600 hover:bg-emerald-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Selesaikan Rapat
                    </x-primary-button>
                @endif

                <x-secondary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-meeting-modal'); $wire.openEditModal()">
                    <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit
                </x-secondary-button>
            </div>
        @endif
    </div>

    <!-- Modal Edit Rapat -->
    <x-modal name="edit-meeting-modal" maxWidth="2xl" :show="$errors->isNotEmpty()">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900">
                    Edit Data Rapat
                </h2>
                <x-danger-button wire:click="deleteMeeting" wire:confirm="Apakah Anda yakin ingin menghapus rapat ini? Data presensi, foto, dan notulen terkait akan terhapus." class="text-xs">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus Rapat
                </x-danger-button>
            </div>

            <form wire:submit="updateMeeting">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <x-input-label for="edit_title" value="Judul Rapat" />
                        <x-text-input wire:model="title" id="edit_title" type="text" class="mt-1 block w-full" placeholder="Contoh: Rapat Koordinasi SPBE Tahunan" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="col-span-2">
                        <x-input-label for="edit_agenda" value="Agenda" />
                        <x-textarea-input wire:model="agenda" id="edit_agenda" class="mt-1 block w-full" rows="3" placeholder="Deskripsikan garis besar hal yang akan dibahas..." required></x-textarea-input>
                        <x-input-error :messages="$errors->get('agenda')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit_date" value="Tanggal" />
                        <x-text-input wire:model="date" id="edit_date" type="date" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-2" />
                    </div>

                    <div class="flex gap-2">
                        <div class="w-1/2">
                            <x-input-label for="edit_start_time" value="Mulai" />
                            <x-text-input wire:model="start_time" id="edit_start_time" type="time" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                        </div>
                        <div class="w-1/2">
                            <x-input-label for="edit_end_time" value="Selesai" />
                            <x-text-input wire:model="end_time" id="edit_end_time" type="time" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                        </div>
                    </div>

                    <div class="col-span-2">
                        <x-input-label for="edit_location" value="Tempat / Ruangan" />
                        <x-text-input wire:model="location" id="edit_location" type="text" class="mt-1 block w-full" placeholder="Contoh: Ruang Pola Kantor Bupati" required />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        Batal
                    </x-secondary-button>

                    <x-primary-button class="ml-3">
                        Simpan Perubahan
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
