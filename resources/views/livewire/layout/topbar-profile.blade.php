<?php

use Livewire\Volt\Component;
use App\Models\User;

new class extends Component
{
    public function switchRole(string $role): void
    {
        $user = auth()->user();
        if ($user && $user->switchRole($role)) {
            $this->dispatch('close-modal', 'switch-role-modal');
            session()->flash('message', 'Peran dialihkan ke ' . User::getRoleLabel($role));
            $this->redirect(route('dashboard'), navigate: true);
        }
    }
}; ?>

<div>
    @php
    $user = auth()->user();
    $activeRole = $user->currentRole();
    $roleLabel = User::getRoleLabel($activeRole);
    $allRoles = $user->sortedRoles();
    $hasMultipleRoles = $allRoles->count() > 1;

    $roleBadgeColors = match($activeRole) {
        'admin' => 'bg-purple-100 text-purple-700 border-purple-200',
        'pimpinan' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
        'admin_opd' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
    @endphp

    <x-dropdown align="right" width="w-48">
        <x-slot name="trigger">
            <button type="button" class="flex items-center gap-3 py-1.5 px-2.5 sm:py-2 sm:px-3.5 rounded-2xl bg-slate-50/80 hover:bg-slate-100/80 border border-slate-200/80 outline-none focus:outline-none focus-visible:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 active:scale-[0.99] transition-all shadow-2xs select-none cursor-pointer">
                <div class="w-9 h-9 rounded-xl bg-primary-600 text-white font-extrabold text-sm flex items-center justify-center shadow-xs shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div class="text-left pr-1">
                    <div class="text-xs font-bold text-slate-900 truncate max-w-[150px] sm:max-w-[190px] leading-tight">{{ $user->name }}</div>
                    <div class="text-[10px] font-semibold text-slate-500 leading-tight mt-0.5 flex items-center gap-1.5">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold border {{ $roleBadgeColors }}">
                            {{ $roleLabel }}
                        </span>
                    </div>
                </div>
                <svg class="h-4 w-4 text-slate-400 shrink-0 ml-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            <!-- Menu Links -->
            <div class="py-1">
                <x-dropdown-link :href="route('profile')" wire:navigate class="flex items-center gap-2.5 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 hover:text-primary-600 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Profil</span>
                </x-dropdown-link>

                <button type="button" 
                        x-on:click="$dispatch('open-modal', 'switch-role-modal')" 
                        class="w-full flex items-center gap-2.5 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 hover:text-primary-600 hover:bg-slate-50 transition-colors text-left cursor-pointer">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>Role</span>
                </button>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full text-start">
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center gap-2.5 px-4 py-2 text-xs sm:text-sm font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50">
                            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Logout</span>
                        </x-dropdown-link>
                    </button>
                </form>
            </div>
        </x-slot>
    </x-dropdown>

    <!-- Modal Switch Role Menggunakan Komponen x-modal -->
    <x-modal name="switch-role-modal" maxWidth="sm">
        <div class="p-5 sm:p-6">
            <div class="flex justify-between items-center pb-3 mb-4 border-b border-slate-100">
                <h2 class="text-base font-extrabold text-slate-900">
                    Pilih Role
                </h2>
                <button type="button" x-on:click="show = false" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-2">
                @forelse($allRoles as $r)
                @php
                $isCurrent = $r->name === $activeRole;
                $roleBadge = match($r->name) {
                    'admin' => 'bg-purple-100 text-purple-700 border-purple-200',
                    'pimpinan' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                    'admin_opd' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                @endphp
                <button type="button"
                        wire:click="switchRole('{{ $r->name }}')"
                        class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl border text-left transition-all active:scale-[0.99] cursor-pointer {{ $isCurrent ? 'bg-primary-50/80 border-primary-300 ring-2 ring-primary-500/20' : 'bg-white border-slate-200 hover:bg-slate-50' }}">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold border {{ $roleBadge }}">
                        {{ User::getRoleLabel($r->name) }}
                    </span>
                    @if($isCurrent)
                    <span class="text-xs font-extrabold text-primary-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        Aktif
                    </span>
                    @endif
                </button>
                @empty
                <p class="text-xs text-slate-500 text-center py-4">Tidak ada role.</p>
                @endforelse
            </div>
        </div>
    </x-modal>
</div>
