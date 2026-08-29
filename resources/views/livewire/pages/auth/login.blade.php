<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest', [
    'title' => 'Login | eRapat',
    'description' => 'Login eRapat Pemerintah Kabupaten Sinjai',
    'robots' => 'noindex, nofollow',
])] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        try {
            $this->validate();

            $this->form->authenticate();

            Session::regenerate();

            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
        } catch (\Throwable $e) {
            $this->form->password = '';
            $this->dispatch('login-failed');
            throw $e;
        }
    }
}; ?>

<div x-data="{ isLoggingIn: false }" x-on:login-failed.window="isLoggingIn = false; $nextTick(() => document.getElementById('password')?.focus())">
    <div class="mb-6 text-center">
        <h2 class="text-lg font-bold text-slate-900">Login NIP dan Password ENIKDA</h2>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" @submit="isLoggingIn = true" class="space-y-4">
        <!-- NIP -->
        <div>
            <x-input-label for="nip" value="NIP" />
            <x-text-input wire:model="form.nip" id="nip" class="block mt-1 w-full font-mono" type="text" name="nip" placeholder="Contoh: 199610072022031013" required autofocus x-init="$nextTick(() => $el.focus())" autocomplete="username" />
            <x-input-error :messages="$errors->get('form.nip')" class="mt-1" />
        </div>

        <!-- Password -->
        <div x-data="{ showPassword: false }">
            <x-input-label for="password" value="Password" />
            <div class="relative mt-1">
                <x-text-input wire:model="form.password" id="password"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    class="w-full text-base sm:text-sm py-2.5 pl-3.5 pr-10 bg-white border border-slate-300 rounded-xl text-slate-900 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition-colors"
                    placeholder="••••••••"
                    required autocomplete="current-password" />
                <button type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                    :title="showPassword ? 'Sembunyikan Password' : 'Lihat Password'">
                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
        </div>

        <div class="pt-2">
            <x-primary-button 
                type="submit" 
                x-bind:disabled="isLoggingIn" 
                class="w-full justify-center py-2.5 shadow-xs gap-2 disabled:opacity-75 disabled:cursor-not-allowed cursor-pointer"
            >
                <svg x-show="!isLoggingIn" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <svg x-show="isLoggingIn" x-cloak class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Login</span>
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-slate-100 text-center">
        <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-primary-600 uppercase tracking-wider transition-colors">
            &larr; Kembali ke Beranda
        </a>
    </div>
</div>