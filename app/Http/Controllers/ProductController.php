<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'low'  => $query->whereColumn('qty', '<=', 'threshold')->where('qty', '>', 0),
                'out'  => $query->where('qty', 0),
                default => null,
            };
        }

        $products   = $query->paginate(10)->withQueryString();
        $categories = Product::select('category')->distinct()->pluck('category');

        // Stat cards
        $totalSkus        = Product::count();
        $lowStockCount    = Product::whereColumn('qty', '<=', 'threshold')->where('qty', '>', 0)->count();
        $outOfStockCount  = Product::where('qty', 0)->count();
        $stockValue       = Product::selectRaw('SUM(qty * sell_price) as total')->value('total') ?? 0;

        // Category breakdown
        $categoryBreakdown = Product::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        // Stock alerts (5 paling kritis)
        $stockAlerts = Product::whereColumn('qty', '<=', 'threshold')
            ->orderBy('qty')
            ->limit(5)
            ->get();

        // Suppliers (nanti dari tabel suppliers, sementara hardcode)
        $suppliers = collect([
            ['initials'=>'SJ','name'=>'Sembako Jaya',  'desc'=>'Rice, Flour, Staple Goods','phone'=>'+62 21 555-0123','last'=>'Oct 24, 2023'],
            ['initials'=>'SM','name'=>'Sumber Makmur', 'desc'=>'Cooking Oil, Margarine',   'phone'=>'+62 21 555-0987','last'=>'Oct 21, 2023'],
            ['initials'=>'BP','name'=>'Bumbu Pusaka',  'desc'=>'Spices, Condiments',       'phone'=>'+62 21 555-0456','last'=>'Oct 18, 2023'],
        ]);

        return view('pages.inventory', compact(
            'products', 'categories',
            'totalSkus', 'lowStockCount', 'outOfStockCount', 'stockValue',
            'categoryBreakdown', 'stockAlerts', 'suppliers'
        ));
    }

    public function create()
    {
        $categories = Product::select('category')->distinct()->pluck('category');
        return view('pages.inventory-form', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'sku'        => 'required|string|unique:products,sku',
            'category'   => 'required|string|max:100',
            'unit'       => 'required|string|max:50',
            'qty'        => 'required|integer|min:0',
            'threshold'  => 'required|integer|min:0',
            'buy_price'  => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
        ]);

        Product::create($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Product::select('category')->distinct()->pluck('category');
        return view('pages.inventory-form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'sku'        => 'required|string|unique:products,sku,' . $product->id,
            'category'   => 'required|string|max:100',
            'unit'       => 'required|string|max:50',
            'qty'        => 'required|integer|min:0',
            'threshold'  => 'required|integer|min:0',
            'buy_price'  => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
        ]);

        $product->update($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('inventory.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    // Restock langsung dari tabel
    public function restock(Request $request, Product $product)
    {
        $request->validate(['qty' => 'required|integer|min:1']);
        $product->increment('qty', $request->qty);
        return back()->with('success', "Restock {$product->name} berhasil.");
    }
}