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
        <div class="mb-4">
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Kolom Kiri: QR Code -->
        <div class="md:col-span-1 bg-gray-50 p-6 rounded-xl border border-gray-200 flex flex-col items-center text-center">
            <h3 class="text-lg font-bold text-gray-900 mb-2">QR Code Presensi</h3>

            @if($meeting->status === 'ongoing')
                <p class="text-sm text-gray-500 mb-6">Scan QR di bawah ini atau buka link untuk melakukan presensi kehadiran.</p>
                
                <div class="bg-white p-4 rounded-xl shadow-sm mb-4 inline-block border border-gray-200">
                    {!! QrCode::size(200)->generate(route('meetings.check-in', $meeting->id)) !!}
                </div>

                <div class="mt-2 w-full space-y-2">
                    <input type="text" readonly value="{{ route('meetings.check-in', $meeting->id) }}" class="w-full text-xs text-gray-600 bg-white border border-gray-300 rounded-lg px-2.5 py-1.5 cursor-text text-center select-all focus:ring-primary-500 focus:border-primary-500">
                    <a href="{{ route('meetings.check-in', $meeting->id) }}" target="_blank" class="inline-flex items-center justify-center w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-xs font-semibold rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        Buka Halaman Presensi
                    </a>
                </div>
            @elseif($meeting->status === 'scheduled')
                <div class="my-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-center">
                    <svg class="w-12 h-12 mx-auto text-amber-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <h4 class="font-semibold text-amber-800 text-sm mb-1">Presensi Belum Dibuka</h4>
                    <p class="text-xs text-amber-700">QR Code dan link presensi hanya dapat dibagikan saat rapat <strong>Sedang Berlangsung (Ongoing)</strong>.</p>
                </div>
                <p class="text-xs text-gray-500">Klik tombol <strong>"Mulai Rapat"</strong> di kanan atas untuk membuka sesi presensi.</p>
            @else
                <div class="my-6 p-4 bg-gray-100 border border-gray-300 rounded-xl text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <h4 class="font-semibold text-gray-700 text-sm mb-1">Presensi Telah Ditutup</h4>
                    <p class="text-xs text-gray-500">Rapat ini sudah berstatus <strong>Selesai (Completed)</strong>.</p>
                </div>
            @endif
        </div>

        <!-- Kolom Kanan: Daftar Kehadiran -->
        <div class="md:col-span-2 space-y-6">
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Rekap Kehadiran Aktual</h3>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>Total Hadir: <strong class="text-gray-900">{{ $meeting->attendances->count() }}</strong></span>
                        <a href="{{ route('meetings.export.attendance', $meeting->id) }}" class="inline-flex items-center px-3.5 py-1.5 bg-white border border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-95 transition-all ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Cetak PDF
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal border-b border-gray-200">
                                <th class="py-2.5 px-4 text-left">Nama Peserta</th>
                                <th class="py-2.5 px-4 text-left">Instansi / OPD</th>
                                <th class="py-2.5 px-4 text-left">Jabatan</th>
                                <th class="py-2.5 px-4 text-left">Waktu Hadir</th>
                                <th class="py-2.5 px-4 text-center">Tanda Tangan</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            @forelse($meeting->attendances as $attendance)
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4 text-left">
                                        @if($attendance->user)
                                            <div class="font-medium text-gray-900">{{ $attendance->user->name }}</div>
                                            @if($attendance->user->nip)
                                                <div class="text-xs text-gray-400">NIP. {{ $attendance->user->nip }}</div>
                                            @endif
                                        @else
                                            <div class="font-medium text-gray-900">{{ $attendance->guest_name }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-left">
                                        <span class="text-gray-900 text-xs font-medium">
                                            {{ $attendance->user ? ($attendance->user->unit_name ?? 'Pemkab Sinjai') : $attendance->guest_agency }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-left text-xs text-gray-600">
                                        {{ $attendance->user ? ($attendance->user->jabatan ?? '-') : ($attendance->guest_position ?: '-') }}
                                    </td>
                                    <td class="py-3 px-4 text-left text-xs whitespace-nowrap">
                                        <div class="font-medium text-gray-800">{{ $attendance->check_in->format('H:i') }} WITA</div>
                                        <div class="text-[11px] text-gray-400">{{ $attendance->check_in->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($attendance->signature)
                                            <img src="{{ $attendance->signature }}" alt="Tanda Tangan" class="h-10 mx-auto bg-white border border-gray-200 rounded p-0.5 shadow-sm">
                                        @else
                                            <span class="text-gray-400 italic text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 px-4 text-center text-gray-500">
                                        <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        <p class="font-medium text-gray-700 text-sm">Belum ada yang mencatat kehadiran.</p>
                                        <p class="text-xs text-gray-400 mt-1">Data presensi akan otomatis muncul saat peserta melakukan presensi.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-meeting-layout>


