<?php

namespace App\Http\Controllers;

use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\PurchaseOrder;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierReturn::with(['supplier', 'purchaseOrder', 'creator'])->latest();

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $returns = $query->paginate(10)->withQueryString();

        // ===== Stat cards (angka utama) =====
        $totalDraft     = SupplierReturn::where('status', 'draft')->count();
        $totalConfirmed = SupplierReturn::where('status', 'confirmed')->count();
        $totalCompleted = SupplierReturn::where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();
        $totalValue     = SupplierReturn::where('status', 'confirmed')->sum('total');

        // ===== Breakdown untuk popup: DRAFT =====
        $draftValue  = SupplierReturn::where('status', 'draft')->sum('total');
        $oldestDraft = SupplierReturn::where('status', 'draft')
            ->with('supplier')
            ->oldest()
            ->first();

        // ===== Breakdown untuk popup: DIKONFIRMASI =====
        $confirmedValue = SupplierReturn::where('status', 'confirmed')->sum('total');
        $oldestConfirmed = SupplierReturn::where('status', 'confirmed')
            ->with('supplier')
            ->orderBy('confirmed_at')
            ->first();

        // ===== Breakdown untuk popup: SELESAI BULAN INI =====
        $completedValueThisMonth = SupplierReturn::where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->sum('total');

        $completedCountLastMonth = SupplierReturn::where('status', 'completed')
            ->whereMonth('completed_at', now()->subMonthNoOverflow()->month)
            ->whereYear('completed_at', now()->subMonthNoOverflow()->year)
            ->count();

        // ===== Breakdown untuk popup: NILAI RETUR AKTIF (top 5 supplier) =====
        $valueBySupplier = SupplierReturn::where('status', 'confirmed')
            ->select('supplier_id', DB::raw('SUM(total) as total_value'), DB::raw('COUNT(*) as return_count'))
            ->groupBy('supplier_id')
            ->orderByDesc('total_value')
            ->with('supplier')
            ->take(5)
            ->get();

        return view('pages.supplier-returns', compact(
            'returns',
            'totalDraft', 'totalConfirmed', 'totalCompleted', 'totalValue',
            'draftValue', 'oldestDraft',
            'confirmedValue', 'oldestConfirmed',
            'completedValueThisMonth', 'completedCountLastMonth',
            'valueBySupplier'
        ));
    }

    public function create(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
        ]);

        $po = PurchaseOrder::with('items.product')->findOrFail($request->purchase_order_id);

        abort_if($po->status !== 'received', 403, 'Retur hanya bisa dibuat dari PO yang sudah diterima.');

        // Hitung qty yang sudah diretur per PO item
        $returnedQtys = SupplierReturnItem::whereIn('purchase_order_item_id', $po->items->pluck('id'))
            ->whereHas('supplierReturn', fn($q) => $q->whereIn('status', ['draft', 'confirmed', 'completed']))
            ->selectRaw('purchase_order_item_id, SUM(qty_returned) as total_returned')
            ->groupBy('purchase_order_item_id')
            ->pluck('total_returned', 'purchase_order_item_id');

        // Filter item yang masih bisa diretur
        $returnableItems = $po->items->filter(function ($item) use ($returnedQtys) {
            $alreadyReturned = $returnedQtys[$item->id] ?? 0;
            return ($item->qty_received - $alreadyReturned) > 0;
        })->values();

        abort_if($returnableItems->isEmpty(), 403, 'Semua item di PO ini sudah diretur.');

        return view('pages.supplier-return-form', compact('po', 'returnableItems', 'returnedQtys'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'reason'            => 'nullable|string|max:500',
            'items'             => 'required|array|min:1',
            'items.*.po_item_id'    => 'required|exists:purchase_order_items,id',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty_returned'  => 'required|integer|min:1',
            'items.*.buy_price'     => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $po    = PurchaseOrder::findOrFail($request->purchase_order_id);
            $code  = 'RTR-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $total = 0;
            $itemsToCreate = [];

            foreach ($request->items as $item) {
                if (($item['qty_returned'] ?? 0) <= 0) continue;

                $subtotal = $item['qty_returned'] * $item['buy_price'];
                $total   += $subtotal;

                $itemsToCreate[] = [
                    'purchase_order_item_id' => $item['po_item_id'],
                    'product_id'             => $item['product_id'],
                    'qty_returned'           => $item['qty_returned'],
                    'buy_price'              => $item['buy_price'],
                    'subtotal'               => $subtotal,
                ];
            }

            abort_if(empty($itemsToCreate), 422, 'Tidak ada item yang valid untuk diretur.');

            $return = SupplierReturn::create([
                'code'               => $code,
                'purchase_order_id'  => $po->id,
                'supplier_id'        => $po->supplier_id,
                'created_by'         => auth()->id(),
                'status'             => 'draft',
                'reason'             => $request->reason,
                'total'              => $total,
            ]);

            $return->items()->createMany($itemsToCreate);

            ActivityLog::record(
                'SUPPLIER_RETURN',
                'Retur supplier dibuat',
                $return->code,
                ['po_code' => $po->code, 'total' => $total]
            );
        });

        return redirect()->route('supplier-returns.index')
            ->with('success', 'Retur berhasil dibuat.');
    }

    public function show(SupplierReturn $supplierReturn)
    {
        $supplierReturn->load(['supplier', 'purchaseOrder', 'creator', 'confirmer', 'completer', 'items.product']);
        return view('pages.supplier-return-show', compact('supplierReturn'));
    }

    public function updateStatus(Request $request, SupplierReturn $supplierReturn)
    {
        $action = $request->get('action');

        match ($action) {
            'confirm'  => $this->confirmReturn($supplierReturn),
            'complete' => $this->completeReturn($supplierReturn),
            'cancel'   => $this->cancelReturn($supplierReturn),
            default    => abort(422, 'Aksi tidak valid.'),
        };

        return back()->with('success', 'Status retur berhasil diupdate.');
    }

    private function confirmReturn(SupplierReturn $return): void
    {
        abort_if(!$return->canBeConfirmed(), 422, 'Retur tidak bisa dikonfirmasi.');

        DB::transaction(function () use ($return) {
            // Kurangi stok saat confirmed — barang sudah keluar gudang
            foreach ($return->items as $item) {
                // 1. LOCK row produk terlebih dahulu
                $product = \App\Models\Product::lockForUpdate()->findOrFail($item->product_id);

                // 2. VALIDASI kecukupan stok setelah data ter-lock
                if ($product->qty < $item->qty_returned) {
                    throw new \Exception("Stok produk {$product->name} tidak cukup untuk diretur (Stok saat ini: {$product->qty}, Butuh: {$item->qty_returned}).");
                }

                // 3. OPERASI decrement stok
                $product->decrement('qty', $item->qty_returned);
            }

            $return->update([
                'status'       => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            ActivityLog::record('SUPPLIER_RETURN', 'Retur dikonfirmasi — stok dikurangi', $return->code);
        });
    }

    private function completeReturn(SupplierReturn $return): void
    {
        abort_if(!$return->canBeCompleted(), 422, 'Retur tidak bisa diselesaikan.');

        $return->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        ActivityLog::record('SUPPLIER_RETURN', 'Retur selesai — supplier terima barang', $return->code);
    }

    private function cancelReturn(SupplierReturn $return): void
    {
        abort_if(!$return->canBeCancelled(), 422, 'Retur tidak bisa dibatalkan.');

        $return->update(['status' => 'cancelled']);

        ActivityLog::record('SUPPLIER_RETURN', 'Retur dibatalkan', $return->code);
    }
}