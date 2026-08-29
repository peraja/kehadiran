<x-app-layout>
    <div class="space-y-6 pb-10">
        @php
        $user = auth()->user();
        $allRoles = $user->sortedRoles();
        $activeRole = $user->currentRole();
        @endphp

        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
            <div class="relative z-10">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                    Profil Pengguna
                </h1>
                <p class="text-sm font-medium text-slate-500">
                    {{ $user->name }}
                </p>
            </div>
            <div class="relative z-10 flex flex-wrap items-center gap-1.5">
                @forelse($allRoles as $r)
                    <x-user-role-badge :role="$r->name" />
                @empty
                    <x-user-role-badge role="pegawai" />
                @endforelse
            </div>
        </div>

        <!-- User Profile Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100 text-sm">
            <!-- Nama Lengkap -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-5 sm:px-6 gap-1 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    Nama Lengkap
                </div>
                <div class="sm:w-3/4 text-slate-900 font-extrabold text-sm">
                    {{ $user->name }}
                </div>
            </div>

            <!-- NIP -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-5 sm:px-6 gap-1 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    NIP
                </div>
                <div class="sm:w-3/4 text-slate-800 font-mono text-sm font-semibold">
                    {{ $user->nip ?? '-' }}
                </div>
            </div>

            @if($user->hasRole('pimpinan'))
            <!-- NIK -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-5 sm:px-6 gap-1 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    NIK
                </div>
                <div class="sm:w-3/4 text-slate-800 font-mono text-sm font-semibold">
                    {{ $user->nik ?? '-' }}
                </div>
            </div>
            @endif

            @php
            $userPositions = $user->getAllPositions();
            @endphp

            @if(count($userPositions) > 1)
            <!-- Jabatan & Penugasan Multi-Peran -->
            <div class="flex flex-col sm:flex-row py-4 px-5 sm:px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm pt-1">
                    Jabatan & Unit Kerja
                </div>
                <div class="sm:w-3/4 space-y-2.5">
                    @foreach($userPositions as $pos)
                    <div class="flex items-center justify-between gap-3 p-3.5 rounded-2xl border {{ $pos['is_plt'] ? 'bg-amber-50/40 border-amber-200/70' : 'bg-slate-50/80 border-slate-200/80' }}">
                        <div class="space-y-0.5">
                            <div class="font-extrabold text-slate-900 text-sm">
                                {{ $pos['jabatan'] }}
                            </div>
                            <div class="text-xs font-semibold text-slate-500">
                                {{ $pos['unit'] }}
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider shrink-0 {{ $pos['is_plt'] ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                            {{ $pos['badge'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <!-- Jabatan Tunggal -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-5 sm:px-6 gap-1 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    Jabatan
                </div>
                <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">
                    {{ $user->jabatan ?? '-' }}
                </div>
            </div>

            <!-- OPD / Unit Kerja -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-5 sm:px-6 gap-1 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    OPD
                </div>
                <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">
                    {{ $user->unit_name ?? '-' }}
                </div>
            </div>
            @endif

            <!-- Waktu Terdaftar -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-5 sm:px-6 gap-1 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    Waktu Terdaftar
                </div>
                <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">
                    {{ $user->created_at ? $user->created_at->translatedFormat('d F Y, H:i') . ' WITA' : '-' }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>