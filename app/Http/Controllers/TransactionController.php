<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TransactionCreatedNotification;
use App\Models\User;
use Carbon\Carbon;
use App\Models\ActivityLog;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'search'    => 'nullable|string|max:100',
            'method'    => 'nullable|string|max:100',
            'status'    => 'nullable|in:completed,voided',
        ]);

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->subDays(30)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = Carbon::today()->subDays(30)->startOfDay();
            $dateTo   = Carbon::today()->endOfDay();
        }

        $baseQuery = fn() => Transaction::where('status', 'completed')
            ->whereBetween('created_at', [
                $dateFrom->toDateTimeString(),
                $dateTo->toDateTimeString(),
            ]);

        $voidedQuery = fn() => Transaction::where('status', 'voided')
            ->whereBetween('created_at', [
                $dateFrom->toDateTimeString(),
                $dateTo->toDateTimeString(),
            ]);

        $totalTransactions = $baseQuery()->count();
        $totalRevenue      = $baseQuery()->sum('total');
        $voidedCount       = $voidedQuery()->count();
        $voidedValue       = $voidedQuery()->sum('total');
        $avgBasket         = $totalTransactions > 0 ? round($totalRevenue / $totalTransactions) : 0;

        $diffDays    = $dateFrom->diffInDays($dateTo);
        $groupByHour = $diffDays === 0;

        $chartData = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [
                $dateFrom->toDateTimeString(),
                $dateTo->toDateTimeString(),
            ])
            ->when(
                $groupByHour,
                fn($q) => $q->selectRaw('HOUR(created_at) as label, COUNT(*) as count')
                            ->groupByRaw('HOUR(created_at)'),
                fn($q) => $q->selectRaw('DATE(created_at) as label, COUNT(*) as count')
                            ->groupByRaw('DATE(created_at)')
            )
            ->orderBy('label')
            ->get();

        $paymentBreakdown = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [
                $dateFrom->toDateTimeString(),
                $dateTo->toDateTimeString(),
            ])
            ->selectRaw('payment_method, COUNT(*) as trx_count, SUM(total) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();

        $popupQuery = fn() => Transaction::with(['cashier'])
            ->whereBetween('created_at', [
                $dateFrom->toDateTimeString(),
                $dateTo->toDateTimeString(),
            ])
            ->when(auth()->user()->role === 'cashier', fn($q) => $q->where('cashier_id', auth()->id()));

        $popupAllTx     = $popupQuery()->latest()->get();
        $popupRevenueTx = $popupQuery()->where('status', 'completed')->latest()->get();
        $popupVoidedTx  = $popupQuery()->where('status', 'voided')->latest()->get();

        $query = Transaction::with(['cashier', 'items.product'])->latest();

        if (auth()->user()->role === 'cashier') {
            $query->where('cashier_id', auth()->id());
        }

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $query->whereBetween('created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()]);

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate(10)->withQueryString();

        return view('pages.transactions', compact(
            'transactions',
            'totalTransactions', 'totalRevenue',
            'voidedCount', 'voidedValue', 'avgBasket',
            'paymentBreakdown', 'chartData', 'groupByHour',
            'popupAllTx', 'popupRevenueTx', 'popupVoidedTx',
            'dateFrom', 'dateTo'
        ));
    }

    public function export(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'search'    => 'nullable|string|max:100',
            'method'    => 'nullable|string|max:100',
            'status'    => 'nullable|in:completed,voided',
        ]);

        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->subDays(30)->startOfDay();

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        $query = Transaction::with(['cashier', 'items.product'])
            ->whereBetween('created_at', [
                $dateFrom->toDateTimeString(),
                $dateTo->toDateTimeString(),
            ]);

        if (auth()->user()->role === 'cashier') {
            $query->where('cashier_id', auth()->id());
        }
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->latest()->get();

        $filename = 'transaksi-' . $dateFrom->format('Ymd') . '-' . $dateTo->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            // BOM untuk Excel agar bisa baca UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Kode', 'Tanggal', 'Kasir', 'Payment Method', 'Total', 'Status']);

            foreach ($transactions as $trx) {
                fputcsv($handle, [
                    $trx->code,
                    $trx->created_at->format('d/m/Y H:i'),
                    $trx->cashier?->name ?? '-',
                    $trx->payment_method,
                    $trx->total,
                    $trx->status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        // Kritis: Sembunyikan 'buy_price' dari kasir, hanya kirim 'sell_price' dan atribut relevan
        $products = Product::where('qty', '>', 0)
            ->select('id', 'sku', 'name', 'category', 'unit', 'qty', 'threshold', 'sell_price', 'tag', 'photo')
            ->orderBy('name')
            ->get();
            
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        return view('pages.cashier', compact('products', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.id'     => 'required|exists:products,id',
            'items.*.qty'    => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'amount_paid'    => 'required|numeric|min:0',
        ]);

        $activeShift = \App\Models\Shift::where('user_id', auth()->id())
            ->whereDate('started_at', \Carbon\Carbon::today())
            ->whereNull('ended_at')
            ->exists();

        if (!$activeShift && auth()->user()->role === 'cashier') {
            return response()->json(['success' => false, 'message' => 'Anda harus melakukan Clock-In shift hari ini terlebih dahulu sebelum bertransaksi.'], 422);
        }

        try {
            $transaction = DB::transaction(function () use ($request) {
                $total = 0;
                $itemsToCreate = [];

                foreach ($request->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['id']);

                    if ($product->qty < $item['qty']) {
                        throw new \Exception("Stok {$product->name} tidak cukup.");
                    }

                    $subtotal = $product->sell_price * $item['qty'];
                    $total   += $subtotal;

                    $itemsToCreate[] = [
                        'product_id' => $product->id,
                        'qty'        => $item['qty'],
                        'unit'       => $product->unit,
                        'buy_price'  => $product->buy_price,
                        'price'      => $product->sell_price,
                        'subtotal'   => $subtotal,
                    ];

                    $product->decrement('qty', $item['qty']);
                }

                $transaction = Transaction::create([
                    'code'           => 'TRX-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                    'cashier_id'     => auth()->id(),
                    'total'          => $total,
                    'amount_paid'    => $request->amount_paid,
                    'change'         => $request->amount_paid - $total,
                    'payment_method' => $request->payment_method,
                    'status'         => 'completed',
                ]);

                $transaction->items()->createMany($itemsToCreate);

                // Logging di sini, setelah semua data tersedia
                ActivityLog::record(
                    'TRANSACTION',
                    'Transaksi baru dibuat',
                    $transaction->code,
                    ['total' => $total, 'payment_method' => $request->payment_method, 'items_count' => count($itemsToCreate)]
                );

                return $transaction;
            });

            // Kirim notifikasi ke semua admin SETELAH DB transaction commit berhasil
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new TransactionCreatedNotification($transaction));

            return response()->json(['success' => true, 'redirect' => route('transactions.index')]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(Transaction $transaction)
    {
        // Kasir hanya bisa melihat detail transaksi milik sendiri
        if (auth()->user()->role === 'cashier' && $transaction->cashier_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki hak untuk melihat transaksi ini.');
        }

        $transaction->load(['cashier', 'items.product']);
        return view('pages.transaction-detail', compact('transaction'));
    }

    public function void(Transaction $transaction)
    {
        if ($transaction->status !== 'completed') {
            return back()->with('error', 'Transaksi ini tidak bisa di-void.');
        }

        DB::transaction(function () use ($transaction) {
            // Kembalikan stok
            foreach ($transaction->items as $item) {
                $productToRestore = \App\Models\Product::lockForUpdate()->findOrFail($item->product_id);
                $productToRestore->increment('qty', $item->qty);
            }

            $transaction->update(['status' => 'voided']);

            ActivityLog::record(
                'VOID',
                'Transaksi di-void',
                $transaction->code,
                ['total' => $transaction->total]
            );
        });

        return back()->with('success', 'Transaksi berhasil di-void.');
    }

    public function receipt(Transaction $transaction)
    {
        // Kasir hanya bisa cetak struk milik sendiri
        if (auth()->user()->role === 'cashier' && $transaction->cashier_id !== auth()->id()) {
            abort(403);
        }

        $transaction->load(['cashier', 'items.product']);
        return view('pages.receipt', compact('transaction'));
    }
}