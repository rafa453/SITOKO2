<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Shift;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialRecordsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function cashier(): User
    {
        return User::factory()->create(['role' => 'cashier', 'status' => 'active']);
    }

    private function supplier(): Supplier
    {
        return Supplier::create(['name' => 'Supplier PO', 'is_active' => true]);
    }

    private function product(int $qty = 50, int $buy = 10000): Product
    {
        return Product::create([
            'sku' => 'SKU-PO-'.uniqid(), 'name' => 'Produk PO',
            'category' => 'BT', 'unit' => 'Pcs',
            'qty' => $qty, 'threshold' => 10,
            'buy_price' => $buy, 'sell_price' => 15000,
            'is_active' => true,
        ]);
    }

    // ── Purchase Order (TC-PO-001..016) ──

    public function test_tc_po_001_create_po_draft(): void
    {
        $admin = $this->admin();
        $supplier = $this->supplier();
        $product = $this->product();

        $res = $this->actingAs($admin)->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'qty' => 10, 'buy_price' => 12000]],
        ]);

        $res->assertRedirect(route('purchase-orders.index'));
        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->id, 'status' => 'draft']);
    }

    public function test_tc_po_002_confirm_po_ordered(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-1', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'draft', 'total' => 1000]);
        $po->items()->create(['product_id' => $this->product()->id, 'qty_ordered' => 5, 'qty_received' => 0, 'buy_price' => 1000, 'subtotal' => 5000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), ['action' => 'order']);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'ordered']);
    }

    public function test_tc_po_003_confirm_po_without_items_rejected(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-EMPTY', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'draft', 'total' => 0]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), ['action' => 'order']);

        $res->assertStatus(422);
    }

    public function test_tc_po_004_receive_po_partial(): void
    {
        $admin = $this->admin();
        $prod1 = $this->product(10, 10000);
        $prod2 = $this->product(10, 10000);
        $po = PurchaseOrder::create(['code' => 'PO-2', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 20000]);
        $item1 = $po->items()->create(['product_id' => $prod1->id, 'qty_ordered' => 10, 'qty_received' => 0, 'buy_price' => 10000, 'subtotal' => 100000]);
        $item2 = $po->items()->create(['product_id' => $prod2->id, 'qty_ordered' => 10, 'qty_received' => 0, 'buy_price' => 10000, 'subtotal' => 100000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), [
            'action' => 'receive',
            'received_qtys' => [$item1->id => 5, $item2->id => 0],
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'ordered']);
        $this->assertDatabaseHas('products', ['id' => $prod1->id, 'qty' => 15]);
    }

    public function test_tc_po_005_receive_po_full(): void
    {
        $admin = $this->admin();
        $prod = $this->product(10, 10000);
        $po = PurchaseOrder::create(['code' => 'PO-3', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000]);
        $item = $po->items()->create(['product_id' => $prod->id, 'qty_ordered' => 10, 'qty_received' => 0, 'buy_price' => 10000, 'subtotal' => 100000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), [
            'action' => 'receive',
            'received_qtys' => [$item->id => 10],
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'received']);
        $this->assertDatabaseHas('products', ['id' => $prod->id, 'qty' => 20]);
    }

    public function test_tc_po_006_cancel_draft_po(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-CANCEL1', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'draft', 'total' => 1000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), ['action' => 'cancel']);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'cancelled']);
    }

    public function test_tc_po_007_cancel_ordered_po(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-CANCEL2', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 1000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), ['action' => 'cancel']);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'cancelled']);
    }

    public function test_tc_po_008_cancel_received_po_rejected(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-REC', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 1000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), ['action' => 'cancel']);

        $res->assertStatus(422);
    }

    public function test_tc_po_009_pay_po_full(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-PAY1', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000, 'payment_status' => 'unpaid']);

        $res = $this->actingAs($admin)->post(route('purchase-orders.payment', $po), ['payment_type' => 'full']);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'payment_status' => 'paid', 'amount_paid' => 100000]);
    }

    public function test_tc_po_010_pay_po_dp(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-PAY2', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000, 'payment_status' => 'unpaid']);

        $res = $this->actingAs($admin)->post(route('purchase-orders.payment', $po), ['payment_type' => 'dp', 'amount_paid' => 50000]);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'payment_status' => 'partial', 'amount_paid' => 50000]);
    }

    public function test_tc_po_011_pay_dp_exceeding_total_rejected(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-PAY3', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000, 'payment_status' => 'unpaid']);

        $res = $this->actingAs($admin)->post(route('purchase-orders.payment', $po), ['payment_type' => 'dp', 'amount_paid' => 150000]);

        $res->assertSessionHasErrors('amount_paid');
    }

    public function test_tc_po_012_settle_dp(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-SETTLE', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000, 'payment_type' => 'dp', 'payment_status' => 'partial', 'amount_paid' => 50000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.payment.settle', $po));

        $res->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'payment_status' => 'paid', 'amount_paid' => 100000]);
    }

    public function test_tc_po_013_double_payment_prevented(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-DOUBLE', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000, 'payment_type' => 'full', 'payment_status' => 'paid', 'amount_paid' => 100000]);

        $res = $this->actingAs($admin)->post(route('purchase-orders.payment', $po), ['payment_type' => 'full']);

        $res->assertStatus(422);
    }

    public function test_tc_po_014_edit_po_only_draft(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-EDIT', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000]);

        $res = $this->actingAs($admin)->get(route('purchase-orders.edit', $po));

        $res->assertStatus(403);
    }

    public function test_tc_po_015_download_pdf_po(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-PDF', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000]);

        $res = $this->actingAs($admin)->get(route('purchase-orders.pdf', $po));

        $res->assertOk();
        $res->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_tc_po_016_download_nota_unpaid_rejected(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-NOTA', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 100000, 'payment_status' => 'unpaid']);

        $res = $this->actingAs($admin)->get(route('purchase-orders.pdf', [$po, 'type' => 'nota']));

        $res->assertStatus(422);
    }

    // ── Supplier Return (TC-RTR-001..008) ──

    public function test_tc_rtr_001_create_return_from_received_po(): void
    {
        $admin = $this->admin();
        $supplier = $this->supplier();
        $prod = $this->product();
        $po = PurchaseOrder::create(['code' => 'PO-RTR1', 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 10000]);
        $poItem = $po->items()->create(['product_id' => $prod->id, 'qty_ordered' => 10, 'qty_received' => 10, 'buy_price' => 1000, 'subtotal' => 10000]);

        $res = $this->actingAs($admin)->post(route('supplier-returns.store'), [
            'purchase_order_id' => $po->id,
            'reason' => 'Rusak',
            'items' => [['po_item_id' => $poItem->id, 'product_id' => $prod->id, 'qty_returned' => 2, 'buy_price' => 1000]],
        ]);

        $res->assertRedirect(route('supplier-returns.index'));
        $this->assertDatabaseHas('supplier_returns', ['purchase_order_id' => $po->id, 'status' => 'draft']);
    }

    public function test_tc_rtr_002_create_return_unreceived_po_rejected(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-UNREC', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'ordered', 'total' => 10000]);

        $res = $this->actingAs($admin)->get(route('supplier-returns.create', ['purchase_order_id' => $po->id]));

        $res->assertStatus(403);
    }

    public function test_tc_rtr_003_confirm_return_decrements_stock(): void
    {
        $admin = $this->admin();
        $prod = $this->product(20);
        $supplier = $this->supplier();
        $po = PurchaseOrder::create(['code' => 'PO-RTR2', 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 10000]);
        $poItem = $po->items()->create(['product_id' => $prod->id, 'qty_ordered' => 10, 'qty_received' => 10, 'buy_price' => 1000, 'subtotal' => 10000]);
        $ret = SupplierReturn::create(['code' => 'RTR-1', 'purchase_order_id' => $po->id, 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'draft', 'total' => 2000]);
        $ret->items()->create(['purchase_order_item_id' => $poItem->id, 'product_id' => $prod->id, 'qty_returned' => 2, 'buy_price' => 1000, 'subtotal' => 2000]);

        $res = $this->actingAs($admin)->post(route('supplier-returns.update-status', $ret), ['action' => 'confirm']);

        $res->assertRedirect();
        $this->assertDatabaseHas('supplier_returns', ['id' => $ret->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('products', ['id' => $prod->id, 'qty' => 18]);
    }

    public function test_tc_rtr_004_confirm_return_insufficient_stock_fails(): void
    {
        $admin = $this->admin();
        $prod = $this->product(1);
        $supplier = $this->supplier();
        $po = PurchaseOrder::create(['code' => 'PO-RTR3', 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 10000]);
        $poItem = $po->items()->create(['product_id' => $prod->id, 'qty_ordered' => 10, 'qty_received' => 10, 'buy_price' => 1000, 'subtotal' => 10000]);
        $ret = SupplierReturn::create(['code' => 'RTR-2', 'purchase_order_id' => $po->id, 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'draft', 'total' => 5000]);
        $ret->items()->create(['purchase_order_item_id' => $poItem->id, 'product_id' => $prod->id, 'qty_returned' => 5, 'buy_price' => 1000, 'subtotal' => 5000]);

        $this->withoutExceptionHandling();
        try {
            $this->actingAs($admin)->post(route('supplier-returns.update-status', $ret), ['action' => 'confirm']);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('tidak cukup untuk diretur', $e->getMessage());
        }
        $this->assertDatabaseHas('products', ['id' => $prod->id, 'qty' => 1]);
        $this->assertDatabaseHas('supplier_returns', ['id' => $ret->id, 'status' => 'draft']);
    }

    public function test_tc_rtr_005_complete_return(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-DUMMY1', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 1000]);
        $ret = SupplierReturn::create(['code' => 'RTR-COMP', 'purchase_order_id' => $po->id, 'supplier_id' => $po->supplier_id, 'created_by' => $admin->id, 'status' => 'confirmed', 'total' => 1000]);

        $res = $this->actingAs($admin)->post(route('supplier-returns.update-status', $ret), ['action' => 'complete']);

        $res->assertRedirect();
        $this->assertDatabaseHas('supplier_returns', ['id' => $ret->id, 'status' => 'completed']);
    }

    public function test_tc_rtr_006_cancel_return_draft(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-DUMMY2', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 1000]);
        $ret = SupplierReturn::create(['code' => 'RTR-CAN', 'purchase_order_id' => $po->id, 'supplier_id' => $po->supplier_id, 'created_by' => $admin->id, 'status' => 'draft', 'total' => 1000]);

        $res = $this->actingAs($admin)->post(route('supplier-returns.update-status', $ret), ['action' => 'cancel']);

        $res->assertRedirect();
        $this->assertDatabaseHas('supplier_returns', ['id' => $ret->id, 'status' => 'cancelled']);
    }

    public function test_tc_rtr_007_cancel_confirmed_return_rejected(): void
    {
        $admin = $this->admin();
        $po = PurchaseOrder::create(['code' => 'PO-DUMMY3', 'supplier_id' => $this->supplier()->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 1000]);
        $ret = SupplierReturn::create(['code' => 'RTR-CONF', 'purchase_order_id' => $po->id, 'supplier_id' => $po->supplier_id, 'created_by' => $admin->id, 'status' => 'confirmed', 'total' => 1000]);

        $res = $this->actingAs($admin)->post(route('supplier-returns.update-status', $ret), ['action' => 'cancel']);

        $res->assertStatus(422);
    }

    public function test_tc_rtr_008_full_returned_po_rejected(): void
    {
        $admin = $this->admin();
        $supplier = $this->supplier();
        $prod = $this->product();
        $po = PurchaseOrder::create(['code' => 'PO-FULLRTR', 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 10000]);
        $poItem = $po->items()->create(['product_id' => $prod->id, 'qty_ordered' => 5, 'qty_received' => 5, 'buy_price' => 1000, 'subtotal' => 5000]);
        $ret = SupplierReturn::create(['code' => 'RTR-PREV', 'purchase_order_id' => $po->id, 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'completed', 'total' => 5000]);
        $ret->items()->create(['purchase_order_item_id' => $poItem->id, 'product_id' => $prod->id, 'qty_returned' => 5, 'buy_price' => 1000, 'subtotal' => 5000]);

        $res = $this->actingAs($admin)->get(route('supplier-returns.create', ['purchase_order_id' => $po->id]));

        $res->assertStatus(403);
    }

    // ── Shift (TC-SHIFT-001..009) ──

    public function test_tc_shift_001_clock_in_success(): void
    {
        $cashier = $this->cashier();

        $res = $this->actingAs($cashier)->post(route('shifts.clock-in'), ['type' => 'pagi']);

        $res->assertRedirect();
        $this->assertDatabaseHas('shifts', ['user_id' => $cashier->id, 'type' => 'pagi', 'ended_at' => null]);
    }

    public function test_tc_shift_002_double_clock_in_rejected(): void
    {
        $cashier = $this->cashier();
        Shift::create(['user_id' => $cashier->id, 'type' => 'pagi', 'started_at' => now()]);

        $res = $this->actingAs($cashier)->post(route('shifts.clock-in'), ['type' => 'siang']);

        $res->assertSessionHas('error');
    }

    public function test_tc_shift_003_clock_out_success(): void
    {
        $cashier = $this->cashier();
        Shift::create(['user_id' => $cashier->id, 'type' => 'pagi', 'started_at' => now()]);

        $res = $this->actingAs($cashier)->post(route('shifts.clock-out'));

        $res->assertRedirect();
        $this->assertDatabaseMissing('shifts', ['user_id' => $cashier->id, 'ended_at' => null]);
    }

    public function test_tc_shift_004_clock_out_without_active_shift_rejected(): void
    {
        $cashier = $this->cashier();

        $res = $this->actingAs($cashier)->post(route('shifts.clock-out'));

        $res->assertSessionHas('error');
    }

    public function test_tc_shift_005_admin_assign_shift(): void
    {
        $admin = $this->admin();
        $cashier = $this->cashier();

        $res = $this->actingAs($admin)->post(route('shifts.store'), [
            'user_id' => $cashier->id, 'type' => 'pagi', 'started_at' => now()->toDateTimeString(),
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('shifts', ['user_id' => $cashier->id, 'type' => 'pagi']);
    }

    public function test_tc_shift_006_assign_duplicate_shift_rejected(): void
    {
        $admin = $this->admin();
        $cashier = $this->cashier();
        Shift::create(['user_id' => $cashier->id, 'type' => 'pagi', 'started_at' => now()]);

        $res = $this->actingAs($admin)->post(route('shifts.store'), [
            'user_id' => $cashier->id, 'type' => 'siang', 'started_at' => now()->toDateTimeString(),
        ]);

        $res->assertSessionHas('error');
    }

    public function test_tc_shift_007_summary_api(): void
    {
        $admin = $this->admin();
        Shift::create(['user_id' => $admin->id, 'type' => 'pagi', 'started_at' => now()]);

        $res = $this->actingAs($admin)->getJson(route('shifts.summary'));

        $res->assertOk();
    }

    public function test_tc_shift_008_delete_active_shift(): void
    {
        $admin = $this->admin();
        $shift = Shift::create(['user_id' => $admin->id, 'type' => 'pagi', 'started_at' => now(), 'ended_at' => null]);

        $res = $this->actingAs($admin)->delete(route('shifts.destroy', $shift));

        $res->assertRedirect();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_tc_shift_009_delete_finished_shift_rejected(): void
    {
        $admin = $this->admin();
        $shift = Shift::create(['user_id' => $admin->id, 'type' => 'pagi', 'started_at' => now(), 'ended_at' => now()]);

        $res = $this->actingAs($admin)->delete(route('shifts.destroy', $shift));

        $res->assertSessionHas('error');
        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
    }

    // ── Staff (TC-STAFF-001..005) ──

    public function test_tc_staff_001_add_cashier(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->post(route('staff.store'), [
            'name' => 'Cashier Barus', 'email' => 'cashierbarus@example.com',
            'phone' => '0812999', 'shift' => 'pagi', 'password' => 'password123',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'cashierbarus@example.com', 'role' => 'cashier', 'status' => 'active']);
    }

    public function test_tc_staff_002_edit_staff(): void
    {
        $admin = $this->admin();
        $cashier = $this->cashier();

        $res = $this->actingAs($admin)->put(route('staff.update', $cashier), [
            'name' => 'Cashier Updated', 'phone' => '08888', 'shift' => 'siang', 'status' => 'active',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $cashier->id, 'name' => 'Cashier Updated']);
    }

    public function test_tc_staff_003_deactivate_self_rejected(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->put(route('staff.update', $admin), [
            'name' => $admin->name, 'shift' => 'pagi', 'status' => 'inactive',
        ]);

        $res->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }

    public function test_tc_staff_004_deactivate_main_admin_rejected(): void
    {
        $adminMain = User::factory()->create(['id' => 1, 'role' => 'admin', 'status' => 'active']);
        $adminOther = $this->admin();

        $res = $this->actingAs($adminOther)->put(route('staff.update', $adminMain), [
            'name' => $adminMain->name, 'shift' => 'pagi', 'status' => 'inactive',
        ]);

        $res->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => 1, 'status' => 'active']);
    }

    public function test_tc_staff_005_upload_photo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $cashier = $this->cashier();
        $file = UploadedFile::fake()->image('staff.jpg');

        $res = $this->actingAs($admin)->put(route('staff.update', $cashier), [
            'name' => $cashier->name, 'shift' => 'pagi', 'status' => 'active',
            'photo' => $file,
        ]);

        $res->assertRedirect();
        $cashier->refresh();
        $this->assertNotNull($cashier->photo);
        Storage::disk('public')->assertExists($cashier->photo);
    }

    // ── Report (TC-RPT-001..006) ──

    public function test_tc_rpt_001_reports_index(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('reports.index'));

        $res->assertOk();
        $res->assertViewHas(['totalRevenue', 'totalTrx', 'netProfit']);
    }

    public function test_tc_rpt_002_filter_by_period(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('reports.index', ['period' => 'last_7']));

        $res->assertOk();
    }

    public function test_tc_rpt_003_export_sales_csv(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('reports.export-custom', [
            'type' => 'sales', 'group_by' => 'product', 'format' => 'csv',
        ]));

        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
    }

    public function test_tc_rpt_004_export_sales_pdf(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('reports.export-custom', [
            'type' => 'sales', 'group_by' => 'product', 'format' => 'pdf',
        ]));

        $res->assertOk();
        $res->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_tc_rpt_005_export_inventory(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('reports.export-custom', [
            'type' => 'inventory', 'group_by' => 'category', 'format' => 'csv',
        ]));

        $res->assertOk();
    }

    public function test_tc_rpt_006_export_staff(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('reports.export-custom', [
            'type' => 'staff', 'group_by' => 'cashier', 'format' => 'csv',
        ]));

        $res->assertOk();
    }

    // ── Payment Methods (TC-PAY-001..005) ──

    public function test_tc_pay_001_store_payment_method(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->post(route('payment-methods.store'), [
            'name' => 'GoPay', 'code' => 'gopay', 'type' => 'digital',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('payment_methods', ['code' => 'gopay']);
    }

    public function test_tc_pay_002_duplicate_code_rejected(): void
    {
        $admin = $this->admin();
        PaymentMethod::create(['name' => 'Tunai', 'code' => 'cash', 'type' => 'cash']);

        $res = $this->actingAs($admin)->post(route('payment-methods.store'), [
            'name' => 'Cash 2', 'code' => 'cash', 'type' => 'cash',
        ]);

        $res->assertSessionHasErrors('code');
    }

    public function test_tc_pay_003_toggle_payment_method(): void
    {
        $admin = $this->admin();
        $pm = PaymentMethod::create(['name' => 'OVO', 'code' => 'ovo', 'type' => 'digital', 'is_active' => true]);

        $res = $this->actingAs($admin)->patch(route('settings.payment-methods.toggle', $pm));

        $res->assertRedirect();
        $this->assertDatabaseHas('payment_methods', ['id' => $pm->id, 'is_active' => false]);
    }

    public function test_tc_pay_004_update_payment_method(): void
    {
        $admin = $this->admin();
        $pm = PaymentMethod::create(['name' => 'ShopeePay', 'code' => 'shopeepay', 'type' => 'digital']);

        $res = $this->actingAs($admin)->patch(route('settings.payment-methods.update', $pm), [
            'name' => 'ShopeePay New', 'code' => 'shopeepay', 'type' => 'digital',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('payment_methods', ['id' => $pm->id, 'name' => 'ShopeePay New']);
    }

    public function test_tc_pay_005_update_store_profile(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->patch(route('settings.store-profile.update'), [
            'store_name' => 'Toko Sembako Jaya', 'address' => 'Jl. Merdeka 10',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('store_profile', ['store_name' => 'Toko Sembako Jaya']);
    }

    // ── Brand (TC-BRAND-001..002) ──

    public function test_tc_brand_001_store_brand(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->post(route('brands.store'), ['name' => 'Indomie']);

        $res->assertRedirect(route('brands.index'));
        $this->assertDatabaseHas('brands', ['name' => 'Indomie']);
    }

    public function test_tc_brand_002_assign_brand_supplier(): void
    {
        $admin = $this->admin();
        $brand = Brand::create(['name' => 'Sania']);
        $supplier = $this->supplier();

        $res = $this->actingAs($admin)->patch(route('brands.assign-supplier', $brand), ['supplier_id' => $supplier->id]);

        $res->assertRedirect();
        $this->assertDatabaseHas('brand_supplier', ['brand_id' => $brand->id, 'supplier_id' => $supplier->id]);
    }

    // ── Security (TC-SEC-004..005) ──

    public function test_tc_sec_004_mass_assignment_protection(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->post('/register', [
            'name' => 'Hacker', 'email' => 'hacker@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'admin', // mass assignment injection attempt
        ]);

        // Role register controller hardcodes user role or ignores role parameter
        $user = User::where('email', 'hacker@example.com')->first();
        $this->assertNotEquals('admin', $user?->role);
    }

    public function test_tc_sec_005_foreign_key_cascade_or_protection(): void
    {
        $cashier = $this->cashier();
        $product = $this->product();
        $trx = Transaction::create([
            'code' => 'TRX-CASCADE', 'cashier_id' => $cashier->id,
            'total' => 1000, 'amount_paid' => 1000, 'change' => 0,
            'payment_method' => 'Cash', 'status' => 'completed',
        ]);

        $cashier->delete();

        $this->assertDatabaseMissing('transactions', ['id' => $trx->id]);
    }
}
