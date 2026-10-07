<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_sku_returns_distinct_values_for_same_input(): void
    {
        $sku1 = Product::generateSku('Sembako', 'Indomie', 'Indofood');

        Product::create([
            'sku'        => $sku1,
            'name'       => 'Produk A',
            'category'   => 'Sembako',
            'unit'       => 'Pcs',
            'qty'        => 1,
            'threshold'  => 1,
            'buy_price'  => 1000,
            'sell_price' => 2000,
        ]);

        $sku2 = Product::generateSku('Sembako', 'Indomie', 'Indofood');

        $this->assertNotEquals($sku1, $sku2);
        $this->assertStringStartsWith($sku1, $sku2);
    }

    public function test_duplicate_sku_is_rejected_by_unique_constraint(): void
    {
        $sku = Product::generateSku('Sembako', 'Indomie', 'Indofood');

        Product::create([
            'sku' => $sku, 'name' => 'A', 'category' => 'Sembako', 'unit' => 'Pcs',
            'qty' => 1, 'threshold' => 1, 'buy_price' => 1000, 'sell_price' => 2000,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Product::create([
            'sku' => $sku, 'name' => 'B', 'category' => 'Sembako', 'unit' => 'Pcs',
            'qty' => 1, 'threshold' => 1, 'buy_price' => 1000, 'sell_price' => 2000,
        ]);
    }
}
