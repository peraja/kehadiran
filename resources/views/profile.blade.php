<x-app-layout>
    <div class="space-y-6 pb-10">
        @php
        $roleName = auth()->user()->roles->first()?->name ?? 'pegawai';
        @endphp

        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full blur-3xl pointer-events-none opacity-60"></div>
            <div class="relative z-10">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-1">
                    Profil Pengguna
                </h1>
                <p class="text-sm font-medium text-slate-500">
                    {{ auth()->user()->name }}
                </p>
            </div>
            <div class="relative z-10">
                <x-user-role-badge :role="$roleName" />
            </div>
        </div>

        <!-- User Profile Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100 text-sm">
            <!-- Nama Lengkap -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    Nama Lengkap
                </div>
                <div class="sm:w-3/4 text-slate-900 font-extrabold text-sm">
                    {{ auth()->user()->name }}
                </div>
            </div>

            <!-- NIP -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    NIP
                </div>
                <div class="sm:w-3/4 text-slate-800 font-mono text-sm font-semibold">
                    {{ auth()->user()->nip ?? '-' }}
                </div>
            </div>

            <!-- Jabatan -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    Jabatan
                </div>
                <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">
                    {{ auth()->user()->jabatan ?? '-' }}
                </div>
            </div>

            <!-- OPD / Unit Kerja -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    OPD
                </div>
                <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">
                    {{ auth()->user()->unit_name ?? '-' }}
                </div>
            </div>

            <!-- Waktu Terdaftar -->
            <div class="flex flex-col sm:flex-row sm:items-center py-4 px-6 gap-2 sm:gap-6 hover:bg-slate-50/50 transition-colors">
                <div class="sm:w-1/4 text-slate-500 font-bold text-sm">
                    Waktu Terdaftar
                </div>
                <div class="sm:w-3/4 text-slate-800 font-semibold text-sm">
                    {{ auth()->user()->created_at ? auth()->user()->created_at->translatedFormat('d F Y, H:i') . ' WITA' : '-' }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>