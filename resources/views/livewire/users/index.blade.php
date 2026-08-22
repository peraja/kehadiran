<?php

use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $roleFilter = '';

    // Form modal state
    public $userId = null;
    public $name = '';
    public $nip = '';
    public $role = 'pegawai';
    public $unit_name = '';
    public $jabatan = '';

    public $isEdit = false;
    public $apiSynced = false;
    public $apiStatusMessage = '';

    public function mount()
    {
        // Restrict access to Super Admin only
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Akses khusus Super Admin.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetValidation();
        $this->reset(['userId', 'name', 'nip', 'unit_name', 'jabatan', 'apiSynced', 'apiStatusMessage']);
        $this->role = 'pegawai';

        // If current user is admin_opd, prefill unit_name
        if (auth()->user()->hasRole('admin_opd') && !auth()->user()->hasRole('admin')) {
            $this->unit_name = auth()->user()->unit_name;
        }

        $this->isEdit = false;
        $this->dispatch('open-modal', 'user-form-modal');
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $this->reset(['apiSynced', 'apiStatusMessage']);
        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->nip = $user->nip;
        $this->role = $user->roles->first()?->name ?? 'pegawai';
        $this->unit_name = $user->unit_name;
        $this->jabatan = $user->jabatan;
        $this->isEdit = true;

        $this->dispatch('open-modal', 'user-form-modal');
    }

    public function checkNipFromApi()
    {
        $this->resetValidation('nip');
        $this->apiSynced = false;
        $this->apiStatusMessage = '';

        $nip = trim($this->nip);
        if (empty($nip)) {
            $this->addError('nip', 'Silakan ketikkan NIP terlebih dahulu.');
            return;
        }

        try {
            $pegawaiResponse = Http::timeout(6)->get('http://apps.sinjaikab.go.id/api/pegawai/data_pegawai/', [
                'nip' => $nip
            ]);

            $pegawaiData = $pegawaiResponse->json();
            $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

            if ($pegawaiResponse->successful() && is_array($pData) && !empty($pData['nama'] ?? $pData['nama_pegawai'] ?? null)) {
                $this->name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $this->name;
                $this->jabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? $this->jabatan;

                $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                if ($unit_id) {
                    $unitResponse = Http::timeout(5)->get('http://apps.sinjaikab.go.id/api/pegawai/get_unit/', [
                        'unit_id' => $unit_id
                    ]);
                    $unitData = $unitResponse->json();
                    $uData = isset($unitData['data']) ? $unitData['data'] : (isset($unitData[0]) ? $unitData[0] : $unitData);
                    $this->unit_name = $uData['unit_nama'] ?? $uData['nama_unit'] ?? $uData['unit_kerja'] ?? $this->unit_name;
                }

                $this->apiSynced = true;
                $this->apiStatusMessage = 'Data pegawai berhasil disinkronkan dari API Kepegawaian Sinjai.';
            } else {
                $this->addError('nip', 'NIP tidak ditemukan dalam database API Kepegawaian Sinjai.');
            }
        } catch (\Exception $e) {
            $this->addError('nip', 'Gagal terhubung ke API Kepegawaian: ' . $e->getMessage());
        }
    }

    public function saveUser()
    {
        if ($this->isEdit) {
            $rules = [
                'name' => 'required|string|max:255',
                'nip' => 'required|string|max:30|unique:users,nip,' . $this->userId,
                'role' => 'required|in:admin,admin_opd,pegawai',
                'unit_name' => 'nullable|string|max:255',
                'jabatan' => 'nullable|string|max:255',
            ];

            // Prevent admin_opd from elevating user to admin
            if (!auth()->user()->hasRole('admin') && $this->role === 'admin') {
                $this->role = 'admin_opd';
            }

            $validated = $this->validate($rules);

            $user = User::findOrFail($this->userId);
            $user->update([
                'name' => $validated['name'],
                'nip' => $validated['nip'],
                'unit_name' => $validated['unit_name'],
                'jabatan' => $validated['jabatan'],
            ]);

            $user->syncRoles([$validated['role']]);

            session()->flash('message', 'Data pengguna berhasil diperbarui.');
        } else {
            $rules = [
                'name' => 'required|string|max:255',
                'nip' => 'required|string|max:30|unique:users,nip',
                'role' => 'required|in:admin,admin_opd,pegawai',
                'unit_name' => 'nullable|string|max:255',
                'jabatan' => 'nullable|string|max:255',
            ];

            // Prevent admin_opd from creating global admin
            if (!auth()->user()->hasRole('admin') && $this->role === 'admin') {
                $this->role = 'admin_opd';
            }

            $validated = $this->validate($rules);

            $user = User::create([
                'name' => $validated['name'],
                'nip' => $validated['nip'],
                'unit_name' => $validated['unit_name'],
                'jabatan' => $validated['jabatan'],
            ]);

            $user->assignRole($validated['role']);

            session()->flash('message', 'Pengguna baru berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', 'user-form-modal');
        $this->reset(['userId', 'name', 'nip', 'unit_name', 'jabatan', 'apiSynced', 'apiStatusMessage']);
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            return;
        }

        $user = User::findOrFail($id);
        $user->delete();
        session()->flash('message', 'Pengguna berhasil dihapus.');
    }

    public function with(): array
    {
        $query = User::with('roles')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('nip', 'like', '%' . $this->search . '%')
                        ->orWhere('unit_name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($q) {
                $q->role($this->roleFilter);
            })
            ->latest();

        $users = $query->paginate(10);

        $counts = [
            'total' => User::count(),
            'admin' => User::role('admin')->count(),
            'admin_opd' => User::role('admin_opd')->count(),
            'pegawai' => User::role('pegawai')->count(),
        ];

        return compact('users', 'counts');
    }
}; ?>

<div class="space-y-6 pb-10">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
        <div class="relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                Master Pengguna
            </h1>
            <p class="text-sm font-medium text-slate-500">
                {{ auth()->user()->hasRole('admin') ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>

        <button wire:click="openAddModal" class="relative z-10 inline-flex justify-center items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 w-full sm:w-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Pengguna Baru
        </button>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('message'))
    <x-alert type="success">
        <h3 class="font-semibold text-emerald-800">Berhasil</h3>
        <p class="text-emerald-700 mt-0.5">{{ session('message') }}</p>
    </x-alert>
    @endif

    @if (session()->has('error'))
    <x-alert type="danger">
        <h3 class="font-semibold text-rose-800">Perhatian</h3>
        <p class="text-rose-700 mt-0.5">{{ session('error') }}</p>
    </x-alert>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pengguna</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-2">{{ $counts['total'] }}</h3>
                </div>
                <div class="p-3 bg-slate-100 rounded-xl text-slate-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Super Admin</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-2">{{ $counts['admin'] }}</h3>
                </div>
                <div class="p-3 bg-purple-50 rounded-xl text-purple-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Admin OPD</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-2">{{ $counts['admin_opd'] }}</h3>
                </div>
                <div class="p-3 bg-primary-50 rounded-xl text-primary-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm hover:-translate-y-1 transition-transform duration-300">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pegawai</p>
                    <h3 class="text-3xl font-black text-slate-900 mt-2">{{ $counts['pegawai'] }}</h3>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Toolbar -->
        <div class="p-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-50/50">
            <!-- Filter Pills -->
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="$set('roleFilter','')" 
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $roleFilter === '' ? 'bg-slate-800 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
                    Semua Role
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === '' ? 'bg-slate-700 text-slate-300' : 'bg-slate-100 text-slate-500' }}">{{ $counts['total'] }}</span>
                </button>
                <button wire:click="$set('roleFilter','admin')" 
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $roleFilter === 'admin' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700' }}">
                    Admin
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'admin' ? 'bg-purple-700 text-purple-100' : 'bg-purple-100 text-purple-700' }}">{{ $counts['admin'] }}</span>
                </button>
                <button wire:click="$set('roleFilter','admin_opd')" 
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $roleFilter === 'admin_opd' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700' }}">
                    Admin OPD
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'admin_opd' ? 'bg-primary-700 text-primary-100' : 'bg-primary-100 text-primary-700' }}">{{ $counts['admin_opd'] }}</span>
                </button>
                <button wire:click="$set('roleFilter','pegawai')" 
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold transition-all {{ $roleFilter === 'pegawai' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700' }}">
                    Pegawai
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'pegawai' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-100 text-emerald-700' }}">{{ $counts['pegawai'] }}</span>
                </button>
            </div>

            <!-- Search Field -->
            <div class="w-full lg:w-80">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full rounded-xl border border-slate-200 pl-10 pr-10 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500 shadow-sm transition-colors" placeholder="Cari nama, NIP, atau OPD...">
                    @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5 bg-slate-100 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr class="text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="py-4 px-6 text-left w-1/3">Nama & NIP</th>
                        <th class="py-4 px-6 text-left">Role</th>
                        <th class="py-4 px-6 text-left">OPD & Jabatan</th>
                        <th class="py-4 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm bg-white">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <!-- Pengguna -->
                        <td class="py-4 px-6 text-left">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-50 to-primary-100 text-primary-700 font-extrabold text-sm flex items-center justify-center shrink-0 border border-primary-200 shadow-sm">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-extrabold text-slate-900 group-hover:text-primary-600 transition-colors block truncate">{{ $u->name }}</p>
                                    <div class="mt-0.5 text-xs text-slate-500 font-semibold">
                                        <span class="font-mono">{{ $u->nip ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Peran -->
                        <td class="py-4 px-6 text-left whitespace-nowrap">
                            @php
                            $roleName = $u->roles->first()?->name ?? 'pegawai';
                            @endphp
                            @if($roleName === 'admin')
                            <span class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-700 border border-purple-200 rounded-full text-[11px] font-bold uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-1.5"></span>
                                Super Admin
                            </span>
                            @elseif($roleName === 'admin_opd')
                            <span class="inline-flex items-center px-3 py-1 bg-primary-100 text-primary-700 border border-primary-200 rounded-full text-[11px] font-bold uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary-500 mr-1.5"></span>
                                Admin OPD
                            </span>
                            @else
                            <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[11px] font-bold uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                                Pegawai
                            </span>
                            @endif
                        </td>

                        <!-- OPD & Jabatan -->
                        <td class="py-4 px-6 text-left">
                            <div class="space-y-0.5">
                                <div class="text-slate-900 font-bold text-sm leading-tight">
                                    {{ $u->unit_name ?? '-' }}
                                </div>
                                <div class="text-slate-500 font-semibold text-xs">
                                    {{ $u->jabatan ?? '-' }}
                                </div>
                            </div>
                        </td>

                        <!-- Aksi -->
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openEditModal({{ $u->id }})" class="p-2 text-slate-400 hover:text-primary-700 hover:bg-primary-50 rounded-xl transition-colors" title="Edit Pengguna">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                </button>
                                @if($u->id !== auth()->id())
                                <button wire:click="deleteUser({{ $u->id }})" wire:confirm="Apakah Anda yakin ingin menghapus pengguna '{{ $u->name }}'?" class="p-2 text-slate-400 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition-colors" title="Hapus Pengguna">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                @else
                                <div class="w-9"></div> <!-- Placeholder for alignment -->
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-16 px-6 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 mb-1">Pengguna Tidak Ditemukan</h3>
                                <p class="text-sm text-slate-500 max-w-sm mx-auto">
                                    @if($search || $roleFilter)
                                    Pencarian Anda tidak menemukan hasil. Coba ubah kata kunci atau hapus filter.
                                    @else
                                    Belum ada data pengguna tambahan.
                                    @endif
                                </p>
                                @if($search || $roleFilter)
                                <button wire:click="$set('search', ''); $set('roleFilter', '')" class="mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
                                    Reset Pencarian
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 sm:px-6 sm:py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Form Tambah / Edit Pengguna -->
    <x-modal name="user-form-modal" maxWidth="lg">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-6 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    {{ $isEdit ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
                </h2>
                <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveUser" class="space-y-5">
                <!-- NIP -->
                <div>
                    <label for="nip" class="block text-sm font-bold text-slate-700 mb-1">NIP</label>
                    <div class="flex items-center gap-3">
                        <input wire:model="nip" wire:keydown.enter.prevent="checkNipFromApi" id="nip" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Ketik 18 digit NIP" />

                        <button type="button" wire:click="checkNipFromApi" wire:loading.attr="disabled" class="shrink-0 flex items-center justify-center px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-sm transition-colors shadow-sm">
                            <svg wire:loading.remove wire:target="checkNipFromApi" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <svg wire:loading wire:target="checkNipFromApi" class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="checkNipFromApi">Cek NIP</span>
                        </button>
                    </div>
                    @error('nip') <span class="text-xs text-red-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror

                    @if($apiSynced)
                    <div class="mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-emerald-900 truncate">{{ $name }}</p>
                            <p class="text-xs font-medium text-emerald-700 truncate mt-0.5">{{ $jabatan ? $jabatan . ' • ' : '' }}{{ $unit_name }}</p>
                        </div>
                        <span class="shrink-0 flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 bg-emerald-200 text-emerald-800 rounded-xl uppercase tracking-wide">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label for="name" class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input wire:model="name" id="name" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Nama lengkap pegawai / admin" required />
                    @error('name') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <!-- Peran (Role) -->
                <div>
                    <label for="role" class="block text-sm font-bold text-slate-700 mb-1">Role</label>
                    <select wire:model="role" id="role" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors appearance-none">
                        <option value="pegawai">Pegawai</option>
                        <option value="admin_opd">Admin OPD</option>
                        @if(auth()->user()->hasRole('admin'))
                        <option value="admin">Super Admin</option>
                        @endif
                    </select>
                    @error('role') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- OPD -->
                    <div>
                        <label for="unit_name" class="block text-sm font-bold text-slate-700 mb-1">OPD</label>
                        <input wire:model="unit_name" id="unit_name" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Dinas Kominfo" />
                        @error('unit_name') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Jabatan -->
                    <div>
                        <label for="jabatan" class="block text-sm font-bold text-slate-700 mb-1">Jabatan</label>
                        <input wire:model="jabatan" id="jabatan" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Staf, Kepala Seksi" />
                        @error('jabatan') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-sm transition-colors shadow-sm">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold text-sm transition-colors shadow-sm active:scale-95">
                        <span wire:loading.remove wire:target="saveUser">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</span>
                        <span wire:loading wire:target="saveUser" class="flex items-center gap-2">
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
    </x-modal>
</div>