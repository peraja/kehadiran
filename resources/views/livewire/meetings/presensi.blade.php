<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Services\BsreEsignService;
use Livewire\Attributes\Layout;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

new #[Layout('layouts.app')] class extends Component {
    public Meeting $meeting;

    public bool $showSignModal = false;
    public string $passphrase = '';
    public string $errorMessage = '';

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function openSignModal()
    {
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->showSignModal = true;
        $this->dispatch('open-modal', 'sign-attendance-modal');
    }

    public function closeSignModal()
    {
        $this->showSignModal = false;
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->dispatch('close-modal', 'sign-attendance-modal');
    }

    public function executeSign(BsreEsignService $esignService)
    {
        $this->errorMessage = '';
        $this->validate([
            'passphrase' => 'required|string',
        ], [
            'passphrase.required' => 'Passphrase BSrE wajib diisi.',
        ]);

        $result = $esignService->signDocument($this->meeting, auth()->user(), 'attendance', $this->passphrase);

        if ($result['success']) {
            $this->meeting->refresh();
            $this->closeSignModal();
            session()->flash('message', $result['message']);
        } else {
            $this->errorMessage = $result['message'];
        }
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
            <div class="flex items-center gap-2.5">
                <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Daftar Hadir</h3>
                <span class="px-2.5 py-0.5 bg-primary-50 text-primary-700 border border-primary-200/60 rounded-full text-xs font-bold">{{ $meeting->attendances->count() }} Orang</span>
            </div>

            @if($meeting->attendances->count() > 0)
            <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                @if($meeting->attendance_signed_at)
                <span class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-100/80 text-emerald-800 rounded-xl text-xs font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    <span>Sudah TTE</span>
                </span>
                @elseif(auth()->user()->hasRole('pimpinan') && $meeting->status === 'completed')
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'sign-attendance-modal'); $wire.openSignModal()" class="inline-flex justify-center items-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>TTE Presensi</span>
                </button>
                @endif

                @if($meeting->status === 'completed')
                <a href="{{ route('meetings.export.attendance', $meeting->id) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Cetak PDF</span>
                </a>
                @else
                <button type="button" disabled class="inline-flex justify-center items-center px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-xl font-bold text-sm cursor-not-allowed gap-2" title="Ekspor PDF hanya dapat dilakukan setelah status rapat diselesaikan">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Cetak PDF</span>
                </button>
                @endif
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

    <!-- Modal Konfirmasi TTE BSrE -->
    <x-modal name="sign-attendance-modal" maxWidth="lg" :show="$showSignModal">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-5 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Tanda Tangan Elektronik
                </h2>
                <button type="button" x-on:click="$dispatch('close')" wire:click="closeSignModal" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if($errorMessage)
            <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="flex-1 leading-relaxed">{{ $errorMessage }}</span>
            </div>
            @endif

            <form wire:submit="executeSign" class="space-y-5">
                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-2.5 text-sm">
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Dokumen</span>
                        <span class="font-extrabold text-slate-900 text-right">Daftar Hadir</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Penandatangan</span>
                        <span class="font-bold text-slate-900 text-right">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">NIK</span>
                        <span class="font-mono font-bold text-slate-800 text-right">{{ auth()->user()->nik ?: '-' }}</span>
                    </div>
                </div>

                <div>
                    <label for="passphrase_attendance" class="block text-sm font-bold text-slate-700 mb-1">Passphrase BSrE</label>
                    <input wire:model="passphrase" id="passphrase_attendance" type="password" class="w-full text-sm py-2.5 px-3.5 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Masukkan passphrase" required autofocus />
                    @error('passphrase') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" wire:click="closeSignModal" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-sm rounded-xl transition-all shadow-sm">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="executeSign" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-sm font-bold rounded-xl shadow-sm transition-all gap-2">
                        <svg wire:loading.remove wire:target="executeSign" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <svg wire:loading wire:target="executeSign" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Tandatangani</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</x-meeting-layout>