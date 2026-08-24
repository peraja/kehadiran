<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Services\BsreEsignService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] class extends Component {
    public Meeting $meeting;

    // Single Document TTE BSrE State
    public bool $showSignModal = false;
    public string $signType = 'minutes'; // 'minutes' | 'attendance' | 'photos'
    public string $passphrase = '';
    public string $errorMessage = '';

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;

        $user = auth()->user();
        if ($user->hasActiveRole('pimpinan') && !$meeting->isSigner($user)) {
            abort(403, 'Anda hanya dapat mengakses rapat yang penandatangannya adalah Anda sendiri.');
        }

        if ($user->hasActiveRole('pegawai') && $meeting->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengakses rapat yang Anda buat sendiri.');
        }
    }

    #[On('meeting-updated')]
    public function refreshMeeting(): void
    {
        $this->meeting->refresh();
    }

    public function openSingleSignModal(string $type): void
    {
        $this->signType = $type;
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->showSignModal = true;
        $this->dispatch('open-modal', 'sign-single-modal');
    }

    public function closeSignModal(): void
    {
        $this->showSignModal = false;
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->dispatch('close-modal', 'sign-single-modal');
    }

    public function executeSign(BsreEsignService $esignService): void
    {
        if (!auth()->user()->hasActiveRole('pimpinan') || !$this->meeting->isSigner(auth()->user())) {
            abort(403, 'Anda bukan pejabat penandatangan yang ditunjuk untuk rapat ini.');
        }

        $this->errorMessage = '';
        $this->validate([
            'passphrase' => 'required|string',
        ], [
            'passphrase.required' => 'Passphrase BSrE wajib diisi.',
        ]);

        $result = $esignService->signDocument($this->meeting, auth()->user(), $this->signType, $this->passphrase);

        if ($result['success']) {
            $this->meeting->refresh();
            $this->closeSignModal();
            session()->flash('message', $result['message']);
            $this->dispatch('meeting-updated');
        } else {
            $this->errorMessage = $result['message'];
            $this->passphrase = '';
        }
    }

    public function with(BsreEsignService $esignService): array
    {
        return [
            'tteStatus' => $esignService->checkUserStatus(auth()->user()?->nik),
        ];
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="overview">
    @if (session()->has('message'))
    <x-alert type="success" class="mb-5">
        {{ session('message') }}
    </x-alert>
    @endif

    <div class="space-y-8">
        @if(auth()->user()->hasActiveRole('pimpinan'))
        <!-- Section: Dokumen Rapat (Khusus Role Pimpinan) -->
        <div class="space-y-4">
            <div class="flex items-center gap-2.5 pb-2">
                <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Dokumen Rapat</h3>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="divide-y divide-slate-100 text-sm">
                    <!-- 1. Presensi Rapat -->
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                            Presensi Rapat
                        </div>
                        <div class="sm:w-3/4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                @if($meeting->attendance_signed_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Sudah TTE
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Belum TTE
                                </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if($meeting->status === 'completed')
                                <a href="{{ route('meetings.export.attendance', $meeting->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 active:scale-95 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-2xs">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Lihat PDF</span>
                                </a>
                                @endif

                                @if(!$meeting->attendance_signed_at && $meeting->status === 'completed')
                                <button type="button" wire:click="openSingleSignModal('attendance')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span>TTE Presensi</span>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 2. Dokumentasi Rapat -->
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                            Dokumentasi Rapat
                        </div>
                        <div class="sm:w-3/4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                @if($meeting->photos_signed_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Sudah TTE
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Belum TTE
                                </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if($meeting->status === 'completed' && $meeting->photos()->count() > 0)
                                <a href="{{ route('meetings.export.photos', $meeting->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 active:scale-95 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-2xs">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Lihat PDF</span>
                                </a>
                                @endif

                                @if(!$meeting->photos_signed_at && $meeting->status === 'completed' && $meeting->photos()->count() > 0)
                                <button type="button" wire:click="openSingleSignModal('photos')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span>TTE Dokumentasi</span>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 3. Notulen Rapat -->
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                            Notulen Rapat
                        </div>
                        <div class="sm:w-3/4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                @if($meeting->minutes_signed_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Sudah TTE
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Belum TTE
                                </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if($meeting->status === 'completed' && $meeting->minutes && !empty(trim($meeting->minutes->content)))
                                <a href="{{ route('meetings.export.minutes', $meeting->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 active:scale-95 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-2xs">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Lihat PDF</span>
                                </a>
                                @endif

                                @if(!$meeting->minutes_signed_at && $meeting->status === 'completed' && $meeting->minutes && !empty(trim($meeting->minutes->content)))
                                <button type="button" wire:click="openSingleSignModal('minutes')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-2xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span>TTE Notulen</span>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Details Table Container -->
        <div class="space-y-4">
            <div class="flex items-center gap-2.5 pb-2">
                <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Informasi Rapat</h3>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="divide-y divide-slate-100 text-sm">
                    <!-- Agenda -->
                    <div class="flex flex-col sm:flex-row sm:items-start py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0 pt-0.5">
                            Agenda
                        </div>
                        <div class="sm:w-3/4 text-slate-900 font-extrabold text-base leading-relaxed break-words">{{ trim($meeting->title) }}</div>
                    </div>

                    <!-- Tanggal & Waktu -->
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
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
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                            Lokasi
                        </div>
                        <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">{{ trim($meeting->location ?? '-') }}</div>
                    </div>

                    <!-- Penandatangan Dokumen -->
                    @if($meeting->signer_name || $meeting->signer_title)
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
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
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                            Dibuat Oleh
                        </div>
                        <div class="sm:w-3/4">
                            <div class="text-slate-900 font-bold text-sm">{{ trim($meeting->creator->name ?? 'Administrator') }}</div>
                            <div class="text-slate-500 font-medium text-xs mt-0.5">{{ trim($meeting->creator->unit_name ?? 'Pemerintah Kabupaten Sinjai') }}</div>
                        </div>
                    </div>

                    <!-- Waktu Dibuat -->
                    <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                        <div class="sm:w-1/4 text-slate-500 font-bold text-sm shrink-0">
                            Waktu Dibuat
                        </div>
                        <div class="sm:w-3/4 text-slate-700 font-semibold text-sm">{{ $meeting->created_at ? $meeting->created_at->translatedFormat('d F Y, H:i') . ' WITA' : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->hasActiveRole('pimpinan'))
    <!-- Modal Konfirmasi TTE Satuan BSrE -->
    <x-modal name="sign-single-modal" maxWidth="lg" :show="$showSignModal">
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

            @php
                $docLabels = [
                    'minutes' => 'Notulen Rapat',
                    'attendance' => 'Presensi Rapat',
                    'photos' => 'Dokumentasi Rapat',
                ];
                $activeDocLabel = $docLabels[$signType] ?? 'Dokumen Rapat';
            @endphp

            <form wire:submit="executeSign" class="space-y-5">
                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-2.5 text-sm">
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Dokumen</span>
                        <span class="font-extrabold text-slate-900 text-right">{{ $activeDocLabel }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Penandatangan</span>
                        <span class="font-bold text-slate-900 text-right">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">NIK</span>
                        <span class="font-mono font-bold text-slate-800 text-right">{{ auth()->user()->nik ?: '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm pt-2.5 border-t border-slate-200/70">
                        <span class="text-slate-500 font-medium">Status TTE</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $tteStatus['badge_class'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $tteStatus['dot_class'] ?? ($tteStatus['can_sign'] ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500') }}"></span>
                            {{ $tteStatus['label'] }}
                        </span>
                    </div>
                </div>

                @if(!$tteStatus['can_sign'])
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium leading-relaxed">{{ $tteStatus['description'] }}</span>
                </div>
                @endif

                <div x-data="{ showPassphrase: false }">
                    <label for="passphrase_single" class="block text-sm font-bold text-slate-700 mb-1">Passphrase BSrE</label>
                    <div class="relative">
                        <input wire:model="passphrase"
                               id="passphrase_single"
                               :type="showPassphrase ? 'text' : 'password'"
                               class="w-full text-sm py-2.5 pl-3.5 pr-10 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors"
                               placeholder="Masukkan passphrase"
                               required
                               autofocus />
                        <button type="button"
                                @click="showPassphrase = !showPassphrase"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                :title="showPassphrase ? 'Sembunyikan Passphrase' : 'Lihat Passphrase'">
                            <svg x-show="showPassphrase" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                            <svg x-show="!showPassphrase" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('passphrase') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" wire:click="closeSignModal" class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 font-bold text-sm rounded-xl transition-all shadow-sm">
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
    @endif
</x-meeting-layout>