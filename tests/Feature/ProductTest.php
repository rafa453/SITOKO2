<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
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

    private function product(array $over = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Produk Test',
            'category' => 'BT',
            'unit' => 'Kg',
            'qty' => 50,
            'threshold' => 10,
            'buy_price' => 10000,
            'sell_price' => 15000,
        ], $over));
    }

    public function test_tc_prod_001_store_product_with_supplier(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'PT Supplier A', 'is_active' => true]);

        $res = $this->actingAs($admin)->post(route('inventory.store'), [
            'name' => 'Beras Premium', 'category' => 'BT', 'unit' => 'Kg',
            'buy_price' => 12000, 'sell_price' => 15000, 'qty' => 50, 'threshold' => 10,
            'supplier_ids' => [$supplier->id], 'supplier_prices' => [11000],
        ]);

        $res->assertRedirect(route('inventory.index'));
        $res->assertSessionHas('success');
        $this->assertDatabaseHas('products', ['name' => 'Beras Premium']);
        $this->assertDatabaseHas('activity_logs', ['type' => 'PRODUCT']);
    }

    public function test_tc_prod_002_store_without_supplier(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->post(route('inventory.store'), [
            'name' => 'Gula Pasir', 'category' => 'GG', 'unit' => 'Kg',
            'buy_price' => 14000, 'sell_price' => 16000, 'qty' => 30, 'threshold' => 5,
        ]);

        $res->assertRedirect(route('inventory.index'));
        $this->assertDatabaseHas('products', ['name' => 'Gula Pasir']);
        $prod = Product::where('name', 'Gula Pasir')->first();
        $this->assertCount(0, $prod->suppliers);
    }

    public function test_tc_prod_003_validation_name_required(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->post(route('inventory.store'), [
            'name' => '', 'category' => 'BT', 'unit' => 'Kg',
            'buy_price' => 1000, 'sell_price' => 2000, 'qty' => 10, 'threshold' => 5,
        ]);

        $res->assertSessionHasErrors('name');
    }

    public function test_tc_prod_004_update_product(): void
    {
        $admin = $this->admin();
        $prod = $this->product(['name' => 'Old']);

        $res = $this->actingAs($admin)->put(route('inventory.update', $prod), [
            'name' => 'New Name', 'category' => 'BT', 'unit' => 'Kg',
            'buy_price' => 10000, 'sell_price' => 17000, 'qty' => 50, 'threshold' => 10,
        ]);

        $res->assertRedirect(route('inventory.index'));
        $this->assertDatabaseHas('products', ['id' => $prod->id, 'sell_price' => 17000, 'name' => 'New Name']);
    }

    public function test_tc_prod_005_qty_change_logs_stock_adjustment(): void
    {
        $admin = $this->admin();
        $prod = $this->product(['qty' => 50]);

        $this->actingAs($admin)->put(route('inventory.update', $prod), [
            'name' => $prod->name, 'category' => $prod->category, 'unit' => $prod->unit,
            'buy_price' => $prod->buy_price, 'sell_price' => $prod->sell_price,
            'qty' => 80, 'threshold' => $prod->threshold,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'type' => 'STOCK_ADJUSTMENT',
            'action' => 'Penyesuaian stok manual (Update Product)',
        ]);
    }

    public function test_tc_prod_006_delete_product_no_history(): void
    {
        $admin = $this->admin();
        $prod = $this->product();

        $res = $this->actingAs($admin)->delete(route('inventory.destroy', $prod));

        $res->assertRedirect(route('inventory.index'));
        $res->assertSessionHas('success');
        $this->assertDatabaseMissing('products', ['id' => $prod->id]);
    }

    public function test_tc_prod_007_delete_product_with_transaction_rejected(): void
    {
        $admin = $this->admin();
        $prod = $this->product();
        $trx = Transaction::create([
            'code' => 'TRX-'.uniqid(), 'cashier_id' => $admin->id,
            'total' => 1000, 'amount_paid' => 1000, 'change' => 0,
            'payment_method' => 'Cash', 'status' => 'completed',
        ]);
        TransactionItem::create([
            'transaction_id' => $trx->id, 'product_id' => $prod->id,
            'qty' => 1, 'unit' => 'Kg', 'price' => 1000, 'subtotal' => 1000,
        ]);

        $res = $this->actingAs($admin)->delete(route('inventory.destroy', $prod));

        $res->assertRedirect(route('inventory.index'));
        $res->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $prod->id]);
    }

    public function test_tc_prod_008_restock(): void
    {
        $admin = $this->admin();
        $prod = $this->product(['qty' => 10]);

        $res = $this->actingAs($admin)->post(route('inventory.restock', $prod), ['qty' => 20]);

        $res->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $prod->id, 'qty' => 30]);
        $this->assertDatabaseHas('activity_logs', ['type' => 'RESTOCK']);
    }

    public function test_tc_prod_009_filter_by_category(): void
    {
        $admin = $this->admin();
        $this->product(['name' => 'Beras A', 'category' => 'BT']);
        $this->product(['name' => 'Minyak B', 'category' => 'ML']);

        $res = $this->actingAs($admin)->get(route('inventory.index', ['category' => 'BT']));

        $res->assertSee('Beras A');
        $res->assertDontSee('Minyak B');
    }

    public function test_tc_prod_010_filter_low_stock(): void
    {
        $admin = $this->admin();
        $this->product(['name' => 'Low Stock', 'qty' => 2, 'threshold' => 10]);
        $this->product(['name' => 'Safe Stock', 'qty' => 50, 'threshold' => 5]);

        $res = $this->actingAs($admin)->get(route('inventory.index', ['status' => 'low']));

        $res->assertSee('Low Stock');
        $res->assertDontSee('Safe Stock');
    }

    public function test_tc_prod_011_filter_out_of_stock(): void
    {
        $admin = $this->admin();
        $this->product(['name' => 'Habis', 'qty' => 0, 'threshold' => 5]);
        $this->product(['name' => 'Ada', 'qty' => 10, 'threshold' => 5]);

        $res = $this->actingAs($admin)->get(route('inventory.index', ['status' => 'out']));

        $res->assertSee('Habis');
        $res->assertDontSee('Ada');
    }

    public function test_tc_prod_012_search(): void
    {
        $admin = $this->admin();
        $this->product(['name' => 'Beras Wangi']);

        $res = $this->actingAs($admin)->get(route('inventory.index', ['search' => 'Beras']));

        $res->assertSee('Beras Wangi');
    }

    public function test_tc_prod_013_sku_uniqueness(): void
    {
        $sku1 = Product::generateSku('Sembako', 'Indomaret', 'Indofood');
        Product::create([
            'sku' => $sku1, 'name' => 'A', 'category' => 'Sembako', 'unit' => 'Pcs',
            'qty' => 1, 'threshold' => 1, 'buy_price' => 1000, 'sell_price' => 2000,
        ]);
        $sku2 = Product::generateSku('Sembako', 'Indomaret', 'Indofood');

        $this->assertNotEquals($sku1, $sku2);
    }

    public function test_tc_prod_014_delete_product_with_po_rejected(): void
    {
        $admin = $this->admin();
        $prod = $this->product();
        $supplier = Supplier::create(['name' => 'Sup PO']);
        $po = PurchaseOrder::create([
            'code' => 'PO-'.uniqid(), 'supplier_id' => $supplier->id,
            'created_by' => $admin->id, 'status' => 'draft', 'total' => 1000,
        ]);
        $po->items()->create(['product_id' => $prod->id, 'qty_ordered' => 5, 'qty_received' => 0, 'buy_price' => 1000, 'subtotal' => 5000]);

        $res = $this->actingAs($admin)->delete(route('inventory.destroy', $prod));

        $res->assertSessionHas('error');
        $this->assertStringContainsString('Purchase Order', session('error') ?? $res->getSession()->get('errors'));
        $this->assertDatabaseHas('products', ['id' => $prod->id]);
    }

    public function test_tc_prod_015_delete_product_with_return_rejected(): void
    {
        $admin = $this->admin();
        $prod = $this->product();
        $supplier = Supplier::create(['name' => 'Sup RTR']);
        $po = PurchaseOrder::create([
            'code' => 'PO-'.uniqid(), 'supplier_id' => $supplier->id,
            'created_by' => $admin->id, 'status' => 'received', 'total' => 5000,
            'received_at' => now(), 'received_by' => $admin->id,
        ]);
        $poItem = $po->items()->create(['product_id' => $prod->id, 'qty_ordered' => 5, 'qty_received' => 5, 'buy_price' => 1000, 'subtotal' => 5000]);
        $ret = SupplierReturn::create([
            'code' => 'RTR-'.uniqid(), 'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id, 'created_by' => $admin->id,
            'status' => 'draft', 'total' => 1000,
        ]);
        $ret->items()->create([
            'purchase_order_item_id' => $poItem->id, 'product_id' => $prod->id,
            'qty_returned' => 1, 'buy_price' => 1000, 'subtotal' => 1000,
        ]);

        $res = $this->actingAs($admin)->delete(route('inventory.destroy', $prod));

        $res->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $prod->id]);
    }

    public function test_tc_sec_001_cashier_cannot_access_inventory_create(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier)->get(route('inventory.create'))->assertStatus(403);
        $this->actingAs($cashier)->post(route('inventory.store'), [
            'name' => 'X', 'category' => 'BT', 'unit' => 'Kg',
            'buy_price' => 1000, 'sell_price' => 2000, 'qty' => 1, 'threshold' => 1,
        ])->assertStatus(403);
    }
}
