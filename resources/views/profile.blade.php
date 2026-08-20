<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profil Saya') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-6 sm:p-8 bg-white shadow-sm sm:rounded-xl border border-gray-200">
                <header class="mb-6 border-b pb-4">
                    <h2 class="text-lg font-medium text-gray-900">
                        Informasi Akun Pegawai
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Data profil ini tersinkronisasi secara otomatis dari server kepegawaian pusat.
                    </p>
                </header>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-100 pb-4">
                        <div class="text-sm font-medium text-gray-500">Nama Lengkap</div>
                        <div class="text-sm text-gray-900 md:col-span-2 font-semibold">{{ auth()->user()->name }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-100 pb-4">
                        <div class="text-sm font-medium text-gray-500">NIP</div>
                        <div class="text-sm text-gray-900 md:col-span-2">{{ auth()->user()->nip ?? '-' }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-100 pb-4">
                        <div class="text-sm font-medium text-gray-500">Jabatan</div>
                        <div class="text-sm text-gray-900 md:col-span-2">{{ auth()->user()->jabatan ?? '-' }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-100 pb-4">
                        <div class="text-sm font-medium text-gray-500">Instansi / Unit Kerja</div>
                        <div class="text-sm text-gray-900 md:col-span-2">{{ auth()->user()->unit_name ?? '-' }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-sm font-medium text-gray-500">Hak Akses (Role)</div>
                        <div class="text-sm text-gray-900 md:col-span-2">
                            @foreach(auth()->user()->roles as $role)
                                <span class="inline-block bg-primary-100 text-primary-800 text-xs px-2.5 py-1 rounded-full uppercase tracking-wider font-semibold mr-1">
                                    {{ str_replace('_', ' ', $role->name) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-95 transition ease-in-out duration-150">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
