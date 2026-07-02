<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.payment-methods');
    }

    public function paymentMethods(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::today()->subDays(30)->startOfDay();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            $dateFrom = Carbon::today()->subDays(30)->startOfDay();
            $dateTo   = Carbon::today()->endOfDay();
        }

        $methods = PaymentMethod::all();
        $activeCount = $methods->where('is_active', true)->count();

        // Stat cards
        $digitalToday = Transaction::whereBetween('transactions.created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()])
            ->join('payment_methods', 'transactions.payment_method', '=', 'payment_methods.name')
            ->where('payment_methods.type', 'digital')
            ->count();

        $cashToday = Transaction::whereBetween('transactions.created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()])
            ->join('payment_methods', 'transactions.payment_method', '=', 'payment_methods.name')
            ->where('payment_methods.type', 'cash')
            ->count();

        // Performance tabel
        $performance = PaymentMethod::withCount([
                'transactions as trx_count' => fn($q) =>
                    $q->whereBetween('created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()])->where('status', 'completed')
            ])
            ->withSum([
                'transactions as revenue' => fn($q) =>
                    $q->whereBetween('created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()])->where('status', 'completed')
            ], 'total')
            ->get();

        // Trend chart
        $trend = Transaction::whereBetween('created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()])
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, payment_method, COUNT(*) as count')
            ->groupBy('date', 'payment_method')
            ->orderBy('date')
            ->get()
            ->groupBy('payment_method');

        return view('pages.payment-methods', compact(
            'methods', 'activeCount', 'digitalToday', 'cashToday',
            'performance', 'trend', 'dateFrom', 'dateTo'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'code'     => 'required|string|unique:payment_methods,code|max:50',
            'type'     => 'required|in:digital,cash,edc',
            'provider' => 'nullable|string|max:100',
            'mdr_fee'  => 'nullable|numeric|min:0|max:100',
            'notes'    => 'nullable|string',
        ]);

        PaymentMethod::create([
            'name'      => $request->name,
            'code'      => Str::slug($request->code),
            'type'      => $request->type,
            'provider'  => $request->provider,
            'mdr_fee'   => $request->mdr_fee ?? 0,
            'notes'     => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back()->with('success', 'Metode pembayaran berhasil ditambahkan!');
    }

    public function togglePaymentMethod(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);
        return back()->with('success', 'Status payment method diperbarui.');
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'name'     => 'required|string|max:100|unique:payment_methods,name,' . $paymentMethod->id,
            'code'     => 'required|string|max:50|unique:payment_methods,code,' . $paymentMethod->id,
            'type'     => 'required|in:digital,cash,edc',
            'provider' => 'nullable|string|max:100',
            'mdr_fee'  => 'nullable|numeric|min:0|max:100',
            'notes'    => 'nullable|string',
        ]);

        $data = $request->only('name', 'type', 'provider', 'mdr_fee', 'notes');
        $data['code'] = \Illuminate\Support\Str::slug($request->code);
        
        $paymentMethod->update($data);

        return back()->with('success', 'Payment method berhasil diupdate.');
    }

    public function updateStoreProfile(Request $request)
    {
        $request->validate([
            'store_name'     => 'required|string|max:255',
            'store_subtitle' => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'city'           => 'nullable|string|max:100',
        ]);

        $store = \App\Models\StoreProfile::get();
        $store->update($request->only('store_name', 'store_subtitle', 'address', 'phone', 'city'));

        return back()->with('success', 'Store profile berhasil diperbarui.');
    }
}