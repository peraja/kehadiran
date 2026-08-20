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

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
        $this->minutes = $meeting->minutes;
        $this->canEdit = auth()->user()->hasRole(['admin', 'admin_opd']) || $meeting->created_by === auth()->id();

        if ($this->minutes) {
            $this->content = $this->minutes->content ?? '';
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
        
        session()->flash('message', 'Notulen rapat berhasil disimpan.');
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="notulen">
    
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-medium text-gray-900">Notulen Rapat</h3>
        <a href="{{ route('meetings.export.minutes', $meeting->id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-95 transition-all ease-in-out duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Cetak PDF
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-6">
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        </div>
    @endif

    <form wire:submit="saveMinutes" class="space-y-6">
        <div class="mb-6">
            <x-input-label for="content" value="Isi Notulen Rapat" class="text-base font-semibold" />
            <p class="text-xs text-gray-500 mb-2">Tuliskan seluruh hasil rapat secara lengkap dan bebas di sini (ringkasan, poin pembahasan, dan keputusan).</p>
            <x-textarea-input wire:model="content" id="content" rows="16" 
                class="mt-1 block w-full {{ !$canEdit ? 'bg-gray-50' : '' }}"
                placeholder="Contoh:

[Ringkasan Rapat]
Rapat dibuka pukul 09.00 WITA...

[Pembahasan]
- Evaluasi kinerja...
- Usulan perbaikan...

[Keputusan]
1. Draft disetujui...
2. Target penyelesaian Jumat depan..."
                :disabled="!$canEdit" :readonly="!$canEdit"></x-textarea-input>
            <x-input-error :messages="$errors->get('content')" class="mt-2" />
        </div>

        @if($canEdit)
            <div class="flex justify-end pt-4">
                <x-primary-button type="submit">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Simpan Notulen
                </x-primary-button>
            </div>
        @endif
    </form>

</x-meeting-layout>


