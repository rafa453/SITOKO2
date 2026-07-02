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
use Barryvdh\DomPDF\Facade\Pdf;

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

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchaseOrders = $query->paginate(10)->withQueryString();
        $suppliers      = Supplier::where('is_active', true)->orderBy('name')->get();

        // ===== Stat cards (angka utama) =====
        $totalDraft    = PurchaseOrder::where('status', 'draft')->count();
        $totalOrdered  = PurchaseOrder::where('status', 'ordered')->count();
        $totalReceived = PurchaseOrder::where('status', 'received')
            ->whereMonth('received_at', now()->month)
            ->whereYear('received_at', now()->year)
            ->count();
        $totalValue    = PurchaseOrder::where('status', 'ordered')->sum('total');

        // ===== Breakdown untuk popup: DRAFT =====
        $draftValue  = PurchaseOrder::where('status', 'draft')->sum('total');
        $oldestDraft = PurchaseOrder::where('status', 'draft')
            ->with('supplier')
            ->oldest()
            ->first();

        // ===== Breakdown untuk popup: ORDERED =====
        $orderedValue   = PurchaseOrder::where('status', 'ordered')->sum('total');
        $overdueOrdered = PurchaseOrder::where('status', 'ordered')
            ->whereNotNull('expected_at')
            ->where('expected_at', '<', now()->startOfDay())
            ->count();

        // ===== Breakdown untuk popup: DITERIMA BULAN INI =====
        $receivedValueThisMonth = PurchaseOrder::where('status', 'received')
            ->whereMonth('received_at', now()->month)
            ->whereYear('received_at', now()->year)
            ->sum('total');

        $receivedCountLastMonth = PurchaseOrder::where('status', 'received')
            ->whereMonth('received_at', now()->subMonthNoOverflow()->month)
            ->whereYear('received_at', now()->subMonthNoOverflow()->year)
            ->count();

        // ===== Breakdown untuk popup: NILAI PO AKTIF (top 5 supplier) =====
        $valueBySupplier = PurchaseOrder::where('status', 'ordered')
            ->select('supplier_id', DB::raw('SUM(total) as total_value'), DB::raw('COUNT(*) as po_count'))
            ->groupBy('supplier_id')
            ->orderByDesc('total_value')
            ->with('supplier')
            ->take(5)
            ->get();

        return view('pages.purchase-orders', compact(
            'purchaseOrders', 'suppliers',
            'totalDraft', 'totalOrdered', 'totalReceived', 'totalValue',
            'draftValue', 'oldestDraft',
            'orderedValue', 'overdueOrdered',
            'receivedValueThisMonth', 'receivedCountLastMonth',
            'valueBySupplier'
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

    public function downloadPdf(PurchaseOrder $purchaseOrder, Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $requestedType = $request->query('type');

        // GUARD: 'nota' hanya valid kalau payment_status benar-benar paid atau partial
        // PO yang masih unpaid tidak boleh menghasilkan dokumen bertajuk nota pembayaran
        if ($requestedType === 'nota' && $purchaseOrder->payment_status === 'unpaid') {
            abort(422, 'PO ini belum memiliki pembayaran, tidak bisa dicetak sebagai nota.');
        }

        $isNota = $requestedType === 'nota' && $purchaseOrder->payment_status !== 'unpaid';

        $purchaseOrder->load(['supplier', 'items.product']);
        $supplierPhone = ltrim($purchaseOrder->supplier->phone, '0+');
        $store = \App\Models\StoreProfile::get();

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'supplierPhone' => $supplierPhone,
            'isNota'        => $isNota, // pass sebagai variabel eksplisit, bukan dihitung ulang di view
            'store'         => $store,
        ]);

        $filename = ($isNota ? 'NOTA-' : 'PO-') . $purchaseOrder->code . '.pdf';

        return $pdf->download($filename);
    }

    public function storePayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Hanya admin
        if (auth()->user()->role !== 'admin') abort(403);

        // Guard: PO tidak boleh draft
        abort_if($purchaseOrder->status === 'draft', 422, 'PO belum dikonfirmasi.');

        // Guard: jangan proses kalau sudah ada payment
        abort_if($purchaseOrder->payment_status !== 'unpaid', 422, 'Pembayaran sudah pernah dicatat.');

        $request->validate([
            'payment_type' => 'required|in:full,dp',
            'amount_paid'  => 'required_if:payment_type,dp|nullable|numeric|min:1|max:' . $purchaseOrder->total,
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            if ($request->payment_type === 'full') {
                $purchaseOrder->update([
                    'payment_type'   => 'full',
                    'payment_status' => 'paid',
                    'amount_paid'    => $purchaseOrder->total,
                ]);
            } else {
                $purchaseOrder->update([
                    'payment_type'   => 'dp',
                    'payment_status' => 'partial',
                    'amount_paid'    => $request->amount_paid,
                ]);
            }

            ActivityLog::record(
                'PURCHASE_ORDER',
                $request->payment_type === 'full' ? 'Pembayaran PO lunas dicatat' : 'Pembayaran DP dicatat',
                $purchaseOrder->code,
                ['payment_type' => $request->payment_type, 'amount_paid' => $request->payment_type === 'full' ? $purchaseOrder->total : $request->amount_paid]
            );
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function settlePayment(PurchaseOrder $purchaseOrder)
    {
        // Hanya admin
        if (auth()->user()->role !== 'admin') abort(403);

        // Guard: hanya boleh dari status partial
        abort_if($purchaseOrder->payment_status !== 'partial', 422, 'Status pembayaran tidak valid untuk dilunasi.');

        DB::transaction(function () use ($purchaseOrder) {
            // Lock row supaya check-then-act tidak race kalau ada 2 request nyaris bersamaan
            $po = PurchaseOrder::where('id', $purchaseOrder->id)->lockForUpdate()->firstOrFail();

            abort_if($po->payment_status !== 'partial', 422, 'Status pembayaran tidak valid untuk dilunasi.');

            $po->update([
                'payment_status' => 'paid',
                'amount_paid'    => $po->total,
            ]);

            ActivityLog::record(
                'PURCHASE_ORDER',
                'PO dilunasi (settle payment)',
                $po->code,
                ['amount_settled' => $po->total - $po->getOriginal('amount_paid')]
            );
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO telah dilunasi.');
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
            // Lock PO & items dulu supaya qty_received yang dibaca selalu fresh
            // (mencegah race condition kalau ada 2 request "receive" hampir bersamaan)
            $po = PurchaseOrder::where('id', $po->id)->lockForUpdate()->firstOrFail();
            $items = $po->items()->lockForUpdate()->get();

            $allFulfilled = true;

            foreach ($items as $item) {
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
                    // 1. LOCK row produk terlebih dahulu
                    $product = \App\Models\Product::lockForUpdate()->findOrFail($item->product_id);
                    // 2. OPERASI increment stok
                    $product->increment('qty', $actualQty);

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