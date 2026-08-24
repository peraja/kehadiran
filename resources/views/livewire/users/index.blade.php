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
    public $userId;
    public $name = '';
    public $nip = '';
    public $nik = '';
    public array $roles = ['pegawai'];
    public $password = '';
    public $unit_name = '';
    public $jabatan = '';
    public $pangkat = '';

    public $isEdit = false;
    public $apiSynced = false;
    public $apiStatusMessage = '';

    public function mount()
    {
        // Restrict access to Super Admin only
        if (!auth()->user()->hasActiveRole('admin')) {
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetValidation();
        $this->reset(['userId', 'name', 'nip', 'nik', 'password', 'unit_name', 'jabatan', 'pangkat', 'apiSynced', 'apiStatusMessage']);
        $this->roles = ['pegawai'];

        // If current user is admin_opd, prefill unit_name
        if (auth()->user()->hasActiveRole('admin_opd') && !auth()->user()->hasActiveRole('admin')) {
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
        $this->nip = $user->nip ?? '';
        $this->nik = $user->nik ?? '';
        $this->roles = $user->roles->pluck('name')->toArray();
        if (empty($this->roles)) {
            $this->roles = ['pegawai'];
        }
        $this->unit_name = $user->unit_name ?? '';
        $this->jabatan = $user->jabatan ?? '';
        $this->pangkat = $user->pangkat ?? '';
        $this->password = '';
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

        $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
        $timeout = config('services.simpeg.timeout', 10);

        try {
            $pegawaiResponse = Http::timeout($timeout)->get("{$baseUrl}/data_pegawai/", [
                'nip' => $nip
            ]);

            $pegawaiData = $pegawaiResponse->json();
            $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

            if ($pegawaiResponse->successful() && is_array($pData) && !empty($pData['nama'] ?? $pData['nama_pegawai'] ?? null)) {
                $this->name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $this->name;
                $this->jabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? $this->jabatan;
                $this->pangkat = $pData['pangkat_nama'] ?? $this->pangkat;

                $nik = trim((string)($pData['nik'] ?? ($pData['no_ktp'] ?? ($pData['ktp'] ?? ($pData['no_identitas'] ?? '')))));
                if ($nik) {
                    $this->nik = $nik;
                }

                $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                if ($unit_id) {
                    $unitResponse = Http::timeout(5)->get("{$baseUrl}/get_unit/", [
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
        $rules = [
            'name' => 'required|string|max:255',
            'nip' => 'required|string|max:30|unique:users,nip,' . ($this->isEdit ? $this->userId : 'NULL') . ',id',
            'nik' => 'nullable|string|max:16',
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:admin,admin_opd,pimpinan,pegawai',
            'unit_name' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'pangkat' => 'nullable|string|max:255',
        ];

        // Prevent non-super-admin from assigning 'admin' role
        if (!auth()->user()->hasActiveRole('admin')) {
            $this->roles = array_values(array_diff($this->roles, ['admin']));
            if (empty($this->roles)) {
                $this->roles = ['admin_opd'];
            }
        }

        $messages = [
            'name.required' => 'Nama wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'roles.required' => 'Minimal satu peran wajib dipilih.',
            'roles.min' => 'Minimal satu peran wajib dipilih.',
            'roles.*.in' => 'Peran yang dipilih tidak valid.',
        ];

        $validated = $this->validate($rules, $messages);

        if ($this->isEdit) {
            $user = User::findOrFail($this->userId);
            $user->update([
                'name'      => $validated['name'],
                'nip'       => $validated['nip'],
                'nik'       => !empty($this->nik) ? trim($this->nik) : null,
                'unit_name' => $validated['unit_name'],
                'jabatan'   => $validated['jabatan'],
                'pangkat'   => !empty($this->pangkat) ? trim($this->pangkat) : null,
            ]);

            $user->syncRoles($validated['roles']);

            session()->flash('message', 'Pengguna berhasil diperbarui.');
        } else {
            $user = User::create([
                'name'      => $validated['name'],
                'nip'       => $validated['nip'],
                'nik'       => !empty($this->nik) ? trim($this->nik) : null,
                'password'  => null,
                'unit_name' => $validated['unit_name'],
                'jabatan'   => $validated['jabatan'],
                'pangkat'   => !empty($this->pangkat) ? trim($this->pangkat) : null,
            ]);

            $user->syncRoles($validated['roles']);

            session()->flash('message', 'Pengguna berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', 'user-form-modal');
        $this->reset(['userId', 'name', 'nip', 'nik', 'roles', 'unit_name', 'jabatan', 'pangkat', 'password', 'apiSynced', 'apiStatusMessage']);
        $this->roles = ['pegawai'];
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', 'Tidak dapat menghapus akun sendiri.');
            return;
        }

        $user = User::findOrFail($id);
        $user->delete();
        session()->flash('message', 'Pengguna berhasil dihapus.');
    }

    public function with(): array
    {
        $query = User::with('roles');

        if (!auth()->user()->hasActiveRole('admin')) {
            $query->where('unit_name', auth()->user()->unit_name);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('nip', 'like', '%' . $this->search . '%')
                    ->orWhere('unit_name', 'like', '%' . $this->search . '%')
                    ->orWhere('jabatan', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->roleFilter)) {
            $query->role($this->roleFilter);
        }

        $users = $query->orderBy('name', 'asc')->paginate(15);

        $counts = [
            'total' => User::count(),
            'admin' => User::role('admin')->count(),
            'admin_opd' => User::role('admin_opd')->count(),
            'pimpinan' => User::role('pimpinan')->count(),
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
                {{ auth()->user()->hasActiveRole('admin') ? 'Pemerintah Kabupaten Sinjai' : (auth()->user()->unit_name ?? 'Pemkab Sinjai') }}
            </p>
        </div>

        <button wire:click="openAddModal" class="relative z-10 inline-flex justify-center items-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2 w-full sm:w-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Pengguna
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

    <!-- Main Table Container -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Toolbar -->
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 space-y-4">
            <!-- Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <button wire:click="$set('roleFilter','')" 
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl text-xs font-bold transition-all {{ $roleFilter === '' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900' }}">
                    Semua
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === '' ? 'bg-slate-800 text-slate-300' : 'bg-slate-100 text-slate-500' }}">{{ $counts['total'] }}</span>
                </button>
                <button wire:click="$set('roleFilter','admin')" 
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl text-xs font-bold transition-all {{ $roleFilter === 'admin' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $roleFilter === 'admin' ? 'bg-purple-200' : 'bg-purple-500' }}"></span>
                    Super Admin
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'admin' ? 'bg-purple-700 text-purple-100' : 'bg-purple-100 text-purple-700' }}">{{ $counts['admin'] }}</span>
                </button>
                <button wire:click="$set('roleFilter','pimpinan')" 
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl text-xs font-bold transition-all {{ $roleFilter === 'pimpinan' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $roleFilter === 'pimpinan' ? 'bg-indigo-200' : 'bg-indigo-500' }}"></span>
                    Pimpinan
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'pimpinan' ? 'bg-indigo-700 text-indigo-100' : 'bg-indigo-100 text-indigo-700' }}">{{ $counts['pimpinan'] }}</span>
                </button>
                <button wire:click="$set('roleFilter','admin_opd')" 
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl text-xs font-bold transition-all {{ $roleFilter === 'admin_opd' ? 'bg-primary-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $roleFilter === 'admin_opd' ? 'bg-primary-200' : 'bg-primary-500' }}"></span>
                    Admin OPD
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'admin_opd' ? 'bg-primary-700 text-primary-100' : 'bg-primary-100 text-primary-700' }}">{{ $counts['admin_opd'] }}</span>
                </button>
                <button wire:click="$set('roleFilter','pegawai')" 
                    class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl text-xs font-bold transition-all {{ $roleFilter === 'pegawai' ? 'bg-slate-700 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $roleFilter === 'pegawai' ? 'bg-slate-300' : 'bg-slate-400' }}"></span>
                    Pegawai
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] {{ $roleFilter === 'pegawai' ? 'bg-slate-800 text-slate-100' : 'bg-slate-100 text-slate-700' }}">{{ $counts['pegawai'] }}</span>
                </button>
            </div>

            <!-- Search Field -->
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                <div class="relative flex-1 min-w-[220px] max-w-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full h-10 rounded-xl border border-slate-200 pl-9 pr-9 py-2 text-xs sm:text-sm focus:border-primary-500 focus:ring-primary-500 shadow-2xs transition-colors bg-white placeholder:text-slate-400"
                        placeholder="Cari nama, NIP, atau OPD...">
                    @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors" title="Hapus pencarian">
                        <svg class="w-4 h-4 bg-slate-100 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>

                @if($roleFilter)
                <button wire:click="resetFilters"
                    class="h-10 inline-flex items-center gap-1.5 px-3 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 active:scale-95 transition-all border border-rose-200/80 shadow-2xs cursor-pointer shrink-0"
                    title="Reset semua filter">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Reset Filter</span>
                </button>
                @endif
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
                        <td class="py-4 px-6 text-left">
                            <div class="flex flex-wrap gap-1.5 max-w-[220px]">
                                @forelse($u->sortedRoles() as $roleObj)
                                    <x-user-role-badge :role="$roleObj->name" />
                                @empty
                                    <x-user-role-badge role="pegawai" />
                                @endforelse
                            </div>
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
                                <button wire:click="openEditModal({{ $u->id }})" class="p-2 text-slate-400 hover:text-primary-700 hover:bg-primary-50 rounded-xl active:scale-95 transition-all" title="Edit Pengguna">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                </button>
                                @if($u->id !== auth()->id())
                                <button wire:click="deleteUser({{ $u->id }})" wire:confirm="Hapus pengguna {{ $u->name }}?" class="p-2 text-slate-400 hover:text-rose-700 hover:bg-rose-50 rounded-xl active:scale-95 transition-all" title="Hapus Pengguna">
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
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3 text-slate-400">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900">Tidak Ada Data Pengguna</h3>
                                @if($search || $roleFilter)
                                <button type="button" wire:click="resetFilters" class="mt-3 px-4 py-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                                    Reset Filter
                                </button>
                                @else
                                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'user-form-modal'); $wire.openAddModal()" class="mt-3 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-sm gap-2">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    Tambah Pengguna
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$users" />
    </div>

    <!-- Modal Form Tambah / Edit Pengguna -->
    <x-modal name="user-form-modal" maxWidth="2xl">
        <div class="p-6 sm:p-8">
            <div class="flex justify-between items-center pb-4 mb-6 border-b border-slate-100">
                <h2 class="text-xl font-extrabold text-slate-900">
                    {{ $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}
                </h2>
                <button type="button" x-on:click="$dispatch('close')" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveUser" class="space-y-4">
                <!-- Nama Lengkap (Paling Atas) -->
                <div>
                    <label for="name" class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input wire:model="name" id="name" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Ahmad Yani, S.Kom" required />
                    @error('name') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                @if(in_array('pimpinan', $roles))
                <!-- NIP & NIK (1 Baris untuk Pimpinan) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- NIP dengan Cek SIMPEG -->
                    <div>
                        <label for="nip" class="block text-sm font-bold text-slate-700 mb-1">NIP</label>
                        <div class="flex items-center gap-2">
                            <input wire:model="nip" wire:keydown.enter.prevent="checkNipFromApi" id="nip" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 198501012010011001" />

                            <button type="button" wire:click="checkNipFromApi" wire:loading.attr="disabled" wire:target="checkNipFromApi" class="shrink-0 inline-flex items-center justify-center px-3.5 py-2.5 bg-slate-800 hover:bg-slate-900 active:scale-95 text-white rounded-xl font-bold text-xs transition-all shadow-sm gap-1.5" title="Tarik dari SIMPEG">
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
                        @error('nip') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- NIK -->
                    <div>
                        <label for="nik" class="block text-sm font-bold text-slate-700 mb-1">NIK</label>
                        <input wire:model="nik" id="nik" type="text" maxlength="16" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 7307010101850001" />
                        @error('nik') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>
                @else
                <!-- NIP (Jika bukan Pimpinan) -->
                <div>
                    <label for="nip" class="block text-sm font-bold text-slate-700 mb-1">NIP</label>
                    <div class="flex items-center gap-2">
                        <input wire:model="nip" wire:keydown.enter.prevent="checkNipFromApi" id="nip" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: 198501012010011001" />

                        <button type="button" wire:click="checkNipFromApi" wire:loading.attr="disabled" wire:target="checkNipFromApi" class="shrink-0 inline-flex items-center justify-center px-3.5 py-2.5 bg-slate-800 hover:bg-slate-900 active:scale-95 text-white rounded-xl font-bold text-xs transition-all shadow-sm gap-1.5" title="Tarik dari SIMPEG">
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
                    @error('nip') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                @endif

                @if($apiSynced)
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between gap-3">
                    <div class="min-w-0 space-y-0.5">
                        <p class="text-sm font-bold text-emerald-900 truncate">{{ $name }}</p>
                        @if($jabatan || $pangkat)
                        <p class="text-xs font-semibold text-emerald-800 truncate">
                            {{ $jabatan ?: '-' }}
                            @if($pangkat)
                            <span class="text-emerald-700 font-medium">• {{ $pangkat }}</span>
                            @endif
                        </p>
                        @endif
                        @if($unit_name)
                        <p class="text-xs font-medium text-emerald-600 truncate">{{ $unit_name }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 flex items-center justify-center w-7 h-7 bg-emerald-500 text-white rounded-xl shadow-xs" title="Terverifikasi SIMPEG">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
                @endif

                <!-- Penugasan Peran -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Role
                    </label>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                        @if(auth()->user()->hasActiveRole('admin'))
                        <!-- Super Admin -->
                        <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border cursor-pointer select-none transition-all text-xs font-bold {{ in_array('admin', $roles) ? 'bg-purple-50 text-purple-900 border-purple-300 ring-2 ring-purple-500/20 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                            <input type="checkbox" wire:model.live="roles" value="admin" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-slate-300">
                            <span>Super Admin</span>
                        </label>
                        @endif

                        <!-- Pimpinan -->
                        <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border cursor-pointer select-none transition-all text-xs font-bold {{ in_array('pimpinan', $roles) ? 'bg-indigo-50 text-indigo-900 border-indigo-300 ring-2 ring-indigo-500/20 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                            <input type="checkbox" wire:model.live="roles" value="pimpinan" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                            <span>Pimpinan</span>
                        </label>

                        <!-- Admin OPD -->
                        <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border cursor-pointer select-none transition-all text-xs font-bold {{ in_array('admin_opd', $roles) ? 'bg-emerald-50 text-emerald-900 border-emerald-300 ring-2 ring-emerald-500/20 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                            <input type="checkbox" wire:model.live="roles" value="admin_opd" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                            <span>Admin OPD</span>
                        </label>

                        <!-- Pegawai -->
                        <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border cursor-pointer select-none transition-all text-xs font-bold {{ in_array('pegawai', $roles) ? 'bg-slate-100 text-slate-900 border-slate-300 ring-2 ring-slate-400/20 shadow-2xs' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                            <input type="checkbox" wire:model.live="roles" value="pegawai" class="w-4 h-4 rounded text-slate-700 focus:ring-slate-500 border-slate-300">
                            <span>Pegawai</span>
                        </label>
                    </div>
                    @error('roles') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                    @error('roles.*') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Jabatan -->
                    <div>
                        <label for="jabatan" class="block text-sm font-bold text-slate-700 mb-1">Jabatan</label>
                        <input wire:model="jabatan" id="jabatan" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Pranata Komputer Ahli Muda" />
                        @error('jabatan') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Pangkat -->
                    <div>
                        <label for="pangkat" class="block text-sm font-bold text-slate-700 mb-1">Pangkat</label>
                        <input wire:model="pangkat" id="pangkat" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Pembina" />
                        @error('pangkat') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- OPD -->
                <div>
                    <label for="unit_name" class="block text-sm font-bold text-slate-700 mb-1">OPD</label>
                    <input wire:model="unit_name" id="unit_name" type="text" class="w-full text-sm py-2.5 px-3 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors" placeholder="Contoh: Dinas Komunikasi Informatika dan Persandian" />
                    @error('unit_name') <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close-modal', 'user-form-modal')" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 active:scale-95 text-slate-700 rounded-xl font-bold text-sm transition-all shadow-sm">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveUser" class="inline-flex items-center justify-center px-5 py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-95 text-white rounded-xl font-bold text-sm transition-all shadow-sm gap-2">
                        <svg wire:loading.remove wire:target="saveUser" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="saveUser" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>