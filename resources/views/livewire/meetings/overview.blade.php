<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public Meeting $meeting;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="overview">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Agenda</h3>
            <div class="prose max-w-none text-gray-600 bg-gray-50 p-4 rounded-xl min-h-[150px]">
                {!! nl2br(e($meeting->agenda ?: 'Tidak ada deskripsi agenda.')) !!}
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Rapat</h3>
            <ul class="space-y-3">
                <li class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500">Penyelenggara / Dibuat Oleh</span>
                    <span class="text-base text-gray-900">{{ $meeting->creator->name ?? 'Admin' }} ({{ $meeting->creator->unit_name ?? 'Pemerintah Daerah' }})</span>
                </li>
                <li class="flex flex-col mt-4">
                    <span class="text-sm font-medium text-gray-500 mb-1">Status Rapat</span>
                    <div>
                        @if($meeting->status == 'scheduled')
                            <span class="inline-flex px-2.5 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold tracking-wide">DIJADWALKAN</span>
                        @elseif($meeting->status == 'ongoing')
                            <span class="inline-flex px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold tracking-wide animate-pulse">BERLANGSUNG</span>
                        @elseif($meeting->status == 'completed')
                            <span class="inline-flex px-2.5 py-1 bg-primary-100 text-primary-800 rounded-full text-xs font-semibold tracking-wide">SELESAI</span>
                        @endif
                    </div>
                </li>
            </ul>
        </div>
    </div>
</x-meeting-layout>


