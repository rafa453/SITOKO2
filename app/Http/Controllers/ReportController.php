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

        // Top kategori (revenue tertinggi) — dipakai di stat card pengganti Net Profit
        $topCategory = $categoryRevenue->first();

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
                  ->where(function($q) {
                      $q->whereColumn('transactions.created_at', '<=', 'shifts.ended_at')
                        ->orWhereNull('shifts.ended_at');
                  })
            )
            ->selectRaw('shifts.type as shift, COUNT(DISTINCT shifts.user_id) as staff_count,
                         COUNT(transactions.id) as trx_count, SUM(transactions.total) as revenue')
            ->groupBy('shifts.type')
            ->get();

        return view('pages.reports', compact(
            'totalRevenue', 'totalTrx', 'netProfit', 'bestSeller', 'topCategory',
            'dailyRevenue', 'topProducts', 'categoryRevenue',
            'cashierPerformance', 'inventoryMovement', 'shiftSummary',
            'period'
        ));
    }

    public function exportCustom(Request $request)
{
    $type    = $request->get('type', 'sales');
    $range   = $request->get('range', 'this_month');
    $groupBy = $request->get('group_by', 'product');

    [$startDate, $endDate] = $this->resolvePeriod($range);

    $filename = 'report_' . $type . '_' . $groupBy . '_' . now()->format('Ymd_His') . '.csv';

    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function () use ($type, $groupBy, $startDate, $endDate) {
        $out = fopen('php://output', 'w');

        // BOM supaya Excel baca UTF-8 dengan benar
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($type === 'sales') {
            $this->exportSales($out, $groupBy, $startDate, $endDate);
        } elseif ($type === 'inventory') {
            $this->exportInventory($out, $groupBy, $startDate, $endDate);
        } elseif ($type === 'staff') {
            $this->exportStaff($out, $groupBy, $startDate, $endDate);
        }

        fclose($out);
    };

    return response()->stream($callback, 200, $headers);
}

private function exportSales($out, string $groupBy, $startDate, $endDate): void
{
    $totalRevenue = Transaction::whereBetween('created_at', [$startDate, $endDate])
        ->where('status', 'completed')->sum('total') ?: 1;

    if ($groupBy === 'category') {
        fputcsv($out, ['Kategori', 'Jumlah Transaksi Item', 'Total Qty Terjual', 'Revenue (Rp)', '% dari Total']);
        $rows = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->selectRaw('products.category as nama,
                         COUNT(DISTINCT transactions.id) as jumlah_trx,
                         SUM(transaction_items.qty) as total_qty,
                         SUM(transaction_items.subtotal) as revenue')
            ->groupBy('products.category')
            ->orderByDesc('revenue')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->nama,
                $r->jumlah_trx,
                $r->total_qty,
                $r->revenue,
                round($r->revenue / $totalRevenue * 100, 2) . '%',
            ]);
        }

    } elseif ($groupBy === 'product') {
        fputcsv($out, ['Produk', 'Kategori', 'Total Qty Terjual', 'Revenue (Rp)', '% dari Total']);
        $rows = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->selectRaw('products.name as nama,
                         products.category as kategori,
                         SUM(transaction_items.qty) as total_qty,
                         SUM(transaction_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderByDesc('revenue')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->nama,
                $r->kategori,
                $r->total_qty,
                $r->revenue,
                round($r->revenue / $totalRevenue * 100, 2) . '%',
            ]);
        }

    } elseif ($groupBy === 'cashier') {
        fputcsv($out, ['Kasir', 'Jumlah Transaksi', 'Total Revenue (Rp)', '% dari Total']);
        $rows = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->join('users', 'transactions.cashier_id', '=', 'users.id')
            ->selectRaw('users.name as nama,
                         COUNT(transactions.id) as jumlah_trx,
                         SUM(transactions.total) as revenue')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->nama,
                $r->jumlah_trx,
                $r->revenue,
                round($r->revenue / $totalRevenue * 100, 2) . '%',
            ]);
        }

    } elseif ($groupBy === 'shift') {
        // Sales group by shift — pakai join ke shifts
        fputcsv($out, ['Shift', 'Jumlah Transaksi', 'Total Revenue (Rp)', '% dari Total']);
        $rows = Transaction::whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'completed')
            ->join('shifts', fn($j) =>
                $j->on('transactions.cashier_id', '=', 'shifts.user_id')
                  ->whereColumn('transactions.created_at', '>=', 'shifts.started_at')
                  ->where(function($q) {
                      $q->whereColumn('transactions.created_at', '<=', 'shifts.ended_at')
                        ->orWhereNull('shifts.ended_at');
                  })
            )
            ->selectRaw('shifts.type as shift,
                         COUNT(DISTINCT transactions.id) as jumlah_trx,
                         SUM(transactions.total) as revenue')
            ->groupBy('shifts.type')
            ->orderByDesc('revenue')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                ucfirst($r->shift),
                $r->jumlah_trx,
                $r->revenue,
                round($r->revenue / $totalRevenue * 100, 2) . '%',
            ]);
        }
    }
}

private function exportInventory($out, string $groupBy, $startDate, $endDate): void
{
    if ($groupBy === 'product' || $groupBy === 'cashier' || $groupBy === 'shift') {
        // cashier & shift tidak valid untuk inventory — fallback ke product
        fputcsv($out, ['Produk', 'Kategori', 'Stok Saat Ini', 'Total Terjual (Periode)', 'Estimasi Nilai Stok (Rp)']);
        $rows = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->selectRaw('products.name as nama,
                         products.category as kategori,
                         products.qty as stok,
                         products.sell_price as harga,
                         SUM(transaction_items.qty) as terjual')
            ->groupBy('products.id', 'products.name', 'products.category', 'products.qty', 'products.sell_price')
            ->orderByDesc('terjual')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->nama,
                $r->kategori,
                $r->stok,
                $r->terjual,
                $r->stok * $r->harga,
            ]);
        }

    } elseif ($groupBy === 'category') {
        fputcsv($out, ['Kategori', 'Jumlah Produk', 'Total Stok Saat Ini', 'Total Terjual (Periode)', 'Estimasi Nilai Stok (Rp)']);
        $rows = TransactionItem::whereBetween('transaction_items.created_at', [$startDate, $endDate])
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->selectRaw('products.category as kategori,
                         COUNT(DISTINCT products.id) as jumlah_produk,
                         SUM(products.qty) as total_stok,
                         SUM(transaction_items.qty) as terjual,
                         SUM(products.qty * products.sell_price) as nilai_stok')
            ->groupBy('products.category')
            ->orderByDesc('terjual')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->kategori,
                $r->jumlah_produk,
                $r->total_stok,
                $r->terjual,
                $r->nilai_stok,
            ]);
        }
    }
}

private function exportStaff($out, string $groupBy, $startDate, $endDate): void
{
    if ($groupBy === 'cashier' || $groupBy === 'product' || $groupBy === 'category') {
        // product & category tidak valid untuk staff — fallback ke cashier
        fputcsv($out, ['Kasir', 'Jumlah Transaksi', 'Total Revenue (Rp)', 'Rata-rata per Transaksi (Rp)']);
        $rows = User::withCount([
                'transactions as jumlah_trx' => fn($q) =>
                    $q->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'completed')
            ])
            ->withSum([
                'transactions as revenue' => fn($q) =>
                    $q->whereBetween('created_at', [$startDate, $endDate])
                      ->where('status', 'completed')
            ], 'total')
            ->having('jumlah_trx', '>', 0)
            ->orderByDesc('revenue')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->name,
                $r->jumlah_trx,
                $r->revenue,
                $r->jumlah_trx > 0 ? round($r->revenue / $r->jumlah_trx) : 0,
            ]);
        }

    } elseif ($groupBy === 'shift') {
        fputcsv($out, ['Shift', 'Jumlah Staff', 'Jumlah Transaksi', 'Total Revenue (Rp)', 'Rata-rata per Staff (Rp)']);
        $rows = Transaction::whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'completed')
            ->join('shifts', fn($j) =>
                $j->on('transactions.cashier_id', '=', 'shifts.user_id')
                  ->whereColumn('transactions.created_at', '>=', 'shifts.started_at')
                  ->where(function($q) {
                      $q->whereColumn('transactions.created_at', '<=', 'shifts.ended_at')
                        ->orWhereNull('shifts.ended_at');
                  })
            )
            ->selectRaw('shifts.type as shift,
                         COUNT(DISTINCT shifts.user_id) as jumlah_staff,
                         COUNT(DISTINCT transactions.id) as jumlah_trx,
                         SUM(transactions.total) as revenue')
            ->groupBy('shifts.type')
            ->orderByDesc('revenue')
            ->get();

        foreach ($rows as $r) {
            fputcsv($out, [
                ucfirst($r->shift),
                $r->jumlah_staff,
                $r->jumlah_trx,
                $r->revenue,
                $r->jumlah_staff > 0 ? round($r->revenue / $r->jumlah_staff) : 0,
            ]);
        }
    }
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