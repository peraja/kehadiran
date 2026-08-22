<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h2 class="text-lg font-bold text-slate-900">Login NIP dan Password ENIKDA</h2>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <!-- NIP -->
        <div>
            <x-input-label for="nip" value="NIP" />
            <x-text-input wire:model="form.nip" id="nip" class="block mt-1 w-full font-mono" type="text" name="nip" placeholder="Contoh: 198501012010011001" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.nip')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                placeholder="••••••••"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-1" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-2.5 shadow-xs">
                Login
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-slate-100 text-center">
        <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-primary-600 transition-colors">
            &larr; Kembali ke Beranda
        </a>
    </div>
</div>