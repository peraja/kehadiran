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
                $this->message = 'Sesi presensi belum dibuka. Sesi akan otomatis aktif setelah penyelenggara memulai rapat.';
            } elseif ($this->meeting->status === 'completed') {
                $this->message = 'Sesi presensi telah ditutup karena pelaksanaan rapat telah selesai.';
            } else {
                $this->message = 'Sesi presensi untuk rapat ini tidak tersedia.';
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
        $this->validate([
            'nip' => 'required|string|min:5',
        ], [
            'nip.required' => 'Silakan masukkan NIP Anda.',
            'nip.min' => 'NIP minimal 5 karakter.',
        ]);

        $nip = trim($this->nip);
        $user = User::where('nip', $nip)->first();

        // If user not yet in local database, fetch from Kepegawaian API
        if (!$user) {
            try {
                $pegawaiResponse = \Illuminate\Support\Facades\Http::timeout(6)->get('http://apps.sinjaikab.go.id/api/pegawai/data_pegawai/', [
                    'nip' => $nip
                ]);

                $pegawaiData = $pegawaiResponse->json();
                $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

                if ($pegawaiResponse->successful() && is_array($pData) && !empty($pData['nama'] ?? $pData['nama_pegawai'] ?? null)) {
                    $name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $nip;
                    $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                    $jabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? null;
                    $unit_name = null;

                    if ($unit_id) {
                        $unitResponse = \Illuminate\Support\Facades\Http::timeout(5)->get('http://apps.sinjaikab.go.id/api/pegawai/get_unit/', [
                            'unit_id' => $unit_id
                        ]);
                        $unitData = $unitResponse->json();
                        $uData = isset($unitData['data']) ? $unitData['data'] : (isset($unitData[0]) ? $unitData[0] : $unitData);
                        $unit_name = $uData['unit_nama'] ?? $uData['nama_unit'] ?? $uData['unit_kerja'] ?? null;
                    }

                    $user = User::updateOrCreate(
                        ['nip' => $nip],
                        [
                            'name' => $name,
                            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                            'jabatan' => $jabatan,
                            'unit_name' => $unit_name
                        ]
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
            $this->addError('nip', 'NIP tidak terdaftar. Periksa kembali atau pilih tab Eksternal.');
            return;
        }

        // Check if already checked in
        $existingAttendance = $this->meeting->attendances()->where('user_id', $user->id)->first();
        if ($existingAttendance) {
            $this->status = 'success';
            $this->employee_name = $user->name;
            $this->recorded_time = $existingAttendance->check_in->format('H:i') . ' WITA';
            $this->message = 'Presensi Anda (' . $user->name . ') telah tercatat sebelumnya pada rapat ini.';
            return;
        }

        $this->employee_id = $user->id;
        $this->employee_name = $user->name;
        $this->employee_jabatan = $user->jabatan ?: 'Pegawai';
        $this->employee_unit = $user->unit_name ?: 'Pemkab Sinjai';
        $this->nip_checked = true;
    }

    public function confirmCheckIn()
    {
        if ($this->meeting->status !== 'ongoing') {
            $this->status = 'not_available';
            $this->message = 'Presensi tidak dapat dikirim karena sesi rapat belum dibuka atau telah selesai.';
            return;
        }

        $this->validate([
            'signature' => 'required|string',
        ], [
            'signature.required' => 'Tanda tangan wajib dibubuhkan sebelum mengirim presensi.'
        ]);

        $now = now();
        $this->recorded_time = $now->format('H:i') . ' WITA';

        if ($this->participant_type == 'internal') {
            if (!$this->nip_checked || !$this->employee_id) {
                $this->addError('nip', 'Silakan klik tombol "Cek NIP" terlebih dahulu.');
                return;
            }

            // Check again if already checked in
            $existingAttendance = $this->meeting->attendances()->where('user_id', $this->employee_id)->first();
            if ($existingAttendance) {
                $this->status = 'success';
                $this->message = 'Presensi Anda (' . $this->employee_name . ') telah tercatat sebelumnya.';
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
            // Guest check-in
            $this->validate([
                'guest_name' => 'required|string|max:255',
                'guest_agency' => 'required|string|max:255',
                'guest_position' => 'nullable|string|max:255',
            ], [
                'guest_name.required' => 'Nama lengkap wajib diisi.',
                'guest_agency.required' => 'Instansi / Lembaga wajib diisi.',
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
    }
}; ?>

<div>
    <!-- Meeting Info Header -->
    <div class="mb-8 pb-6 border-b border-slate-100 text-center">
        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 leading-snug">
            {{ $meeting->title }}
        </h2>
        <div class="mt-3 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm font-medium text-slate-500">
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $meeting->date ? $meeting->date->translatedFormat('l, d M Y') : '-' }}</span>
            </div>
            <span class="text-slate-300">&bull;</span>
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ $meeting->location }}</span>
            </div>
        </div>
    </div>

    @if($status === 'ready')
    <form wire:submit="confirmCheckIn" class="space-y-6">

        <!-- Category Segmented Control -->
        <div class="flex p-1.5 bg-slate-100 rounded-2xl shadow-inner border border-slate-200/50">
            <button type="button" wire:click="$set('participant_type', 'internal')" class="flex-1 py-2.5 text-sm font-extrabold rounded-xl transition-all active:scale-95 focus:outline-none {{ $participant_type == 'internal' ? 'bg-white shadow-sm text-primary-700 ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50' }}">
                Pemkab Sinjai
            </button>
            <button type="button" wire:click="$set('participant_type', 'eksternal')" class="flex-1 py-2.5 text-sm font-extrabold rounded-xl transition-all active:scale-95 focus:outline-none {{ $participant_type == 'eksternal' ? 'bg-white shadow-sm text-primary-700 ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50' }}">
                Eksternal
            </button>
        </div>

        @if($participant_type === 'internal')
        <!-- Pegawai Internal NIP Section -->
        <div class="space-y-3">
            <label for="nip" class="block text-sm font-bold text-slate-700">NIP</label>

            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                <div class="flex-1 w-full">
                    <input wire:model="nip" id="nip" type="text"
                        class="block w-full py-3 px-4 rounded-xl border border-slate-300 text-sm font-mono focus:ring-primary-500 focus:border-primary-500 transition-colors {{ $nip_checked ? 'bg-slate-50 text-slate-500 opacity-70' : 'bg-white' }}"
                        placeholder="Masukkan NIP"
                        :readonly="$nip_checked"
                        wire:keydown.enter.prevent="checkNip"
                        required autofocus />
                    @error('nip') <span class="text-xs text-rose-600 mt-1.5 block font-bold">{{ $message }}</span> @enderror
                </div>

                @if(!$nip_checked)
                <button type="button" wire:click="checkNip" wire:loading.attr="disabled" class="w-full sm:w-auto shrink-0 inline-flex justify-center items-center px-5 py-3 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm">
                    <span wire:loading.remove wire:target="checkNip">Cek NIP</span>
                    <span wire:loading wire:target="checkNip" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
                @else
                <button type="button" wire:click="resetNip" class="w-full sm:w-auto shrink-0 inline-flex justify-center items-center px-5 py-3 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                    Ganti NIP
                </button>
                @endif
            </div>

            @if($nip_checked)
            <!-- Verified Employee Identity Card -->
            <div class="mt-4 p-4 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-2xl">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold shadow-sm shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <div class="font-extrabold text-base text-slate-900 leading-snug">{{ $employee_name }}</div>
                        <div class="text-slate-700 font-semibold text-xs leading-relaxed">{{ $employee_jabatan }}</div>
                        <div class="text-slate-500 font-medium text-xs leading-relaxed pt-1 border-t border-emerald-200/60">{{ $employee_unit }}</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @else
        <!-- Eksternal Form Section -->
        <div class="space-y-5 bg-slate-50 p-5 rounded-2xl border border-slate-200">
            <div>
                <label for="guest_name" class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
                <input wire:model="guest_name" id="guest_name" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Masukkan nama lengkap Anda" required />
                @error('guest_name') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="guest_agency" class="block text-sm font-bold text-slate-700 mb-1">Instansi / Lembaga</label>
                <input wire:model="guest_agency" id="guest_agency" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Polres Sinjai, Perusahaan ABC" required />
                @error('guest_agency') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="guest_position" class="block text-sm font-bold text-slate-700 mb-1">Jabatan (Opsional)</label>
                <input wire:model="guest_position" id="guest_position" type="text" class="block w-full py-2.5 px-3 rounded-xl border border-slate-300 text-sm focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Contoh: Staf, Manajer, Perwakilan" />
                @error('guest_position') <span class="text-xs text-rose-600 mt-1 block font-bold">{{ $message }}</span> @enderror
            </div>
        </div>
        @endif

        @if($participant_type === 'eksternal' || $nip_checked)
        <!-- Signature Pad Component -->
        <div class="pt-4 border-t border-slate-100" x-data="signaturePad(@entangle('signature'))">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-extrabold text-slate-900">Tanda Tangan Digital</label>
                <button type="button" @click="clearSignature" class="text-xs font-bold text-rose-600 hover:text-rose-700 active:scale-95 transition flex items-center gap-1 bg-rose-50 px-2 py-1 rounded-xl">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus
                </button>
            </div>

            <div class="relative border-2 border-dashed border-slate-300 rounded-2xl overflow-hidden bg-slate-50">
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

        <div class="pt-6">
            <button type="submit" class="w-full flex justify-center items-center px-6 py-4 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-2xl font-extrabold text-base transition-all shadow-sm">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Kirim Presensi
            </button>
        </div>
        @endif
    </form>

    @elseif($status === 'success')
    <div class="text-center py-6">
        <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-emerald-600 text-white rounded-full flex items-center justify-center mx-auto shadow-sm shadow-emerald-100 mb-6 relative">
            <div class="absolute inset-0 rounded-full border-4 border-emerald-100 scale-110 animate-ping opacity-20"></div>
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h3 class="text-2xl font-extrabold text-slate-900 mb-2">Presensi Berhasil!</h3>
        <p class="text-sm font-medium text-slate-500 mb-8">{{ $message }}</p>

        @if($employee_name)
        <div class="bg-slate-50 rounded-2xl p-1 shadow-inner border border-slate-200">
            <div class="bg-white rounded-xl p-5 text-left divide-y divide-slate-100">
                <div class="py-3 flex justify-between items-start gap-4">
                    <span class="text-slate-500 font-bold text-sm shrink-0">Nama Lengkap</span>
                    <span class="font-extrabold text-slate-900 text-right leading-snug">{{ $employee_name }}</span>
                </div>
                @if($recorded_time)
                <div class="py-3 flex justify-between items-center gap-4">
                    <span class="text-slate-500 font-bold text-sm shrink-0">Waktu Tercatat</span>
                    <span class="font-bold text-emerald-600 text-right">{{ $recorded_time }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="mt-8">
            <a href="{{ route('meetings.check-in', $meeting->id) }}" wire:navigate class="inline-flex justify-center items-center px-6 py-3.5 bg-primary-50 text-primary-700 hover:bg-primary-100 active:scale-95 rounded-2xl font-bold text-sm transition-all shadow-sm border border-primary-200 w-full">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <h3 class="text-xl font-extrabold text-slate-900 mb-3">Presensi Belum Tersedia</h3>
        <p class="text-sm font-medium text-slate-600 max-w-sm mx-auto leading-relaxed mb-8">{{ $message }}</p>

        <a href="{{ url('/') }}" class="inline-flex justify-center items-center px-6 py-3 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
            &larr; Kembali ke Beranda
        </a>
    </div>
    @endif

    <!-- Script for High-Precision Signature Canvas -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('signaturePad', (signatureData) => ({
                isDrawing: false,
                hasDrawn: false,
                ctx: null,
                signature: signatureData,

                init() {
                    this.$nextTick(() => {
                        this.setupCanvas();
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
                },

                draw(e) {
                    if (!this.isDrawing) return;
                    const pos = this.getPos(e);
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                },

                stopDrawing() {
                    if (!this.isDrawing) return;
                    this.isDrawing = false;
                    this.updateSignatureData();
                },

                clearSignature() {
                    const canvas = this.$refs.canvas;
                    if (!canvas || !this.ctx) return;
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasDrawn = false;
                    this.signature = '';
                },

                updateSignatureData() {
                    const canvas = this.$refs.canvas;
                    if (!canvas) return;
                    this.signature = canvas.toDataURL('image/png');
                }
            }));
        });
    </script>
</div>