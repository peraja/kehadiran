<?php

use Livewire\Volt\Component;
use App\Models\Opd;
use App\Models\OpdSigner;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public ?Opd $opd = null;

    public string $name = '';
    public string $unit_id = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $leader_title = '';
    public string $leader_name = '';
    public string $leader_nip = '';
    public string $leader_rank = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'admin_opd'])) {
            abort(403, 'Akses khusus Admin OPD.');
        }

        $unitName = $user->unit_name;
        $this->opd = Opd::where('name', $unitName)->first();

        if (!$this->opd && $unitName) {
            $cleanUnit = str_replace([',', '.', '-'], '', $unitName);
            $this->opd = Opd::whereRaw("REPLACE(REPLACE(REPLACE(name, ',', ''), '.', ''), '-', '') LIKE ?", ['%' . $cleanUnit . '%'])->first();
        }

        if (!$this->opd && !empty($unitName)) {
            $this->opd = Opd::create([
                'name' => $unitName,
                'is_active' => true,
            ]);
        }

        if ($this->opd) {
            // Auto sync from SIMPEG if signers table is empty and unit_id is set
            if ($this->opd->unit_id && $this->opd->signers()->count() === 0) {
                $this->opd->syncSignersFromApi();
            }

            $this->unit_id = $this->opd->unit_id ?? '';
            $this->name = $this->opd->name;
            $this->address = $this->opd->address ?? '';
            $this->phone = $this->opd->phone ?? '';
            $this->email = $this->opd->email ?? '';
            $this->leader_title = $this->opd->leader_title ?? '';
            $this->leader_name = $this->opd->leader_name ?? '';
            $this->leader_nip = $this->opd->leader_nip ?? '';
            $this->leader_rank = $this->opd->leader_rank ?? '';
        } else {
            $this->name = $unitName ?? 'OPD Saya';
        }
    }

    public function syncFromSimpeg(): void
    {
        if (!$this->opd || !$this->opd->unit_id) {
            session()->flash('error', 'Kode unit OPD belum terhubung untuk sinkronisasi SIMPEG.');
            return;
        }

        $count = $this->opd->syncSignersFromApi();

        $this->opd->refresh();
        $this->leader_name = $this->opd->leader_name ?? $this->leader_name;
        $this->leader_nip = $this->opd->leader_nip ?? $this->leader_nip;
        $this->leader_title = $this->opd->leader_title ?? $this->leader_title;
        $this->leader_rank = $this->opd->leader_rank ?? $this->leader_rank;

        session()->flash('message', "Sinkronisasi SIMPEG berhasil! Data Kepala OPD dan {$count} data pejabat penandatangan berhasil diperbarui.");
    }

    public function saveSettings(): void
    {
        $this->validate([
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'leader_title' => 'nullable|string|max:255',
            'leader_name' => 'nullable|string|max:255',
            'leader_nip' => 'nullable|string|max:50',
            'leader_rank' => 'nullable|string|max:255',
        ], [
            'email.email' => 'Format email tidak valid.',
        ]);

        if ($this->opd) {
            $this->opd->update([
                'address' => $this->address ? trim($this->address) : null,
                'phone' => $this->phone ? trim($this->phone) : null,
                'email' => $this->email ? trim($this->email) : null,
                'leader_title' => $this->leader_title ? trim($this->leader_title) : null,
                'leader_name' => $this->leader_name ? trim($this->leader_name) : null,
                'leader_nip' => $this->leader_nip ? trim($this->leader_nip) : null,
                'leader_rank' => $this->leader_rank ? trim($this->leader_rank) : null,
            ]);
        }

        session()->flash('message', 'Pengaturan profil & pimpinan OPD berhasil disimpan.');
    }

    public function with(): array
    {
        return [
            'signers' => $this->opd
                ? $this->opd->signers()
                ->orderByRaw("CASE eselon WHEN 'II.a' THEN 1 WHEN 'II.b' THEN 2 WHEN 'III.a' THEN 3 WHEN 'III.b' THEN 4 WHEN 'IV.a' THEN 5 WHEN 'IV.b' THEN 6 ELSE 7 END, id ASC")
                ->get()
                : collect(),
        ];
    }
}; ?>

<div class="space-y-6 pb-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Pengaturan OPD
            </h1>
            <p class="text-sm font-medium text-slate-500 mt-2">
                {{ $name }}
            </p>
        </div>
        <div class="relative z-10">
            <button type="button" wire:click="syncFromSimpeg" wire:loading.attr="disabled" class="inline-flex justify-center items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm shrink-0 gap-2">
                <svg wire:loading.remove wire:target="syncFromSimpeg" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="syncFromSimpeg" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="syncFromSimpeg">Sinkron SIMPEG</span>
                <span wire:loading wire:target="syncFromSimpeg">Menyinkronkan...</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('message'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
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

    @if (session()->has('error'))
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3">
        <div class="shrink-0">
            <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-rose-800">Perhatian</h3>
            <p class="text-sm text-rose-700 mt-0.5">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <form wire:submit="saveSettings" class="space-y-6">
        <!-- Card 1: Informasi OPD -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">
                    Informasi OPD
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nama OPD</label>
                    <input type="text" value="{{ $name }}" disabled class="w-full text-sm py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 font-bold cursor-not-allowed shadow-inner" />
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-bold text-slate-700 mb-1">Alamat Kantor</label>
                    <textarea wire:model="address" id="address" rows="2" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Jl. Persatuan Raya No. 1, Sinjai"></textarea>
                    @error('address') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-bold text-slate-700 mb-1">Nomor Telepon / Kontak</label>
                    <input wire:model="phone" id="phone" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: (0482) 21123" />
                    @error('phone') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-1">Email Resmi</label>
                    <input wire:model="email" id="email" type="email" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: diskominfo@sinjaikab.go.id" />
                    @error('email') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- Card 2: Kepala OPD -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">
                    Kepala OPD
                </h2>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="leader_name" class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap & Gelar</label>
                    <input wire:model="leader_name" id="leader_name" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Dr. H. Muh. Saleh, M.Si" />
                    @error('leader_name') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="leader_title" class="block text-sm font-bold text-slate-700 mb-1">Jabatan</label>
                    <input wire:model="leader_title" id="leader_title" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Kepala Dinas Kominfo" />
                    @error('leader_title') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="leader_nip" class="block text-sm font-bold text-slate-700 mb-1">NIP</label>
                        <input wire:model="leader_nip" id="leader_nip" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 197501012000031001" />
                        @error('leader_nip') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="leader_rank" class="block text-sm font-bold text-slate-700 mb-1">Pangkat</label>
                        <input wire:model="leader_rank" id="leader_rank" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Pembina Utama Muda (IV/c)" />
                        @error('leader_rank') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Daftar Pejabat / Bidang -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-slate-100 bg-white">
                <h2 class="text-lg font-bold text-slate-900">
                    Pejabat Penandatangan
                </h2>
            </div>

            @if($signers->isEmpty())
            <div class="text-center py-12 bg-slate-50/50">
                <p class="text-sm font-semibold text-slate-500">Belum ada data pejabat.</p>
                <p class="text-xs text-slate-400 mt-1">Silakan klik tombol sinkronisasi di atas.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                        <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                            <th class="py-4 px-6 text-left">Eselon</th>
                            <th class="py-4 px-6 text-left">Nama & NIP</th>
                            <th class="py-4 px-6 text-left">Jabatan & Pangkat</th>
                            <th class="py-4 px-6 text-left">Unit Kerja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-sm">
                        @foreach($signers as $signer)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-4 px-6 whitespace-nowrap">
                                @if($signer->eselon)
                                @php
                                $badgeClass = match(true) {
                                str_contains($signer->eselon, 'II') => 'bg-purple-50 text-purple-700 border-purple-200/80',
                                str_contains($signer->eselon, 'III.a') => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
                                str_contains($signer->eselon, 'III.b') => 'bg-sky-50 text-sky-700 border-sky-200/80',
                                str_contains($signer->eselon, 'IV.a') => 'bg-amber-50 text-amber-700 border-amber-200/80',
                                default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $badgeClass }}">
                                    {{ $signer->eselon }}
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    -
                                </span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-800">{{ $signer->name }}</div>
                                <div class="text-xs text-slate-400 font-mono mt-0.5">NIP. {{ $signer->nip ?: '-' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-xs font-semibold text-slate-800">{{ $signer->title }}</div>
                                @if($signer->rank)
                                <div class="text-xs text-slate-500 font-medium mt-0.5">{{ $signer->rank }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-900 block">{{ $signer->bidang_name }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <!-- Global Save Button -->
        <div class="flex justify-end pt-4 pb-8">
            <button type="submit" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white font-bold text-sm rounded-xl shadow-md transition-all gap-2">
                <span wire:loading.remove wire:target="saveSettings">Simpan Pengaturan</span>
                <span wire:loading wire:target="saveSettings" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>