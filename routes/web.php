<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PaymentMethodController;

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
    Route::resource('inventory', ProductController::class);

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
    Route::get('/settings',                   [PaymentMethodController::class, 'index'])->name('settings.index');
    Route::get('/settings/payment-methods',   [PaymentMethodController::class, 'paymentMethods'])->name('settings.payment-methods');
    Route::post('/settings/payment-methods',  [PaymentMethodController::class, 'storePaymentMethod'])->name('settings.payment-methods.store');
    Route::patch('/settings/payment-methods/{id}/toggle', [PaymentMethodController::class, 'togglePaymentMethod'])->name('settings.payment-methods.toggle');

    // Admin — kelola shift
    Route::resource('shifts', ShiftController::class)->except(['show', 'edit', 'create']);

    // Kasir — clock in / clock out
    Route::post('/shifts/clock-in',  [ShiftController::class, 'clockIn'])->name('shifts.clock-in');
    Route::post('/shifts/clock-out', [ShiftController::class, 'clockOut'])->name('shifts.clock-out');

    // Laporan shift (dipanggil via AJAX dari Reports page)
    Route::get('/shifts/summary', [ShiftController::class, 'summary'])->name('shifts.summary');


});

// Memuat rute autentikasi bawaan Laravel Breeze (Login, Register, Logout, dll.)
require __DIR__.'/auth.php';