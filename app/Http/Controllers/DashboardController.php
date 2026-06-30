<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\TransactionItem;

class DashboardController extends Controller
{
    public function index()
{
    $today = Carbon::today();

    try {
        $todayRevenue = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total');
    } catch (\Exception $e) {
        $todayRevenue = 0;
    }

    try {
        $todayTransactions = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->with('cashier')
            ->latest()
            ->get();
    } catch (\Exception $e) {
        $todayTransactions = collect();
    }

    try {
        $onDutyStaff = Shift::whereDate('started_at', $today)
            ->whereNull('ended_at')
            ->count();
    } catch (\Exception $e) {
        $onDutyStaff = 0;
    }

    try {
        $onDutyShifts = Shift::whereDate('started_at', $today)
            ->whereNull('ended_at')
            ->with('user')
            ->get();
    } catch (\Exception $e) {
        $onDutyShifts = collect();
    }

    try {
        $lowStockCount = Product::whereColumn('qty', '<=', 'threshold')
            ->where('qty', '>', 0)
            ->count();
    } catch (\Exception $e) {
        $lowStockCount = 0;
    }

    try {
        $outOfStockCount = Product::where('qty', 0)->count();
    } catch (\Exception $e) {
        $outOfStockCount = 0;
    }

    try {
        $stockAlertProducts = Product::where('qty', 0)
            ->orWhere(function ($q) {
                $q->whereColumn('qty', '<=', 'threshold')->where('qty', '>', 0);
            })
            ->orderBy('qty')
            ->get();
    } catch (\Exception $e) {
        $stockAlertProducts = collect();
    }

    // FIX: sebelumnya tidak difilter tanggal, padahal card-nya "Top Selling Item TODAY"
    $topProducts = TransactionItem::join('products', 'transaction_items.product_id', '=', 'products.id')
        ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
        ->whereDate('transactions.created_at', $today)
        ->where('transactions.status', 'completed')
        ->selectRaw('products.name, SUM(transaction_items.subtotal) as revenue, SUM(transaction_items.qty) as qty_sold')
        ->groupBy('products.id', 'products.name')
        ->orderByDesc('revenue')
        ->limit(5)
        ->get();

    $liveInventory = collect();
    try {
        $liveInventory = Product::orderBy('qty')->limit(8)->get();
    } catch (\Exception $e) {}

    return view('pages.dashboard', compact(
        'todayRevenue',
        'todayTransactions',
        'onDutyStaff',
        'onDutyShifts',
        'lowStockCount',
        'outOfStockCount',
        'stockAlertProducts',
        'topProducts',
        'liveInventory'
    ));
    }
}