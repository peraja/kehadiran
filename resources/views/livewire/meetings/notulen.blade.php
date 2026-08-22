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
            return;
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
        session()->flash('message', 'Notulen rapat berhasil disimpan.');
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="notulen">
    @if (session()->has('message'))
    <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
        <div class="shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-emerald-800">Berhasil</h3>
            <p class="text-sm text-emerald-700 mt-0.5">{{ session('message') }}</p>
        </div>
    </div>
    @endif

    <div class="space-y-6">
        <!-- Toolbar Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Notulen Rapat</h3>
                </div>
            </div>

            @if($minutes && !empty($minutes->content))
            <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                <a href="{{ route('meetings.export.minutes', $meeting->id) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Cetak PDF
                </a>
            </div>
            @endif
        </div>

        <form wire:submit="saveMinutes" @keydown.window.prevent.ctrl.s="$wire.saveMinutes()" @keydown.window.prevent.cmd.s="$wire.saveMinutes()" class="space-y-4">
            <div class="relative">
                <textarea wire:model="content" id="content" rows="20"
                    class="block w-full p-4 sm:p-5 rounded-2xl border border-slate-200 font-normal leading-relaxed text-slate-800 text-sm shadow-xs transition-colors duration-150 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:outline-none resize-y min-h-[380px] {{ !$canEdit ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'bg-white' }}"
                    placeholder="Tuliskan poin pembahasan dan hasil keputusan rapat di sini:

1. Pembahasan Utama:
- 

2. Kesepakatan / Keputusan:
- 

3. Tindak Lanjut:
- "
                    @disabled(!$canEdit) @readonly(!$canEdit)></textarea>
            </div>
            @error('content') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                @if($canEdit)
                <div class="flex items-center gap-2 text-slate-400">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs font-medium text-slate-500">Gunakan <kbd class="px-1.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 font-mono text-[10px] font-bold text-slate-700 shadow-2xs">Ctrl+S</kbd> / <kbd class="px-1.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 font-mono text-[10px] font-bold text-slate-700 shadow-2xs">Cmd+S</kbd> untuk menyimpan cepat.</span>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="saveMinutes" class="inline-flex justify-center items-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <span wire:loading.remove wire:target="saveMinutes" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Simpan Notulen
                    </span>
                    <span wire:loading wire:target="saveMinutes" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                </button>
                @else
                <div class="w-full p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-bold text-amber-800 text-sm">Mode Baca Saja</p>
                        <p class="text-xs font-medium text-amber-700 mt-0.5">Hanya penyelenggara rapat atau administrator yang dapat menyunting notulen ini.</p>
                    </div>
                </div>
                @endif
            </div>
        </form>
    </div>
</x-meeting-layout>