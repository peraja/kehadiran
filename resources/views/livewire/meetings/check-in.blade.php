<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\User;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component {
    public Meeting $meeting;
    public $message = '';
    public $status = 'ready'; // ready, success, not_available

    public $participant_type = 'internal'; // 'internal' or 'eksternal'

    // Pemkab Sinjai
    public $nip = '';
    public $nip_checked = false;
    public $employee_name = '';
    public $employee_jabatan = '';
    public $employee_unit = '';
    public $employee_id = null;

    // Eksternal
    public $guest_name = '';
    public $guest_agency = '';
    public $guest_position = '';

    public $signature = '';
    public $recorded_time = '';

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;

        if ($this->meeting->status !== 'ongoing') {
            $this->status = 'not_available';
            if ($this->meeting->status === 'scheduled') {
                $this->message = 'Presensi belum dibuka, silakan tunggu hingga rapat dimulai.';
            } elseif ($this->meeting->status === 'completed') {
                $this->message = 'Presensi telah ditutup karena rapat telah selesai.';
            } else {
                $this->message = 'Presensi untuk rapat ini tidak tersedia.';
            }
        }
    }

    public function updatedParticipantType()
    {
        $this->resetValidation();
        $this->resetNip();
        $this->reset(['guest_name', 'guest_agency', 'guest_position', 'signature']);
    }

    public function resetNip()
    {
        $this->nip_checked = false;
        $this->employee_name = '';
        $this->employee_jabatan = '';
        $this->employee_unit = '';
        $this->employee_id = null;
        $this->signature = '';
    }

    public function checkNip()
    {
        $this->resetValidation();
        $this->nip = trim((string) $this->nip);
        $this->validate([
            'nip' => 'required|digits:18',
        ], [
            'nip.required' => 'NIP wajib diisi.',
            'nip.digits' => 'NIP harus 18 digit.',
        ]);

        $nip = trim($this->nip);
        $user = User::where('nip', $nip)->first();

        // If user not yet in local database, fetch from Kepegawaian API
        if (!$user) {
            $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
            $timeout = config('services.simpeg.timeout', 10);

            try {
                $pegawaiResponse = \Illuminate\Support\Facades\Http::timeout($timeout)->get("{$baseUrl}/data_pegawai/", [
                    'nip' => $nip
                ]);

                $pegawaiData = $pegawaiResponse->json();
                $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

                if ($pegawaiResponse->successful() && is_array($pData) && !empty($pData['nama'] ?? $pData['nama_pegawai'] ?? null)) {
                    $name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $nip;
                    $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                    $jabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? null;
                    $pangkat = $pData['pangkat_nama'] ?? $pData['pangkat'] ?? null;
                    $unit_name = null;

                    if ($unit_id) {
                        $unitResponse = \Illuminate\Support\Facades\Http::timeout(5)->get("{$baseUrl}/get_unit/", [
                            'unit_id' => $unit_id
                        ]);
                        $unitData = $unitResponse->json();
                        $uData = isset($unitData['data']) ? $unitData['data'] : (isset($unitData[0]) ? $unitData[0] : $unitData);
                        $unit_name = $uData['unit_nama'] ?? $uData['nama_unit'] ?? $uData['unit_kerja'] ?? null;
                    }

                    $userData = [
                        'name' => $name,
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                        'jabatan' => $jabatan,
                        'unit_name' => $unit_name,
                    ];

                    if (!empty($pangkat)) {
                        $userData['pangkat'] = trim((string)$pangkat);
                    }

                    $user = User::updateOrCreate(
                        ['nip' => $nip],
                        $userData
                    );

                    if ($user->roles->count() == 0) {
                        $user->assignRole('pegawai');
                    }
                }
            } catch (\Exception $e) {
                // Ignore API connection issue, will trigger error below
            }
        }

        if (!$user) {
            $this->nip_checked = false;
            $this->addError('nip', 'NIP tidak ditemukan.');
            return;
        }

        // Check if already checked in
        $existingAttendance = $this->meeting->attendances()->where('user_id', $user->id)->first();
        if ($existingAttendance) {
            $this->status = 'success';
            $this->employee_name = $user->name;
            $this->recorded_time = $existingAttendance->check_in->format('H:i') . ' WITA';
            $this->message = 'Presensi sudah tercatat sebelumnya.';
            return;
        }

        $this->employee_id = $user->id;
        $this->employee_name = $user->name;
        $this->employee_jabatan = $user->jabatan ?: 'Pegawai';
        $this->employee_unit = $user->unit_name ?: 'Pemkab Sinjai';
        $this->nip_checked = true;
    }

    public function confirmCheckIn(?string $signatureData = null, ?string $participantType = null)
    {
        if ($participantType && in_array($participantType, ['internal', 'eksternal'])) {
            $this->participant_type = $participantType;
        }

        try {
            if (!empty($signatureData)) {
                // If data contains '|', it's the raw coordinates format: width|height|pathData
                if (str_contains($signatureData, '|')) {
                    $parts = explode('|', $signatureData, 3);
                    if (count($parts) === 3) {
                        $w = (int)$parts[0];
                        $h = (int)$parts[1];
                        $path = $parts[2];
                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'"><path d="'.$path.'" fill="none" stroke="#0f172a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                        $this->signature = 'data:image/svg+xml;base64,' . base64_encode($svg);
                    } else {
                        $this->signature = $signatureData;
                    }
                } else {
                    $this->signature = $signatureData;
                }
            }

            if ($this->meeting->status !== 'ongoing') {
                $this->status = 'not_available';
                $this->message = 'Presensi hanya dibuka saat rapat berlangsung.';
                return;
            }

            $now = now();
            $this->recorded_time = $now->format('H:i') . ' WITA';

            if ($this->participant_type == 'internal') {
                $this->validate([
                    'signature' => 'required|string',
                ], [
                    'signature.required' => 'Tanda Tangan wajib diisi.'
                ]);

                if (!$this->nip_checked || !$this->employee_id) {
                    $this->addError('nip', 'Silakan cek NIP terlebih dahulu.');
                    return;
                }

                // Check again if already checked in
                $existingAttendance = $this->meeting->attendances()->where('user_id', $this->employee_id)->first();
                if ($existingAttendance) {
                    $this->status = 'success';
                    $this->employee_name = $this->employee_name ?: ($existingAttendance->user?->name ?? 'Pegawai');
                    $this->recorded_time = $existingAttendance->check_in ? $existingAttendance->check_in->format('H:i') . ' WITA' : $this->recorded_time;
                    $this->message = 'Presensi sudah tercatat sebelumnya.';
                    return;
                }

                $this->meeting->attendances()->create([
                    'user_id' => $this->employee_id,
                    'signature' => $this->signature,
                    'check_in' => $now,
                    'method' => 'qr',
                    'device_info' => request()->userAgent()
                ]);
            } else {
                // Guest check-in: Validate name, agency, position, and signature simultaneously
                $this->validate([
                    'guest_name' => 'required|string|max:255',
                    'guest_agency' => 'required|string|max:255',
                    'guest_position' => 'nullable|string|max:255',
                    'signature' => 'required|string',
                ], [
                    'guest_name.required' => 'Nama Lengkap wajib diisi.',
                    'guest_agency.required' => 'Instansi / Lembaga wajib diisi.',
                    'signature.required' => 'Tanda Tangan wajib diisi.',
                ]);

                $this->meeting->attendances()->create([
                    'guest_name' => $this->guest_name,
                    'guest_agency' => $this->guest_agency,
                    'guest_position' => $this->guest_position,
                    'signature' => $this->signature,
                    'check_in' => $now,
                    'method' => 'qr',
                    'device_info' => request()->userAgent()
                ]);

                $this->employee_name = $this->guest_name;
            }

            $this->status = 'success';
            $this->message = 'Presensi berhasil dicatat.';
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CheckIn Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->addError('signature', 'Gagal menyimpan presensi: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot:seo>
        <x-seo-meta
            :title="'Presensi: ' . $meeting->title . ' - eRapat Sinjai'"
            :description="'Laman presensi kehadiran mandiri rapat ' . $meeting->title . ' Pemerintah Kabupaten Sinjai.'"
            :url="route('meetings.check-in', $meeting->id)"
            image="https://sinjaikab.go.id/v4/wp-content/uploads/2022/03/logo-sinjai.png" />
    </x-slot:seo>

    <!-- Meeting Info Header -->
    <div class="mb-6 sm:mb-8 pb-5 sm:pb-6 border-b border-slate-100 text-center">
        <h2 class="text-lg sm:text-2xl font-extrabold text-slate-900 leading-snug break-words">
            {{ $meeting->title }}
        </h2>
        <div class="mt-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-xs sm:text-sm font-medium text-slate-500">
            <span>{{ $meeting->date ? $meeting->date->translatedFormat('l, d F Y') : '-' }}</span>
            <span class="hidden sm:inline text-slate-300">&bull;</span>
            <span class="break-words">{{ $meeting->location }}</span>
        </div>
    </div>

    @if($status === 'ready')
    <div class="space-y-6" x-data="{ tab: @entangle('participant_type') }">

        <!-- Category Segmented Control -->
        <div class="flex p-1.5 bg-slate-100 rounded-2xl shadow-inner border border-slate-200/50">
            <button type="button" @click="tab = 'internal'" :class="tab === 'internal' ? 'bg-white shadow-sm text-primary-700 ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'" class="flex-1 py-2.5 text-sm font-extrabold rounded-xl transition-all active:scale-95 focus:outline-none cursor-pointer">
                Pemkab Sinjai
            </button>
            <button type="button" @click="tab = 'eksternal'" :class="tab === 'eksternal' ? 'bg-white shadow-sm text-primary-700 ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'" class="flex-1 py-2.5 text-sm font-extrabold rounded-xl transition-all active:scale-95 focus:outline-none cursor-pointer">
                Eksternal
            </button>
        </div>

        <!-- Pegawai Internal NIP Section -->
        <div x-show="tab === 'internal'" class="space-y-3">
            <label for="nip" class="block text-sm font-bold text-slate-700">Masukkan NIP</label>

            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                <div class="flex-1 w-full">
                    <input wire:model="nip" id="nip" type="text" maxlength="18" inputmode="numeric"
                        class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm font-mono focus:ring-primary-500 focus:border-primary-500 transition-colors {{ $nip_checked ? 'bg-slate-50 text-slate-500 opacity-70' : 'bg-white' }}"
                        placeholder="Contoh: 199610072022031013"
                        @readonly($nip_checked)
                        wire:keydown.enter.prevent="checkNip"
                        required />
                    @error('nip')
                        <div class="mt-2 text-xs space-y-1.5">
                            <span class="text-rose-600 font-bold block">{{ $message }}</span>
                            @if(str_contains($message, 'tidak ditemukan'))
                            <button type="button" @click="tab = 'eksternal'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer">
                                <span>Gunakan Tab Eksternal</span>
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    @enderror
                </div>

                @if(!$nip_checked)
                <button type="button" wire:click="checkNip" wire:loading.attr="disabled" class="w-full sm:w-auto shrink-0 inline-flex justify-center items-center px-5 py-3 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg wire:loading.remove wire:target="checkNip" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <svg wire:loading wire:target="checkNip" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Cek NIP</span>
                </button>
                @else
                <button type="button" wire:click="resetNip" class="w-full sm:w-auto shrink-0 inline-flex justify-center items-center px-5 py-3 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Ganti NIP
                </button>
                @endif
            </div>

            @if($nip_checked)
            <!-- Verified Employee Identity Card -->
            <div class="mt-3 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2">
                <div class="min-w-0 space-y-0.5">
                    <p class="text-sm font-bold text-emerald-900 truncate">{{ $employee_name }}</p>
                    @if($employee_jabatan)
                    <p class="text-xs font-semibold text-emerald-800 truncate">{{ $employee_jabatan }}</p>
                    @endif
                    @if($employee_unit)
                    <p class="text-xs font-medium text-emerald-600 truncate">{{ $employee_unit }}</p>
                    @endif
                </div>
                <span class="shrink-0 flex items-center justify-center w-7 h-7 bg-emerald-500 text-white rounded-xl shadow-xs" title="Terverifikasi SIMPEG">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>
            @endif
        </div>

        <!-- Eksternal Form Section -->
        <div x-show="tab === 'eksternal'" class="space-y-4" x-cloak>
            <div>
                <label for="guest_name" class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
                <input wire:model.blur="guest_name" id="guest_name" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Anthony" required />
                @error('guest_name') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="guest_agency" class="block text-sm font-bold text-slate-700 mb-1">Instansi / Lembaga</label>
                <input wire:model.blur="guest_agency" id="guest_agency" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Pengadilan Negeri Sinjai" required />
                @error('guest_agency') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="guest_position" class="block text-sm font-bold text-slate-700 mb-1">Jabatan (Opsional)</label>
                <input wire:model.blur="guest_position" id="guest_position" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-base sm:text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Ketua" />
                @error('guest_position') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Signature Pad Component & Submit Button -->
        <div x-show="tab === 'eksternal' || @js($nip_checked)" class="space-y-6 pt-4 border-t border-slate-100" x-data="signaturePad()" x-cloak>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-extrabold text-slate-900">Tanda Tangan</label>
                    <button type="button" @click="clearSignature" class="text-xs font-bold text-rose-600 hover:text-rose-700 active:scale-95 transition flex items-center gap-1 bg-rose-50 px-2 py-1 rounded-xl cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                </div>

                <div class="relative border-2 border-dashed border-slate-300 rounded-2xl overflow-hidden bg-slate-50" wire:ignore>
                    <canvas x-ref="canvas" class="w-full h-48 touch-none cursor-crosshair select-none block"
                        @mousedown="startDrawing" @mousemove="draw" @mouseup="stopDrawing" @mouseleave="stopDrawing"
                        @touchstart.prevent="startDrawing" @touchmove.prevent="draw" @touchend.prevent="stopDrawing">
                    </canvas>
                    <div x-show="!hasDrawn" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-slate-400 gap-2">
                        <div class="p-3 bg-white rounded-full shadow-sm">
                            <svg class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-slate-400">Tanda Tangan</span>
                    </div>
                </div>

                @error('signature') <span class="text-xs text-rose-600 mt-2 block font-bold text-center">{{ $message }}</span> @enderror
            </div>

            <div>
                <button type="button" @click.prevent="submitCheckIn()" wire:loading.attr="disabled" wire:target="confirmCheckIn" class="w-full flex justify-center items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                    <svg wire:loading.remove wire:target="confirmCheckIn" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg wire:loading wire:target="confirmCheckIn" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Kirim Presensi</span>
                </button>
            </div>
        </div>
    </div>

    @elseif($status === 'success')
    <div class="text-center py-4 space-y-6">
        <div>
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900">Presensi Berhasil!</h3>
        </div>

        @if($employee_name)
        <div class="bg-white border border-slate-200 rounded-2xl p-4 text-left divide-y divide-slate-100 shadow-2xs">
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-500 font-medium text-sm">Nama Lengkap</span>
                <span class="font-bold text-slate-900 text-right">{{ $employee_name }}</span>
            </div>
            @if($recorded_time)
            <div class="py-2.5 flex justify-between items-center gap-4">
                <span class="text-slate-500 font-medium text-sm">Waktu Presensi</span>
                <span class="font-bold text-slate-900 font-mono text-right">{{ $recorded_time }}</span>
            </div>
            @endif
        </div>
        @endif

        @php
        $skmUrl = \App\Models\Setting::get('skm_url', 'https://skm.go.id/share/instansi/22748fb4-56a9-4101-9e6d-4145a727e0f5/1');
        @endphp
        @if($skmUrl)
        <!-- Banner Survei Kepuasan Masyarakat (SKM) -->
        <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl text-left space-y-3">
            <div>
                <h4 class="text-sm font-bold text-slate-900">Survei Kepuasan Masyarakat (SKM)</h4>
                <p class="text-xs text-slate-500 mt-0.5">Bantu kami meningkatkan kualitas layanan melalui survei singkat.</p>
            </div>

            <a href="{{ $skmUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm w-full gap-2">
                <span>Isi Survei SKM</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
        @endif

        <div>
            <a href="{{ route('meetings.check-in', $meeting->id) }}" wire:navigate class="inline-flex justify-center items-center px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-xs w-full gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Isi Presensi Peserta Lain
            </a>
        </div>
    </div>

    @elseif($status === 'not_available')
    <div class="text-center py-10 px-4">
        <div class="w-20 h-20 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto border-2 border-amber-200 mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="text-xl font-extrabold text-slate-900 mb-3">Presensi Tidak Tersedia</h3>
        <p class="text-sm font-medium text-slate-600 max-w-sm mx-auto leading-relaxed mb-8">{{ $message }}</p>

        <a href="{{ url('/') }}" class="inline-flex justify-center items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl font-bold text-xs uppercase tracking-wider transition-all shadow-sm">
            &larr; Kembali ke Beranda
        </a>
    </div>
    @endif

    <!-- Script for High-Precision Signature Canvas -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('signaturePad', () => ({
                isDrawing: false,
                hasDrawn: false,
                ctx: null,
                pathData: '',

                init() {
                    this.$nextTick(() => {
                        this.setupCanvas();
                    });
                    this.$watch('tab', () => {
                        this.$nextTick(() => {
                            this.setupCanvas();
                        });
                    });
                },

                setupCanvas() {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return;

                    this.ctx = canvas.getContext('2d');

                    // Fixed high-resolution internal buffer
                    const rect = canvas.getBoundingClientRect();
                    const width = rect.width > 0 ? rect.width : 400;
                    const height = rect.height > 0 ? rect.height : 180;

                    // Set internal bitmap resolution
                    canvas.width = Math.round(width * 2);
                    canvas.height = Math.round(height * 2);

                    this.ctx.lineWidth = 4;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';
                    this.ctx.strokeStyle = '#0f172a';
                },

                getPos(e) {
                    const canvas = this.$refs.canvas;
                    const rect = canvas.getBoundingClientRect();
                    let clientX, clientY;

                    if (e.touches && e.touches.length > 0) {
                        clientX = e.touches[0].clientX;
                        clientY = e.touches[0].clientY;
                    } else if (e.changedTouches && e.changedTouches.length > 0) {
                        clientX = e.changedTouches[0].clientX;
                        clientY = e.changedTouches[0].clientY;
                    } else {
                        clientX = e.clientX;
                        clientY = e.clientY;
                    }

                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;

                    return {
                        x: (clientX - rect.left) * scaleX,
                        y: (clientY - rect.top) * scaleY
                    };
                },

                startDrawing(e) {
                    this.isDrawing = true;
                    this.hasDrawn = true;
                    const pos = this.getPos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(pos.x, pos.y);
                    this.pathData += `M ${Math.round(pos.x)} ${Math.round(pos.y)} `;
                },

                draw(e) {
                    if (!this.isDrawing) return;
                    const pos = this.getPos(e);
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    this.pathData += `L ${Math.round(pos.x)} ${Math.round(pos.y)} `;
                },

                stopDrawing() {
                    if (!this.isDrawing) return;
                    this.isDrawing = false;
                },

                clearSignature() {
                    const canvas = this.$refs.canvas;
                    if (!canvas || !this.ctx) return;
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasDrawn = false;
                    this.pathData = '';
                },

                updateSignatureData() {
                    const canvas = this.$refs.canvas;
                    if (!canvas || !this.hasDrawn || !this.pathData) {
                        return '';
                    }

                    // Format: width|height|pathData
                    return `${canvas.width}|${canvas.height}|${this.pathData.trim()}`;
                },

                submitCheckIn() {
                    const sig = this.updateSignatureData();
                    this.$wire.confirmCheckIn(sig || '', this.tab);
                }
            }));
        });
    </script>
</div>