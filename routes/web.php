<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Mengarahkan root (/) langsung ke halaman dashboard sesuai bawaan MarketOS
Route::get('/', fn() => redirect()->route('dashboard'));

// Admin panel — Semua rute di bawah ini wajib login (Auth)
Route::middleware(['auth'])->group(function () {

    // Dashboard (Menggunakan DashboardController dari MarketOS)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Bawaan Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory
    Route::resource('inventory', InventoryController::class);

    // Transactions
    Route::resource('transactions', TransactionController::class);
    Route::post('/transactions/{id}/void', [TransactionController::class, 'void'])->name('transactions.void');

    // Staff
    Route::resource('staff', StaffController::class);

    // Reports
    Route::get('/reports',         [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export',  [ReportController::class, 'export'])->name('reports.export');
    Route::post('/reports/generate',[ReportController::class, 'generate'])->name('reports.generate');

    // Settings — Payment Methods
    Route::get('/settings',                   [SettingController::class, 'index'])->name('settings.index');
    Route::get('/settings/payment-methods',   [SettingController::class, 'paymentMethods'])->name('settings.payment-methods');
    Route::post('/settings/payment-methods',  [SettingController::class, 'storePaymentMethod'])->name('settings.payment-methods.store');
    Route::patch('/settings/payment-methods/{id}/toggle', [SettingController::class, 'togglePaymentMethod'])->name('settings.payment-methods.toggle');

});

// Memuat rute autentikasi bawaan Laravel Breeze (Login, Register, Logout, dll.)
require __DIR__.'/auth.php';