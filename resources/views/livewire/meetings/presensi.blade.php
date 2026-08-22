<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Livewire\Attributes\Layout;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

new #[Layout('layouts.app')] class extends Component {
    public Meeting $meeting;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="presensi">
    @if (session()->has('message'))
    <x-alert type="success" class="mb-5">
        {{ session('message') }}
    </x-alert>
    @endif

    <div class="space-y-6">
        <!-- Action Header Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Daftar Hadir</h3>
                    <p class="text-sm text-slate-500 font-medium mt-0.5">Total Hadir: <span class="font-extrabold text-primary-600">{{ $meeting->attendances->count() }} orang</span></p>
                </div>
            </div>

            @if($meeting->attendances->count() > 0)
            <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                <a href="{{ route('meetings.export.attendance', $meeting->id) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Cetak PDF
                </a>
            </div>
            @endif
        </div>

        <!-- Full-Width Attendee Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500">
                    <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="py-3.5 px-4 text-center w-12">#</th>
                        <th class="py-3.5 px-6 text-left">Nama Peserta</th>
                        <th class="py-3.5 px-6 text-left">OPD / Instansi</th>
                        <th class="py-3.5 px-6 text-left">Jabatan</th>
                        <th class="py-3.5 px-6 text-left">Waktu Presensi</th>
                        <th class="py-3.5 px-6 text-center">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm bg-white">
                    @forelse($meeting->attendances as $attendance)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="py-4 px-4 text-center text-xs font-bold text-slate-400">
                            {{ $loop->iteration }}
                        </td>
                        <td class="py-4 px-6 text-left">
                            @if($attendance->user)
                            <div>
                                <div class="font-extrabold text-slate-900 group-hover:text-primary-600 transition-colors block leading-tight">{{ $attendance->user->name }}</div>
                                @if($attendance->user->nip)
                                <div class="mt-0.5 text-xs text-slate-500 font-mono font-medium">NIP. {{ $attendance->user->nip }}</div>
                                @endif
                            </div>
                            @else
                            <div>
                                <div class="font-extrabold text-slate-900 leading-tight">{{ $attendance->guest_name }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[10px] font-bold uppercase tracking-wider mt-1">
                                    Eksternal
                                </span>
                            </div>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-left">
                            <div class="text-slate-800 font-semibold text-sm leading-tight">
                                {{ $attendance->user ? ($attendance->user->unit_name ?? 'Pemkab Sinjai') : ($attendance->guest_agency ?: '-') }}
                            </div>
                        </td>
                        <td class="py-4 px-6 text-left">
                            <div class="text-slate-600 font-medium text-xs">
                                {{ $attendance->user ? ($attendance->user->jabatan ?? '-') : ($attendance->guest_position ?: '-') }}
                            </div>
                        </td>
                        <td class="py-4 px-6 text-left whitespace-nowrap">
                            <div class="font-mono font-bold text-slate-800 text-sm">{{ $attendance->check_in ? $attendance->check_in->format('H:i') . ' WITA' : '-' }}</div>
                        </td>
                        <td class="py-4 px-6 text-center">
                            @if($attendance->signature)
                            <img src="{{ $attendance->signature }}" alt="Tanda Tangan" class="h-9 max-w-[120px] object-contain mx-auto bg-white border border-slate-200 rounded-xl p-1 shadow-xs">
                            @else
                            <span class="inline-flex px-2.5 py-1 bg-slate-100 text-slate-400 rounded-full text-[10px] font-bold tracking-wider">TIDAK ADA</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 px-6 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3 text-slate-400">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900">Belum Ada Data Presensi</h3>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-meeting-layout>