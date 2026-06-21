<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.payment-methods');
    }

    public function paymentMethods()
    {
        $today   = Carbon::today();
        $methods = PaymentMethod::all();

        // Stat cards
        $activeCount    = $methods->where('is_active', true)->count();
        $digitalToday = Transaction::whereDate('transactions.created_at', $today)
            ->join('payment_methods', 'transactions.payment_method', '=', 'payment_methods.name')
            ->where('payment_methods.type', 'digital')
            ->count();

        $cashToday = Transaction::whereDate('transactions.created_at', $today)
            ->join('payment_methods', 'transactions.payment_method', '=', 'payment_methods.name')
            ->where('payment_methods.type', 'cash')
            ->count();

        // Performance tabel
        $performance = PaymentMethod::withCount([
                'transactions as trx_count' => fn($q) =>
                    $q->whereDate('created_at', $today)->where('status', 'completed')
            ])
            ->withSum([
                'transactions as revenue' => fn($q) =>
                    $q->whereDate('created_at', $today)->where('status', 'completed')
            ], 'total')
            ->get();

        // 7-day trend per method
        $trend = Transaction::whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, payment_method, COUNT(*) as count')
            ->groupBy('date', 'payment_method')
            ->orderBy('date')
            ->get()
            ->groupBy('payment_method');

        return view('pages.payment-methods', compact(
            'methods', 'activeCount', 'digitalToday', 'cashToday',
            'performance', 'trend'
        ));
    }

    public function storePaymentMethod(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100|unique:payment_methods,name',
            'type'     => 'required|in:digital,cash,edc',
            'provider' => 'nullable|string|max:100',
            'mdr_fee'  => 'nullable|numeric|min:0|max:100',
            'notes'    => 'nullable|string',
        ]);

        PaymentMethod::create([
            'name'      => $request->name,
            'type'      => $request->type,
            'provider'  => $request->provider,
            'mdr_fee'   => $request->mdr_fee ?? 0,
            'notes'     => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Payment method berhasil ditambahkan.');
    }

    public function togglePaymentMethod(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);
        return back()->with('success', 'Status payment method diperbarui.');
    }
}