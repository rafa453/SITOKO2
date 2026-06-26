<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Brand;
use App\Models\Product;

class BrandAndSkuSeeder extends Seeder
{
    // Mapping kode kategori
    private array $categoryCode = [
        'Beras & Tepung'   => 'BT',
        'Minyak & Lemak'   => 'ML',
        'Gula & Garam'     => 'GG',
        'Mie & Pasta'      => 'MP',
        'Bumbu & Rempah'   => 'BR',
        'Minuman'          => 'MN',
        'Kebutuhan Rumah'  => 'KR',
    ];

    // Data produk → merek
    private array $productBrands = [
        1  => 'Cap Ayam Jago',
        2  => 'Cap Ayam Jago',
        3  => 'Segitiga Biru',
        4  => 'Rose Brand',
        5  => 'Bimoli',
        6  => 'Bimoli',
        7  => 'Blue Band',
        8  => 'Gulaku',
        9  => 'Cap Pohon Pinang',
        10 => 'Refina',
        11 => 'Indomie',
        12 => 'Indomie',
        13 => 'Cap 3 Ayam',
        14 => 'ABC',
        15 => 'ABC',
        16 => 'Royco',
        17 => 'Sosro',
        18 => 'Aqua',
        19 => 'Ultra',
        20 => 'Sunlight',
        21 => 'Rinso',
        22 => 'No Brand',
    ];

    public function run(): void
    {
        // 1. Insert semua brand unik
        $uniqueBrands = array_unique(array_values($this->productBrands));
        foreach ($uniqueBrands as $brandName) {
            Brand::firstOrCreate(['name' => $brandName]);
        }

        // 2. Assign brand_id ke tiap produk + generate SKU baru
        $products = Product::all();
        $counterPerDay = []; // track nomor urut per tanggal

        foreach ($products as $product) {
            $brandName = $this->productBrands[$product->id] ?? 'No Brand';
            $brand     = Brand::where('name', $brandName)->first();

            // Assign brand
            $product->brand_id = $brand->id;

            // Generate SKU
            $catCode  = $this->categoryCode[$product->category] ?? 'XX';
            $brandCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $brandName));
            $brandCode = substr($brandCode, 0, 6); // max 6 char
            $tgl      = $product->created_at->format('Ymd');

            // Counter per tanggal agar nomor urut tidak collision
            if (!isset($counterPerDay[$tgl])) {
                $counterPerDay[$tgl] = 1;
            }
            $nomor = str_pad($counterPerDay[$tgl]++, 4, '0', STR_PAD_LEFT);

            $product->sku = "{$catCode}-{$brandCode}-{$tgl}-{$nomor}";
            $product->save();
        }

        $this->command->info('Brand dan SKU berhasil di-generate.');
    }
}