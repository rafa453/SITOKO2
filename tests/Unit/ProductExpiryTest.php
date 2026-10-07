<?php

namespace Tests\Unit;

use App\Models\Product;
use Carbon\Carbon;
use Tests\TestCase;

class ProductExpiryTest extends TestCase
{
    public function test_expiry_status_logic(): void
    {
        $product = new Product;

        // 1. null expired_at -> null
        $this->assertNull($product->expiry_status);

        // 2. past date -> expired
        $product->expired_at = Carbon::now()->subDay();
        $this->assertEquals('expired', $product->expiry_status);

        // 3. near future (<= 7 days) -> near
        $product->expired_at = Carbon::now()->addDays(5);
        $this->assertEquals('near', $product->expiry_status);

        // 4. far future (> 7 days) -> safe
        $product->expired_at = Carbon::now()->addDays(15);
        $this->assertEquals('safe', $product->expiry_status);
    }
}
