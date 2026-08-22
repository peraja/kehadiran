<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\MeetingMinute;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public Meeting $meeting;
    public ?MeetingMinute $minutes = null;

    public $content = '';
    public $canEdit = false;
    public $lastSaved = null;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
        $this->minutes = $meeting->minutes;
        $this->canEdit = auth()->user()->hasRole(['admin', 'admin_opd']) || $meeting->created_by === auth()->id();

        if ($this->minutes) {
            $this->content = $this->minutes->content ?? '';
            $this->lastSaved = $this->minutes->updated_at ? $this->minutes->updated_at->format('H:i') . ' WITA' : null;
        }
    }

    public function saveMinutes()
    {
        if (!$this->canEdit) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $this->validate([
            'content' => 'nullable|string',
        ]);

        $this->minutes = $this->meeting->minutes()->updateOrCreate(
            ['meeting_id' => $this->meeting->id],
            [
                'content' => $this->content,
            ]
        );

        $this->lastSaved = now()->format('H:i') . ' WITA';
        session()->flash('message', 'Notulen berhasil disimpan.');
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="notulen">
    @if (session()->has('message'))
    <x-alert type="success" class="mb-5">
        {{ session('message') }}
    </x-alert>
    @endif

    <div class="space-y-6">
        <!-- Toolbar Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Notulen Rapat</h3>
            </div>

            @if($minutes && !empty($minutes->content))
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('meetings.export.minutes', $meeting->id) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Cetak PDF
                </a>
            </div>
            @endif
        </div>

        @if($canEdit)
        <!-- Editor Mode (Penyelenggara / Admin) -->
        <form wire:submit="saveMinutes" @keydown.window.prevent.ctrl.s="$wire.saveMinutes()" @keydown.window.prevent.cmd.s="$wire.saveMinutes()" class="space-y-4">
            <div class="relative">
                <textarea wire:model="content" id="content" rows="18"
                    class="block w-full p-4 sm:p-5 rounded-2xl border border-slate-200 font-normal leading-relaxed text-slate-800 text-sm shadow-xs transition-colors duration-150 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:outline-none resize-y min-h-[360px] bg-white"
                    placeholder="Tuliskan notulen rapat di sini..."></textarea>
            </div>
            @error('content') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror

            <div class="flex justify-end pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveMinutes" class="inline-flex justify-center items-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg wire:loading.remove wire:target="saveMinutes" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    <svg wire:loading wire:target="saveMinutes" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Simpan Notulen</span>
                </button>
            </div>
        </form>
        @else
        <!-- Read-Only Mode (Pegawai / Non-Penyelenggara) -->
        @if(!empty($content))
        <div class="bg-slate-50/70 rounded-2xl border border-slate-200 p-6 sm:p-8">
            <div class="prose prose-slate max-w-none text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">
                {{ $content }}
            </div>
        </div>
        @else
        <div class="py-14 px-6 text-center flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
            <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mb-2.5">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <p class="text-sm font-extrabold text-slate-800">Notulen Belum Diisi</p>
        </div>
        @endif
        @endif
    </div>
</x-meeting-layout>