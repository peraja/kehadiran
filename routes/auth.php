<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('login', 'pages.auth.login')
        ->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', function (\App\Livewire\Actions\Logout $logout) {
        $logout();
        return redirect('/');
    })->name('logout');
});
