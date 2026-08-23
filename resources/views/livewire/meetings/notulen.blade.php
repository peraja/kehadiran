<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\MeetingMinute;
use App\Services\BsreEsignService;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public Meeting $meeting;
    public ?MeetingMinute $minutes = null;

    public $content = '';
    public $canEdit = false;
    public $lastSaved = null;

    public bool $showSignModal = false;
    public string $passphrase = '';
    public string $errorMessage = '';

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
        $this->minutes = $meeting->minutes;
        
        $user = auth()->user();
        if ($user->hasRole('pimpinan') && !$meeting->isSigner($user)) {
            abort(403, 'Anda hanya dapat mengakses notulen rapat yang penandatangannya adalah Anda sendiri.');
        }

        if ($user->hasRole('pegawai') && $meeting->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengakses notulen rapat yang Anda buat sendiri.');
        }

        if ($user->hasRole('admin')) {
            $this->canEdit = true;
        } elseif ($user->hasRole('admin_opd')) {
            $this->canEdit = $user->unit_name === $meeting->opd?->name || $user->unit_name === $meeting->creator?->unit_name;
        } else {
            // Pegawai biasa: hanya bisa mengelola rapat yang dibuat sendiri
            $this->canEdit = $meeting->created_by === $user->id;
        }

        if ($this->minutes) {
            $this->content = $this->minutes->content ?? '';
            $this->lastSaved = $this->minutes->updated_at ? $this->minutes->updated_at->format('H:i') . ' WITA' : null;
        }
    }

    public function openSignModal()
    {
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->showSignModal = true;
        $this->dispatch('open-modal', 'sign-minutes-modal');
    }

    public function closeSignModal()
    {
        $this->showSignModal = false;
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->dispatch('close-modal', 'sign-minutes-modal');
    }

    public function executeSign(BsreEsignService $esignService)
    {
        if (!auth()->user()->hasRole('pimpinan') || !$this->meeting->isSigner(auth()->user())) {
            abort(403, 'Anda bukan pejabat penandatangan yang ditunjuk untuk rapat ini.');
        }

        $this->errorMessage = '';
        $this->validate([
            'passphrase' => 'required|string',
        ], [
            'passphrase.required' => 'Passphrase BSrE wajib diisi.',
        ]);

        $result = $esignService->signDocument($this->meeting, auth()->user(), 'minutes', $this->passphrase);

        if ($result['success']) {
            $this->meeting->refresh();
            $this->closeSignModal();
            session()->flash('message', $result['message']);
        } else {
            $this->errorMessage = $result['message'];
        }
    }

    public function unlockForRevision(): void
    {
        if (!$this->canEdit) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($this->meeting->minutes_pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->meeting->minutes_pdf_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->meeting->minutes_pdf_path);
        }

        $this->meeting->update([
            'minutes_signed_at' => null,
            'minutes_signed_by' => null,
            'minutes_pdf_path' => null,
        ]);

        $this->meeting->refresh();
        session()->flash('message', 'Kunci notulen dibuka untuk revisi. Status TTE telah direset, silakan lakukan perbaikan lalu minta Pimpinan untuk menandatangani ulang.');
    }

    public function saveMinutes()
    {
        if (!$this->canEdit || $this->meeting->minutes_signed_at) {
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
            <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                @if($meeting->minutes_signed_at)
                <span class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-100/80 text-emerald-800 rounded-xl text-xs font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    <span>Sudah TTE</span>
                </span>
                @elseif(auth()->user()->hasRole('pimpinan') && $meeting->status === 'completed')
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'sign-minutes-modal'); $wire.openSignModal()" class="inline-flex justify-center items-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>TTE Notulen</span>
                </button>
                @endif

                @if($meeting->status === 'completed')
                <a href="{{ route('meetings.export.minutes', $meeting->id) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
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

        @if($meeting->minutes_signed_at)
        <!-- Locked Banner & Read-Only Content (TTE Signed) -->
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs sm:text-sm font-medium">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>Notulen telah disahkan secara digital (TTE BSrE) dan dikunci.</span>
            </div>
            @if($canEdit)
            <button wire:click="unlockForRevision"
                    wire:confirm="Membuka kunci notulen akan membatalkan status TTE saat ini dan mewajibkan Pimpinan melakukan TTE ulang setelah perbaikan selesai. Lanjutkan revisi?"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm shrink-0 self-start sm:self-auto">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                <span>Buka Kunci untuk Revisi</span>
            </button>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
            <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">{{ trim($content) }}</div>
        </div>

        @elseif($canEdit)
        <!-- Editor Mode (Penyelenggara / Admin - Belum TTE) -->
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
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-5 sm:p-6">
            <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">{{ trim($content) }}</div>
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

    <!-- Modal Konfirmasi TTE BSrE -->
    <x-modal name="sign-minutes-modal" maxWidth="lg" :show="$showSignModal">
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
                        <span class="font-extrabold text-slate-900 text-right">Notulen Rapat</span>
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

                <div x-data="{ showPassphrase: false }">
                    <label for="passphrase_minutes" class="block text-sm font-bold text-slate-700 mb-1">Passphrase BSrE</label>
                    <div class="relative">
                        <input wire:model="passphrase"
                               id="passphrase_minutes"
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