<?php
// app/Http/Controllers/BrandController.php
namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')->orderBy('name')->paginate(20);
        return view('pages.brands', compact('brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name',
        ]);

        Brand::create($validated);

        return redirect()->route('brands.index')->with('success', 'Merek berhasil ditambahkan.');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:brands,name,' . $brand->id,
        ]);

        $brand->update($validated);

        return redirect()->route('brands.index')->with('success', 'Merek berhasil diupdate.');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return redirect()->route('brands.index')->with('error', 'Merek tidak bisa dihapus karena masih dipakai produk.');
        }

        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Merek berhasil dihapus.');
    }
}