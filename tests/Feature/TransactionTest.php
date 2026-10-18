<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
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

    private function product(int $qty = 50, int $sell = 2000): Product
    {
        return Product::create([
            'sku' => 'TRX-'.uniqid(),
            'name' => 'Produk Transaksi',
            'category' => 'Sembako',
            'unit' => 'Pcs',
            'qty' => $qty,
            'threshold' => 5,
            'buy_price' => 1000,
            'sell_price' => $sell,
            'is_active' => true,
        ]);
    }

    private function clockedInCashier(): User
    {
        $cashier = $this->cashier();
        Shift::create(['user_id' => $cashier->id, 'type' => 'pagi', 'started_at' => now()]);
        PaymentMethod::create(['name' => 'Cash', 'code' => 'cash', 'type' => 'cash', 'is_active' => true]);

        return $cashier;
    }

    private function completedTransaction(User $cashier, Product $product, int $qty): Transaction
    {
        $trx = Transaction::create([
            'code' => 'TRX-TEST-'.uniqid(),
            'cashier_id' => $cashier->id,
            'total' => $product->sell_price * $qty,
            'amount_paid' => $product->sell_price * $qty,
            'change' => 0,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        TransactionItem::create([
            'transaction_id' => $trx->id, 'product_id' => $product->id,
            'qty' => $qty, 'unit' => $product->unit,
            'buy_price' => $product->buy_price, 'price' => $product->sell_price,
            'subtotal' => $product->sell_price * $qty,
        ]);

        return $trx;
    }

    public function test_tc_trx_001_cashier_create_transaction(): void
    {
        $cashier = $this->clockedInCashier();
        $product = $this->product(50);

        $res = $this->actingAs($cashier)->postJson(route('transactions.store'), [
            'items' => [['id' => $product->id, 'qty' => 2]],
            'payment_method' => 'Cash',
            'amount_paid' => 50000,
        ]);

        $res->assertOk();
        $res->assertJson(['success' => true]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 48]);
        $this->assertDatabaseHas('transactions', ['cashier_id' => $cashier->id, 'status' => 'completed']);
    }

    public function test_tc_trx_002_insufficient_stock_rejected(): void
    {
        $cashier = $this->clockedInCashier();
        $product = $this->product(5);

        $res = $this->actingAs($cashier)->postJson(route('transactions.store'), [
            'items' => [['id' => $product->id, 'qty' => 10]],
            'payment_method' => 'Cash',
            'amount_paid' => 50000,
        ]);

        $res->assertStatus(422);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 5]);
    }

    public function test_tc_trx_003_underpayment_rejected(): void
    {
        $cashier = $this->clockedInCashier();
        $product = $this->product(50, 25000);

        $res = $this->actingAs($cashier)->postJson(route('transactions.store'), [
            'items' => [['id' => $product->id, 'qty' => 2]],
            'payment_method' => 'Cash',
            'amount_paid' => 40000,
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment(['message' => 'Jumlah bayar kurang dari total transaksi.']);
    }

    public function test_tc_trx_004_cashier_without_shift_rejected(): void
    {
        $cashier = $this->cashier();
        $product = $this->product(50);

        $res = $this->actingAs($cashier)->postJson(route('transactions.store'), [
            'items' => [['id' => $product->id, 'qty' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50000,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Clock-In', $res->json('message'));
    }

    public function test_tc_trx_005_admin_void_transaction(): void
    {
        $admin = $this->admin();
        $product = $this->product(10);
        $trx = $this->completedTransaction($this->cashier(), $product, 3);

        $res = $this->actingAs($admin)->post(route('transactions.void', $trx));
        $res->assertRedirect();
        $this->assertDatabaseHas('transactions', ['id' => $trx->id, 'status' => 'voided']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 13]);
        $this->assertDatabaseHas('activity_logs', ['type' => 'VOID']);
    }

    public function test_tc_trx_006_void_voided_rejected(): void
    {
        $admin = $this->admin();
        $product = $this->product(10);
        $trx = $this->completedTransaction($this->cashier(), $product, 3);
        $trx->update(['status' => 'voided']);

        $res = $this->actingAs($admin)->post(route('transactions.void', $trx));
        $res->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'qty' => 10]);
    }

    public function test_tc_trx_007_cashier_sees_only_own_transactions(): void
    {
        $cashierA = $this->cashier();
        $cashierB = $this->cashier();
        $product = $this->product(10);
        $this->completedTransaction($cashierA, $product, 1);
        $this->completedTransaction($cashierB, $product, 1);

        $res = $this->actingAs($cashierA)->get(route('transactions.index'));
        $res->assertOk();
        $res->assertViewHas('transactions', function ($trx) use ($cashierA) {
            return $trx->every(fn ($t) => $t->cashier_id === $cashierA->id);
        });
    }

    public function test_tc_trx_008_admin_sees_all_transactions(): void
    {
        $admin = $this->admin();
        $product = $this->product(10);
        $a = $this->completedTransaction($this->cashier(), $product, 1);
        $b = $this->completedTransaction($this->cashier(), $product, 1);

        $res = $this->actingAs($admin)->get(route('transactions.index'));
        $res->assertOk();
        $res->assertViewHas('transactions', fn ($trx) => $trx->total() >= 2);
    }

    public function test_tc_trx_009_filter_by_date(): void
    {
        $admin = $this->admin();
        $product = $this->product(10);
        $this->completedTransaction($this->cashier(), $product, 1);

        $res = $this->actingAs($admin)->get(route('transactions.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]));
        $res->assertOk();
    }

    public function test_tc_trx_010_export_csv(): void
    {
        $admin = $this->admin();
        $product = $this->product(10);
        $this->completedTransaction($this->cashier(), $product, 1);

        $res = $this->actingAs($admin)->get(route('transactions.export'));
        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
    }

    public function test_tc_trx_011_receipt(): void
    {
        $admin = $this->admin();
        $product = $this->product(10);
        $trx = $this->completedTransaction($this->cashier(), $product, 1);

        $res = $this->actingAs($admin)->get(route('transactions.receipt', $trx));
        $res->assertOk();
        $res->assertSee($trx->code);
    }

    public function test_tc_trx_012_cashier_cannot_void_other_transaction(): void
    {
        $cashierA = $this->cashier();
        $cashierB = $this->cashier();
        $product = $this->product(10);
        $trxB = $this->completedTransaction($cashierB, $product, 1);

        $res = $this->actingAs($cashierA)->post(route('transactions.void', $trxB));
        $res->assertStatus(403);
        $this->assertDatabaseHas('transactions', ['id' => $trxB->id, 'status' => 'completed']);
    }
}
