<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Models\User;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component {
    public Meeting $meeting;
    public $message = '';
    public $status = 'ready'; // ready, success
    
    public $participant_type = 'internal'; // internal or eksternal
    public $nip = '';
    public $nip_checked = false;
    public $employee_name = '';
    public $employee_jabatan = '';
    public $employee_unit = '';
    public $employee_id = null;
    
    public $guest_name = '';
    public $guest_agency = '';
    public $guest_position = '';
    public $signature = '';

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;

        if ($this->meeting->status !== 'ongoing') {
            $this->status = 'not_available';
            if ($this->meeting->status === 'scheduled') {
                $this->message = 'Sesi presensi belum dibuka. Silakan tunggu hingga penyelenggara memulai rapat.';
            } elseif ($this->meeting->status === 'completed') {
                $this->message = 'Sesi presensi telah ditutup karena rapat ini sudah selesai.';
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
            'nip.required' => 'Silakan ketikkan NIP Anda.',
            'nip.min' => 'NIP minimal 5 karakter.',
        ]);

        $nip = trim($this->nip);
        $user = User::where('nip', $nip)->first();

        // If user not in local database, fetch from Kepegawaian API
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
                            'email' => $nip . '@pegawai.sinjaikab.go.id',
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
            $this->addError('nip', 'NIP tidak ditemukan pada data kepegawaian. Silakan periksa kembali atau gunakan tab Undangan Eksternal.');
            return;
        }

        // Check if already checked in
        $existingAttendance = $this->meeting->attendances()->where('user_id', $user->id)->first();
        if ($existingAttendance) {
            $this->status = 'success';
            $this->message = 'Anda (' . $user->name . ') sudah tercatat hadir pada rapat ini sebelumnya.';
            return;
        }

        $this->employee_id = $user->id;
        $this->employee_name = $user->name;
        $this->employee_jabatan = $user->jabatan ?: 'Pegawai';
        $this->employee_unit = $user->unit_name ?: 'Pemerintah Daerah';
        $this->nip_checked = true;
    }

    public function confirmCheckIn()
    {
        if ($this->meeting->status !== 'ongoing') {
            $this->status = 'not_available';
            $this->message = 'Presensi tidak dapat dilakukan karena rapat belum dimulai atau sudah selesai.';
            return;
        }

        $this->validate([
            'signature' => 'required|string',
        ], [
            'signature.required' => 'Tanda tangan wajib dibubuhkan.'
        ]);

        if ($this->participant_type == 'internal') {
            if (!$this->nip_checked || !$this->employee_id) {
                $this->addError('nip', 'Silakan klik tombol "Cek NIP" terlebih dahulu.');
                return;
            }

            // Check again if already checked in
            $existingAttendance = $this->meeting->attendances()->where('user_id', $this->employee_id)->first();
            if ($existingAttendance) {
                $this->status = 'success';
                $this->message = 'Anda (' . $this->employee_name . ') sudah tercatat hadir sebelumnya.';
                return;
            }

            $this->meeting->attendances()->create([
                'user_id' => $this->employee_id,
                'signature' => $this->signature,
                'check_in' => now(),
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
                'guest_agency.required' => 'Instansi / Asal instansi wajib diisi.',
            ]);
            
            $this->meeting->attendances()->create([
                'guest_name' => $this->guest_name,
                'guest_agency' => $this->guest_agency,
                'guest_position' => $this->guest_position,
                'signature' => $this->signature,
                'check_in' => now(),
                'method' => 'qr',
                'device_info' => request()->userAgent()
            ]);
        }

        $this->status = 'success';
        $this->message = 'Kehadiran Anda berhasil dicatat!';
    }
}; ?>
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 relative overflow-hidden py-10">
    <!-- Dekorasi Latar Belakang -->
    <div class="absolute inset-0 z-0 opacity-40" style="background-image: radial-gradient(#10b981 1px, transparent 1px); background-size: 24px 24px;"></div>
    <div class="absolute top-0 left-0 w-full h-64 bg-gradient-to-b from-primary-600 to-transparent z-0 opacity-90"></div>

    <div class="w-full sm:max-w-lg mt-6 px-8 py-10 bg-white shadow-2xl overflow-hidden sm:rounded-2xl z-10 border border-gray-100 relative">
        
        <!-- Header Instansi -->
        <div class="flex flex-col items-center justify-center border-b border-gray-100 pb-6 mb-6">
            <div class="mb-3">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Pemkab Sinjai" class="w-20 h-auto drop-shadow-md">
            </div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-1 text-center">Pemerintah Kabupaten Sinjai</h1>
            <h2 class="text-2xl font-bold text-gray-900 text-center leading-tight">{{ $meeting->title }}</h2>
            <p class="text-sm text-gray-500 mt-2 flex items-center bg-gray-50 px-3 py-1 rounded-full border border-gray-200">
                <svg class="w-4 h-4 mr-1.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                {{ $meeting->date->translatedFormat('l, d F Y') }} &bull; {{ $meeting->start_time->format('H:i') }} WITA
            </p>
        </div>

        @if($status == 'ready')
            <form wire:submit="confirmCheckIn">
                
                <div class="mb-6 flex gap-2 p-1.5 bg-gray-100 rounded-xl">
                    <button type="button" wire:click="$set('participant_type', 'internal')" class="w-1/2 py-2.5 text-sm font-semibold rounded-lg transition-all active:scale-95 focus:outline-none {{ $participant_type == 'internal' ? 'bg-white shadow-sm text-gray-900 ring-1 ring-black/5' : 'text-gray-500 hover:text-gray-700' }}">
                        Pegawai Pemkab
                    </button>
                    <button type="button" wire:click="$set('participant_type', 'eksternal')" class="w-1/2 py-2.5 text-sm font-semibold rounded-lg transition-all active:scale-95 focus:outline-none {{ $participant_type == 'eksternal' ? 'bg-white shadow-sm text-gray-900 ring-1 ring-black/5' : 'text-gray-500 hover:text-gray-700' }}">
                        Undangan Eksternal
                    </button>
                </div>

                @if($participant_type == 'internal')
                    <div class="mb-6">
                        <x-input-label for="nip" value="Nomor Induk Pegawai (NIP)" />
                        
                        <div class="mt-1 flex items-start gap-2">
                            <div class="flex-1">
                                <x-text-input wire:model="nip" id="nip" type="text" class="block w-full {{ $nip_checked ? 'bg-gray-100 text-gray-500' : '' }}" placeholder="Masukkan 18 digit NIP..." :readonly="$nip_checked" wire:keydown.enter.prevent="checkNip" required />
                            </div>
                            
                            @if(!$nip_checked)
                                <x-primary-button type="button" wire:click="checkNip" wire:loading.attr="disabled" class="flex-shrink-0 py-3">
                                    <span wire:loading.remove wire:target="checkNip">Cek NIP</span>
                                    <span wire:loading wire:target="checkNip" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Memeriksa...
                                    </span>
                                </x-primary-button>
                            @else
                                <x-secondary-button type="button" wire:click="resetNip" class="flex-shrink-0 py-3">
                                    Ganti NIP
                                </x-secondary-button>
                            @endif
                        </div>
                        <x-input-error :messages="$errors->get('nip')" class="mt-2" />

                        @if($nip_checked)
                            <!-- Kartu Identitas Pegawai Terverifikasi -->
                            <div class="mt-4 p-4 bg-primary-50 border border-primary-200 rounded-xl text-primary-950">
                                <div class="flex items-start gap-3">
                                    <div class="p-1 bg-primary-600 text-white rounded-full mt-0.5 shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <div class="space-y-0.5 text-xs flex-1">
                                        <div class="font-bold text-sm text-primary-900">{{ $employee_name }}</div>
                                        <div class="text-primary-700 font-medium">{{ $employee_jabatan }}</div>
                                        <div class="text-primary-600">{{ $employee_unit }}</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-xs text-gray-500 mt-2">
                                💡 Masukkan NIP Anda dan klik <strong>"Cek NIP"</strong> untuk memverifikasi data sebelum membubuhkan tanda tangan.
                            </p>
                        @endif
                    </div>
                @else
                    <div class="space-y-4 mb-6">
                        <div>
                            <x-input-label for="guest_name" value="Nama Lengkap" />
                            <x-text-input wire:model="guest_name" id="guest_name" type="text" class="mt-1 block w-full" placeholder="Masukkan nama lengkap Anda" required />
                            <x-input-error :messages="$errors->get('guest_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="guest_agency" value="Instansi / Asal" />
                            <x-text-input wire:model="guest_agency" id="guest_agency" type="text" class="mt-1 block w-full" placeholder="Contoh: Polres Sinjai / Wartawan / Umum" required />
                            <x-input-error :messages="$errors->get('guest_agency')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="guest_position" value="Jabatan (Opsional)" />
                            <x-text-input wire:model="guest_position" id="guest_position" type="text" class="mt-1 block w-full" placeholder="Contoh: Kasat / Anggota / Wartawan / Staf" />
                            <x-input-error :messages="$errors->get('guest_position')" class="mt-2" />
                        </div>
                    </div>
                @endif
                
                @if($participant_type == 'eksternal' || $nip_checked)
                    <div class="mb-6" x-data="signaturePad(@entangle('signature'))">
                        <x-input-label value="Tanda Tangan" class="mb-2" />
                        
                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-white">
                            <canvas x-ref="canvas" width="400" height="200" class="w-full touch-none cursor-crosshair"
                                @mousedown="startDrawing" @mousemove="draw" @mouseup="stopDrawing" @mouseleave="stopDrawing"
                                @touchstart.prevent="startDrawing" @touchmove.prevent="draw" @touchend.prevent="stopDrawing">
                            </canvas>
                            <div x-show="!hasDrawn" class="absolute inset-0 pointer-events-none flex items-center justify-center text-gray-400 text-sm">
                                Bubuhkan tanda tangan Anda di sini
                            </div>
                        </div>
                        
                        <div class="mt-2 flex justify-between items-center">
                            <x-input-error :messages="$errors->get('signature')" class="mt-0" />
                            <button type="button" @click="clearSignature" class="text-xs font-semibold text-red-600 hover:text-red-800 active:scale-95 focus:outline-none focus:underline transition">Bersihkan Tanda Tangan</button>
                        </div>
                    </div>

                    <x-primary-button class="w-full justify-center text-base py-3">
                        Konfirmasi Kehadiran
                    </x-primary-button>
                @endif
            </form>
        @elseif($status == 'success')
            <div class="mb-6 text-primary-600 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="text-xl font-bold text-gray-900">Kehadiran Berhasil Tercatat!</h3>
                <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>
            </div>
            <div class="text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white font-semibold text-xs uppercase tracking-widest rounded-xl shadow-sm transition">
                    Kembali ke Halaman Utama
                </a>
            </div>
        @elseif($status == 'not_available')
            <div class="mb-6 text-center py-4">
                <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Presensi Belum / Tidak Dibuka</h3>
                <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>
            </div>
            <div class="text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">
                    &larr; Halaman Utama
                </a>
            </div>
        @endif
    </div>

    <!-- Script for Signature Canvas -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('signaturePad', (signatureData) => ({
                isDrawing: false,
                hasDrawn: false,
                ctx: null,
                signature: signatureData,
                
                init() {
                    const canvas = this.$refs.canvas;
                    this.ctx = canvas.getContext('2d');
                    
                    // Handle high DPI displays
                    const rect = canvas.getBoundingClientRect();
                    canvas.width = rect.width * 2;
                    canvas.height = rect.height * 2;
                    this.ctx.scale(2, 2);
                    
                    this.ctx.lineWidth = 3;
                    this.ctx.lineCap = 'round';
                    this.ctx.strokeStyle = '#000000';
                },
                
                getPos(e) {
                    const canvas = this.$refs.canvas;
                    const rect = canvas.getBoundingClientRect();
                    let clientX, clientY;
                    
                    if (e.touches && e.touches.length > 0) {
                        clientX = e.touches[0].clientX;
                        clientY = e.touches[0].clientY;
                    } else {
                        clientX = e.clientX;
                        clientY = e.clientY;
                    }
                    
                    return {
                        x: clientX - rect.left,
                        y: clientY - rect.top
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
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasDrawn = false;
                    this.signature = '';
                },
                
                updateSignatureData() {
                    const canvas = this.$refs.canvas;
                    this.signature = canvas.toDataURL('image/png');
                }
            }));
        });
    </script>
</div>


