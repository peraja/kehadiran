<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\Opd;
use App\Models\OpdSigner;

new class extends Component {
    public Meeting $meeting;
    public $title, $date, $start_time, $end_time, $location;
    public $signer_title, $signer_name, $signer_nip, $signer_rank;
    public $selected_signer_id = '';
    public $selected_opd_id = '';
    public $opd;
    public $opdSigners = [];

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
        $this->loadMeetingData();
    }

    public function loadMeetingData()
    {
        $this->title = $this->meeting->title;
        $this->date = $this->meeting->date ? $this->meeting->date->format('Y-m-d') : '';
        $this->start_time = $this->meeting->start_time ? $this->meeting->start_time->format('H:i') : '';
        $this->end_time = $this->meeting->end_time ? $this->meeting->end_time->format('H:i') : '';
        $this->location = $this->meeting->location;
        $this->signer_title = $this->meeting->signer_title ?? '';
        $this->signer_name = $this->meeting->signer_name ?? '';
        $this->signer_nip = $this->meeting->signer_nip ?? '';
        $this->signer_rank = $this->meeting->signer_rank ?? '';
        $this->selected_opd_id = (string)($this->meeting->opd_id ?? '');

        $opd = null;
        if ($this->selected_opd_id) {
            $opd = Opd::find($this->selected_opd_id);
        } else {
            $userUnit = $this->meeting->creator?->unit_name ?? auth()->user()->unit_name;
            if ($userUnit) {
                $opd = Opd::where('name', $userUnit)->first();
                if (!$opd) {
                    $cleanUnit = str_replace([',', '.', '-'], '', $userUnit);
                    $opd = Opd::whereRaw("REPLACE(REPLACE(REPLACE(name, ',', ''), '.', ''), '-', '') LIKE ?", ['%' . $cleanUnit . '%'])->first();
                }
            }
        }
        $this->opd = $opd;
        $this->opdSigners = $opd ? $opd->signers()->where('is_active', true)->orderByRaw("CASE eselon WHEN 'II.a' THEN 1 WHEN 'II.b' THEN 2 WHEN 'III.a' THEN 3 WHEN 'III.b' THEN 4 WHEN 'IV.a' THEN 5 WHEN 'IV.b' THEN 6 ELSE 7 END, id ASC")->get() : collect();

        $matchedSigner = $this->opdSigners->first(function ($s) {
            return $s->name === $this->meeting->signer_name || $s->title === $this->meeting->signer_title;
        });
        $this->selected_signer_id = $matchedSigner ? (string) $matchedSigner->id : '';
    }

    public function updatedSelectedOpdId($val)
    {
        $this->selected_signer_id = '';
        $opd = !empty($val) ? Opd::find($val) : null;
        $this->opd = $opd;
        $this->opdSigners = $opd ? $opd->signers()->where('is_active', true)->orderByRaw("CASE eselon WHEN 'II.a' THEN 1 WHEN 'II.b' THEN 2 WHEN 'III.a' THEN 3 WHEN 'III.b' THEN 4 WHEN 'IV.a' THEN 5 WHEN 'IV.b' THEN 6 ELSE 7 END, id ASC")->get() : collect();

        if ($opd) {
            $this->signer_title = $opd->leader_title ?: 'Kepala OPD';
            $this->signer_name = $opd->leader_name;
            $this->signer_nip = $opd->leader_nip;
            $this->signer_rank = $opd->leader_rank;
        } else {
            $this->signer_title = '';
            $this->signer_name = '';
            $this->signer_nip = '';
            $this->signer_rank = '';
        }
    }

    public function updatedSelectedSignerId($val)
    {
        if (empty($val)) {
            $this->signer_title = '';
            $this->signer_name = '';
            $this->signer_nip = '';
            $this->signer_rank = '';
        } else {
            $signer = OpdSigner::find($val);
            if ($signer) {
                $this->signer_title = $signer->title;
                $this->signer_name = $signer->name;
                $this->signer_nip = $signer->nip ?? '';
                $this->signer_rank = $signer->rank ?? '';
            }
        }
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255',
            'signer_title' => 'nullable|string|max:255',
            'signer_name' => 'nullable|string|max:255',
            'signer_nip' => 'nullable|string|max:50',
            'signer_rank' => 'nullable|string|max:255',
        ];
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
        ];
    }

    public function startMeeting()
    {
        if (!auth()->user()->hasRole(['admin', 'admin_opd']) && $this->meeting->created_by !== auth()->id()) {
            return;
        }

        $this->meeting->update(['status' => 'ongoing']);
        $this->meeting->refresh();
        session()->flash('message', 'Rapat dimulai.');
    }

    public function finishMeeting()
    {
        if (!auth()->user()->hasRole(['admin', 'admin_opd']) && $this->meeting->created_by !== auth()->id()) {
            return;
        }

        $this->meeting->update(['status' => 'completed']);
        $this->meeting->refresh();
        session()->flash('message', 'Rapat diselesaikan.');
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
        if (auth()->user()->hasRole('admin')) {
            $validated['opd_id'] = $this->selected_opd_id ?: null;
        }
        $this->meeting->update($validated);
        $this->meeting->refresh();

        $this->dispatch('close-modal', 'edit-meeting-modal');
        session()->flash('message', 'Rapat berhasil diperbarui.');
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

    public function with(): array
    {
        return [
            'allOpds' => auth()->user()->hasRole('admin') ? Opd::where('is_active', true)->orderBy('name')->get() : collect(),
            'isAdmin' => auth()->user()->hasRole('admin'),
        ];
    }
}; ?>

<div class="relative">
    @if (session()->has('message'))
    <x-alert type="success" class="mb-5">
        {{ session('message') }}
    </x-alert>
    @endif

    <div class="flex flex-col md:flex-row md:items-start justify-between gap-5 relative z-10">
        <div class="space-y-2.5 flex-1 min-w-0">
            <div>
                <x-meeting-status-badge :status="$meeting->status" />
            </div>

            <h1 class="font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight leading-tight break-words">{{ trim($meeting->title) }}</h1>

            <!-- Single Line Metadata with Icons -->
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium text-slate-600 pt-1">
                <div class="flex items-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}</span>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $meeting->start_time ? $meeting->start_time->format('H:i') : '' }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA</span>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="break-words">{{ $meeting->location }}</span>
                </div>

                @if($meeting->opd || ($meeting->creator && $meeting->creator->unit_name))
                <div class="flex items-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="text-slate-800 break-words">{{ $meeting->opd?->name ?? $meeting->creator?->unit_name }}</span>
                </div>
                @endif
            </div>
        </div>

        @if(auth()->user()->hasRole(['admin', 'admin_opd']) || $meeting->created_by === auth()->id())
        <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full md:w-auto mt-2 md:mt-0">
            @if($meeting->status == 'scheduled')
            <button wire:click="startMeeting" wire:confirm="Mulai rapat ini?" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Mulai Rapat
            </button>
            @elseif($meeting->status == 'ongoing')
            <button wire:click="finishMeeting" wire:confirm="Selesaikan rapat ini?" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Selesaikan
            </button>
            @endif

            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'qr-presensi-modal')" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2 bg-primary-50 hover:bg-primary-100 border border-primary-200 active:scale-95 text-primary-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                QR Code
            </button>

            <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-meeting-modal'); $wire.openEditModal()" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
                Edit
            </button>
        </div>
        @endif
    </div>

    <!-- Modal Edit Rapat -->
    <x-modal name="edit-meeting-modal" maxWidth="2xl" :show="$errors->isNotEmpty()">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-6 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Edit Data Rapat
                </h2>
                <div class="flex items-center gap-2">
                    <button wire:click="deleteMeeting" wire:confirm="Apakah Anda yakin ingin menghapus rapat ini? Data presensi, foto, dan notulen terkait akan terhapus secara permanen." class="inline-flex items-center px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl text-xs font-bold transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                    <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit="updateMeeting" class="space-y-5">
                <div>
                    <label for="edit_title" class="block text-sm font-bold text-slate-700 mb-1">Agenda</label>
                    <input wire:model="title" id="edit_title" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Rapat Koordinasi SPBE" required />
                    @error('title') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="edit_date" class="block text-sm font-bold text-slate-700 mb-1">Tanggal</label>
                        <input wire:model="date" id="edit_date" type="date" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" required />
                        @error('date') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-3">
                        <div class="w-1/2">
                            <label for="edit_start_time" class="block text-sm font-bold text-slate-700 mb-1">Waktu Mulai</label>
                            <input wire:model="start_time" id="edit_start_time" type="time" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" required />
                            @error('start_time') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-1/2">
                            <label for="edit_end_time" class="block text-sm font-bold text-slate-700 mb-1">Waktu Selesai</label>
                            <input wire:model="end_time" id="edit_end_time" type="time" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" required />
                            @error('end_time') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="edit_location" class="block text-sm font-bold text-slate-700 mb-1">Lokasi</label>
                    <input wire:model="location" id="edit_location" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Ruang Pola Kantor Bupati" required />
                    @error('location') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                @if($isAdmin)
                <div>
                    <label for="edit_selected_opd_id" class="block text-sm font-bold text-slate-700 mb-1">OPD</label>
                    <select wire:model.live="selected_opd_id" id="edit_selected_opd_id" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" required>
                        <option value="">-- Pilih OPD --</option>
                        @foreach($allOpds as $opdItem)
                        <option value="{{ $opdItem->id }}">{{ $opdItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Penandatangan -->
                <div class="pt-4 border-t border-slate-100">
                    <label for="edit_selected_signer_id" class="block text-sm font-bold text-slate-700 mb-1">Penandatangan</label>
                    <select wire:model.live="selected_signer_id" id="edit_selected_signer_id" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" {{ $isAdmin && empty($selected_opd_id) ? 'disabled' : '' }}>
                        <option value="">
                            @if($isAdmin && empty($selected_opd_id))
                            -- Pilih OPD terlebih dahulu --
                            @else
                            {{ $opd?->leader_title ?: 'Kepala OPD' }}{{ !empty($opd?->leader_name) ? ' — ' . $opd->leader_name : ' (Default)' }}
                            @endif
                        </option>
                        @foreach($opdSigners as $s)
                        <option value="{{ $s->id }}">{{ $s->title }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                        Batal
                    </button>

                    <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm">
                        <span wire:loading.remove wire:target="updateMeeting" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Simpan Perubahan
                        </span>
                        <span wire:loading wire:target="updateMeeting" class="flex items-center gap-2">
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

    <!-- QR Code Presensi Modal (Global for Meeting Workspace) -->
    <x-modal name="qr-presensi-modal" maxWidth="md">
        <div class="p-6 sm:p-8 text-center">
            <div class="flex justify-between items-center pb-4 border-b border-slate-100 mb-6">
                <h2 class="text-xl font-extrabold text-slate-900">
                    QR Code Presensi
                </h2>
                <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if($meeting->status === 'ongoing')
            <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm mb-6 inline-block">
                {!! QrCode::size(240)->generate(route('meetings.check-in', $meeting->id)) !!}
            </div>

            <p class="text-sm font-bold text-slate-900 mb-4">Scan QR atau bagikan link berikut:</p>

            <div class="space-y-3 text-left" x-data="{ copied: false, copyLink() { navigator.clipboard.writeText('{{ route('meetings.check-in', $meeting->id) }}'); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
                <div class="relative flex items-center">
                    <input type="text" readonly value="{{ route('meetings.check-in', $meeting->id) }}" class="w-full text-xs font-mono font-medium text-slate-600 bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-12 py-3 text-left select-all focus:ring-primary-500 focus:border-primary-500 shadow-inner transition-colors">
                    <button type="button" @click="copyLink" :title="copied ? 'Tersalin!' : 'Salin Link'" class="absolute right-1.5 p-2 rounded-xl text-slate-400 hover:text-primary-600 hover:bg-white hover:shadow-sm transition-all">
                        <template x-if="!copied">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </template>
                        <template x-if="copied">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </template>
                    </button>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="copyLink" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-sm rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                        <span x-text="copied ? 'Tersalin!' : 'Salin Link'"></span>
                    </button>
                    <a href="{{ route('meetings.check-in', $meeting->id) }}" target="_blank" class="flex-1 inline-flex items-center justify-center py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-sm font-bold rounded-xl shadow-sm transition-all">
                        Buka Link &rarr;
                    </a>
                </div>
            </div>
            @elseif($meeting->status === 'scheduled')
            <div class="p-8 bg-amber-50 border border-amber-200 rounded-2xl text-center">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="font-extrabold text-amber-900 text-lg">Presensi Belum Dibuka</p>
                <p class="text-sm font-medium text-amber-800 mt-2">Silakan mulai rapat untuk membuka sesi presensi.</p>
            </div>
            @else
            <div class="p-8 bg-slate-50 border border-slate-200 rounded-2xl text-center">
                <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="font-extrabold text-slate-800 text-lg">Presensi Telah Ditutup</p>
                <p class="text-sm font-medium text-slate-500 mt-2">Sesi presensi untuk rapat ini telah ditutup.</p>
            </div>
            @endif
        </div>
    </x-modal>
</div>