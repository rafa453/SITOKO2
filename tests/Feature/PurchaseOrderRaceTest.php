<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderRaceTest extends TestCase
{
    use RefreshDatabase;

    private function createPurchaseOrder(string $status = 'ordered', int $qtyOrdered = 10): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::create(['name' => 'Test Supplier']);
        $product = Product::create([
            'sku' => uniqid('P-', true),
            'name' => 'Produk Test',
            'category' => 'Sembako',
            'unit' => 'Pcs',
            'qty' => 0,
            'threshold' => 10,
            'buy_price' => 1000,
            'sell_price' => 2000,
        ]);
        $po = PurchaseOrder::create([
            'code' => uniqid('PO-', true),
            'supplier_id' => $supplier->id,
            'created_by' => $admin->id,
            'status' => $status,
            'total' => $qtyOrdered * 1000,
        ]);
        $item = $po->items()->create([
            'product_id' => $product->id,
            'qty_ordered' => $qtyOrdered,
            'qty_received' => 0,
            'buy_price' => 1000,
            'subtotal' => $qtyOrdered * 1000,
        ]);

        return [$admin, $po, $item, $product];
    }

    public function test_partial_receipt_updates_received_quantity_once(): void
    {
        [$admin, $po, $item, $product] = $this->createPurchaseOrder();

        $response = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), [
            'action' => 'receive',
            'received_qtys' => [$item->id => 5],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_order_items', ['id' => $item->id, 'qty_received' => 5]);
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'ordered']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 5]);
    }

    public function test_final_receipt_marks_purchase_order_received(): void
    {
        [$admin, $po, $item] = $this->createPurchaseOrder();
        $item->update(['qty_received' => 5]);

        $response = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), [
            'action' => 'receive',
            'received_qtys' => [$item->id => 5],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_order_items', ['id' => $item->id, 'qty_received' => 10]);
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'received']);
    }

    public function test_cancelled_purchase_order_cannot_receive(): void
    {
        [$admin, $po, $item, $product] = $this->createPurchaseOrder('cancelled');

        $response = $this->actingAs($admin)->post(route('purchase-orders.update-status', $po), [
            'action' => 'receive',
            'received_qtys' => [$item->id => 5],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('purchase_order_items', ['id' => $item->id, 'qty_received' => 0]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 0]);
    }
}
