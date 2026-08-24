<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <!-- Mobile Sidebar Backdrop & Drawer -->
    <div x-show="sidebarOpen" class="relative z-[100] lg:hidden" role="dialog" aria-modal="true" style="display: none;">
        <!-- Backdrop -->
        <div x-show="sidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"
            @click="sidebarOpen = false"></div>

        <div class="fixed inset-0 flex">
            <!-- Drawer Menu -->
            <div x-show="sidebarOpen"
                x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="relative mr-16 flex w-full max-w-xs flex-1">

                <!-- Close Button -->
                <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                    <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5 text-white/70 hover:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-white transition-colors">
                        <span class="sr-only">Tutup navigasi</span>
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Drawer Content -->
                <div class="flex grow flex-col overflow-y-auto bg-slate-900 px-6 pb-4 shadow-2xl">
                    <div class="flex h-20 shrink-0 items-center gap-3 border-b border-white/10">
                        <img class="h-10 w-auto" src="{{ asset('img/logo.png') }}" alt="Logo Pemkab Sinjai">
                        <div>
                            <span class="text-xl font-extrabold text-white tracking-tight leading-tight block">e<span class="text-primary-400">Rapat</span></span>
                            <span class="block text-[10px] text-slate-400 font-bold mt-0.5 uppercase tracking-widest">Pemkab Sinjai</span>
                        </div>
                    </div>

                    <nav class="flex flex-1 flex-col mt-6">
                        <ul role="list" class="flex flex-1 flex-col gap-y-2">
                            <li>
                                <a href="{{ route('dashboard') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ request()->routeIs('dashboard') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                    </svg>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('meetings.index') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ (request()->routeIs('meetings.*') && !request()->routeIs('meetings.history')) ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ (request()->routeIs('meetings.*') && !request()->routeIs('meetings.history')) ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                    Daftar Rapat
                                </a>
                            </li>
                            @if(auth()->user()->hasAnyActiveRole(['admin', 'admin_opd']))
                            <li>
                                <a href="{{ route('meetings.history') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ request()->routeIs('meetings.history') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('meetings.history') ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Riwayat Rapat
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasActiveRole('admin'))
                            <li class="mt-4">
                                <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 px-3.5 mb-2">
                                    Administrator
                                </div>
                                <a href="{{ route('users.index') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ request()->routeIs('users.*') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('users.*') ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                    Master Pengguna
                                </a>
                            </li>
                            <li class="mt-1">
                                <a href="{{ route('opd.index') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ request()->routeIs('opd.index') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('opd.index') ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                    Master OPD
                                </a>
                            </li>
                            <li class="mt-1">
                                <a href="{{ route('admin.audit-logs') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ request()->routeIs('admin.audit-logs') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.audit-logs') ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                    </svg>
                                    Audit Log
                                </a>
                            </li>
                            <li class="mt-1">
                                <a href="{{ route('admin.settings') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ request()->routeIs('admin.settings') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.settings') ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Pengaturan
                                </a>
                            </li>
                            @elseif(auth()->user()->hasActiveRole('admin_opd'))
                            <li class="mt-4">
                                <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 px-3.5 mb-2">
                                    Pengaturan
                                </div>
                                <a href="{{ route('opd.settings') }}" wire:navigate
                                    class="group flex gap-x-3 rounded-xl p-3.5 text-sm font-bold leading-6 transition-all {{ request()->routeIs('opd.settings') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('opd.settings') ? 'text-primary-400' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                    Pengaturan OPD
                                </a>
                            </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Fixed Left Sidebar -->
    <aside class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-72 lg:flex-col border-r border-slate-200/80 bg-slate-900 shadow-2xl">
        <!-- Sidebar decorative glow -->
        <div class="absolute top-0 left-0 w-full h-64 bg-gradient-to-b from-primary-900/40 to-transparent pointer-events-none"></div>

        <!-- Sidebar Brand Header -->
        <div class="flex h-20 shrink-0 items-center gap-3 px-8 relative z-10">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 group">
                <img class="h-10 w-auto group-hover:scale-105 transition-transform" src="{{ asset('img/logo.png') }}" alt="Logo Pemkab Sinjai">
                <div>
                    <span class="text-xl font-extrabold text-white tracking-tight leading-tight block">e<span class="text-primary-400">Rapat</span></span>
                    <span class="block text-[10px] text-slate-400 font-bold mt-0.5 uppercase tracking-widest">Pemkab Sinjai</span>
                </div>
            </a>
        </div>

        <!-- Sidebar Navigation Menu -->
        <div class="flex flex-1 flex-col justify-between overflow-y-auto px-5 py-6 relative z-10 custom-scrollbar">
            <nav class="space-y-8">
                <div>
                    <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 px-3 mb-3">
                        Menu Utama
                    </div>
                    <ul role="list" class="space-y-1.5">
                        <li>
                            <a href="{{ route('dashboard') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs('dashboard') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('meetings.index') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ (request()->routeIs('meetings.*') && !request()->routeIs('meetings.history')) ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ (request()->routeIs('meetings.*') && !request()->routeIs('meetings.history')) ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                Daftar Rapat
                            </a>
                        </li>
                        @if(auth()->user()->hasAnyActiveRole(['admin', 'admin_opd']))
                        <li>
                            <a href="{{ route('meetings.history') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs('meetings.history') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('meetings.history') ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Riwayat Rapat
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                @if(auth()->user()->hasActiveRole('admin'))
                <div>
                    <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 px-3 mb-3">
                        Administrator
                    </div>
                    <ul role="list" class="space-y-1.5">
                        <li>
                            <a href="{{ route('users.index') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs('users.*') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('users.*') ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                Master Pengguna
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('opd.index') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs('opd.index') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('opd.index') ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                                Master OPD
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.audit-logs') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs('admin.audit-logs') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.audit-logs') ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                </svg>
                                Audit Log
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.settings') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs('admin.settings') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.settings') ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Pengaturan
                            </a>
                        </li>
                    </ul>
                </div>
                @elseif(auth()->user()->hasActiveRole('admin_opd'))
                <div>
                    <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 px-3 mb-3">
                        Pengaturan
                    </div>
                    <ul role="list" class="space-y-1.5">
                        <li>
                            <a href="{{ route('opd.settings') }}" wire:navigate
                                class="group flex items-center gap-x-3 rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs('opd.settings') ? 'bg-primary-500/10 text-primary-400 shadow-inner ring-1 ring-primary-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 shrink-0 {{ request()->routeIs('opd.settings') ? 'text-primary-400' : 'text-slate-500 group-hover:text-slate-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                                Pengaturan OPD
                            </a>
                        </li>
                    </ul>
                </div>
                @endif
            </nav>


        </div>
    </aside>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .custom-scrollbar:hover::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</div>