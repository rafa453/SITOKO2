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
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierReturnController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BrandController;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory — static routes DULU, baru wildcard
    Route::get('/inventory', [ProductController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/{product}', [ProductController::class, 'show'])
        ->name('inventory.show')
        ->whereNumber('product');

    // Transactions — static routes DULU, baru wildcard
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show')->whereNumber('transaction');

    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt')->whereNumber('transaction');

    // Shift kasir
    Route::post('/shifts/clock-in', [ShiftController::class, 'clockIn'])->name('shifts.clock-in');
    Route::post('/shifts/clock-out', [ShiftController::class, 'clockOut'])->name('shifts.clock-out');

    // ── Admin only ──────────────────────────────────────────────
    Route::middleware(['role:admin'])->group(function () {

        // Inventory CRUD — /create dan /store DULU, baru {product}
        Route::get('/inventory/create', [ProductController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [ProductController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{product}/edit', [ProductController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{product}', [ProductController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{product}', [ProductController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/inventory/{product}/restock', [ProductController::class, 'restock'])->name('inventory.restock');
        Route::get('/inventory/{product}/detail', [ProductController::class, 'detail'])->name('inventory.detail');

        // Transactions — void admin
        Route::post('/transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');

        // Staff
        Route::resource('staff', StaffController::class)->except(['destroy']);

        // Suppliers
        Route::resource('suppliers', SupplierController::class)->except(['show']);
        Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggle-active');

        // Purchase Orders — /create DULU, baru {purchaseOrder}
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit'); 
        Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update'); 
        Route::post('/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.update-status');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show')->whereNumber('purchaseOrder');

        // Reports — semua static routes DULU
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/export-custom', [ReportController::class, 'exportCustom'])->name('reports.export-custom');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');

        // Settings
        Route::get('/settings', [PaymentMethodController::class, 'index'])->name('settings.index');
        Route::get('/settings/payment-methods', [PaymentMethodController::class, 'paymentMethods'])->name('settings.payment-methods');
        Route::post('/settings/payment-methods', [PaymentMethodController::class, 'storePaymentMethod'])->name('settings.payment-methods.store');
        Route::patch('/settings/payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'togglePaymentMethod'])->name('settings.payment-methods.toggle');

        // Shifts management — /summary DULU sebelum resource wildcard
        Route::get('/shifts/summary', [ShiftController::class, 'summary'])->name('shifts.summary');
        Route::resource('shifts', ShiftController::class)->except(['show', 'edit', 'create']);

        // Supplier Returns
        Route::get('/supplier-returns', [SupplierReturnController::class, 'index'])->name('supplier-returns.index');
        Route::get('/supplier-returns/create', [SupplierReturnController::class, 'create'])->name('supplier-returns.create');
        Route::post('/supplier-returns', [SupplierReturnController::class, 'store'])->name('supplier-returns.store');
        Route::post('/supplier-returns/{supplierReturn}/status', [SupplierReturnController::class, 'updateStatus'])->name('supplier-returns.update-status');
        Route::get('/supplier-returns/{supplierReturn}', [SupplierReturnController::class, 'show'])->name('supplier-returns.show')->whereNumber('supplierReturn');

        // routes/web.php — tambahkan di grup admin
        Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);

        // API endpoint untuk PO form — ambil produk berdasarkan supplier
        Route::get('/api/suppliers/{supplier}/products', [ProductController::class, 'bySupplier'])->name('api.supplier.products');

        // API Notifications
        Route::get('/api/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/api/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/api/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });

});

require __DIR__.'/auth.php';