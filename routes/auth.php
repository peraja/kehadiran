<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('login', 'pages.auth.login')
        ->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', function (\Illuminate\Http\Request $request, \App\Livewire\Actions\Logout $logout) {
        $logout();
        if ($request->input('reason') === 'timeout') {
            return redirect()->route('login')->with('status', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.');
        }
        return redirect('/');
    })->name('logout');
});
