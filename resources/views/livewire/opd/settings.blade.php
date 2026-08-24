<?php

use Livewire\Volt\Component;
use App\Models\Opd;
use App\Models\OpdSigner;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public ?Opd $opd = null;

    // OPD Info
    public string $name = '';
    public string $unit_id = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';

    // Signer Form Modal State
    public bool $isEditingLeader = false;
    public ?int $editingSignerId = null;
    public string $signer_name = '';
    public string $signer_nip = '';
    public string $signer_nik = '';
    public string $signer_title = '';
    public string $signer_rank = '';
    public string $signer_bidang = '';
    public string $signer_eselon = '';
    public bool $apiSynced = false;
    public string $apiStatusMessage = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user->hasActiveRole(['admin', 'admin_opd'])) {
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
            if (!$this->opd->unit_id) {
                $this->opd->resolveUnitIdFromApi();
            }

            // Auto sync from SIMPEG if signers table is empty and unit_id is set
            if ($this->opd->unit_id && $this->opd->signers()->count() === 0) {
                $this->opd->syncSignersFromApi();
            }

            $this->unit_id = $this->opd->unit_id ?? '';
            $this->name = $this->opd->name;
            $this->address = $this->opd->address ?? '';
            $this->phone = $this->opd->phone ?? '';
            $this->email = $this->opd->email ?? '';
        } else {
            $this->name = $unitName ?? 'OPD Saya';
        }
    }

    public function syncFromSimpeg(): void
    {
        if (!$this->opd) {
            session()->flash('error', 'Data OPD tidak ditemukan.');
            return;
        }

        if (!$this->opd->unit_id) {
            $this->opd->resolveUnitIdFromApi();
        }

        if (!$this->opd->unit_id) {
            session()->flash('error', 'Kode unit OPD belum terhubung ke API SIMPEG.');
            return;
        }

        $this->opd->syncSignersFromApi();
        $this->opd->refresh();

        $this->address = $this->opd->address ?? $this->address;
        $this->phone = $this->opd->phone ?? $this->phone;
        $this->email = $this->opd->email ?? $this->email;

        session()->flash('message', 'Data OPD dan Pejabat berhasil disinkronkan dari SIMPEG.');
        $this->redirect(route('opd.settings'), navigate: true);
    }

    public function saveOpdInfo(): void
    {
        $this->validate([
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
        ]);

        if ($this->opd) {
            $this->opd->update([
                'address' => $this->address ? trim($this->address) : null,
                'phone' => $this->phone ? trim($this->phone) : null,
                'email' => $this->email ? trim($this->email) : null,
            ]);
        }

        session()->flash('message', 'Informasi OPD berhasil disimpan.');
    }

    public function openEditLeaderModal(): void
    {
        $this->resetValidation();
        $this->apiSynced = false;
        $this->apiStatusMessage = '';
        $this->isEditingLeader = true;
        $this->editingSignerId = null;

        $this->signer_name = $this->opd->leader_name ?? '';
        $this->signer_nip = $this->opd->leader_nip ?? '';
        $this->signer_nik = $this->opd->leader_nik ?? '';
        $this->signer_title = $this->opd->leader_title ?? '';
        $this->signer_rank = $this->opd->leader_rank ?? '';
        $this->signer_bidang = $this->opd->name;
        $this->signer_eselon = $this->opd->leader_eselon ?: 'II.a';

        $this->dispatch('open-modal', 'signer-form-modal');
    }

    public function openEditSignerModal(int $id): void
    {
        $this->resetValidation();
        $this->apiSynced = false;
        $this->apiStatusMessage = '';
        $this->isEditingLeader = false;

        $signer = OpdSigner::where('opd_id', $this->opd->id)->findOrFail($id);
        $this->editingSignerId = $signer->id;

        $this->signer_name = $signer->name;
        $this->signer_nip = $signer->nip ?? '';
        $this->signer_nik = $signer->nik ?? '';
        $this->signer_title = $signer->title ?? '';
        $this->signer_rank = $signer->rank ?? '';
        $this->signer_bidang = $signer->bidang_name ?? '';
        $this->signer_eselon = $signer->eselon ?? 'III.a';

        $this->dispatch('open-modal', 'signer-form-modal');
    }

    public function checkNipFromApi(): void
    {
        $this->resetValidation('signer_nip');
        $this->apiSynced = false;
        $this->apiStatusMessage = '';

        $nip = trim($this->signer_nip);
        if (empty($nip)) {
            $this->addError('signer_nip', 'Ketikkan NIP terlebih dahulu.');
            return;
        }

        $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
        $timeout = config('services.simpeg.timeout', 10);

        try {
            $pegawaiResponse = \Illuminate\Support\Facades\Http::timeout($timeout)->get("{$baseUrl}/data_pegawai/", [
                'nip' => $nip
            ]);

            $pegawaiData = $pegawaiResponse->json();
            $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

            if ($pegawaiResponse->successful() && is_array($pData) && !empty($pData['nama'] ?? $pData['nama_pegawai'] ?? null)) {
                $this->signer_name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $this->signer_name;
                $this->signer_title = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? $this->signer_title;
                $this->signer_rank = $pData['pangkat_nama'] ?? $this->signer_rank;

                $nik = trim((string)($pData['nik'] ?? ($pData['no_ktp'] ?? ($pData['ktp'] ?? ($pData['no_identitas'] ?? '')))));
                if ($nik) {
                    $this->signer_nik = $nik;
                }

                $eselon = trim((string)($pData['jabatan_jenis_eselon'] ?? ($pData['eselon'] ?? '')));
                if ($eselon) {
                    $this->signer_eselon = $eselon;
                }

                if (!$this->isEditingLeader) {
                    $this->signer_bidang = Opd::cleanBidangName($this->signer_title);
                }

                $this->apiSynced = true;
                $this->apiStatusMessage = 'Data pegawai berhasil disinkronkan dari SIMPEG.';
            } else {
                $this->addError('signer_nip', 'NIP tidak ditemukan di database SIMPEG Sinjai.');
            }
        } catch (\Exception $e) {
            $this->addError('signer_nip', 'Gagal terhubung ke API SIMPEG: ' . $e->getMessage());
        }
    }

    public function saveSigner(): void
    {
        $this->validate([
            'signer_name' => 'required|string|max:255',
            'signer_title' => 'required|string|max:255',
            'signer_nip' => 'nullable|string|max:50',
            'signer_nik' => 'nullable|string|max:16',
            'signer_rank' => 'nullable|string|max:255',
            'signer_bidang' => 'nullable|string|max:255',
            'signer_eselon' => 'nullable|string|max:50',
        ], [
            'signer_name.required' => 'Nama lengkap wajib diisi.',
            'signer_title.required' => 'Jabatan wajib diisi.',
        ]);

        if ($this->isEditingLeader) {
            $this->opd->update([
                'leader_name' => trim($this->signer_name),
                'leader_nip' => $this->signer_nip ? trim($this->signer_nip) : null,
                'leader_nik' => $this->signer_nik ? trim($this->signer_nik) : null,
                'leader_title' => trim($this->signer_title),
                'leader_rank' => $this->signer_rank ? trim($this->signer_rank) : null,
                'leader_eselon' => $this->signer_eselon ? trim($this->signer_eselon) : null,
            ]);
            $this->opd->refresh();
            session()->flash('message', 'Data Kepala OPD berhasil diperbarui.');
        } elseif ($this->editingSignerId) {
            $signer = OpdSigner::where('opd_id', $this->opd->id)->findOrFail($this->editingSignerId);
            $signer->update([
                'name' => trim($this->signer_name),
                'nip' => $this->signer_nip ? trim($this->signer_nip) : null,
                'nik' => $this->signer_nik ? trim($this->signer_nik) : null,
                'title' => trim($this->signer_title),
                'rank' => $this->signer_rank ? trim($this->signer_rank) : null,
                'bidang_name' => $this->signer_bidang ? trim($this->signer_bidang) : null,
                'eselon' => $this->signer_eselon ? trim($this->signer_eselon) : null,
            ]);
            session()->flash('message', 'Data Pejabat Penandatangan berhasil diperbarui.');
        }

        $this->dispatch('close-modal', 'signer-form-modal');
    }

    public function deleteSigner(int $id): void
    {
        $signer = OpdSigner::where('opd_id', $this->opd->id)->findOrFail($id);
        $signer->delete();
        session()->flash('message', 'Pejabat Penandatangan berhasil dihapus.');
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
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Pengaturan OPD
            </h1>
            <p class="text-sm font-medium text-slate-500">
                {{ $name }}
            </p>
        </div>
        <button type="button" wire:click="syncFromSimpeg" wire:loading.attr="disabled" class="relative z-10 inline-flex justify-center items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm shrink-0 gap-2">
            <svg wire:loading.remove wire:target="syncFromSimpeg" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg wire:loading wire:target="syncFromSimpeg" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>Sinkron SIMPEG</span>
        </button>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('message'))
    <x-alert type="success">
        {{ session('message') }}
    </x-alert>
    @endif

    @if (session()->has('error'))
    <x-alert type="danger">
        {{ session('error') }}
    </x-alert>
    @endif

    <!-- Card 1: Informasi OPD -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-5">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900">
                Informasi OPD
            </h2>
        </div>

        <form wire:submit="saveOpdInfo" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nama OPD</label>
                    <input type="text" value="{{ $name }}" disabled class="w-full text-sm py-2.5 px-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 font-bold cursor-not-allowed shadow-inner" />
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-bold text-slate-700 mb-1">Alamat</label>
                    <textarea wire:model="address" id="address" rows="2" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Alamat kantor"></textarea>
                    @error('address') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-bold text-slate-700 mb-1">Telepon</label>
                    <input wire:model="phone" id="phone" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Nomor telepon" />
                    @error('phone') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-1">Email</label>
                    <input wire:model="email" id="email" type="email" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="email@sinjaikab.go.id" />
                    @error('email') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveOpdInfo" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white font-bold text-sm rounded-xl shadow-sm transition-all gap-2">
                    <svg wire:loading.remove wire:target="saveOpdInfo" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg wire:loading wire:target="saveOpdInfo" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Simpan Informasi</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Card 2: Pejabat Penandatangan -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8 border-b border-slate-100 bg-white">
            <h2 class="text-lg font-bold text-slate-900">
                Pejabat Penandatangan
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="py-4 px-6 text-left w-28">Eselon</th>
                        <th class="py-4 px-6 text-left">Nama & NIP</th>
                        <th class="py-4 px-6 text-left">Jabatan & Pangkat</th>
                        <th class="py-4 px-6 text-left">Unit Kerja</th>
                        <th class="py-4 px-6 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-sm">
                    <!-- Baris Utama: Kepala OPD -->
                    <tr class="bg-primary-50/20 hover:bg-primary-50/40 transition-colors">
                        <td class="py-4 px-6 whitespace-nowrap">
                            @if($opd->leader_eselon)
                            @php
                            $badgeClass = match(true) {
                            str_contains($opd->leader_eselon, 'II') => 'bg-purple-50 text-purple-700 border-purple-200/80',
                            str_contains($opd->leader_eselon, 'III.a') => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
                            str_contains($opd->leader_eselon, 'III.b') => 'bg-sky-50 text-sky-700 border-sky-200/80',
                            str_contains($opd->leader_eselon, 'IV') => 'bg-amber-50 text-amber-700 border-amber-200/80',
                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                            };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeClass }}">
                                {{ $opd->leader_eselon }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                -
                            </span>
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-extrabold text-slate-900">
                                {{ $opd->leader_name ?: '-' }}
                            </div>
                            <div class="text-xs text-slate-500 font-mono mt-0.5 font-semibold">
                                NIP. {{ $opd->leader_nip ?: '-' }}
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-xs font-semibold text-slate-800">{{ $opd->leader_title ?: '-' }}</div>
                            @if($opd->leader_rank)
                            <div class="text-xs text-slate-500 font-medium mt-0.5">{{ $opd->leader_rank }}</div>
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            <span class="font-bold text-slate-800 text-xs block">{{ $opd->name }}</span>
                        </td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">
                            <button type="button" wire:click="openEditLeaderModal" class="inline-flex items-center gap-1 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                <span>Edit</span>
                            </button>
                        </td>
                    </tr>

                    <!-- Baris Pejabat Penandatangan Lainnya -->
                    @forelse($signers as $signer)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-4 px-6 whitespace-nowrap">
                            @if($signer->eselon)
                            @php
                            $badgeClass = match(true) {
                            str_contains($signer->eselon, 'II') => 'bg-purple-50 text-purple-700 border-purple-200/80',
                            str_contains($signer->eselon, 'III.a') => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
                            str_contains($signer->eselon, 'III.b') => 'bg-sky-50 text-sky-700 border-sky-200/80',
                            str_contains($signer->eselon, 'IV') => 'bg-amber-50 text-amber-700 border-amber-200/80',
                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                            };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeClass }}">
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
                            <span class="font-bold text-slate-800 text-xs block">{{ $signer->bidang_name ?: '-' }}</span>
                        </td>
                        <td class="py-4 px-6 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" wire:click="openEditSignerModal({{ $signer->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95" title="Edit">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span>Edit</span>
                                </button>
                                <button type="button" wire:click="deleteSigner({{ $signer->id }})" wire:confirm="Hapus pejabat penandatangan ini?" class="inline-flex items-center justify-center p-1.5 bg-white hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200 hover:border-rose-200 rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95" title="Hapus">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Edit / Tambah Pejabat -->
    <x-modal name="signer-form-modal" maxWidth="2xl">
        <div class="p-6 sm:p-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">
                        {{ $isEditingLeader ? 'Edit Kepala OPD' : 'Edit Pejabat Penandatangan' }}
                    </h2>
                </div>
                <button type="button" x-on:click="$dispatch('close-modal', 'signer-form-modal')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="saveSigner" class="space-y-4">
                <!-- Nama Lengkap (Paling Atas) -->
                <div>
                    <label for="signer_name" class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input wire:model="signer_name" id="signer_name" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Dr. H. Ahmad Yani, M.Si" required />
                    @error('signer_name') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- NIP dengan Cek SIMPEG -->
                    <div>
                        <label for="signer_nip" class="block text-sm font-bold text-slate-700 mb-1">NIP</label>
                        <div class="flex items-center gap-2">
                            <input wire:model="signer_nip" wire:keydown.enter.prevent="checkNipFromApi" id="signer_nip" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 198501012010011001" />

                            <button type="button" wire:click="checkNipFromApi" wire:loading.attr="disabled" wire:target="checkNipFromApi" class="shrink-0 inline-flex items-center justify-center px-3 py-2.5 bg-slate-800 hover:bg-slate-900 active:scale-95 text-white rounded-xl font-bold text-xs transition-all shadow-sm gap-1.5" title="Tarik dari SIMPEG">
                                <svg wire:loading.remove wire:target="checkNipFromApi" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <svg wire:loading wire:target="checkNipFromApi" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Cek</span>
                            </button>
                        </div>
                        @error('signer_nip') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- NIK -->
                    <div>
                        <label for="signer_nik" class="block text-sm font-bold text-slate-700 mb-1">NIK</label>
                        <input wire:model="signer_nik" id="signer_nik" type="text" maxlength="16" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 7307010101850001" />
                        @error('signer_nik') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- API Status Feedback -->
                @if($apiSynced)
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-3">
                    <div class="min-w-0 space-y-0.5">
                        <p class="text-sm font-bold text-emerald-900 truncate">{{ $signer_name }}</p>
                        @if($signer_title)
                        <p class="text-xs font-semibold text-emerald-800 truncate">{{ $signer_title }}</p>
                        @endif
                        @if($signer_bidang)
                        <p class="text-xs font-medium text-emerald-600 truncate">{{ $signer_bidang }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 flex items-center justify-center w-7 h-7 bg-emerald-500 text-white rounded-xl shadow-xs" title="Terverifikasi SIMPEG">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Eselon -->
                    <div>
                        <label for="signer_eselon" class="block text-sm font-bold text-slate-700 mb-1">Eselon</label>
                        <select wire:model="signer_eselon" id="signer_eselon" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors">
                            <option value="II.a">Eselon II.a</option>
                            <option value="II.b">Eselon II.b</option>
                            <option value="III.a">Eselon III.a</option>
                            <option value="III.b">Eselon III.b</option>
                            <option value="IV.a">Eselon IV.a</option>
                            <option value="IV.b">Eselon IV.b</option>
                            <option value="Non-Eselon">Non-Eselon</option>
                        </select>
                        @error('signer_eselon') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Pangkat / Golongan -->
                    <div>
                        <label for="signer_rank" class="block text-sm font-bold text-slate-700 mb-1">Pangkat</label>
                        <input wire:model="signer_rank" id="signer_rank" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Pembina (IV/a)" />
                        @error('signer_rank') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Jabatan -->
                <div>
                    <label for="signer_title" class="block text-sm font-bold text-slate-700 mb-1">Jabatan</label>
                    <input wire:model="signer_title" id="signer_title" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Kepala Bidang Hubungan Masyarakat" required />
                    @error('signer_title') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                @if(!$isEditingLeader)
                <!-- Unit Kerja -->
                <div>
                    <label for="signer_bidang" class="block text-sm font-bold text-slate-700 mb-1">Unit Kerja</label>
                    <input wire:model="signer_bidang" id="signer_bidang" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Bidang Informasi dan Komunikasi Publik" />
                    @error('signer_bidang') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                @endif

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close-modal', 'signer-form-modal')" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveSigner" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                        <svg wire:loading.remove wire:target="saveSigner" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="saveSigner" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>