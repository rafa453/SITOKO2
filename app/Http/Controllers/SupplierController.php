<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $suppliers  = $query->paginate(10)->withQueryString();
        $categories = Supplier::distinct()->orderBy('category')->pluck('category')->filter();

        return view('pages.suppliers', compact('suppliers', 'categories'));
    }

    public function create()
    {
        $categories = Supplier::distinct()->orderBy('category')->pluck('category')->filter();
        return view('pages.supplier-form', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'phone'                => 'nullable|string|max:20',
            'address'              => 'nullable|string|max:500',
            'category'             => 'nullable|string|max:255',
            'brand'                => 'nullable|string|max:255',
            'bank_name'            => 'nullable|string|max:100',
            'bank_account_number'  => 'nullable|string|max:50',
            'bank_account_holder'  => 'nullable|string|max:255',
        ]);

        $supplier = Supplier::create($validated);

        ActivityLog::record(
            'SUPPLIER',
            'Supplier baru ditambahkan',
            $supplier->name,
            ['category' => $supplier->category]
        );

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        $categories = Supplier::distinct()->orderBy('category')->pluck('category')->filter();
        return view('pages.supplier-form', compact('supplier', 'categories'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'phone'                => 'nullable|string|max:20',
            'address'              => 'nullable|string|max:500',
            'category'             => 'nullable|string|max:255',
            'brand'                => 'nullable|string|max:255',
            'bank_name'            => 'nullable|string|max:100',
            'bank_account_number'  => 'nullable|string|max:50',
            'bank_account_holder'  => 'nullable|string|max:255',
        ]);

        $supplier->update($validated);

        ActivityLog::record(
            'SUPPLIER',
            'Supplier diupdate',
            $supplier->name,
            ['category' => $supplier->category]
        );

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil diupdate.');
    }

    public function toggleActive(Supplier $supplier)
    {
        $supplier->update(['is_active' => !$supplier->is_active]);

        ActivityLog::record(
            'SUPPLIER',
            $supplier->is_active ? 'Supplier diaktifkan' : 'Supplier dinonaktifkan',
            $supplier->name
        );

        return back()->with('success', 'Status supplier berhasil diubah.');
    }

    public function destroy(Supplier $supplier)
    {
        // Proteksi: supplier yang punya PO atau Retur tidak bisa dihapus
        if ($supplier->purchaseOrders()->exists() || $supplier->supplierReturns()->exists()) {
            return back()->with('error', 'Supplier tidak bisa dihapus karena memiliki riwayat Purchase Order atau Retur.');
        }

        ActivityLog::record(
            'SUPPLIER',
            'Supplier dihapus',
            $supplier->name
        );

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }

    public function getBrands(\App\Models\Supplier $supplier)
    {
        $brands = $supplier->brands()->select('brands.id', 'brands.name')->orderBy('name')->get();
        return response()->json($brands);
    }
}