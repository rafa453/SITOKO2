<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SupplierController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Mengarahkan root (/) langsung ke halaman dashboard sesuai bawaan MarketOS
Route::get('/', fn() => redirect()->route('dashboard'));

// Admin panel — Semua rute di bawah ini wajib login (Auth)
Route::middleware(['auth'])->group(function () {

    // ── Semua role (admin + kasir) ──────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory — kasir hanya boleh index
    Route::get('/inventory', [ProductController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/{product}', [ProductController::class, 'show'])->name('inventory.show');

    // Transactions — kasir bisa create + lihat milik sendiri
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt'); 

    // Shift kasir
    Route::post('/shifts/clock-in',  [ShiftController::class, 'clockIn'])->name('shifts.clock-in');
    Route::post('/shifts/clock-out', [ShiftController::class, 'clockOut'])->name('shifts.clock-out');


    // ── Admin only ──────────────────────────────────────────────
    Route::middleware(['role:admin'])->group(function () {

        // Inventory CRUD (create, edit, update, delete, restock)
        Route::get('/inventory/create', [ProductController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [ProductController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{product}/edit', [ProductController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{product}', [ProductController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{product}', [ProductController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/inventory/{product}/restock', [ProductController::class, 'restock'])->name('inventory.restock');

        // Transactions — void hanya admin
        Route::post('/transactions/{id}/void', [TransactionController::class, 'void'])->name('transactions.void');

        // Staff
        Route::resource('staff', StaffController::class);

        // Suppliers
        Route::resource('suppliers', SupplierController::class)->except(['show']);
        Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggle-active');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/export-custom', [ReportController::class, 'exportCustom'])->name('reports.export-custom');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');

        // Settings
        Route::get('/settings', [PaymentMethodController::class, 'index'])->name('settings.index');
        Route::get('/settings/payment-methods', [PaymentMethodController::class, 'paymentMethods'])->name('settings.payment-methods');
        Route::post('/settings/payment-methods', [PaymentMethodController::class, 'storePaymentMethod'])->name('settings.payment-methods.store');
        Route::patch('/settings/payment-methods/{id}/toggle', [PaymentMethodController::class, 'togglePaymentMethod'])->name('settings.payment-methods.toggle');

        // Shifts management
        Route::resource('shifts', ShiftController::class)->except(['show', 'edit', 'create']);
        Route::get('/shifts/summary', [ShiftController::class, 'summary'])->name('shifts.summary');
    });

});

// Memuat rute autentikasi bawaan Laravel Breeze (Login, Register, Logout, dll.)
require __DIR__.'/auth.php';