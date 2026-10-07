<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionVoidTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function cashier(): User
    {
        return User::factory()->create(['role' => 'cashier']);
    }

    private function product(int $qty): Product
    {
        return Product::create([
            'sku' => 'TST-0001',
            'name' => 'Produk Test',
            'category' => 'Sembako',
            'unit' => 'Pcs',
            'qty' => $qty,
            'threshold' => 10,
            'buy_price' => 1000,
            'sell_price' => 2000,
        ]);
    }

    private function completedTransaction(Product $product, int $qty): Transaction
    {
        $transaction = Transaction::create([
            'code' => 'TRX-TEST-001',
            'cashier_id' => $this->admin()->id,
            'total' => $product->sell_price * $qty,
            'amount_paid' => $product->sell_price * $qty,
            'change' => 0,
            'payment_method' => 'Tunai',
            'status' => 'completed',
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'qty' => $qty,
            'unit' => $product->unit,
            'price' => $product->sell_price,
            'buy_price' => $product->buy_price,
            'subtotal' => $product->sell_price * $qty,
        ]);

        return $transaction;
    }

    public function test_void_restores_stock_and_marks_voided(): void
    {
        $product = $this->product(10);
        $transaction = $this->completedTransaction($product, 3);

        $this->actingAs($this->admin())
            ->post(route('transactions.void', $transaction));

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'voided']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 13]);
    }

    public function test_voiding_voided_transaction_is_rejected(): void
    {
        $product = $this->product(10);
        $transaction = $this->completedTransaction($product, 3);
        $transaction->update(['status' => 'voided']);

        $this->actingAs($this->admin())
            ->post(route('transactions.void', $transaction));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 10]);
    }

    public function test_voiding_missing_transaction_returns_404(): void
    {
        $this->actingAs($this->admin())
            ->post(route('transactions.void', 9999))
            ->assertNotFound();
    }

    public function test_cashier_cannot_void(): void
    {
        $product = $this->product(10);
        $transaction = $this->completedTransaction($product, 3);

        $this->actingAs($this->cashier())
            ->post(route('transactions.void', $transaction))
            ->assertStatus(403);

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'completed']);
    }
}
