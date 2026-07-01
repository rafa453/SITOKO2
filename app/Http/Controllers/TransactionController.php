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
        $today = Carbon::today();

        $query = Transaction::with(['cashier', 'items.product'])->latest();

        if (auth()->user()->role === 'cashier') {
            $query->where('cashier_id', auth()->id());
        }

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->get('date') === 'today') {
            $query->whereDate('created_at', $today);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate(10)->withQueryString();

        // Stat cards
        $totalTransactions = Transaction::whereDate('created_at', $today)->count();
        $totalRevenue      = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')->sum('total');
        $voidedCount       = Transaction::whereDate('created_at', $today)
            ->where('status', 'voided')->count();
        $voidedValue       = Transaction::whereDate('created_at', $today)
            ->where('status', 'voided')->sum('total');
        $avgBasket         = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')->avg('total') ?? 0;

        // Payment breakdown hari ini
        $paymentBreakdown = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as trx_count, SUM(total) as revenue')
            ->groupBy('payment_method')
            ->get();

        // Hourly volume (07.00–21.00)
        $hourlyVolume = Transaction::whereDate('created_at', $today)
        ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
        ->groupBy('hour')
        ->orderBy('hour')
        ->pluck('count', 'hour');

        return view('pages.transactions', compact(
            'transactions',
            'totalTransactions', 'totalRevenue',
            'voidedCount', 'voidedValue', 'avgBasket',
            'paymentBreakdown', 'hourlyVolume'
        ));
    }

    public function create()
    {
        $products       = Product::where('qty', '>', 0)->orderBy('name')->get();
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