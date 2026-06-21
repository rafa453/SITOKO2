<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $query = Transaction::with(['cashier', 'items'])
            ->latest();

        if ($request->filled('search')) {
            $query->where('id', 'like', '%' . $request->search . '%');
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

        DB::transaction(function () use ($request) {
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
                    'price'      => $product->sell_price,
                    'subtotal'   => $subtotal,
                ];

                // Kurangi stok
                $product->decrement('qty', $item['qty']);
            }

            $transaction = Transaction::create([
                'cashier_id'     => auth()->id(),
                'total'          => $total,
                'amount_paid'    => $request->amount_paid,
                'change'         => $request->amount_paid - $total,
                'payment_method' => $request->payment_method,
                'status'         => 'completed',
            ]);

            $transaction->items()->createMany($itemsToCreate);
        });

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    public function show(Transaction $transaction)
    {
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
                $item->product->increment('qty', $item->qty);
            }

            $transaction->update(['status' => 'voided']);
        });

        return back()->with('success', 'Transaksi berhasil di-void.');
    }
}