<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

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
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku',
            'brand_id'          => 'nullable|exists:brands,id',
            'category'          => 'required|string|max:100',
            'unit'              => 'required|string|max:50',
            'buy_price'         => 'required|numeric|min:0',
            'sell_price'        => 'required|numeric|min:0',
            'qty'               => 'required|integer|min:0',
            'threshold'         => 'required|integer|min:0',
            'description'       => 'nullable|string',
            'supplier_ids'      => 'nullable|array',
            'supplier_ids.*'    => 'exists:suppliers,id',
            'supplier_prices'   => 'nullable|array',
            'supplier_prices.*' => 'nullable|numeric|min:0',
        ]);

        $product = DB::transaction(function () use ($validated) {
            $product = Product::create($validated);

            // Attach suppliers dengan supplier_sku
            if (!empty($validated['supplier_ids'])) {
                $brandName = $validated['brand_id']
                    ? \App\Models\Brand::find($validated['brand_id'])->name
                    : 'NOBRAND';

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

        activity()->log("Tambah produk: {$product->name} (SKU: {$product->sku})");

        return redirect()->route('inventory.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Product::select('category')->distinct()->pluck('category');
        return view('pages.inventory-form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku,' . $product->id,
            'brand_id'          => 'nullable|exists:brands,id',
            'category'          => 'required|string|max:100',
            'unit'              => 'required|string|max:50',
            'buy_price'         => 'required|numeric|min:0',
            'sell_price'        => 'required|numeric|min:0',
            'qty'               => 'required|integer|min:0',
            'threshold'         => 'required|integer|min:0',
            'description'       => 'nullable|string',
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
                $brandName = $validated['brand_id']
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
            activity()->log("Update produk: {$product->name}");
        }

        return redirect()->route('inventory.index')->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product)
    {
        // Cek apakah produk pernah dipakai di transaksi, pemesanan, atau retur
        if ($product->transactionItems()->exists() || 
            $product->purchaseOrderItems()->exists() || 
            $product->supplierReturnItems()->exists()) {
            return redirect()->route('inventory.index')
                ->with('error', "Produk \"{$product->name}\" tidak bisa dihapus karena memiliki riwayat transaksi, pemesanan (PO), atau retur supplier.");
        }

        $product->delete();
        return redirect()->route('inventory.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    // Restock langsung dari tabel
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
}
