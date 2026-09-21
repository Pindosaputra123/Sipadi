<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PetaniController;
use App\Http\Controllers\Admin\PanenController;
use App\Http\Controllers\Admin\StokController;
use App\Http\Controllers\Admin\HargaController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\TujuanDistribusiController;
use App\Http\Controllers\PetaniDashboardController;
use App\Http\Controllers\ChatbotController;



Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    if (Auth::user()->role === 'petani') {
        return redirect()->route('petani.dashboard');
    }

    if (Auth::user()->role === 'petugas') {
        return redirect()->route('petugas.dashboard');
    }

    return redirect()->route('admin.dashboard');
});

Route::middleware(['auth', 'role:petani'])->prefix('petani')->name('petani.')->group(function () {
    Route::get('/', [PetaniDashboardController::class, 'index'])->name('dashboard');
});

// Chatbot Prabowo — proxy ke n8n, bisa diakses admin & petugas yang sudah login
Route::middleware(['auth:web,api'])->group(function () {
    Route::post('chatbot/prabowo', [ChatbotController::class, 'send'])->name('chatbot.prabowo');
});

Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('panen', PanenController::class)->names('panen');
    Route::get('stok', [StokController::class, 'index'])->name('stok.index');
    Route::post('stok', [StokController::class, 'store'])->name('stok.store');
    Route::get('stok/{id}', [StokController::class, 'show'])->name('stok.show');
    Route::get('alert', [AlertController::class, 'index'])->name('alert.index');
    Route::put('alert/konfigurasi', [AlertController::class, 'konfigurasi'])->name('alert.konfigurasi');
    Route::patch('alert/{alert}/tangani', [AlertController::class, 'tangani'])->name('alert.tangani');
    Route::patch('/admin/alert/{id}/selesai', [App\Http\Controllers\Admin\AlertController::class, 'selesai'])->name('admin.alert.selesai');
});
// AUTH
Route::middleware('guest')->group(function () {
    Route::get('/login',  [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
});

Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// ADMIN PANEL  (auth + role:admin)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,petugas'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Petani
    Route::get('petani/export', [PetaniController::class, 'export'])->name('petani.export');
    Route::patch('petani/{id}/toggle-status', [PetaniController::class, 'toggleStatus'])->name('petani.toggle-status');
    Route::resource('petani', PetaniController::class);

    // Panen
    Route::resource('panen', PanenController::class)->names('panen');

    // Stok Gudang
    Route::get ('stok',       [StokController::class, 'index'])->name('stok.index');
    Route::post('stok',       [StokController::class, 'store'])->name('stok.store');
    Route::get ('stok/{id}/edit', [StokController::class, 'edit'])->name('stok.edit');
    Route::put ('stok/{id}',  [StokController::class, 'update'])->name('stok.update');
    Route::get ('stok/{id}',  [StokController::class, 'show'])->name('stok.show');
    Route::patch('stok/{id}/toggle-status', [StokController::class, 'toggleStatus'])->name('stok.toggle-status');
    Route::delete('stok/{id}', [StokController::class, 'destroy'])->name('stok.destroy');
    Route::get('stok/summary', [StokController::class, 'summary'])->name('stok.summary');

    // Alert dapat diakses oleh admin dan petugas. Harga, laporan, pengguna, dan pengaturan hanya untuk admin.
    Route::middleware('role:admin,petugas')->group(function () {
        // Alert
        Route::get  ('alert',                    [AlertController::class, 'index'])    ->name('alert.index');
        Route::put  ('alert/konfigurasi',        [AlertController::class, 'konfigurasi'])->name('alert.konfigurasi');
        Route::patch('alert/{alert}/tangani',    [AlertController::class, 'tangani'])  ->name('alert.tangani');
        Route::delete('alert/{id}',              [AlertController::class, 'destroy'])  ->name('alert.destroy');
        Route::delete('alert',                   [AlertController::class, 'destroyAll'])->name('alert.destroyAll');

        // Tujuan Distribusi (manajemen)
        Route::get('tujuan-distribusi', [TujuanDistribusiController::class, 'index'])->name('tujuan-distribusi.index');
        Route::post('tujuan-distribusi', [TujuanDistribusiController::class, 'store'])->name('tujuan-distribusi.store');
        Route::delete('tujuan-distribusi/{id}', [TujuanDistribusiController::class, 'destroy'])->name('tujuan-distribusi.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        // Manajemen Harga
        Route::get('harga', [HargaController::class, 'index'])->name('harga.index');
        Route::get('harga/create', [HargaController::class, 'create'])->name('harga.create');
        Route::post('harga', [HargaController::class, 'store'])->name('harga.store');
        Route::get('harga/{harga}/edit', [HargaController::class, 'edit'])->name('harga.edit');
        Route::put('harga/{harga}', [HargaController::class, 'update'])->name('harga.update');
        Route::delete('harga/{harga}', [HargaController::class, 'destroy'])->name('harga.destroy');
        Route::patch('harga/{harga}/rasio', [HargaController::class, 'updateRasio'])->name('harga.updateRasio');
        Route::patch('harga/{harga}/activate', [HargaController::class, 'activate'])->name('harga.activate');

        // Laporan
        Route::get('laporan',        [LaporanController::class, 'index']) ->name('laporan.index');
        Route::get('laporan/export', [LaporanController::class, 'export'])->name('laporan.export');

        // Pengguna
        Route::resource('pengguna', PenggunaController::class)->names('pengguna');

        // Pengaturan
        Route::get('pengaturan', [PengaturanController::class, 'index'])->name('pengaturan');
        Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    });
});