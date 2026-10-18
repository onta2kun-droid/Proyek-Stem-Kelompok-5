<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\RadarController;
use App\Http\Controllers\SettingsController;

// ===== LOGIN / LOGOUT =====
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')->name('logout');

// ===== PUBLIK =====
Route::get('/', fn () => redirect()->route('dashboard.kelembapan'));
Route::get('/dashboard/kelembapan', [DashboardController::class, 'kelembapan'])->name('dashboard.kelembapan');
Route::get('/dashboard/kelembapan/data', [DashboardController::class, 'data'])->name('dashboard.kelembapan.data');
Route::get('/dashboard/kelembapan/latest', [DashboardController::class, 'latest'])->name('dashboard.kelembapan.latest');
Route::get('/riwayat', [HistoryController::class, 'index'])->name('riwayat');
Route::get('/riwayat/export', [HistoryController::class, 'export'])->name('riwayat.export');

// ===== TERPROTEKSI (admin) =====
Route::middleware('auth')->group(function () {
    // Kontrol pompa
    Route::get('/pompa', [PumpController::class, 'index'])->name('pompa');
    Route::post('/pompa/{mode}', [PumpController::class, 'setMode'])
        ->whereIn('mode', ['auto', 'on', 'off'])->name('pompa.mode');

    // Radar proteksi + toggle alarm (BARU)
    Route::get('/radar', [RadarController::class, 'index'])->name('radar');
    Route::post('/radar/alarm/{state}', [RadarController::class, 'setAlarm'])
        ->whereIn('state', ['on', 'off'])->name('radar.alarm');

    // Perangkat
    Route::get('/perangkat', [DeviceController::class, 'index'])->name('perangkat');
    Route::post('/perangkat/{device}/regenerate-key', [DeviceController::class, 'regenerateKey'])
        ->name('perangkat.regenerate');

    // Pengaturan
    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('pengaturan');
    Route::post('/pengaturan', [SettingsController::class, 'update'])->name('pengaturan.update');
});
