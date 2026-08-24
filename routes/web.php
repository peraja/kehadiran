<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Volt::route('meetings', 'meetings.index')
    ->middleware(['auth'])
    ->name('meetings.index');

Volt::route('meetings/history', 'meetings.history')
    ->middleware(['auth'])
    ->name('meetings.history');

Route::middleware(['auth'])->group(function () {
    Volt::route('meetings/{meeting}/overview', 'meetings.overview')->name('meetings.overview');
    Volt::route('meetings/{meeting}/presensi', 'meetings.presensi')->name('meetings.presensi');
    Volt::route('meetings/{meeting}/dokumentasi', 'meetings.dokumentasi')->name('meetings.dokumentasi');
    Volt::route('meetings/{meeting}/notulen', 'meetings.notulen')->name('meetings.notulen');
    
    // PDF Exports
    Route::get('meetings/{meeting}/export/minutes', [\App\Http\Controllers\MeetingExportController::class, 'exportMinutes'])->name('meetings.export.minutes');
    Route::get('meetings/{meeting}/export/attendance', [\App\Http\Controllers\MeetingExportController::class, 'exportAttendance'])->name('meetings.export.attendance');
    Route::get('meetings/{meeting}/export/photos', [\App\Http\Controllers\MeetingExportController::class, 'exportPhotos'])->name('meetings.export.photos');
});

// Public Check-in Route
Volt::route('meetings/{meeting}/check-in', 'meetings.check-in')->name('meetings.check-in');

// Public TTE Document Verification
Volt::route('meetings/{meeting}/verify/{type?}', 'meetings.verify-tte')->name('meetings.verify.tte');
Route::get('meetings/{meeting}/verify/{type}/download', [\App\Http\Controllers\MeetingExportController::class, 'downloadSigned'])->name('meetings.verify.download');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Volt::route('users', 'users.index')
    ->middleware(['auth'])
    ->name('users.index');

Volt::route('opd', 'opd.index')
    ->middleware(['auth'])
    ->name('opd.index');

Volt::route('opd/settings', 'opd.settings')
    ->middleware(['auth'])
    ->name('opd.settings');

Volt::route('settings', 'admin.settings')
    ->middleware(['auth'])
    ->name('admin.settings');

Volt::route('audit-logs', 'admin.audit-logs')
    ->middleware(['auth'])
    ->name('admin.audit-logs');

require __DIR__.'/auth.php';
