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
    @if (session()->has('message'))
    <x-alert type="success" class="mb-6">
        {{ session('message') }}
    </x-alert>
    @endif

    <div class="space-y-6">
        <!-- Informasi Rapat Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-2">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Informasi Rapat</h3>
                </div>
            </div>

            <div class="divide-y divide-slate-100 text-sm">
                <!-- Agenda -->
                <div class="flex flex-col sm:flex-row sm:items-start py-4 px-2 gap-2 sm:gap-6 hover:bg-slate-50/50 rounded-xl transition-colors">
                    <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0 pt-0.5">
                        Agenda
                    </div>
                    <div class="sm:w-3/4 text-slate-900 font-extrabold text-base leading-relaxed break-words">{{ trim($meeting->title) }}</div>
                </div>

                <!-- Tanggal & Waktu -->
                <div class="flex flex-col sm:flex-row sm:items-center py-4 px-2 gap-2 sm:gap-6 hover:bg-slate-50/50 rounded-xl transition-colors">
                    <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                        Tanggal & Waktu
                    </div>
                    <div class="sm:w-3/4 flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-slate-900">{{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}</span>
                        <span class="text-slate-300">•</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 font-mono">{{ $meeting->start_time ? $meeting->start_time->format('H:i') : '' }} - {{ $meeting->end_time ? $meeting->end_time->format('H:i') : 'Selesai' }} WITA</span>
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="flex flex-col sm:flex-row sm:items-center py-4 px-2 gap-2 sm:gap-6 hover:bg-slate-50/50 rounded-xl transition-colors">
                    <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                        Lokasi
                    </div>
                    <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">{{ trim($meeting->location ?? '-') }}</div>
                </div>

                <!-- Penandatangan Dokumen -->
                @if($meeting->signer_name || $meeting->signer_title)
                <div class="flex flex-col sm:flex-row sm:items-center py-4 px-2 gap-2 sm:gap-6 hover:bg-slate-50/50 rounded-xl transition-colors">
                    <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                        Penandatangan
                    </div>
                    <div class="sm:w-3/4">
                        <div class="text-slate-900 font-bold text-sm">{{ trim($meeting->signer_name ?: '-') }}</div>
                        <div class="text-slate-500 font-medium text-xs mt-0.5">{{ trim($meeting->signer_title ?: 'Kepala OPD') }}</div>
                    </div>
                </div>
                @endif

                <!-- Dibuat Oleh -->
                <div class="flex flex-col sm:flex-row sm:items-center py-4 px-2 gap-2 sm:gap-6 hover:bg-slate-50/50 rounded-xl transition-colors">
                    <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                        Dibuat Oleh
                    </div>
                    <div class="sm:w-3/4">
                        <div class="text-slate-900 font-bold text-sm">{{ trim($meeting->creator->name ?? 'Administrator') }}</div>
                        <div class="text-slate-500 font-medium text-xs mt-0.5">{{ trim($meeting->creator->unit_name ?? 'Pemerintah Kabupaten Sinjai') }}</div>
                    </div>
                </div>

                <!-- Waktu Dibuat -->
                <div class="flex flex-col sm:flex-row sm:items-center py-4 px-2 gap-2 sm:gap-6 hover:bg-slate-50/50 rounded-xl transition-colors">
                    <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                        Waktu Dibuat
                    </div>
                    <div class="sm:w-3/4 text-slate-700 font-semibold text-sm">{{ $meeting->created_at ? $meeting->created_at->translatedFormat('d F Y, H:i') . ' WITA' : '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-meeting-layout>