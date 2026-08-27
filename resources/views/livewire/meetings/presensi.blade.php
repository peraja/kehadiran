<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Services\BsreEsignService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

new #[Layout('layouts.app')] class extends Component {
    public Meeting $meeting;
    public bool $canEdit = false;

    public bool $showSignModal = false;
    public string $passphrase = '';
    public string $errorMessage = '';
    public ?string $successMessage = null;
    public int $alertKey = 0;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;

        $user = auth()->user();
        if ($user->hasActiveRole('pimpinan')) {
            if (!$meeting->isSigner($user)) {
                abort(403, 'Anda hanya dapat mengakses presensi rapat yang penandatangannya adalah Anda sendiri.');
            }
            return $this->redirect(route('meetings.overview', $meeting->id), navigate: true);
        }

        if ($user->hasActiveRole('pegawai') && $meeting->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengakses presensi rapat yang Anda buat sendiri.');
        }

        if ($user->hasActiveRole('pimpinan')) {
            $this->canEdit = false;
        } elseif ($user->hasActiveRole('admin')) {
            $this->canEdit = true;
        } elseif ($user->hasActiveRole('admin_opd')) {
            $this->canEdit = $user->unit_name === $meeting->opd?->name || $user->unit_name === $meeting->creator?->unit_name;
        } else {
            // Pegawai biasa: hanya bisa mengelola rapat yang dibuat sendiri
            $this->canEdit = $meeting->created_by === $user->id;
        }
    }

    #[On('meeting-updated')]
    public function refreshMeeting(): void
    {
        $this->meeting->refresh();
    }

    public function openSignModal()
    {
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->resetErrorBag();
        $this->showSignModal = true;
        $this->dispatch('open-modal', 'sign-attendance-modal');
    }

    public function closeSignModal()
    {
        $this->showSignModal = false;
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->resetErrorBag();
        $this->dispatch('close-modal', 'sign-attendance-modal');
    }

    public function executeSign(BsreEsignService $esignService)
    {
        if (!auth()->user()->hasActiveRole('pimpinan') || !$this->meeting->isSigner(auth()->user())) {
            abort(403, 'Anda bukan pejabat penandatangan yang ditunjuk untuk rapat ini.');
        }

        if (empty(auth()->user()->nik)) {
            $this->errorMessage = 'Hubungi Admin OPD untuk mendaftarkan NIK.';
            return;
        }

        $this->errorMessage = '';
        $this->resetErrorBag();
        $this->validate([
            'passphrase' => 'required|string',
        ], [
            'passphrase.required' => 'Passphrase wajib diisi.',
        ]);

        try {
            $result = $esignService->signDocument($this->meeting, auth()->user(), 'attendance', $this->passphrase);

            if ($result['success']) {
                $this->meeting->refresh();
                $this->closeSignModal();
                $this->alertKey = hrtime(true);
                $this->successMessage = $result['message'];
                session()->flash('message', $result['message']);
                $this->dispatch('meeting-updated');
            } else {
                $this->errorMessage = $result['message'];
                $this->passphrase = '';
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('TTE Attendance Error: ' . $e->getMessage());
            $this->errorMessage = 'Gagal memproses TTE. Silakan coba beberapa saat lagi.';
            $this->passphrase = '';
        }
    }

    public function unlockForRevision(): void
    {
        $user = auth()->user();

        if (!$this->canEdit || $user->hasActiveRole('pimpinan')) {
            abort(403, 'Akses tidak diizinkan. Pembukaan kunci hanya dapat dilakukan oleh penyelenggara atau admin.');
        }

        if ($this->meeting->attendance_signed_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->meeting->attendance_signed_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->meeting->attendance_signed_path);
        }

        $this->meeting->update([
            'attendance_signed_at' => null,
            'attendance_signed_by' => null,
            'attendance_signed_path' => null,
        ]);

        $this->meeting->refresh();
        $this->alertKey = hrtime(true);
        $this->successMessage = 'Kunci presensi dibuka untuk revisi.';
        session()->flash('message', 'Kunci presensi dibuka untuk revisi.');
        $this->dispatch('meeting-updated');
    }

    public function with(): array
    {
        return [];
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="presensi">
    @if ($successMessage || session()->has('message'))
    <x-alert type="success" class="mb-5" :wire:key="'presensi-alert-'.$alertKey">
        {{ $successMessage ?? session('message') }}
    </x-alert>
    @endif

    <div class="space-y-6">
        <!-- Action Header Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Presensi Rapat</h3>
                <span class="px-2.5 py-0.5 bg-primary-50 text-primary-700 border border-primary-200/60 rounded-full text-xs font-bold">{{ $meeting->attendances->count() }} Orang</span>
            </div>

            @if($meeting->attendances->count() > 0)
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2.5 sm:gap-3 w-full sm:w-auto self-start sm:self-auto">
                @if(auth()->user()->hasActiveRole('pimpinan') && !$meeting->attendance_signed_at && $meeting->status === 'completed')
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'sign-attendance-modal'); $wire.openSignModal()" class="only:col-span-2 w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-xs sm:text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>TTE Presensi</span>
                </button>
                @endif

                @if($meeting->status === 'completed')
                    @if($meeting->attendance_signed_at)
                    <a href="{{ route('meetings.export.attendance', $meeting->id) }}" download class="only:col-span-2 w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 hover:border-emerald-400 active:scale-95 text-emerald-800 rounded-xl font-bold text-xs sm:text-sm transition-all shadow-2xs gap-2" title="Unduh Dokumen Presensi TTE">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Download PDF</span>
                    </a>
                    @else
                    <a href="{{ route('meetings.export.attendance', $meeting->id) }}" target="_blank" class="only:col-span-2 w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-xs sm:text-sm transition-all shadow-sm gap-2" title="Lihat Pratinjau Presensi">
                        <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Lihat PDF</span>
                    </a>
                    @endif
                @endif
            </div>
            @endif
        </div>

        @if($meeting->attendance_signed_at)
        <!-- Locked Banner (TTE Signed) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 sm:p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 font-medium">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span class="font-semibold text-amber-900">Presensi dikunci — sudah TTE.</span>
            </div>
            @if($canEdit)
            <button wire:click="unlockForRevision"
                wire:confirm="Buka kunci presensi untuk revisi? Dokumen perlu ditandatangani ulang setelahnya."
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 sm:py-2 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm shrink-0 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                </svg>
                <span>Buka Revisi</span>
            </button>
            @endif
        </div>
        @endif

        <!-- Full-Width Attendee Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left border-collapse min-w-[680px]">
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
                <tbody wire:loading.class="opacity-50" class="divide-y divide-slate-100 text-sm bg-white transition-opacity duration-200">
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
            <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 flex items-start justify-between gap-2.5">
                <div class="flex items-start gap-2.5 flex-1 min-w-0">
                    <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="flex-1 leading-relaxed break-words">{{ $errorMessage }}</span>
                </div>
                <button type="button" wire:click="$set('errorMessage', '')" class="text-rose-400 hover:text-rose-600 cursor-pointer shrink-0 ml-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @endif

            @if(empty(auth()->user()->nik))
            <div class="text-center py-4">
                <div class="w-14 h-14 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-rose-100">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-base font-extrabold text-slate-900 mb-1.5">NIK Belum Terdaftar</h3>
                <p class="text-xs text-slate-500 max-w-xs mx-auto leading-relaxed mb-6">
                    Hubungi <strong>Admin OPD</strong> untuk mendaftarkan NIK.
                </p>
                <div class="flex items-center justify-center">
                    <button type="button" x-on:click="$dispatch('close')" wire:click="closeSignModal" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
            @else
            <form wire:submit="executeSign" class="space-y-5">
                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-2.5 text-sm">
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Dokumen</span>
                        <span class="font-extrabold text-slate-900 text-right">Presensi Rapat</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">Penandatangan</span>
                        <span class="font-bold text-slate-900 text-right">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="text-slate-500 font-medium">NIK</span>
                        <span class="font-mono font-bold text-slate-800 text-right">{{ auth()->user()->nik }}</span>
                    </div>
                </div>

                <div x-data="{ showPassphrase: false }">
                    <label for="passphrase_attendance" class="block text-sm font-bold text-slate-700 mb-1">Passphrase</label>
                    <div class="relative">
                        <input wire:model="passphrase"
                               id="passphrase_attendance"
                               :type="showPassphrase ? 'text' : 'password'"
                               class="w-full text-base sm:text-sm py-2.5 pl-3.5 pr-10 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors"
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
            @endif
        </div>
    </x-modal>
</x-meeting-layout>