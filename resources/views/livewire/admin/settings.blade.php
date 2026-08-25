<?php

use Livewire\Volt\Component;
use App\Models\Setting;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public string $skm_url = '';

    public function mount(): void
    {
        if (!auth()->user()->hasActiveRole('admin')) {
            abort(403, 'Akses khusus Super Admin.');
        }

        $this->skm_url = Setting::get('skm_url', 'https://skm.go.id/share/instansi/22748fb4-56a9-4101-9e6d-4145a727e0f5/1');
    }

    public function saveSettings(): void
    {
        if (!auth()->user()->hasActiveRole('admin')) {
            abort(403);
        }

        $this->validate([
            'skm_url' => 'required|url|max:500',
        ], [
            'skm_url.required' => 'Link SKM wajib diisi.',
            'skm_url.url' => 'Link SKM tidak valid.',
        ]);

        Setting::set('skm_url', trim($this->skm_url));

        session()->flash('message', 'Pengaturan berhasil disimpan.');
    }
}; ?>

<div class="space-y-6 pb-10">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Pengaturan
            </h1>
            <p class="text-sm font-medium text-slate-500">
                Pemerintah Kabupaten Sinjai
            </p>
        </div>
        <div class="relative z-10">
            <x-user-role-badge role="admin" />
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('message'))
    <x-alert type="success">
        {{ session('message') }}
    </x-alert>
    @endif

    <form wire:submit="saveSettings" class="space-y-6">
        <!-- Card: Survei Kepuasan Masyarakat (SKM) -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">
                    Survei Kepuasan Masyarakat (SKM)
                </h2>
            </div>

            <div>
                <label for="skm_url" class="block text-sm font-bold text-slate-700 mb-1">Link SKM</label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input wire:model="skm_url" id="skm_url" type="url" class="flex-1 text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-xs transition-colors" placeholder="https://skm.go.id/share/instansi/..." required />
                    @if($skm_url)
                    <a href="{{ $skm_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-slate-50 active:scale-95 text-slate-700 border border-slate-300 rounded-xl font-bold text-xs transition-all shadow-xs gap-1.5 shrink-0">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Buka Link
                    </a>
                    @endif
                </div>
                @error('skm_url') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Global Save Button -->
        <div class="flex justify-end pt-2">
            <button type="submit" wire:loading.attr="disabled" wire:target="saveSettings" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white font-bold text-sm rounded-xl shadow-sm transition-all gap-2">
                <svg wire:loading.remove wire:target="saveSettings" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg wire:loading wire:target="saveSettings" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </form>
</div>
