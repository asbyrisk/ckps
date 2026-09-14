<?php

use App\Http\Controllers\AsnController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KompetensiController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PelatihanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:register')
        ->name('register.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Protected application
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');

    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import', [ImportController::class, 'store'])->name('import.store');
    Route::get('/import/preview/{preview}', [ImportController::class, 'showPreview'])->name('import.preview');
    Route::post('/import/preview/{preview}/confirm', [ImportController::class, 'confirm'])->name('import.preview.confirm');
    Route::post('/import/preview/{preview}/cancel', [ImportController::class, 'cancel'])->name('import.preview.cancel');

    Route::prefix('asn')->name('asn.')->group(function () {
        Route::get('/', [AsnController::class, 'index'])->name('index');
        Route::get('/create', [AsnController::class, 'create'])->name('create');
        Route::post('/', [AsnController::class, 'store'])->name('store');
        Route::get('/{asn}/edit', [AsnController::class, 'edit'])->name('edit');
        Route::put('/{asn}', [AsnController::class, 'update'])->name('update');
        Route::delete('/{asn}', [AsnController::class, 'destroy'])->name('destroy');
        Route::put('/{asn}/pelatihan/{pelatihan}/status', [AsnController::class, 'updateTrainingStatus'])->name('training.status');
        Route::post('/{asn}/unit', [AsnController::class, 'transferUnit'])->name('unit.transfer');
        Route::post('/{asn}/unit/history/{history}/cancel', [AsnController::class, 'cancelUnitHistory'])->name('unit.history.cancel');
        Route::get('/{asn}', [AsnController::class, 'show'])->name('show');
    });

    Route::prefix('pelatihan')->name('pelatihan.')->group(function () {
        Route::get('/', [PelatihanController::class, 'index'])->name('index');
        Route::get('/{pelatihan}', [PelatihanController::class, 'show'])->name('show');
    });

    Route::prefix('kompetensi')->name('kompetensi.')->group(function () {
        Route::get('/', [KompetensiController::class, 'index'])->name('index');
        Route::get('/{kompetensi}', [KompetensiController::class, 'show'])->name('show');
    });
});
