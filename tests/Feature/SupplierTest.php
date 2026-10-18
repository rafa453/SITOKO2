<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_tc_sup_001_store_supplier(): void
    {
        $admin = $this->admin();
        $res = $this->actingAs($admin)->post(route('suppliers.store'), [
            'name' => 'PT Sejahtera',
            'phone' => '08123456789',
            'category' => 'BT',
            'address' => 'Jl. Raya No. 1',
        ]);
        $res->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'PT Sejahtera']);
    }

    public function test_tc_sup_002_store_supplier_optional(): void
    {
        $admin = $this->admin();
        $res = $this->actingAs($admin)->post(route('suppliers.store'), [
            'name' => 'Supplier Test',
        ]);
        $res->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Supplier Test']);
    }

    public function test_tc_sup_003_update_supplier(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Old Name']);
        $res = $this->actingAs($admin)->put(route('suppliers.update', $supplier), [
            'name' => 'PT Sejahtera Baru',
        ]);
        $res->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'PT Sejahtera Baru']);
    }

    public function test_tc_sup_004_toggle_active(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'PT Aktif', 'is_active' => true]);
        $res = $this->actingAs($admin)->patch(route('suppliers.toggle-active', $supplier));
        $res->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);
    }

    public function test_tc_sup_005_delete_supplier_no_history(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'PT Hapus']);
        $res = $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier));
        $res->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_tc_sup_006_delete_supplier_with_po_rejected(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'PT PO']);
        PurchaseOrder::create([
            'code' => 'PO-001', 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'draft', 'total' => 1000,
        ]);
        $res = $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier));
        $res->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_tc_sup_007_delete_supplier_with_return_rejected(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'PT RTR']);
        $po = PurchaseOrder::create(['code' => 'PO-SUPRTR', 'supplier_id' => $supplier->id, 'created_by' => $admin->id, 'status' => 'received', 'total' => 1000]);
        SupplierReturn::create([
            'code' => 'RTR-SUP', 'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id, 'created_by' => $admin->id,
            'status' => 'draft', 'total' => 1000,
        ]);
        $res = $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier));
        $res->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_tc_sup_008_filter_active_supplier(): void
    {
        $admin = $this->admin();
        Supplier::create(['name' => 'Active', 'is_active' => true]);
        Supplier::create(['name' => 'Inactive', 'is_active' => false]);

        $res = $this->actingAs($admin)->get(route('suppliers.index', ['status' => 'active']));
        $res->assertSee('Active');
        $res->assertDontSee('Inactive');
    }

    public function test_tc_sup_009_get_brands_api(): void
    {
        $admin = $this->admin();
        $supplier = Supplier::create(['name' => 'Brand Supplier']);
        $brand = Brand::create(['name' => 'Indomie']);
        $supplier->brands()->attach($brand->id);

        $res = $this->actingAs($admin)->getJson(route('api.suppliers.brands', $supplier));
        $res->assertOk();
        $res->assertJsonFragment(['name' => 'Indomie']);
    }
}
