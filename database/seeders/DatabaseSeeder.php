<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =========================================================================
        // 1. TRUNCATE DATA DENGAN AMAN (KECUALI USERS)
        // =========================================================================
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToTruncate = [
            'transactions',
            'transaction_items',
            'purchase_orders',
            'purchase_order_items',
            'supplier_returns',
            'supplier_return_items',
            'products',
            'product_supplier',
            'brands',
            'brand_supplier',
            'suppliers',
            'payment_methods',
        ];

        foreach ($tablesToTruncate as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Tabel operasional berhasil di-truncate (Users tetap aman).');

        // =========================================================================
        // 2. SEEDING MASTER DATA
        // =========================================================================
        
        // A. Payment Methods
        $paymentMethods = [
            ['name' => 'Tunai',        'type' => 'cash',    'provider' => null,    'mdr_fee' => 0,   'notes' => 'Pembayaran tunai langsung', 'is_active' => true],
            ['name' => 'QRIS GoPay',   'type' => 'digital', 'provider' => 'GoPay', 'mdr_fee' => 0.7, 'notes' => 'Scan QR semua e-wallet',    'is_active' => true],
            ['name' => 'Transfer BCA', 'type' => 'digital', 'provider' => 'BCA',   'mdr_fee' => 0,   'notes' => 'Transfer antar bank BCA',   'is_active' => true],
        ];
        
        foreach ($paymentMethods as $pm) {
            DB::table('payment_methods')->insert(array_merge($pm, ['created_at' => now(), 'updated_at' => now()]));
        }

        // C. Suppliers
        $supplierIndofoodId = DB::table('suppliers')->insertGetId([
            'name'       => 'PT Indofood',
            'phone'      => '081234567890',
            'address'    => 'Jl. Indofood No. 1, Jakarta',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierUnileverId = DB::table('suppliers')->insertGetId([
            'name'       => 'PT Unilever',
            'phone'      => '081298765432',
            'address'    => 'Jl. Unilever No. 2, Jakarta',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierNestleId = DB::table('suppliers')->insertGetId([
            'name'       => 'PT Nestle Indonesia',
            'phone'      => '081122334455',
            'address'    => 'Arkadia Green Park, Jakarta',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierWingsId = DB::table('suppliers')->insertGetId([
            'name'       => 'PT Wings Surya',
            'phone'      => '081199887766',
            'address'    => 'Jl. Cakung Cilincing, Jakarta',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierMayoraId = DB::table('suppliers')->insertGetId([
            'name'       => 'PT Mayora Indah',
            'phone'      => '081155443322',
            'address'    => 'Gedung Mayora, Jakarta Barat',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // D. Brands & M2M Relasi (brand_supplier)
        $brandIndomieId = DB::table('brands')->insertGetId(['name' => 'Indomie', 'created_at' => now(), 'updated_at' => now()]);
        $brandBogasariId = DB::table('brands')->insertGetId(['name' => 'Bogasari', 'created_at' => now(), 'updated_at' => now()]);
        
        $brandLifebuoyId = DB::table('brands')->insertGetId(['name' => 'Lifebuoy', 'created_at' => now(), 'updated_at' => now()]);
        $brandSunlightId = DB::table('brands')->insertGetId(['name' => 'Sunlight', 'created_at' => now(), 'updated_at' => now()]);

        $brandDancowId = DB::table('brands')->insertGetId(['name' => 'Dancow', 'created_at' => now(), 'updated_at' => now()]);
        $brandMiloId = DB::table('brands')->insertGetId(['name' => 'Milo', 'created_at' => now(), 'updated_at' => now()]);
        $brandBearBrandId = DB::table('brands')->insertGetId(['name' => 'Bear Brand', 'created_at' => now(), 'updated_at' => now()]);

        $brandMieSedaapId = DB::table('brands')->insertGetId(['name' => 'Mie Sedaap', 'created_at' => now(), 'updated_at' => now()]);
        $brandNuvoId = DB::table('brands')->insertGetId(['name' => 'Nuvo', 'created_at' => now(), 'updated_at' => now()]);

        $brandKopikoId = DB::table('brands')->insertGetId(['name' => 'Kopiko', 'created_at' => now(), 'updated_at' => now()]);
        $brandRomaId = DB::table('brands')->insertGetId(['name' => 'Roma', 'created_at' => now(), 'updated_at' => now()]);

        DB::table('brand_supplier')->insert([
            ['brand_id' => $brandIndomieId,  'supplier_id' => $supplierIndofoodId, 'created_at' => now(), 'updated_at' => now()],
            ['brand_id' => $brandBogasariId, 'supplier_id' => $supplierIndofoodId, 'created_at' => now(), 'updated_at' => now()],
            
            ['brand_id' => $brandLifebuoyId, 'supplier_id' => $supplierUnileverId, 'created_at' => now(), 'updated_at' => now()],
            ['brand_id' => $brandSunlightId, 'supplier_id' => $supplierUnileverId, 'created_at' => now(), 'updated_at' => now()],

            ['brand_id' => $brandDancowId, 'supplier_id' => $supplierNestleId, 'created_at' => now(), 'updated_at' => now()],
            ['brand_id' => $brandMiloId, 'supplier_id' => $supplierNestleId, 'created_at' => now(), 'updated_at' => now()],
            ['brand_id' => $brandBearBrandId, 'supplier_id' => $supplierNestleId, 'created_at' => now(), 'updated_at' => now()],

            ['brand_id' => $brandMieSedaapId, 'supplier_id' => $supplierWingsId, 'created_at' => now(), 'updated_at' => now()],
            ['brand_id' => $brandNuvoId, 'supplier_id' => $supplierWingsId, 'created_at' => now(), 'updated_at' => now()],

            ['brand_id' => $brandKopikoId, 'supplier_id' => $supplierMayoraId, 'created_at' => now(), 'updated_at' => now()],
            ['brand_id' => $brandRomaId, 'supplier_id' => $supplierMayoraId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('✅ Master Data (Payment, Categories, Suppliers, Brands) berhasil di-seed.');

        // =========================================================================
        // 3. SEEDING INVENTORY DENGAN LOGIKA BARU
        // =========================================================================
        
        $productsData = [
            // Sembako (Indofood)
            [
                'brand_id'   => $brandIndomieId,
                'sku'        => 'SBK-IDF-0001',
                'name'       => 'Indomie Goreng Spesial',
                'category'   => 'Sembako',
                'unit'       => 'Pcs',
                'qty'        => 350,
                'threshold'  => 50,
                'buy_price'  => 2500,
                'sell_price' => 3200,
            ],
            [
                'brand_id'   => $brandIndomieId,
                'sku'        => 'SBK-IDF-0002',
                'name'       => 'Indomie Ayam Bawang',
                'category'   => 'Sembako',
                'unit'       => 'Pcs',
                'qty'        => 200,
                'threshold'  => 50,
                'buy_price'  => 2400,
                'sell_price' => 3000,
            ],
            [
                'brand_id'   => $brandBogasariId,
                'sku'        => 'SBK-IDF-0003',
                'name'       => 'Tepung Segitiga Biru 1Kg',
                'category'   => 'Sembako',
                'unit'       => 'Pcs',
                'qty'        => 120,
                'threshold'  => 20,
                'buy_price'  => 11000,
                'sell_price' => 13500,
            ],
            // Pembersih (Unilever)
            [
                'brand_id'   => $brandLifebuoyId,
                'sku'        => 'PMB-UNI-0001',
                'name'       => 'Sabun Mandi Lifebuoy Total 10',
                'category'   => 'Pembersih',
                'unit'       => 'Pcs',
                'qty'        => 180,
                'threshold'  => 25,
                'buy_price'  => 3500,
                'sell_price' => 4500,
            ],
            [
                'brand_id'   => $brandSunlightId,
                'sku'        => 'PMB-UNI-0002',
                'name'       => 'Sunlight Jeruk Nipis 755ml',
                'category'   => 'Pembersih',
                'unit'       => 'Pcs',
                'qty'        => 85,
                'threshold'  => 15,
                'buy_price'  => 15000,
                'sell_price' => 18000,
            ],
            // Susu (Nestle)
            [
                'brand_id'   => $brandDancowId,
                'sku'        => 'MIN-NST-0001',
                'name'       => 'Susu Dancow Fortigro 400g',
                'category'   => 'Susu',
                'unit'       => 'Pcs',
                'qty'        => 60,
                'threshold'  => 10,
                'buy_price'  => 38000,
                'sell_price' => 45000,
            ],
            [
                'brand_id'   => $brandBearBrandId,
                'sku'        => 'MIN-NST-0002',
                'name'       => 'Susu Bear Brand 189ml',
                'category'   => 'Susu',
                'unit'       => 'Kaleng',
                'qty'        => 150,
                'threshold'  => 30,
                'buy_price'  => 8500,
                'sell_price' => 10500,
            ],
            // Sembako (Wings)
            [
                'brand_id'   => $brandMieSedaapId,
                'sku'        => 'SBK-WNG-0001',
                'name'       => 'Mie Sedaap Soto',
                'category'   => 'Sembako',
                'unit'       => 'Pcs',
                'qty'        => 400,
                'threshold'  => 100,
                'buy_price'  => 2300,
                'sell_price' => 3000,
            ],
            [
                'brand_id'   => $brandNuvoId,
                'sku'        => 'PMB-WNG-0002',
                'name'       => 'Sabun Nuvo Family Merah',
                'category'   => 'Pembersih',
                'unit'       => 'Pcs',
                'qty'        => 120,
                'threshold'  => 20,
                'buy_price'  => 3000,
                'sell_price' => 4000,
            ],
            // Makanan Ringan (Mayora)
            [
                'brand_id'   => $brandKopikoId,
                'sku'        => 'SNK-MYR-0001',
                'name'       => 'Permen Kopiko Blister',
                'category'   => 'Makanan Ringan',
                'unit'       => 'Pack',
                'qty'        => 90,
                'threshold'  => 15,
                'buy_price'  => 7000,
                'sell_price' => 9000,
            ],
            [
                'brand_id'   => $brandRomaId,
                'sku'        => 'SNK-MYR-0002',
                'name'       => 'Biskuit Roma Kelapa 300g',
                'category'   => 'Makanan Ringan',
                'unit'       => 'Pcs',
                'qty'        => 75,
                'threshold'  => 15,
                'buy_price'  => 11500,
                'sell_price' => 14000,
            ],
        ];

        foreach ($productsData as $index => $prod) {
            $productId = DB::table('products')->insertGetId(array_merge($prod, ['created_at' => now(), 'updated_at' => now()]));

            // Pivot product_supplier assignment
            $supplierId = null;
            $supplierCode = '';
            
            if (in_array($prod['brand_id'], [$brandIndomieId, $brandBogasariId])) {
                $supplierId = $supplierIndofoodId; $supplierCode = 'IDF';
            } elseif (in_array($prod['brand_id'], [$brandLifebuoyId, $brandSunlightId])) {
                $supplierId = $supplierUnileverId; $supplierCode = 'UNI';
            } elseif (in_array($prod['brand_id'], [$brandDancowId, $brandMiloId, $brandBearBrandId])) {
                $supplierId = $supplierNestleId; $supplierCode = 'NST';
            } elseif (in_array($prod['brand_id'], [$brandMieSedaapId, $brandNuvoId])) {
                $supplierId = $supplierWingsId; $supplierCode = 'WNG';
            } elseif (in_array($prod['brand_id'], [$brandKopikoId, $brandRomaId])) {
                $supplierId = $supplierMayoraId; $supplierCode = 'MYR';
            }

            if (!$supplierId) continue;

            DB::table('product_supplier')->insert([
                'product_id'   => $productId,
                'supplier_id'  => $supplierId,
                'supplier_sku' => 'SUP-' . $supplierCode . '-' . now()->format('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'price'        => $prod['buy_price'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $this->command->info('✅ Inventory Data (Products & Supplier pivot) berhasil di-seed.');

        // =========================================================================
        // 3.5 SEEDING PURCHASE ORDERS (ERP REAL-WORLD SIMULATION)
        // =========================================================================
        
        $adminId = DB::table('users')->value('id') ?? 1;
        $suppliersDb = DB::table('suppliers')->get();
        $productsInDb = DB::table('products')->get();
        $poCount = 1;

        // --- KELOMPOK 1: THE FOUNDATION (Bukti Stok Saat Ini) ---
        foreach ($suppliersDb as $supplier) {
            $supplierProductIds = DB::table('product_supplier')->where('supplier_id', $supplier->id)->pluck('product_id')->toArray();
            if (empty($supplierProductIds)) continue;

            $supplierProducts = $productsInDb->whereIn('id', $supplierProductIds);

            // Tanggal: Acak antara 1 hingga 2 bulan yang lalu
            $date = now()->subMonths(rand(1, 2))->subDays(rand(1, 28));
            
            $poId = DB::table('purchase_orders')->insertGetId([
                'code'           => 'PO-' . $date->format('Ymd') . '-' . str_pad($poCount++, 5, '0', STR_PAD_LEFT),
                'supplier_id'    => $supplier->id,
                'created_by'     => $adminId,
                'received_by'    => $adminId,
                'status'         => 'received',
                'payment_status' => 'paid',
                'payment_type'   => 'full',
                'expected_at'    => $date->copy()->addDays(2)->toDateString(),
                'received_at'    => $date->copy()->addDays(2),
                'total'          => 0,
                'amount_paid'    => 0, // Akan diupdate sama dengan total
                'created_at'     => $date,
                'updated_at'     => $date->copy()->addDays(2),
            ]);

            $totalPo = 0;
            foreach ($supplierProducts as $prod) {
                $subtotal = $prod->buy_price * $prod->qty; // Qty sesuai stok riil saat ini
                $totalPo += $subtotal;

                DB::table('purchase_order_items')->insert([
                    'purchase_order_id' => $poId,
                    'product_id'        => $prod->id,
                    'qty_ordered'       => $prod->qty,
                    'qty_received'      => $prod->qty,
                    'buy_price'         => $prod->buy_price,
                    'subtotal'          => $subtotal,
                    'created_at'        => $date,
                    'updated_at'        => $date->copy()->addDays(2),
                ]);
            }
            DB::table('purchase_orders')->where('id', $poId)->update([
                'total' => $totalPo,
                'amount_paid' => $totalPo
            ]);
        }

        // --- KELOMPOK 2: HISTORICAL NOISE (Riwayat Masa Lalu Bervariasi) ---
        $historicalPoCount = rand(15, 20);
        for ($i = 0; $i < $historicalPoCount; $i++) {
            $supplier = $suppliersDb->random();
            $supplierProductIds = DB::table('product_supplier')->where('supplier_id', $supplier->id)->pluck('product_id')->toArray();
            if (empty($supplierProductIds)) continue;

            $supplierProducts = $productsInDb->whereIn('id', $supplierProductIds);
            
            // Tanggal: Acak antara 2 hingga 6 bulan yang lalu
            $date = now()->subMonths(rand(2, 6))->subDays(rand(1, 28));
            
            // Variasi Status
            $isReceived = (rand(1, 100) <= 80); // 80% received, 20% cancelled
            $status = $isReceived ? 'received' : 'cancelled';
            
            // Variasi Pembayaran
            if ($isReceived) {
                $paymentStatus = (rand(1, 100) <= 90) ? 'paid' : 'unpaid';
            } else {
                $paymentStatus = 'unpaid';
            }

            $poId = DB::table('purchase_orders')->insertGetId([
                'code'           => 'PO-' . $date->format('Ymd') . '-' . str_pad($poCount++, 5, '0', STR_PAD_LEFT),
                'supplier_id'    => $supplier->id,
                'created_by'     => $adminId,
                'received_by'    => $isReceived ? $adminId : null,
                'status'         => $status,
                'payment_status' => $paymentStatus,
                'payment_type'   => $paymentStatus == 'paid' ? 'full' : null,
                'expected_at'    => $date->copy()->addDays(rand(1, 5))->toDateString(),
                'received_at'    => $isReceived ? $date->copy()->addDays(rand(1, 5)) : null,
                'total'          => 0,
                'amount_paid'    => 0,
                'created_at'     => $date,
                'updated_at'     => $isReceived ? $date->copy()->addDays(rand(1, 5)) : clone $date,
            ]);

            $totalPo = 0;
            // Ambil 1-3 produk secara acak
            $prodArray = $supplierProducts->toArray();
            shuffle($prodArray);
            $randomProds = array_slice($prodArray, 0, rand(1, min(3, count($prodArray))));

            foreach ($randomProds as $prod) {
                $qtyOrdered = rand(10, 50);
                $qtyReceived = $isReceived ? $qtyOrdered : 0;
                $subtotal = $prod->buy_price * $qtyOrdered;
                $totalPo += $subtotal;

                DB::table('purchase_order_items')->insert([
                    'purchase_order_id' => $poId,
                    'product_id'        => $prod->id,
                    'qty_ordered'       => $qtyOrdered,
                    'qty_received'      => $qtyReceived,
                    'buy_price'         => $prod->buy_price,
                    'subtotal'          => $subtotal,
                    'created_at'        => $date,
                    'updated_at'        => $date,
                ]);
            }
            
            $amountPaid = ($paymentStatus == 'paid') ? $totalPo : 0;
            DB::table('purchase_orders')->where('id', $poId)->update([
                'total' => $totalPo,
                'amount_paid' => $amountPaid
            ]);
        }

        // --- KELOMPOK 3: ACTIVE QUEUE (Antrean Aktif Hari Ini & Minggu Ini) ---
        $activePoCount = rand(5, 10);
        for ($i = 0; $i < $activePoCount; $i++) {
            $supplier = $suppliersDb->random();
            $supplierProductIds = DB::table('product_supplier')->where('supplier_id', $supplier->id)->pluck('product_id')->toArray();
            if (empty($supplierProductIds)) continue;

            $supplierProducts = $productsInDb->whereIn('id', $supplierProductIds);
            
            // Tanggal: Acak antara 1 hingga 7 hari yang lalu
            $date = now()->subDays(rand(1, 7))->subHours(rand(1, 23));
            
            // Variasi Status ('ordered' atau 'draft')
            $status = \Illuminate\Support\Arr::random(['draft', 'ordered']);
            
            // Variasi Pembayaran
            $paymentStatus = \Illuminate\Support\Arr::random(['unpaid', 'partial']);
            $paymentType = ($paymentStatus == 'partial') ? 'dp' : null;

            $poId = DB::table('purchase_orders')->insertGetId([
                'code'           => 'PO-' . $date->format('Ymd') . '-' . str_pad($poCount++, 5, '0', STR_PAD_LEFT),
                'supplier_id'    => $supplier->id,
                'created_by'     => $adminId,
                'received_by'    => null,
                'status'         => $status,
                'payment_status' => $paymentStatus,
                'payment_type'   => $paymentType,
                'expected_at'    => $date->copy()->addDays(rand(2, 7))->toDateString(),
                'received_at'    => null,
                'total'          => 0,
                'amount_paid'    => 0, // Diupdate setelah items
                'created_at'     => $date,
                'updated_at'     => $date,
            ]);

            $totalPo = 0;
            $prodArray = $supplierProducts->toArray();
            shuffle($prodArray);
            $randomProds = array_slice($prodArray, 0, rand(1, min(4, count($prodArray))));

            foreach ($randomProds as $prod) {
                $qtyOrdered = rand(20, 100);
                $subtotal = $prod->buy_price * $qtyOrdered;
                $totalPo += $subtotal;

                DB::table('purchase_order_items')->insert([
                    'purchase_order_id' => $poId,
                    'product_id'        => $prod->id,
                    'qty_ordered'       => $qtyOrdered,
                    'qty_received'      => 0,
                    'buy_price'         => $prod->buy_price,
                    'subtotal'          => $subtotal,
                    'created_at'        => $date,
                    'updated_at'        => $date,
                ]);
            }
            
            $amountPaid = ($paymentStatus == 'partial') ? ($totalPo * (rand(10, 50) / 100)) : 0; // DP 10-50%
            DB::table('purchase_orders')->where('id', $poId)->update([
                'total' => $totalPo,
                'amount_paid' => $amountPaid
            ]);
        }

        $this->command->info('✅ Purchase Order Data (Foundation, Historical Noise, & Active Queue) berhasil di-seed.');

        // =========================================================================
        // 4. SEEDING TRANSAKSI (PEMBUKTIAN HPP/FINANSIAL)
        // =========================================================================
        
        $cashPaymentId = 1; // ID untuk 'Tunai'

        $totalTransactions = rand(18, 25);
        $paymentMethods = DB::table('payment_methods')->get();

        for ($i = 1; $i <= $totalTransactions; $i++) {
            $date = now()->subDays(rand(0, 7))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $paymentMethod = $paymentMethods->random();

            $trxId = DB::table('transactions')->insertGetId([
                'code'           => 'TRX-' . $date->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'cashier_id'     => $adminId,
                'total'          => 0, // Akan di-update nanti
                'amount_paid'    => 0, // Akan di-update nanti
                'change'         => 0,
                'payment_method' => $paymentMethod->name,
                'status'         => 'completed',
                'created_at'     => $date,
                'updated_at'     => $date,
            ]);

            $totalAmount = 0;
            
            // Pilih 2 hingga 5 produk acak untuk transaksi ini
            $productsArray = $productsInDb->toArray();
            shuffle($productsArray);
            $pickedProducts = array_slice($productsArray, 0, rand(2, 5));

            foreach ($pickedProducts as $prod) {
                $qty = rand(1, 3);
                $subtotal = $prod->sell_price * $qty;
                $totalAmount += $subtotal;

                DB::table('transaction_items')->insert([
                    'transaction_id' => $trxId,
                    'product_id'     => $prod->id,
                    'qty'            => $qty,
                    'unit'           => $prod->unit,
                    'price'          => $prod->sell_price,
                    // SNAPSHOT HPP DITERAPKAN DI SINI:
                    'buy_price'      => $prod->buy_price, 
                    'subtotal'       => $subtotal,
                    'created_at'     => $date,
                    'updated_at'     => $date,
                ]);

                // Kurangi stok aktual produk di Master Data
                DB::table('products')->where('id', $prod->id)->decrement('qty', $qty);
            }

            // Perbarui Total Transaksi
            DB::table('transactions')->where('id', $trxId)->update([
                'total' => $totalAmount,
                'amount_paid' => $totalAmount,
            ]);
        }

        $this->command->info('✅ Transaction Data (beserta snapshot HPP buy_price) berhasil di-seed.');
        $this->command->info('🚀 GRAND RESET & DATA CLEANSING SELESAI!');
    }
}