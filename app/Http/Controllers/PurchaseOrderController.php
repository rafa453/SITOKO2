<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'creator'])->latest();

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchaseOrders = $query->paginate(10)->withQueryString();
        $suppliers      = Supplier::where('is_active', true)->orderBy('name')->get();

        // Stat cards
        $totalDraft    = PurchaseOrder::where('status', 'draft')->count();
        $totalOrdered  = PurchaseOrder::where('status', 'ordered')->count();
        $totalReceived = PurchaseOrder::where('status', 'received')
            ->whereMonth('received_at', now()->month)->count();
        $totalValue    = PurchaseOrder::where('status', 'ordered')->sum('total');

        return view('pages.purchase-orders', compact(
            'purchaseOrders', 'suppliers',
            'totalDraft', 'totalOrdered', 'totalReceived', 'totalValue'
        ));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();
        return view('pages.purchase-order-form', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_at' => 'nullable|date|after_or_equal:today',
            'notes'       => 'nullable|string|max:500',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.buy_price'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $code  = 'PO-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $total = 0;
            $itemsToCreate = [];

            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['buy_price'];
                $total   += $subtotal;

                $itemsToCreate[] = [
                    'product_id'  => $item['product_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_received'=> 0,
                    'buy_price'   => $item['buy_price'],
                    'subtotal'    => $subtotal,
                ];
            }

            $po = PurchaseOrder::create([
                'code'        => $code,
                'supplier_id' => $request->supplier_id,
                'created_by'  => auth()->id(),
                'status'      => 'draft',
                'expected_at' => $request->expected_at,
                'total'       => $total,
                'notes'       => $request->notes,
            ]);

            $po->items()->createMany($itemsToCreate);

            ActivityLog::record(
                'PURCHASE_ORDER',
                'Purchase Order dibuat',
                $po->code,
                ['supplier_id' => $po->supplier_id, 'total' => $total, 'items_count' => count($itemsToCreate)]
            );
        });

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'creator', 'receiver', 'items.product']);
        return view('pages.purchase-order-show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        abort_if(!$purchaseOrder->canBeEdited(), 403, 'PO ini tidak bisa diedit.');

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products  = Product::where('is_active', true)->orderBy('name')->get();

        return view('pages.purchase-order-form', compact('purchaseOrder', 'suppliers', 'products'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_if(!$purchaseOrder->canBeEdited(), 403, 'PO ini tidak bisa diedit.');

        $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'expected_at'        => 'nullable|date',
            'notes'              => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.buy_price'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            $total = 0;
            $itemsToSync = [];

            foreach ($request->items as $item) {
                $subtotal = $item['qty'] * $item['buy_price'];
                $total   += $subtotal;

                $itemsToSync[] = [
                    'product_id'   => $item['product_id'],
                    'qty_ordered'  => $item['qty'],
                    'qty_received' => 0,
                    'buy_price'    => $item['buy_price'],
                    'subtotal'     => $subtotal,
                ];
            }

            $purchaseOrder->update([
                'supplier_id' => $request->supplier_id,
                'expected_at' => $request->expected_at,
                'notes'       => $request->notes,
                'total'       => $total,
            ]);

            // Hapus items lama, replace dengan yang baru
            $purchaseOrder->items()->delete();
            $purchaseOrder->items()->createMany($itemsToSync);

            ActivityLog::record(
                'PURCHASE_ORDER',
                'Purchase Order diedit',
                $purchaseOrder->code,
                ['total' => $total, 'items_count' => count($itemsToSync)]
            );
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order berhasil diperbarui.');
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $action = $request->get('action');

        match ($action) {
            'order'   => $this->markOrdered($purchaseOrder),
            'receive' => $this->markReceived($purchaseOrder, $request->input('received_qtys', [])),
            'cancel'  => $this->markCancelled($purchaseOrder),
            default   => abort(422, 'Aksi tidak valid.'),
        };

        return back()->with('success', 'Status Purchase Order berhasil diupdate.');
    }

    private function markOrdered(PurchaseOrder $po): void
    {
        abort_if(!$po->canBeOrdered(), 422, 'PO tidak bisa diubah ke status ordered.');

        $po->update(['status' => 'ordered']);

        ActivityLog::record('PURCHASE_ORDER', 'PO dikonfirmasi ordered', $po->code);
    }

    private function markReceived(PurchaseOrder $po, array $receivedQtys): void
    {
        abort_if(!$po->canBeReceived(), 422, 'PO tidak bisa diubah ke status received.');

        DB::transaction(function () use ($po, $receivedQtys) {
            $allFulfilled = true;

            foreach ($po->items as $item) {
                $inputQty = (int) ($receivedQtys[$item->id] ?? 0);

                if ($inputQty <= 0) {
                    // item ini tidak diterima sekarang, skip
                    if ($item->qty_received < $item->qty_ordered) {
                        $allFulfilled = false;
                    }
                    continue;
                }

                // Maksimal yang bisa diterima = sisa yang belum diterima
                $maxReceivable = $item->qty_ordered - $item->qty_received;
                $actualQty     = min($inputQty, $maxReceivable);

                if ($actualQty > 0) {
                    $item->product->increment('qty', $actualQty);
                    $item->increment('qty_received', $actualQty);
                }

                if ($item->fresh()->qty_received < $item->qty_ordered) {
                    $allFulfilled = false;
                }
            }

            $newStatus = $allFulfilled ? 'received' : 'ordered';

            $po->update([
                'status'      => $newStatus,
                'received_at' => $allFulfilled ? now() : $po->received_at,
                'received_by' => $allFulfilled ? auth()->id() : $po->received_by,
            ]);

            ActivityLog::record(
                'PURCHASE_ORDER',
                $allFulfilled ? 'PO diterima penuh — stok diupdate' : 'PO diterima parsial — stok diupdate',
                $po->code,
                ['fulfilled' => $allFulfilled]
            );
        });
    }

    private function markCancelled(PurchaseOrder $po): void
    {
        abort_if(!$po->canBeCancelled(), 422, 'PO tidak bisa dibatalkan.');

        $po->update(['status' => 'cancelled']);

        ActivityLog::record('PURCHASE_ORDER', 'PO dibatalkan', $po->code);
    }
}