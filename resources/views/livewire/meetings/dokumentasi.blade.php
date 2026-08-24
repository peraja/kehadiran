<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use App\Services\BsreEsignService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Meeting $meeting;
    public $photos = [];
    public $uploadKey = 1;
    public bool $canEdit = false;

    public bool $showSignModal = false;
    public string $passphrase = '';
    public string $errorMessage = '';

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;

        $user = auth()->user();
        if ($user->hasActiveRole('pimpinan')) {
            if (!$meeting->isSigner($user)) {
                abort(403, 'Anda hanya dapat mengakses dokumentasi rapat yang penandatangannya adalah Anda sendiri.');
            }
            return $this->redirect(route('meetings.overview', $meeting->id), navigate: true);
        }

        if ($user->hasActiveRole('pegawai') && $meeting->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengakses dokumentasi rapat yang Anda buat sendiri.');
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
        $this->showSignModal = true;
        $this->dispatch('open-modal', 'sign-photos-modal');
    }

    public function closeSignModal()
    {
        $this->showSignModal = false;
        $this->passphrase = '';
        $this->errorMessage = '';
        $this->dispatch('close-modal', 'sign-photos-modal');
    }

    public function executeSign(BsreEsignService $esignService)
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

        $result = $esignService->signDocument($this->meeting, auth()->user(), 'photos', $this->passphrase);

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

    public function unlockForRevision(): void
    {
        $user = auth()->user();

        // Hanya admin, admin_opd, atau pegawai penyelenggara yang boleh membuka kunci
        if (!$this->canEdit || $user->hasActiveRole('pimpinan')) {
            abort(403, 'Akses tidak diizinkan. Pembukaan kunci hanya dapat dilakukan oleh penyelenggara atau admin.');
        }

        if ($this->meeting->photos_signed_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->meeting->photos_signed_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->meeting->photos_signed_path);
        }

        $this->meeting->update([
            'photos_signed_at' => null,
            'photos_signed_by' => null,
            'photos_signed_path' => null,
        ]);

        $this->meeting->refresh();
        session()->flash('message', 'Kunci dokumentasi dibuka untuk revisi.');
        $this->dispatch('meeting-updated');
    }

    public function updatedPhotos()
    {
        if (!$this->canEdit || $this->meeting->photos_signed_at) {
            $this->photos = [];
            return;
        }

        if (empty($this->photos)) {
            return;
        }

        try {
            $this->validate([
                'photos.*' => 'image|max:2048',
            ], [
                'photos.*.image' => 'File harus berupa gambar.',
                'photos.*.max' => 'Ukuran foto maksimal 2MB.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->photos = [];
            $this->uploadKey++;
            throw $e;
        }
    }

    public function savePhotos()
    {
        if (!$this->canEdit || auth()->user()->hasActiveRole('pimpinan') || $this->meeting->photos_signed_at) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $this->validate([
            'photos' => 'required',
            'photos.*' => 'image|max:2048',
        ], [
            'photos.required' => 'Pilih foto terlebih dahulu.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $photoList = is_array($this->photos) ? $this->photos : [$this->photos];

        foreach ($photoList as $photo) {
            $filename = \Str::random(40) . '.jpg';
            $path = 'meeting-photos/' . $filename;

            // Read, scale down, and encode to jpg using v4 API
            $image = $manager->decode($photo->getRealPath());

            // Scale down if width > 1200px to save space
            $image->scaleDown(width: 1200);

            // Encode as JPEG with 75% quality
            $encoded = $image->encodeUsingFileExtension('jpg', quality: 75);

            \Illuminate\Support\Facades\Storage::disk('public')->put($path, (string) $encoded);

            $this->meeting->photos()->create([
                'file' => $path,
                'uploaded_by' => auth()->id()
            ]);
        }

        $this->reset(['photos']);
        $this->uploadKey++;
        $this->meeting->refresh();
        $this->meeting->load('photos');
        $this->dispatch('meeting-updated');
        session()->flash('message', 'Foto berhasil diupload.');
    }

    public function removeTempPhoto(int $index): void
    {
        if (isset($this->photos[$index])) {
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
        }
    }

    public function deletePhoto($id)
    {
        if (!$this->canEdit || auth()->user()->hasActiveRole('pimpinan') || $this->meeting->photos_signed_at) {
            abort(403);
        }

        $photo = $this->meeting->photos()->find($id);
        if ($photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->file);
            $photo->delete();
            $this->meeting->refresh();
            $this->meeting->load('photos');
            $this->dispatch('meeting-updated');
            session()->flash('message', 'Foto berhasil dihapus.');
        }
    }

    public function with(BsreEsignService $esignService): array
    {
        return [
            'tteStatus' => $esignService->checkUserStatus(auth()->user()?->nik),
        ];
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="dokumentasi">
    @if (session()->has('message'))
    <x-alert type="success" class="mb-5">
        {{ session('message') }}
    </x-alert>
    @endif

    <div class="space-y-6">
        <!-- Action Header Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Dokumentasi Rapat</h3>
                <span class="px-2.5 py-0.5 bg-primary-50 text-primary-700 border border-primary-200/60 rounded-full text-xs font-bold">{{ $meeting->photos->count() }} Foto</span>
            </div>

            @if($meeting->photos->count() > 0)
            <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                @if(!auth()->user()->hasActiveRole('pimpinan') && !$meeting->photos_signed_at)
                    {{-- No badge needed when not signed --}}
                @elseif(auth()->user()->hasActiveRole('pimpinan') && !$meeting->photos_signed_at && $meeting->status === 'completed')
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'sign-photos-modal'); $wire.openSignModal()" class="inline-flex justify-center items-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>TTE Dokumentasi</span>
                </button>
                @endif

                @if($meeting->status === 'completed')
                    @if($meeting->photos_signed_at)
                    <a href="{{ route('meetings.export.photos', $meeting->id) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 hover:border-emerald-400 active:scale-95 text-emerald-800 rounded-xl font-bold text-sm transition-all shadow-2xs gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Lihat PDF</span>
                    </a>
                    @else
                    <a href="{{ route('meetings.export.photos', $meeting->id) }}" target="_blank" class="inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
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

        @if($meeting->photos_signed_at)
        <!-- Locked Banner & Read-Only Content (TTE Signed) -->
        <div class="flex items-center gap-2.5 p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 font-medium justify-between flex-wrap">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Dokumentasi dikunci — sudah TTE.</span>
            </div>
            @if($canEdit)
            <button wire:click="unlockForRevision"
                    wire:confirm="Buka kunci dokumentasi untuk revisi? Dokumen perlu ditandatangani ulang setelahnya."
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-sm shrink-0 self-start sm:self-auto">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
            <span>Buka Revisi</span>
            </button>
            @endif
        </div>
        @elseif($canEdit)
        <!-- Form Upload Foto (Khusus Penyelenggara / Admin - Belum TTE) -->
        <div class="bg-slate-50/70 border border-slate-200/80 p-5 sm:p-6 rounded-2xl shadow-2xs">
            <div class="flex items-center gap-2 mb-4">
                <h3 class="text-base font-extrabold text-slate-900">Upload Foto</h3>
            </div>

            <form wire:submit="savePhotos" class="space-y-4">
                <div x-data="{
                    clientError: '',
                    checkFiles(e) {
                        this.clientError = '';
                        const files = e.target.files;
                        if (!files) return;
                        for (let i = 0; i < files.length; i++) {
                            if (files[i].size > 2 * 1024 * 1024) {
                                this.clientError = 'Ukuran foto maksimal 2MB.';
                                e.target.value = '';
                                $wire.set('photos', []);
                                return false;
                            }
                        }
                    }
                }"
                    x-on:livewire-upload-error="clientError = 'Ukuran foto maksimal 2MB.'; $wire.set('photos', [])">
                    <label for="photos" class="block text-sm font-bold text-slate-700 mb-1.5">Pilih Foto</label>
                    <div class="relative">
                        <input type="file" wire:model="photos" :key="$uploadKey" id="photos" multiple accept="image/*"
                            @change="checkFiles($event)"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-white file:text-slate-700 hover:file:bg-slate-100 hover:file:text-slate-900 file:shadow-xs file:ring-1 file:ring-slate-300 file:transition-all focus:outline-none cursor-pointer bg-white rounded-xl border border-slate-300 p-1.5" required>
                    </div>
                    <div wire:loading wire:target="photos" class="mt-2 text-xs font-semibold text-primary-700 bg-primary-50 border border-primary-200 px-3.5 py-2 rounded-xl flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Memuat foto...</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium mt-1">Format JPG, PNG, WEBP (maks. 2MB per foto).</p>

                    <div x-show="clientError" x-cloak class="mt-2 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-3.5 py-2 rounded-xl flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span x-text="clientError"></span>
                    </div>

                    <template x-if="!clientError">
                        <div>
                            @error('photos') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            @error('photos.*') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </template>
                </div>

                <!-- Preview sebelum unggah -->
                @if ($photos && count($photos) > 0)
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-slate-700">Pratinjau ({{ count($photos) }} foto):</p>
                        <button type="button" wire:click="$set('photos', [])" class="text-[11px] font-bold text-rose-600 hover:text-rose-700">Batal Semua</button>
                    </div>
                    <div class="p-3 bg-white border border-slate-200 rounded-xl flex gap-3 overflow-x-auto shadow-2xs">
                        @foreach($photos as $index => $photo)
                        <div class="relative group shrink-0">
                            <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-24 object-cover rounded-xl border border-slate-200 shadow-2xs">
                            <button type="button" wire:click="removeTempPhoto({{ $index }})" class="absolute -top-1.5 -right-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-full p-1 shadow-sm transition-transform active:scale-90" title="Hapus foto ini">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex justify-end pt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="photos, savePhotos" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                        <svg wire:loading.remove wire:target="savePhotos" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <svg wire:loading wire:target="savePhotos" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Upload Foto</span>
                    </button>
                </div>
            </form>
        </div>
        @endif

        <!-- Section Galeri Foto -->
        <div>

            <!-- Galeri Grid Container with Smooth Lightbox -->
            <div id="meeting-photo-gallery"
                x-data="{
                photos: [],
                currentIndex: 0,
                isOpen: false,
                get currentUrl() {
                    return (this.photos && this.photos[this.currentIndex]) ? this.photos[this.currentIndex] : '';
                },
                updatePhotos() {
                    const gallery = document.getElementById('meeting-photo-gallery');
                    if (gallery) {
                        this.photos = Array.from(gallery.querySelectorAll('[data-photo-url]')).map(el => el.getAttribute('data-photo-url'));
                    }
                },
                open(index) {
                    this.updatePhotos();
                    this.currentIndex = index;
                    this.isOpen = true;
                },
                close() {
                    this.isOpen = false;
                },
                prev() {
                    this.updatePhotos();
                    if (!this.photos || this.photos.length === 0) return;
                    this.currentIndex = (this.currentIndex - 1 + this.photos.length) % this.photos.length;
                },
                next() {
                    this.updatePhotos();
                    if (!this.photos || this.photos.length === 0) return;
                    this.currentIndex = (this.currentIndex + 1) % this.photos.length;
                }
            }"
                x-init="updatePhotos()"
                @keydown.arrow-left.window="if (isOpen) prev()"
                @keydown.arrow-right.window="if (isOpen) next()"
                @keydown.escape.window="if (isOpen) close()">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    @forelse($meeting->photos as $index => $photo)
                    <div data-photo-url="{{ asset('storage/' . $photo->file) }}" class="relative group rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 aspect-4/3 shadow-2xs hover:shadow-md transition-shadow duration-300">
                        <img src="{{ asset('storage/' . $photo->file) }}"
                            alt="Foto Dokumentasi"
                            @click="open({{ $index }})"
                            class="w-full h-full object-cover cursor-pointer group-hover:scale-105 transition-transform duration-500">

                        @if($canEdit && !$meeting->photos_signed_at)
                        <div class="absolute top-2.5 right-2.5 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                            <button type="button"
                                @click.stop
                                wire:click="deletePhoto({{ $photo->id }})"
                                wire:confirm="Hapus foto ini?"
                                class="bg-rose-600 hover:bg-rose-700 text-white p-2 rounded-xl active:scale-95 focus:outline-none shadow-sm transition-all"
                                title="Hapus Foto">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="col-span-full py-16 text-center text-slate-500 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-2xs border border-slate-200 text-slate-400">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <p class="font-extrabold text-slate-900 text-base">Belum Ada Dokumentasi</p>
                    </div>
                    @endforelse
                </div>

                <!-- Lightbox Modal (Teleported to Body) -->
                <template x-teleport="body">
                    <div x-show="isOpen"
                        x-cloak
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/95 backdrop-blur-md select-none"
                        @click="close()">

                        <!-- Top Toolbar (Header & Close) -->
                        <div class="fixed top-0 inset-x-0 p-3 sm:p-4 flex justify-between items-center z-50 pointer-events-none">
                            <div class="pointer-events-auto bg-slate-900/80 border border-slate-700/80 backdrop-blur-md px-3 py-1 rounded-xl shadow-md">
                                <span class="text-white/90 text-xs font-bold font-mono" x-text="`Foto ${(currentIndex ?? 0) + 1} dari ${photos.length}`"></span>
                            </div>
                            <button type="button" @click.stop="close()" class="pointer-events-auto text-white/90 hover:text-white px-3 py-1.5 text-xs font-bold flex items-center gap-1.5 transition-all bg-slate-900/80 hover:bg-slate-800 border border-slate-700/80 rounded-xl backdrop-blur-md shadow-md active:scale-95 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Tutup</span>
                            </button>
                        </div>

                        <!-- Main Preview Image -->
                        <div class="w-full h-full flex items-center justify-center p-4 sm:p-8" @click.stop>
                            <img :src="currentUrl"
                                alt="Foto Dokumentasi"
                                class="max-h-[85vh] max-w-[90vw] w-auto h-auto rounded-2xl shadow-2xl object-contain ring-1 ring-white/10 transition-opacity duration-150">
                        </div>

                        <!-- Tombol Navigasi Prev -->
                        <button type="button" x-show="photos.length > 1" @click.stop="prev()" class="fixed left-3 sm:left-5 top-1/2 -translate-y-1/2 text-white/90 hover:text-white p-2.5 bg-slate-900/80 hover:bg-slate-800 rounded-xl backdrop-blur-md border border-slate-700/80 shadow-lg active:scale-90 transition-all z-50 cursor-pointer" title="Foto Sebelumnya (&larr;)">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>

                        <!-- Tombol Navigasi Next -->
                        <button type="button" x-show="photos.length > 1" @click.stop="next()" class="fixed right-3 sm:right-5 top-1/2 -translate-y-1/2 text-white/90 hover:text-white p-2.5 bg-slate-900/80 hover:bg-slate-800 rounded-xl backdrop-blur-md border border-slate-700/80 shadow-lg active:scale-90 transition-all z-50 cursor-pointer" title="Foto Selanjutnya (&rarr;)">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi TTE BSrE -->
    <x-modal name="sign-photos-modal" maxWidth="lg" :show="$showSignModal">
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
                        <span class="font-extrabold text-slate-900 text-right">Dokumentasi Rapat</span>
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
                    <label for="passphrase_photos" class="block text-sm font-bold text-slate-700 mb-1">Passphrase BSrE</label>
                    <div class="relative">
                        <input wire:model="passphrase"
                               id="passphrase_photos"
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
</x-meeting-layout>