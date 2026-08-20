<?php

use Livewire\Volt\Component;
use App\Models\Meeting;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Meeting $meeting;
    public $photos = [];
    public $caption = '';
    public $uploadKey = 1;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function savePhotos()
    {
        $this->validate([
            'photos' => 'required',
            'photos.*' => 'image|max:10240', // 10MB Max before compression
            'caption' => 'nullable|string|max:255'
        ], [
            'photos.required' => 'Pilih setidaknya satu foto untuk diunggah.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.max' => 'Ukuran setiap gambar maksimal 10MB.',
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
                'caption' => $this->caption,
                'uploaded_by' => auth()->id()
            ]);
        }

        $this->reset(['photos', 'caption']);
        $this->uploadKey++;
        $this->meeting->refresh();
        session()->flash('message', 'Dokumentasi berhasil diunggah & dikompresi.');
    }

    public function deletePhoto($id)
    {
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
        <div class="mb-6">
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        </div>
    @endif

    <div class="mb-8 bg-gray-50 p-6 rounded-xl border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Unggah Dokumentasi Baru</h3>
        <form wire:submit="savePhotos">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="photos" value="Pilih Foto (Bisa lebih dari satu)" />
                    <input type="file" wire:model="photos" :key="$uploadKey" id="photos" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition-colors" required>
                    <x-input-error :messages="$errors->get('photos')" class="mt-2" />
                    <x-input-error :messages="$errors->get('photos.*')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="caption" value="Keterangan (Opsional)" />
                    <x-text-input wire:model="caption" id="caption" type="text" class="mt-1 block w-full" placeholder="Contoh: Sesi pemaparan materi, Penyerahan dokumen..." />
                    <x-input-error :messages="$errors->get('caption')" class="mt-2" />
                </div>
            </div>
            
            <!-- Preview before upload -->
            @if ($photos)
                <div class="mt-4 flex gap-4 overflow-x-auto py-2">
                    @foreach($photos as $photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="h-24 object-cover rounded-xl shadow-sm border border-gray-200">
                    @endforeach
                </div>
            @endif

            <div class="mt-4 flex justify-end">
                <x-primary-button wire:loading.attr="disabled" wire:target="photos, savePhotos">
                    <span wire:loading.remove wire:target="savePhotos">Unggah Foto</span>
                    <span wire:loading wire:target="savePhotos">Mengunggah...</span>
                </x-primary-button>
            </div>
        </form>
    </div>

    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900">Galeri Dokumentasi</h3>
        @if($meeting->photos->count() > 0)
            <a href="{{ route('meetings.export.photos', $meeting->id) }}" class="inline-flex items-center px-3.5 py-1.5 bg-white border border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-95 transition-all ease-in-out duration-150">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Unduh ZIP
            </a>
        @endif
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @forelse($meeting->photos as $photo)
            <div class="relative group rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                <img src="{{ asset('storage/' . $photo->file) }}" alt="{{ $photo->caption }}" class="w-full h-48 object-cover">
                
                @if($photo->caption)
                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs p-2 backdrop-blur-xs">
                        {{ $photo->caption }}
                    </div>
                @endif
                
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button wire:click="deletePhoto({{ $photo->id }})" wire:confirm="Hapus foto ini?" class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700 active:scale-95 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-gray-500 bg-white rounded-xl border border-dashed border-gray-300">
                <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <p class="font-medium text-gray-700 text-sm">Belum Ada Foto Dokumentasi</p>
                <p class="text-xs text-gray-400 mt-1">Gunakan formulir di atas untuk mengunggah foto rapat.</p>
            </div>
        @endforelse
    </div>

</x-meeting-layout>


