<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Meeting $meeting;
    public $photos = [];
    public $uploadKey = 1;
    public bool $canEdit = false;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
        $this->canEdit = auth()->user()->hasRole(['admin', 'admin_opd']) || $meeting->created_by === auth()->id();
    }

    public function updatedPhotos()
    {
        if (!$this->canEdit) {
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
        if (!$this->canEdit) {
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
        session()->flash('message', 'Foto dokumentasi berhasil diunggah.');
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
        if (!$this->canEdit) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $photo = $this->meeting->photos()->find($id);
        if ($photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->file);
            $photo->delete();
            $this->meeting->refresh();
            session()->flash('message', 'Foto berhasil dihapus.');
        }
    }
}; ?>

<x-meeting-layout :meeting="$meeting" activeTab="dokumentasi">
    @if (session()->has('message'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
        <div class="shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-emerald-800">Berhasil</h3>
            <p class="text-sm text-emerald-700 mt-0.5">{{ session('message') }}</p>
        </div>
    </div>
    @endif

    <div class="space-y-8">
        <!-- Form Unggah Foto (Khusus Penyelenggara / Admin) -->
        @if($canEdit)
            @if(in_array($meeting->status, ['ongoing', 'completed']))
            <div class="bg-slate-50/70 border border-slate-200/80 p-5 sm:p-6 rounded-2xl shadow-2xs">
                <div class="flex items-center gap-2 mb-4">
                    <h3 class="text-base font-extrabold text-slate-900">Unggah Foto</h3>
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
                                @error('photos') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                                @error('photos.*') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
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
                                <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-24 object-cover rounded-lg border border-slate-200 shadow-2xs">
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
                            <span wire:loading.remove wire:target="savePhotos" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Unggah Foto
                            </span>
                            <span wire:loading wire:target="savePhotos" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="p-6 bg-slate-50 border border-slate-200 rounded-2xl text-center">
                <div class="w-12 h-12 bg-slate-200/70 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3 class="text-slate-900 font-bold text-sm">Sesi Unggah Belum Dibuka</h3>
                <p class="text-slate-500 text-xs mt-1">Foto dokumentasi dapat diunggah setelah rapat dimulai.</p>
            </div>
            @endif
        @endif

        <!-- Section Galeri Foto -->
        <div>
            <!-- Header Galeri -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 mb-6">
                <div class="flex items-center gap-2.5">
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Galeri Dokumentasi</h3>
                    <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-xs font-bold font-mono">{{ $meeting->photos->count() }} Foto</span>
                </div>

                @if($meeting->photos->count() > 0)
                <a href="{{ route('meetings.export.photos', $meeting->id) }}" class="inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                    <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Unduh Semua Foto
                </a>
                @endif
            </div>

            <!-- Galeri Grid Container with Lightbox -->
            <div x-data="{
                previewUrl: null,
                currentIndex: 0,
                getPhotos() {
                    return Array.from(this.$el.querySelectorAll('.gallery-photo-img')).map(el => el.dataset.src);
                },
                open(url, index) {
                    if (!url) return;
                    this.currentIndex = index;
                    this.previewUrl = url;
                },
                prev() {
                    const photos = this.getPhotos();
                    if (photos.length === 0) return;
                    this.currentIndex = (this.currentIndex - 1 + photos.length) % photos.length;
                    this.previewUrl = photos[this.currentIndex];
                },
                next() {
                    const photos = this.getPhotos();
                    if (photos.length === 0) return;
                    this.currentIndex = (this.currentIndex + 1) % photos.length;
                    this.previewUrl = photos[this.currentIndex];
                }
            }"
                @keydown.arrow-left.window="if (previewUrl) prev()"
                @keydown.arrow-right.window="if (previewUrl) next()">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    @forelse($meeting->photos as $index => $photo)
                    <div class="relative group rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 aspect-4/3 shadow-2xs hover:shadow-md transition-shadow duration-300">
                        <img src="{{ asset('storage/' . $photo->file) }}"
                            data-src="{{ asset('storage/' . $photo->file) }}"
                            alt="Foto Dokumentasi"
                            @click="open('{{ asset('storage/' . $photo->file) }}', {{ $index }})"
                            class="gallery-photo-img w-full h-full object-cover cursor-pointer group-hover:scale-105 transition-transform duration-500">

                        @if($canEdit)
                        <div class="absolute top-2.5 right-2.5 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                            <button type="button"
                                @click.stop
                                wire:click="deletePhoto({{ $photo->id }})"
                                wire:confirm="Yakin ingin menghapus foto ini secara permanen?"
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

                <!-- Lightbox Modal -->
                <div x-show="previewUrl"
                    x-cloak
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 backdrop-blur-none"
                    x-transition:enter-end="opacity-100 backdrop-blur-md"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 backdrop-blur-md"
                    x-transition:leave-end="opacity-0 backdrop-blur-none"
                    @keydown.escape.window="previewUrl = null"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/90"
                    @click.self="previewUrl = null">

                    <div class="relative w-full max-w-6xl max-h-screen flex flex-col items-center justify-center"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100">

                        <!-- Tombol Tutup & Counter -->
                        <div class="absolute -top-12 right-0 left-0 flex justify-between items-center px-2">
                            <span class="text-white/80 text-xs font-mono font-bold bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700" x-text="`Foto ${currentIndex + 1} dari ${getPhotos().length}`"></span>
                            <button type="button" @click="previewUrl = null" class="text-white/80 hover:text-white px-3 py-1.5 text-xs font-bold flex items-center gap-1.5 transition-colors bg-slate-800/80 hover:bg-slate-800 rounded-xl backdrop-blur-sm border border-slate-700 shadow-md">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Tutup
                            </button>
                        </div>

                        <!-- Tombol Navigasi Prev -->
                        <button type="button" x-show="getPhotos().length > 1" @click.stop="prev()" class="absolute left-2 sm:-left-6 top-1/2 -translate-y-1/2 text-white/90 hover:text-white p-3 bg-slate-800/80 hover:bg-slate-800 rounded-2xl backdrop-blur-sm border border-slate-700 shadow-lg active:scale-95 transition-all z-10" title="Foto Sebelumnya (&larr;)">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>

                        <!-- Gambar -->
                        <template x-if="previewUrl">
                            <img :src="previewUrl" alt="Foto Dokumentasi" class="max-h-[80vh] w-auto max-w-full rounded-2xl shadow-2xl object-contain ring-1 ring-white/10">
                        </template>

                        <!-- Tombol Navigasi Next -->
                        <button type="button" x-show="getPhotos().length > 1" @click.stop="next()" class="absolute right-2 sm:-right-6 top-1/2 -translate-y-1/2 text-white/90 hover:text-white p-3 bg-slate-800/80 hover:bg-slate-800 rounded-2xl backdrop-blur-sm border border-slate-700 shadow-lg active:scale-95 transition-all z-10" title="Foto Selanjutnya (&rarr;)">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-meeting-layout>