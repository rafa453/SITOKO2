<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
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

        $query->with('brand');

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('supplier_id')) {
            $query->whereHas('suppliers', function ($q) use ($request) {
                $q->where('suppliers.id', $request->supplier_id);
            });
        }

        $products   = $query->paginate(10)->withQueryString();
        $categories = Product::select('category')->distinct()->pluck('category');

        $totalSkus       = Product::count();
        $lowStockCount   = Product::whereColumn('qty', '<=', 'threshold')->where('qty', '>', 0)->count();
        $outOfStockCount = Product::where('qty', 0)->count();
        $stockValue      = Product::selectRaw('SUM(qty * sell_price) as total')->value('total') ?? 0;

        // ===== Stock Alert: produk mendekati / sudah kadaluarsa (H-7) =====
        $expiringProducts = Product::whereNotNull('expired_at')
            ->where('expired_at', '<=', now()->addDays(7))
            ->orderBy('expired_at')
            ->get();
        $expiringCount = $expiringProducts->count();

        $categoryBreakdown = Product::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        $stockValueByCategory = Product::selectRaw('category, SUM(qty * sell_price) as value')
            ->groupBy('category')
            ->orderByDesc('value')
            ->get();

        $stockAlerts = Product::whereColumn('qty', '<=', 'threshold')
            ->orderBy('qty')
            ->limit(5)
            ->get();

        $suppliers = collect([
            ['initials'=>'SJ','name'=>'Sembako Jaya',  'desc'=>'Rice, Flour, Staple Goods','phone'=>'+62 21 555-0123','last'=>'Oct 24, 2023'],
            ['initials'=>'SM','name'=>'Sumber Makmur', 'desc'=>'Cooking Oil, Margarine',   'phone'=>'+62 21 555-0987','last'=>'Oct 21, 2023'],
            ['initials'=>'BP','name'=>'Bumbu Pusaka',  'desc'=>'Spices, Condiments',       'phone'=>'+62 21 555-0456','last'=>'Oct 18, 2023'],
        ]);

        $filterBrands    = \App\Models\Brand::orderBy('name')->get();
        $filterSuppliers = \App\Models\Supplier::orderBy('name')->get();
        $categoryLabels = [
            'BT' => 'Beras & Tepung',
            'ML' => 'Minyak & Lemak',
            'GG' => 'Gula & Garam',
            'MP' => 'Mie & Pasta',
            'BR' => 'Bumbu & Rempah',
            'MN' => 'Minuman',
            'KR' => 'Kebutuhan Rumah',
        ];

        return view('pages.inventory', compact(
            'products', 'categories',
            'totalSkus', 'lowStockCount', 'outOfStockCount', 'stockValue',
            'expiringProducts', 'expiringCount',
            'categoryBreakdown', 'stockValueByCategory', 'stockAlerts', 'suppliers',
            'filterBrands', 'filterSuppliers', 'categoryLabels'
        ));
    }

    public function create()
    {
        $categories = Product::select('category')->distinct()->pluck('category');
        $brands     = \App\Models\Brand::orderBy('name')->get();
        $suppliers  = \App\Models\Supplier::where('is_active', true)->orderBy('name')->get();
        return view('pages.inventory-form', compact('categories', 'brands', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'brand_id'    => 'nullable|exists:brands,id',
            'category'    => 'required|string|max:100',
            'unit'        => 'required|string|max:50',
            'buy_price'   => 'required|numeric|min:0',
            'sell_price'  => 'required|numeric|min:0',
            'qty'         => 'required|integer|min:0',
            'threshold'   => 'required|integer|min:0',
            'expired_at'  => 'nullable|date',
            'description' => 'nullable|string',
            'supplier_ids'      => 'nullable|array',
            'supplier_ids.*'    => 'exists:suppliers,id',
            'supplier_prices'   => 'nullable|array',
            'supplier_prices.*' => 'nullable|numeric|min:0',
        ]);

        $product = DB::transaction(function () use ($validated) {
            $brandName = !empty($validated['brand_id'])
                ? \App\Models\Brand::find($validated['brand_id'])->name
                : 'NOBRAND';

            $supplierName = !empty($validated['supplier_ids']) 
                ? \App\Models\Supplier::find($validated['supplier_ids'][0])->name 
                : 'NOSUPP';

            $validated['sku'] = \App\Models\Product::generateSku(
                $validated['category'],
                $brandName,
                $supplierName
            );

            $product = Product::create($validated);

            // Attach suppliers dengan supplier_sku
            if (!empty($validated['supplier_ids'])) {
                foreach ($validated['supplier_ids'] as $index => $supplierId) {
                    $supplier = \App\Models\Supplier::find($supplierId);
                    $supplierSku = \App\Models\Product::generateSupplierSku(
                        $validated['category'],
                        $brandName,
                        $supplier->name
                    );

                    $product->suppliers()->attach($supplierId, [
                        'supplier_sku' => $supplierSku,
                        'price'        => $validated['supplier_prices'][$index] ?? 0,
                    ]);
                }
            }

            return $product;
        });

        ActivityLog::record('PRODUCT', 'Tambah produk', $product->name);

        return redirect()->route('inventory.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Product::select('category')->distinct()->pluck('category');
        $brands     = \App\Models\Brand::orderBy('name')->get();
        $suppliers  = \App\Models\Supplier::where('is_active', true)->orderBy('name')->get();
        return view('pages.inventory-form', compact('product', 'categories', 'brands', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'brand_id'    => 'nullable|exists:brands,id',
            'category'    => 'required|string|max:100',
            'unit'        => 'required|string|max:50',
            'buy_price'   => 'required|numeric|min:0',
            'sell_price'  => 'required|numeric|min:0',
            'qty'         => 'required|integer|min:0',
            'threshold'   => 'required|integer|min:0',
            'expired_at'  => 'nullable|date',
            'description' => 'nullable|string',
            'supplier_ids'      => 'nullable|array',
            'supplier_ids.*'    => 'exists:suppliers,id',
            'supplier_prices'   => 'nullable|array',
            'supplier_prices.*' => 'nullable|numeric|min:0',
        ]);

        // Ambil stok awal SEBELUM update dipanggil (Terintegrasi dengan perbaikan temuan #7)
        $oldQty = $product->qty;

        DB::transaction(function () use ($validated, $product) {
            $product->update($validated);

            // Sync suppliers — supplier baru dapat supplier_sku baru
            $syncData = [];
            if (!empty($validated['supplier_ids'])) {
                $brandName = !empty($validated['brand_id'])
                    ? \App\Models\Brand::find($validated['brand_id'])->name
                    : 'NOBRAND';

                foreach ($validated['supplier_ids'] as $index => $supplierId) {
                    // Cek apakah relasi sudah ada (preserve supplier_sku lama)
                    $existing = $product->suppliers()->wherePivot('supplier_id', $supplierId)->first();

                    $supplierSku = $existing
                        ? $existing->pivot->supplier_sku
                        : \App\Models\Product::generateSupplierSku(
                            $validated['category'],
                            $brandName,
                            \App\Models\Supplier::find($supplierId)->name
                        );

                    $syncData[$supplierId] = [
                        'supplier_sku' => $supplierSku,
                        'price'        => $validated['supplier_prices'][$index] ?? 0,
                    ];
                }
            }

            $product->suppliers()->sync($syncData);
        });

        // Sinkronisasi instance dengan nilai terbaru dari database
        $product->refresh();

        // Audit Trail Pencatatan Qty
        if ($oldQty !== $product->qty) {
            \App\Models\ActivityLog::record(
                'STOCK_ADJUSTMENT',
                'Penyesuaian stok manual (Update Product)',
                $product->name,
                ['old_qty' => $oldQty, 'new_qty' => $product->qty, 'diff' => $product->qty - $oldQty]
            );
        } else {
            ActivityLog::record('PRODUCT', 'Update produk', $product->name);
        }

        return redirect()->route('inventory.index')->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product)
    {
        if ($product->transactionItems()->exists()) {
            return redirect()->route('inventory.index')
                ->with('error', "Produk \"{$product->name}\" tidak bisa dihapus karena memiliki riwayat transaksi.");
        }

        $product->delete();
        return redirect()->route('inventory.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    public function restock(Request $request, Product $product)
    {
        $request->validate(['qty' => 'required|integer|min:1']);
        $product->increment('qty', $request->qty);
        ActivityLog::record(
            'RESTOCK',
            'Restock produk',
            $product->name,
            ['qty_added' => $request->qty]
        );
        return back()->with('success', "Restock {$product->name} berhasil.");
    }

    public function bySupplier(\App\Models\Supplier $supplier)
    {
        $products = $supplier->products()
            ->select('products.id', 'products.name', 'products.unit')
            ->withPivot(['supplier_sku', 'price'])
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'unit'         => $p->unit,
                'supplier_sku' => $p->pivot->supplier_sku,
                'price'        => $p->pivot->price,
            ]);

        return response()->json($products);
    }

    public function detail(Product $product)
        {
            $product->load(['brand', 'suppliers']);

            return response()->json([
                'name'        => $product->name,
                'sku'         => $product->sku,
                'brand'       => $product->brand?->name,
                'stock'       => $product->qty,
                'price'       => $product->sell_price,
                'expired_at'  => $product->expired_at?->format('d M Y'),
                'suppliers'   => $product->suppliers->map(fn($s) => [
                    'name'         => $s->name,
                    'supplier_sku' => $s->pivot->supplier_sku,
                    'price'        => $s->pivot->price,
                ]),
            ]);
        }
}