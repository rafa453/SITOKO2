<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_only_sees_own_transactions(): void
    {
        $cashier1 = User::factory()->create(['role' => 'cashier']);
        $cashier2 = User::factory()->create(['role' => 'cashier']);
        $admin = User::factory()->create(['role' => 'admin']);

        $t1 = Transaction::create(['code' => 'TRX1', 'cashier_id' => $cashier1->id, 'total' => 100, 'amount_paid' => 100, 'change' => 0, 'payment_method' => 'Tunai']);
        $t2 = Transaction::create(['code' => 'TRX2', 'cashier_id' => $cashier2->id, 'total' => 200, 'amount_paid' => 200, 'change' => 0, 'payment_method' => 'Tunai']);

        // Cashier 1: query scope & isVisibleTo
        $this->assertEquals(1, Transaction::visibleTo($cashier1)->count());
        $this->assertTrue($t1->isVisibleTo($cashier1));
        $this->assertFalse($t2->isVisibleTo($cashier1));

        // Admin: query scope & isVisibleTo
        $this->assertEquals(2, Transaction::visibleTo($admin)->count());
        $this->assertTrue($t1->isVisibleTo($admin));
        $this->assertTrue($t2->isVisibleTo($admin));
    }
}
