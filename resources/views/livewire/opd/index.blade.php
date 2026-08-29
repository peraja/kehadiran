<?php

use App\Models\Opd;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public ?int $opdId = null;

    public string $unit_id = '';
    public string $name = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $leader_name = '';
    public string $leader_rank = '';
    public string $leader_nip = '';
    public string $leader_title = '';
    public bool $is_active = true;

    public bool $isSyncing = false;
    public int $syncTotal = 0;
    public int $syncCurrent = 0;
    public string $syncCurrentName = '';
    public array $syncQueue = [];

    public function mount(): void
    {
        if (!auth()->user()->hasActiveRole('admin')) {
            abort(403, 'Akses khusus Super Admin.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset(['opdId', 'unit_id', 'name', 'address', 'phone', 'email', 'leader_name', 'leader_rank', 'leader_nip', 'leader_title', 'is_active']);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function editOpd(int $id): void
    {
        $this->resetForm();
        $opd = Opd::findOrFail($id);

        $this->opdId = $opd->id;
        $this->unit_id = (string)($opd->unit_id ?? '');
        $this->name = $opd->name ?? '';
        $this->address = $opd->address ?? '';
        $this->phone = $opd->phone ?? '';
        $this->email = $opd->email ?? '';
        $this->leader_name = $opd->leader_name ?? '';
        $this->leader_rank = $opd->leader_rank ?? '';
        $this->leader_nip = $opd->leader_nip ?? '';
        $this->leader_title = $opd->leader_title ?? '';
        $this->is_active = (bool)$opd->is_active;

        $this->dispatch('open-modal', 'opd-form-modal');
    }

    public function saveOpd(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'unit_id' => 'nullable|string|max:50|unique:opds,unit_id,' . ($this->opdId ?: 'NULL'),
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'leader_name' => 'nullable|string|max:255',
            'leader_rank' => 'nullable|string|max:255',
            'leader_nip' => 'nullable|string|max:50',
            'leader_title' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];

        $messages = [
            'name.required' => 'Nama OPD wajib diisi.',
            'unit_id.unique' => 'Kode Unit ID sudah digunakan oleh OPD lain.',
            'email.email' => 'Format email tidak valid.',
        ];

        $validated = $this->validate($rules, $messages);

        if ($this->opdId) {
            $opd = Opd::findOrFail($this->opdId);
            $opd->update([
                'name' => trim($validated['name']),
                'unit_id' => $validated['unit_id'] ? trim($validated['unit_id']) : null,
                'address' => $validated['address'] ? trim($validated['address']) : null,
                'phone' => $validated['phone'] ? trim($validated['phone']) : null,
                'email' => $validated['email'] ? trim($validated['email']) : null,
                'leader_name' => $validated['leader_name'] ? trim($validated['leader_name']) : null,
                'leader_rank' => $validated['leader_rank'] ? trim($validated['leader_rank']) : null,
                'leader_nip' => $validated['leader_nip'] ? trim($validated['leader_nip']) : null,
                'leader_title' => $validated['leader_title'] ? trim($validated['leader_title']) : null,
                'is_active' => (bool)$validated['is_active'],
            ]);

            session()->flash('message', 'Data OPD berhasil diperbarui.');
        }

        $this->dispatch('close-modal', 'opd-form-modal');
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $opd = Opd::findOrFail($id);
        $opd->update(['is_active' => !$opd->is_active]);

        session()->flash('message', 'Status OPD berhasil diubah.');
    }

    public function deleteOpd(int $id): void
    {
        $opd = Opd::findOrFail($id);
        $opd->delete();

        session()->flash('message', 'OPD berhasil dihapus.');
    }

    public function startSync(): void
    {
        if ($this->isSyncing) {
            return;
        }

        $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
        $timeout = config('services.simpeg.timeout', 15);

        try {
            $units = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::timeout($timeout)->get("{$baseUrl}/get_unit");
                if ($response->successful()) {
                    $data = $response->json();
                    if (is_array($data) && !isset($data['error'])) {
                        $units = $data;
                        break;
                    }
                }
                if ($attempt < 3) {
                    sleep(2);
                }
            }

            if (!is_array($units)) {
                session()->flash('error', 'Gagal menghubungi API SIMPEG.');
                return;
            }

            $queue = [];
            foreach ($units as $unit) {
                $cleaned = Opd::cleanAndFormatData($unit);
                if (!empty($cleaned['name'])) {
                    $opd = Opd::updateOrCreate(
                        ['unit_id' => $cleaned['unit_id'] ?? null],
                        $cleaned
                    );
                    if ($opd->unit_id && $opd->unit_id !== '7307') {
                        $queue[] = [
                            'id' => $opd->id,
                            'unit_id' => $opd->unit_id,
                            'name' => $opd->name,
                        ];
                    }
                }
            }

            $this->syncQueue = $queue;
            $this->syncTotal = count($queue);
            $this->syncCurrent = 0;
            $this->syncCurrentName = 'Menyiapkan...';
            $this->isSyncing = true;

            $this->dispatch('open-modal', 'sync-progress-modal');
            $this->dispatch('trigger-next-sync');
        } catch (\Throwable $e) {
            $this->dispatch('close-modal', 'sync-progress-modal');
            session()->flash('error', 'Gagal memulai sinkronisasi.');
        }
    }

    public function syncNextUnit(): void
    {
        if (!$this->isSyncing || empty($this->syncQueue)) {
            $this->isSyncing = false;
            $this->syncQueue = [];
            $this->dispatch('close-modal', 'sync-progress-modal');
            session()->flash('message', 'Data OPD berhasil disinkronkan.');
            return;
        }

        $item = array_shift($this->syncQueue);
        $this->syncCurrentName = $item['name'];
        $this->syncCurrent++;

        $opd = Opd::find($item['id']);
        if ($opd) {
            $opd->syncSignersFromApi();
        }

        if (!empty($this->syncQueue)) {
            $this->dispatch('trigger-next-sync');
        } else {
            $this->isSyncing = false;
            $this->dispatch('close-modal', 'sync-progress-modal');
            session()->flash('message', 'Data OPD berhasil disinkronkan.');
        }
    }

    public function with(): array
    {
        $query = Opd::query();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('unit_id', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $opds = $query->orderBy('name', 'asc')->paginate(15);

        $countRow = Opd::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN address IS NOT NULL AND address != '' THEN 1 ELSE 0 END) as has_address,
            SUM(CASE WHEN (phone IS NOT NULL AND phone != '') OR (email IS NOT NULL AND email != '') THEN 1 ELSE 0 END) as has_contact
        ")->first();

        $counts = [
            'total' => (int) ($countRow->total ?? 0),
            'active' => (int) ($countRow->active ?? 0),
            'has_address' => (int) ($countRow->has_address ?? 0),
            'has_contact' => (int) ($countRow->has_contact ?? 0),
        ];

        return compact('opds', 'counts');
    }
}; ?>

<div x-data x-on:trigger-next-sync.window="setTimeout(() => { $wire.syncNextUnit() }, 300)" class="space-y-6 pb-10">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Master OPD
            </h1>
            <p class="text-sm font-medium text-slate-500">
                {{ auth()->user()->hasActiveRole('admin') ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>

        <div class="relative z-10 w-full sm:w-auto">
            <button wire:click="startSync" wire:loading.attr="disabled" :disabled="$wire.isSyncing" class="w-full sm:w-auto inline-flex justify-center items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 cursor-pointer">
                <svg wire:loading.remove wire:target="startSync" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="startSync" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Sinkron SIMPEG</span>
            </button>
        </div>
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

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Toolbar -->
        <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-center">
                <!-- Filter Pills -->
                <div class="lg:col-span-6 xl:col-span-7 grid grid-cols-3 gap-2 w-full" x-data="{ currentFilter: @entangle('statusFilter').live }">
                    <button @click="currentFilter = ''"
                        :class="currentFilter === '' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900'"
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-2 sm:px-4 rounded-xl text-sm font-bold transition-all cursor-pointer">
                        Semua
                        <span :class="currentFilter === '' ? 'bg-slate-800 text-slate-300' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold">{{ $counts['total'] }}</span>
                    </button>
                    <button @click="currentFilter = 'active'"
                        :class="currentFilter === 'active' ? 'bg-primary-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700'"
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-2 sm:px-4 rounded-xl text-sm font-bold transition-all cursor-pointer">
                        <span :class="currentFilter === 'active' ? 'bg-primary-200' : 'bg-primary-500'" class="w-1.5 h-1.5 rounded-full"></span>
                        Aktif
                        <span :class="currentFilter === 'active' ? 'bg-primary-700 text-primary-100' : 'bg-primary-100 text-primary-700'" class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold">{{ $counts['active'] }}</span>
                    </button>
                    <button @click="currentFilter = 'inactive'"
                        :class="currentFilter === 'inactive' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700'"
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-2 sm:px-4 rounded-xl text-sm font-bold transition-all cursor-pointer">
                        <span :class="currentFilter === 'inactive' ? 'bg-rose-200' : 'bg-rose-500'" class="w-1.5 h-1.5 rounded-full"></span>
                        Nonaktif
                        <span :class="currentFilter === 'inactive' ? 'bg-rose-700 text-rose-100' : 'bg-rose-100 text-rose-700'" class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold">{{ $counts['total'] - $counts['active'] }}</span>
                    </button>
                </div>

                <!-- Search Field & Reset -->
                <div class="lg:col-span-6 xl:col-span-5 grid {{ $statusFilter ? 'grid-cols-[1fr_auto]' : 'grid-cols-1' }} gap-2.5 w-full">
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            class="block w-full h-10 rounded-xl border border-slate-200 pl-9 pr-9 py-2 text-base sm:text-sm focus:border-primary-500 focus:ring-primary-500 shadow-2xs transition-colors bg-white placeholder:text-slate-400"
                            placeholder="Cari nama OPD atau kode unit...">
                        @if($search)
                        <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors" title="Hapus pencarian">
                            <svg class="w-4 h-4 bg-slate-100 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        @endif
                    </div>

                    @if($statusFilter)
                    <button wire:click="resetFilters"
                        class="h-10 inline-flex items-center justify-center gap-1.5 px-3.5 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 active:scale-95 transition-all border border-rose-200/80 shadow-2xs cursor-pointer shrink-0"
                        title="Reset semua filter">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto min-h-[400px] rounded-2xl">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="py-4 px-6 text-left w-28">Kode Unit</th>
                        <th class="py-4 px-6 text-left">Nama & Kepala OPD</th>
                        <th class="py-4 px-6 text-left">Alamat & Kontak</th>
                        <th class="py-4 px-6 text-center w-32">Status</th>
                        <th class="py-4 px-6 text-right w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-slate-100 text-sm bg-white transition-opacity duration-200">
                    @forelse($opds as $opd)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <!-- Kode Unit -->
                        <td class="py-4 px-6 text-left whitespace-nowrap">
                            <span class="font-mono font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full text-xs">
                                {{ $opd->unit_id ?? '-' }}
                            </span>
                        </td>

                        <!-- Nama & Kepala OPD -->
                        <td class="py-4 px-6 text-left">
                            <div class="font-extrabold text-slate-900 group-hover:text-primary-600 transition-colors">
                                {{ $opd->name }}
                            </div>
                            @if($opd->leader_name)
                            <div class="text-xs text-slate-500 font-medium mt-1">
                                <span class="text-slate-700 font-semibold">{{ $opd->leader_name }}</span>
                            </div>
                            @endif
                        </td>

                        <!-- Alamat & Kontak -->
                        <td class="py-4 px-6 text-left">
                            <div class="text-xs text-slate-700 font-medium line-clamp-2">
                                {{ $opd->address ?: '-' }}
                            </div>
                            @if($opd->phone || $opd->email)
                            <div class="text-xs text-slate-400 font-medium mt-1">
                                {{ collect([$opd->phone, $opd->email])->filter()->join(' • ') }}
                            </div>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-6 text-center whitespace-nowrap">
                            <button type="button" wire:click="toggleStatus({{ $opd->id }})" class="cursor-pointer focus:outline-none rounded-full transition-all active:scale-95">
                                @if($opd->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold hover:bg-emerald-100 transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Aktif
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-xs font-bold hover:bg-slate-200 transition-colors">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                                    Nonaktif
                                </span>
                                @endif
                            </button>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1">
                                <button x-data="" 
                                    x-on:click="
                                        $wire.opdId = {{ $opd->id }};
                                        $wire.unit_id = @js((string)($opd->unit_id ?? ''));
                                        $wire.name = @js($opd->name ?? '');
                                        $wire.address = @js($opd->address ?? '');
                                        $wire.phone = @js($opd->phone ?? '');
                                        $wire.email = @js($opd->email ?? '');
                                        $wire.leader_name = @js($opd->leader_name ?? '');
                                        $wire.leader_rank = @js($opd->leader_rank ?? '');
                                        $wire.leader_nip = @js($opd->leader_nip ?? '');
                                        $wire.leader_title = @js($opd->leader_title ?? '');
                                        $wire.is_active = {{ $opd->is_active ? 'true' : 'false' }};
                                        $dispatch('open-modal', 'opd-form-modal');
                                    "
                                    class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl active:scale-95 transition-all cursor-pointer" 
                                    title="Edit OPD">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                <button wire:click="deleteOpd({{ $opd->id }})" wire:confirm="Hapus OPD {{ $opd->name }}?" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl active:scale-95 transition-all" title="Hapus OPD">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 px-6 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3 text-slate-400">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900">Tidak Ada Data OPD</h3>
                                @if($search || $statusFilter)
                                <button type="button" wire:click="resetFilters" class="mt-3 px-4 py-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                    Reset Filter
                                </button>
                                @else
                                <button wire:click="startSync" wire:loading.attr="disabled" :disabled="$wire.isSyncing" class="mt-3 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-sm gap-2">
                                    <svg wire:loading.remove wire:target="startSync" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg wire:loading wire:target="startSync" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Sinkron SIMPEG</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$opds" />
    </div>

    <!-- Modal Edit OPD -->
    <x-modal name="opd-form-modal" maxWidth="4xl">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-6 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    Edit OPD
                </h2>
                <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveOpd" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <!-- Kolom Kiri: Instansi & Kontak -->
                    <div class="space-y-4">
                        <div class="pb-1 border-b border-slate-100">
                            <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Informasi OPD</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="unit_id" class="block text-xs font-bold text-slate-700 mb-1">Kode Unit</label>
                                <input wire:model="unit_id" id="unit_id" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 730701" />
                                @error('unit_id') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">Nama OPD</label>
                                <input wire:model="name" id="name" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Dinas Komunikasi dan Informatika" required />
                                @error('name') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-xs font-bold text-slate-700 mb-1">Alamat Kantor</label>
                            <textarea wire:model="address" id="address" rows="2" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors resize-none" placeholder="Contoh: Jl. Persatuan Raya No. 1, Sinjai"></textarea>
                            @error('address') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="phone" class="block text-xs font-bold text-slate-700 mb-1">Telepon</label>
                                <input wire:model="phone" id="phone" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: (0482) 21123" />
                                @error('phone') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                                <input wire:model="email" id="email" type="email" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: diskominfo@sinjaikab.go.id" />
                                @error('email') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class=" cursor-pointer select-none">
                                <input wire:model="is_active" type="checkbox" id="is_active" class="w-4 h-4 rounded-md border-slate-300 text-primary-600 focus:ring-primary-500 transition-colors">
                                <span class="text-xs font-bold text-slate-700">Status OPD Aktif</span>
                            </label>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Kepala OPD -->
                    <div class="space-y-4 md:pl-6 md:border-l md:border-slate-100">
                        <div class="pb-1 border-b border-slate-100">
                            <h3 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Kepala OPD</h3>
                        </div>

                        <div>
                            <label for="leader_name" class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap & Gelar</label>
                            <input wire:model="leader_name" id="leader_name" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Dr. H. Muh. Saleh, M.Si" />
                            @error('leader_name') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="leader_title" class="block text-xs font-bold text-slate-700 mb-1">Jabatan</label>
                            <input wire:model="leader_title" id="leader_title" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Kepala Dinas Kominfo" />
                            @error('leader_title') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="leader_nip" class="block text-xs font-bold text-slate-700 mb-1">NIP</label>
                            <input wire:model="leader_nip" id="leader_nip" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 197501012000031001" />
                            @error('leader_nip') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="leader_rank" class="block text-xs font-bold text-slate-700 mb-1">Pangkat</label>
                            <input wire:model="leader_rank" id="leader_rank" type="text" class="w-full text-base sm:text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Pembina Utama Muda (IV/c)" />
                            @error('leader_rank') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-5 border-t border-slate-100">
                    <button type="button" x-on:click="$dispatch('close')" class="w-full sm:w-auto px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveOpd" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                        <svg wire:loading.remove wire:target="saveOpd" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="saveOpd" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Progress Modal Saat Sinkronisasi -->
    @if($isSyncing)
    <div class="fixed inset-0 z-[150] overflow-hidden" role="dialog" aria-modal="true">
        <!-- Modal Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"></div>

        <!-- Centering & Viewport Boundary Wrapper -->
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4 sm:p-6 text-center">
            <!-- Modal Content Card with Internal Scroll -->
            <div class="relative w-full max-w-md transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl border border-slate-200/80 transition-all p-6 sm:p-8 text-center space-y-5 animate-in zoom-in-95 duration-150">
                <div class="w-14 h-14 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center mx-auto shadow-inner">
                    <svg class="animate-spin w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>

                <div>
                    <h3 class="text-base font-extrabold text-slate-900 mb-1">
                        Sinkronisasi SIMPEG
                    </h3>
                    <p class="text-xs font-semibold text-primary-600 truncate px-4">
                        {{ $syncCurrentName }}
                    </p>
                </div>

                <!-- Progress Bar -->
                @php
                    $pct = $syncTotal > 0 ? min(100, round(($syncCurrent / $syncTotal) * 100)) : 0;
                @endphp
                <div class="space-y-2">
                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-200">
                        <div class="bg-primary-600 h-1.5 rounded-full transition-all duration-300 ease-out" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 px-1">
                        <span>{{ $syncCurrent }} / {{ $syncTotal }}</span>
                        <span class="text-primary-700 font-extrabold">{{ $pct }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>