<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
{
    $today = Carbon::today();

    // Pakai try-catch supaya kalau kolom belum ada, tidak crash
    try {
        $todayRevenue = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total');
    } catch (\Exception $e) {
        $todayRevenue = 0;
    }

    try {
        $onDutyStaff = Shift::whereDate('started_at', $today)
            ->whereNull('ended_at')
            ->count();
    } catch (\Exception $e) {
        $onDutyStaff = 0;
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

    $topProducts   = collect();
    $liveInventory = collect();

    try {
        $liveInventory = Product::orderBy('qty')->limit(8)->get();
    } catch (\Exception $e) {}

    return view('pages.dashboard', compact(
        'todayRevenue',
        'onDutyStaff',
        'lowStockCount',
        'outOfStockCount',
        'topProducts',
        'liveInventory'
    ));
}
}