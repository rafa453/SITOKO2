<?php

// app/Http/Controllers/BrandController.php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::with('suppliers')
            ->withCount('products')
            ->orderBy('name')
            ->paginate(20);

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.brands', compact('brands', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name',
        ]);

        $brand = Brand::create($validated);

        ActivityLog::record('BRAND', 'Tambah merek', $brand->name);

        return redirect()->route('brands.index')->with('success', 'Merek berhasil ditambahkan.');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name,'.$brand->id,
        ]);

        $brand->update($validated);

        ActivityLog::record('BRAND', 'Update merek', $brand->name);

        return redirect()->route('brands.index')->with('success', 'Merek berhasil diupdate.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return redirect()->route('brands.index')->with('error', 'Merek tidak bisa dihapus karena masih dipakai produk.');
        }

        $brandName = $brand->name;
        $brand->delete();

        ActivityLog::record('BRAND', 'Hapus merek', $brandName);

        return back()->with('success', 'Brand dihapus.');
    }

    public function assignSupplier(Request $request, Brand $brand)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $brand->suppliers()->sync([$request->supplier_id]);

        ActivityLog::record('BRAND', 'Assign supplier ke merek', $brand->name, ['supplier_id' => $request->supplier_id]);

        return back()->with('success', "Brand {$brand->name} berhasil di-assign ke supplier.");
    }
}
