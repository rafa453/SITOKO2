<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionStockTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function product(int $qty): Product
    {
        return Product::create([
            'sku'        => 'TST-0001',
            'name'       => 'Produk Test',
            'category'   => 'Sembako',
            'unit'       => 'Pcs',
            'qty'        => $qty,
            'threshold'  => 10,
            'buy_price'  => 1000,
            'sell_price' => 2000,
        ]);
    }

    public function test_duplicate_product_in_items_is_aggregated_and_rejected_when_insufficient(): void
    {
        $product = $this->product(3);

        $response = $this->actingAs($this->admin())->postJson(route('transactions.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2],
                ['id' => $product->id, 'qty' => 2],
            ],
            'payment_method' => 'Tunai',
            'amount_paid'    => 8000,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 3]);
    }

    public function test_duplicate_product_in_items_is_aggregated_and_passes_when_sufficient(): void
    {
        $product = $this->product(5);

        $response = $this->actingAs($this->admin())->postJson(route('transactions.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2],
                ['id' => $product->id, 'qty' => 2],
            ],
            'payment_method' => 'Tunai',
            'amount_paid'    => 8000,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 1]);
    }

    public function test_underpayment_is_rejected(): void
    {
        $product = $this->product(5);

        $response = $this->actingAs($this->admin())->postJson(route('transactions.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2],
            ],
            'payment_method' => 'Tunai',
            'amount_paid'    => 3000,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('transactions', ['status' => 'completed']);
    }
}
