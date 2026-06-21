<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Shift;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'this_month');
        [$startDate, $endDate] = $this->resolvePeriod($period);

        // Stat cards — tidak ada JOIN, pakai created_at biasa
        $totalRevenue = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')->sum('total');

        $totalTrx = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')->count();

        // Ada JOIN — wajib prefix tabel
        $netProfit = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->selectRaw('SUM((transaction_items.price - products.buy_price) * transaction_items.qty) as profit')
            ->value('profit') ?? 0;

        // Produk terlaris
        $bestSeller = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->selectRaw('products.name, products.category, SUM(transaction_items.qty) as total_sold')
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderByDesc('total_sold')
            ->first();

        // Daily revenue — tidak ada JOIN
        $dailyRevenue = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top 5 produk — ada JOIN
        $topProducts = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(transaction_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Revenue by category — ada JOIN
        $categoryRevenue = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->selectRaw('products.category, SUM(transaction_items.subtotal) as revenue')
            ->groupBy('products.category')
            ->orderByDesc('revenue')
            ->get();

        // Cashier performance — withCount/withSum tidak pakai JOIN eksplisit
        $cashierPerformance = User::withCount([
                'transactions as trx_count' => fn($q) =>
                    $q->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'completed')
            ])
            ->withSum([
                'transactions as revenue' => fn($q) =>
                    $q->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'completed')
            ], 'total')
            ->orderByDesc('revenue')
            ->limit(3)
            ->get();

        // Inventory movement — ada JOIN
        $inventoryMovement = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->selectRaw('products.name, products.qty as closing, SUM(transaction_items.qty) as sold')
            ->groupBy('products.id', 'products.name', 'products.qty')
            ->orderByDesc('sold')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name'    => $item->name,
                'opening' => $item->closing + $item->sold,
                'sold'    => $item->sold,
                'closing' => $item->closing,
            ]);

        // Shift summary — ada JOIN, prefix transactions
        $shiftSummary = Transaction::whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'completed')
            ->join('shifts', fn($j) =>
                $j->on('transactions.cashier_id', '=', 'shifts.user_id')
                  ->whereColumn('transactions.created_at', '>=', 'shifts.started_at')
            )
            ->selectRaw('shifts.type as shift, COUNT(DISTINCT shifts.user_id) as staff_count,
                         COUNT(transactions.id) as trx_count, SUM(transactions.total) as revenue')
            ->groupBy('shifts.type')
            ->get();

        return view('pages.reports', compact(
            'totalRevenue', 'totalTrx', 'netProfit', 'bestSeller',
            'dailyRevenue', 'topProducts', 'categoryRevenue',
            'cashierPerformance', 'inventoryMovement', 'shiftSummary',
            'period'
        ));
    }

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today'      => [Carbon::today(),            Carbon::now()],
            'last_7'     => [Carbon::now()->subDays(7),  Carbon::now()],
            'last_month' => [Carbon::now()->startOfMonth()->subMonth(), Carbon::now()->subMonth()->endOfMonth()],
            default      => [Carbon::now()->startOfMonth(), Carbon::now()],
        };
    }
}