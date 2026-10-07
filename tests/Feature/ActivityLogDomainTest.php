<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogDomainTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_brand_crud_generates_activity_logs(): void
    {
        $admin = $this->admin();

        // 1. Store
        $this->actingAs($admin)->post(route('brands.store'), ['name' => 'Brand Log Test']);
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'BRAND',
            'action' => 'Tambah merek',
            'subject' => 'Brand Log Test',
        ]);

        $brand = Brand::where('name', 'Brand Log Test')->first();

        // 2. Update
        $this->actingAs($admin)->put(route('brands.update', $brand), ['name' => 'Brand Log Updated']);
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'BRAND',
            'action' => 'Update merek',
            'subject' => 'Brand Log Updated',
        ]);

        // 3. Destroy
        $this->actingAs($admin)->delete(route('brands.destroy', $brand));
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'BRAND',
            'action' => 'Hapus merek',
            'subject' => 'Brand Log Updated',
        ]);
    }

    public function test_payment_method_crud_generates_activity_logs(): void
    {
        $admin = $this->admin();

        // 1. Store
        $this->actingAs($admin)->post(route('payment-methods.store'), [
            'name' => 'GoPay Log Test',
            'code' => 'gopay-test',
            'type' => 'digital',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'PAYMENT_METHOD',
            'action' => 'Tambah metode pembayaran',
            'subject' => 'GoPay Log Test',
        ]);

        $pm = PaymentMethod::where('code', 'gopay-test')->first();

        // 2. Toggle
        $this->actingAs($admin)->patch(route('settings.payment-methods.toggle', $pm));
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'PAYMENT_METHOD',
            'subject' => 'GoPay Log Test',
        ]);
    }
}
